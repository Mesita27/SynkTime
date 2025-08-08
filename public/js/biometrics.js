(function(){
  const $ = (sel) => document.querySelector(sel);
  const $$ = (sel) => document.querySelectorAll(sel);

  async function startCam(videoEl) {
    const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
    videoEl.srcObject = stream;
    return stream;
  }

  function captureDataUrl(videoEl) {
    const canvas = document.createElement('canvas');
    canvas.width = videoEl.videoWidth || 640;
    canvas.height = videoEl.videoHeight || 480;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(videoEl, 0, 0, canvas.width, canvas.height);
    return canvas.toDataURL('image/png');
  }

  function showAlert(el, msg, type='info') {
    el.classList.remove('d-none','alert-success','alert-danger','alert-info','alert-warning');
    el.classList.add('alert', 'alert-' + type);
    el.innerText = msg;
  }

  // Attendance - Face
  const videoFace = $('#cam-preview');
  if (videoFace) startCam(videoFace).catch(console.error);
  let lastFaceDataUrl = null;
  let faceCandidate = null;

  $('#btn-capture-face')?.addEventListener('click', async () => {
    try {
      const dataUrl = captureDataUrl(videoFace);
      lastFaceDataUrl = dataUrl;
      const form = new FormData();
      form.append('image', dataUrl);
      const res = await fetch('/api/biometrics/recognize_face.php', { method:'POST', body: form });
      const json = await res.json();
      if (!res.ok) throw new Error(json.error || 'Error reconocimiento facial');

      const best = (json.candidates || [])[0];
      if (best && best.score >= json.min_score) {
        faceCandidate = best;
        showAlert($('#face-result'), `Empleado (subject=${best.subject}) reconocido con score=${best.score.toFixed(3)}.`, 'success');
        $('#btn-confirm-face').disabled = false;
      } else {
        showAlert($('#face-result'), 'No se logró reconocimiento confiable. Intenta de nuevo.', 'warning');
        $('#btn-confirm-face').disabled = true;
      }
    } catch (e) {
      showAlert($('#face-result'), e.message, 'danger');
      $('#btn-confirm-face').disabled = true;
    }
  });

  $('#btn-reset-face')?.addEventListener('click', () => {
    faceCandidate = null;
    lastFaceDataUrl = null;
    $('#face-result').classList.add('d-none');
    $('#btn-confirm-face').disabled = true;
  });

  $('#btn-confirm-face')?.addEventListener('click', async () => {
    if (!faceCandidate || !lastFaceDataUrl) return;
    try {
      const empId = faceCandidate.subject.replace('emp_', '');
      const form = new FormData();
      form.append('employee_id', empId);
      form.append('channel', 'face');
      form.append('score', faceCandidate.score);
      form.append('image', lastFaceDataUrl);
      form.append('provider_ref', faceCandidate.subject);
      
      const res = await fetch('/api/biometrics/mark_attendance.php', { method:'POST', body: form });
      const json = await res.json();
      if (!res.ok) throw new Error(json.error || 'Error al marcar asistencia');
      
      showAlert($('#face-result'), 'Marcaje registrado exitosamente.', 'success');
      $('#btn-confirm-face').disabled = true;
    } catch (e) {
      showAlert($('#face-result'), e.message, 'danger');
    }
  });

  // Attendance - Fingerprint
  let fpCandidate = null;

  $('#btn-identify-fp')?.addEventListener('click', async () => {
    const fileInput = $('#fp-image');
    if (!fileInput?.files[0]) {
      showAlert($('#fp-result'), 'Selecciona una imagen de huella.', 'warning');
      return;
    }
    try {
      const form = new FormData();
      form.append('image', fileInput.files[0]);
      const res = await fetch('/api/biometrics/identify_fingerprint.php', { method:'POST', body: form });
      const json = await res.json();
      if (!res.ok) throw new Error(json.error || 'Error identificación huella');

      const best = (json.candidates || [])[0];
      if (best && best.score >= json.min_score) {
        fpCandidate = best;
        showAlert($('#fp-result'), `Empleado ID ${best.employeeId} identificado con score=${best.score.toFixed(2)}.`, 'success');
        $('#btn-confirm-fp').disabled = false;
      } else {
        showAlert($('#fp-result'), 'No se logró identificación confiable. Intenta de nuevo.', 'warning');
        $('#btn-confirm-fp').disabled = true;
      }
    } catch (e) {
      showAlert($('#fp-result'), e.message, 'danger');
      $('#btn-confirm-fp').disabled = true;
    }
  });

  $('#btn-reset-fp')?.addEventListener('click', () => {
    fpCandidate = null;
    $('#fp-result').classList.add('d-none');
    $('#btn-confirm-fp').disabled = true;
    $('#fp-image').value = '';
  });

  $('#btn-confirm-fp')?.addEventListener('click', async () => {
    if (!fpCandidate) return;
    try {
      const form = new FormData();
      form.append('employee_id', fpCandidate.employeeId);
      form.append('channel', 'fingerprint');
      form.append('score', fpCandidate.score);
      form.append('provider_ref', fpCandidate.fingerprintId);
      
      const res = await fetch('/api/biometrics/mark_attendance.php', { method:'POST', body: form });
      const json = await res.json();
      if (!res.ok) throw new Error(json.error || 'Error al marcar asistencia');
      
      showAlert($('#fp-result'), 'Marcaje registrado exitosamente.', 'success');
      $('#btn-confirm-fp').disabled = true;
    } catch (e) {
      showAlert($('#fp-result'), e.message, 'danger');
    }
  });

  // Attendance - Photo (traditional)
  const videoPhoto = $('#cam-preview-photo');
  if (videoPhoto) startCam(videoPhoto).catch(console.error);
  let lastPhotoDataUrl = null;

  $('#btn-capture-photo')?.addEventListener('click', () => {
    lastPhotoDataUrl = captureDataUrl(videoPhoto);
    showAlert($('#photo-result'), 'Foto capturada. Ingresa el ID del empleado y confirma.', 'info');
    $('#btn-confirm-photo').disabled = false;
  });

  $('#btn-reset-photo')?.addEventListener('click', () => {
    lastPhotoDataUrl = null;
    $('#photo-result').classList.add('d-none');
    $('#btn-confirm-photo').disabled = true;
    $('#photo-employee-id').value = '';
  });

  $('#btn-confirm-photo')?.addEventListener('click', async () => {
    const empId = $('#photo-employee-id')?.value;
    if (!empId || !lastPhotoDataUrl) {
      showAlert($('#photo-result'), 'ID de empleado e imagen son requeridos.', 'warning');
      return;
    }
    try {
      const form = new FormData();
      form.append('employee_id', empId);
      form.append('channel', 'photo');
      form.append('image', lastPhotoDataUrl);
      
      const res = await fetch('/api/biometrics/mark_attendance.php', { method:'POST', body: form });
      const json = await res.json();
      if (!res.ok) throw new Error(json.error || 'Error al marcar asistencia');
      
      showAlert($('#photo-result'), 'Marcaje registrado exitosamente.', 'success');
      $('#btn-confirm-photo').disabled = true;
    } catch (e) {
      showAlert($('#photo-result'), e.message, 'danger');
    }
  });

  // Enrollment
  const enrollCam = $('#enroll-cam');
  if (enrollCam) startCam(enrollCam).catch(console.error);
  let enrollFaceImages = [];

  $('#btn-enroll-face-capture')?.addEventListener('click', () => {
    if (enrollFaceImages.length >= 3) return;
    const dataUrl = captureDataUrl(enrollCam);
    enrollFaceImages.push(dataUrl);
    
    // Show preview
    const preview = $('#enroll-face-preview');
    const img = document.createElement('img');
    img.src = dataUrl;
    img.className = 'border rounded';
    img.style.width = '80px';
    img.style.height = '60px';
    preview.appendChild(img);
    
    $('#enroll-face-count').textContent = `${enrollFaceImages.length}/3 capturas`;
    if (enrollFaceImages.length >= 3) {
      $('#btn-enroll-face-submit').disabled = false;
    }
  });

  $('#btn-enroll-face-submit')?.addEventListener('click', async () => {
    const empId = $('#enroll-employee-id')?.value;
    if (!empId || enrollFaceImages.length === 0) {
      alert('ID de empleado e imágenes son requeridos.');
      return;
    }
    try {
      const form = new FormData();
      form.append('employee_id', empId);
      enrollFaceImages.forEach((img, i) => {
        form.append(`images[${i}]`, img);
      });
      
      const res = await fetch('/api/biometrics/enroll_face.php', { method:'POST', body: form });
      const json = await res.json();
      if (!res.ok) throw new Error(json.error || 'Error enrolamiento facial');
      
      alert('Rostro enrolado exitosamente.');
      enrollFaceImages = [];
      $('#enroll-face-preview').innerHTML = '';
      $('#enroll-face-count').textContent = '0/3 capturas';
      $('#btn-enroll-face-submit').disabled = true;
    } catch (e) {
      alert('Error: ' + e.message);
    }
  });

  $('#enroll-fp-images')?.addEventListener('change', (e) => {
    $('#btn-enroll-fp-submit').disabled = e.target.files.length === 0;
  });

  $('#btn-enroll-fp-submit')?.addEventListener('click', async () => {
    const empId = $('#enroll-employee-id')?.value;
    const files = $('#enroll-fp-images')?.files;
    if (!empId || !files || files.length === 0) {
      alert('ID de empleado e imágenes de huella son requeridos.');
      return;
    }
    try {
      const form = new FormData();
      form.append('employee_id', empId);
      for (let i = 0; i < files.length; i++) {
        form.append('images[]', files[i]);
      }
      
      const res = await fetch('/api/biometrics/enroll_fingerprint.php', { method:'POST', body: form });
      const json = await res.json();
      if (!res.ok) throw new Error(json.error || 'Error enrolamiento huella');
      
      alert('Huella enrolada exitosamente.');
      $('#enroll-fp-images').value = '';
      $('#btn-enroll-fp-submit').disabled = true;
    } catch (e) {
      alert('Error: ' + e.message);
    }
  });

})();