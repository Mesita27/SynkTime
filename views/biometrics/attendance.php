<!-- Página principal de marcaje con biometría -->
<?php
// Incluye tu header/layout existente
$cfg = require __DIR__ . '/../../config/biometrics.php';
?>
<div class="container mt-4">
  <h3>Marcaje de Asistencia</h3>
  <ul class="nav nav-tabs" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-face" role="tab">Reconocimiento Facial</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-fingerprint" role="tab">Huella Dactilar</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-photo" role="tab">Tradicional (Foto)</a></li>
  </ul>
  <div class="tab-content border border-top-0 p-3">
    <div class="tab-pane fade show active" id="tab-face" role="tabpanel">
      <div class="row">
        <div class="col-md-6">
          <video id="cam-preview" class="w-100 border rounded" autoplay playsinline></video>
          <div class="mt-2">
            <button id="btn-capture-face" class="btn btn-primary">Reconocer y Capturar</button>
          </div>
        </div>
        <div class="col-md-6">
          <div id="face-result" class="alert d-none"></div>
          <div class="mt-3 d-flex gap-2">
            <button id="btn-confirm-face" class="btn btn-success" disabled>Confirmar Marcaje</button>
            <button id="btn-reset-face" class="btn btn-outline-secondary">Reintentar</button>
          </div>
        </div>
      </div>
    </div>
    <div class="tab-pane fade" id="tab-fingerprint" role="tabpanel">
      <div class="row">
        <div class="col-md-6">
          <div class="border p-3 text-center">
            <img src="/images/placeholders/fingerprint_placeholder.svg" alt="Fingerprint" class="img-fluid" style="max-height:240px">
          </div>
          <div class="mt-2">
            <input type="file" id="fp-image" accept="image/*" class="form-control">
            <small class="text-muted">Sube la imagen de huella desde el lector/SDK.</small>
          </div>
          <div class="mt-2">
            <button id="btn-identify-fp" class="btn btn-primary">Identificar</button>
          </div>
        </div>
        <div class="col-md-6">
          <div id="fp-result" class="alert d-none"></div>
          <div class="mt-3 d-flex gap-2">
            <button id="btn-confirm-fp" class="btn btn-success" disabled>Confirmar Marcaje</button>
            <button id="btn-reset-fp" class="btn btn-outline-secondary">Reintentar</button>
          </div>
        </div>
      </div>
    </div>
    <div class="tab-pane fade" id="tab-photo" role="tabpanel">
      <div class="row">
        <div class="col-md-6">
          <video id="cam-preview-photo" class="w-100 border rounded" autoplay playsinline></video>
          <div class="mt-2">
            <button id="btn-capture-photo" class="btn btn-primary">Capturar Foto</button>
          </div>
        </div>
        <div class="col-md-6">
          <div id="photo-result" class="alert d-none"></div>
          <div class="mt-3 d-flex gap-2">
            <label for="photo-employee-id" class="col-form-label">Empleado ID</label>
            <input id="photo-employee-id" class="form-control" placeholder="ID del empleado">
            <button id="btn-confirm-photo" class="btn btn-success" disabled>Confirmar Marcaje</button>
            <button id="btn-reset-photo" class="btn btn-outline-secondary">Reintentar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal de confirmación genérico -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmar Marcaje</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="confirmBody">¿Deseas confirmar el marcaje?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="confirmYes">Confirmar</button>
      </div>
    </div>
  </div>
</div>

<script src="/js/biometrics.js"></script>