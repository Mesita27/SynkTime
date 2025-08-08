<!-- Módulo de enrolamiento de biometría -->
<div class="container mt-4">
  <h3>Enrolamiento Biométrico</h3>
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Empleado ID</label>
          <input id="enroll-employee-id" class="form-control" placeholder="ID del empleado">
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">Rostro</div>
        <div class="card-body">
          <video id="enroll-cam" class="w-100 border rounded" autoplay playsinline></video>
          <div class="mt-2 d-flex gap-2">
            <button id="btn-enroll-face-capture" class="btn btn-outline-primary">Capturar Foto</button>
            <span id="enroll-face-count" class="text-muted">0/3 capturas</span>
          </div>
          <div class="mt-2" id="enroll-face-preview" class="d-flex gap-2 flex-wrap"></div>
          <div class="mt-3">
            <button id="btn-enroll-face-submit" class="btn btn-primary" disabled>Guardar Rostro</button>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">Huella</div>
        <div class="card-body">
          <div class="mb-2">
            <input type="file" id="enroll-fp-images" accept="image/*" class="form-control" multiple>
            <small class="text-muted">Sube 2-3 imágenes de huella del empleado.</small>
          </div>
          <div>
            <button id="btn-enroll-fp-submit" class="btn btn-primary" disabled>Guardar Huella</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="/js/biometrics.js"></script>