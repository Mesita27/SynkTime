<div class="modal" id="attendancePhotoModal">
  <div class="modal-content modal-content-md" onclick="event.stopPropagation()">
    <button type="button" class="modal-close" onclick="closeAttendancePhotoModal()">
      <i class="fas fa-times"></i>
    </button>
    <h3 id="photoModalTitle">Registrar Asistencia</h3>
    <div style="text-align:center; margin-bottom:1em;">
      <video id="video" width="240" height="180" autoplay playsinline style="background:#222;border-radius:8px;"></video>
      <canvas id="canvas" width="240" height="180" style="display:none; margin-top:1em;"></canvas>
      <div id="photoPreview" style="margin-top:1em;"></div>
    </div>
    <button type="button" id="takePhotoBtn" class="btn-primary" style="margin-bottom:0.8em;">Tomar Foto</button>
    <button type="button" id="saveAttendanceBtn" class="btn-success" disabled>Guardar</button>
  </div>
</div>