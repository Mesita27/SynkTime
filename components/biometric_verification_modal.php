<!-- Modal para Verificación Biométrica -->
<div class="modal" id="biometricVerificationModal">
  <div class="modal-content">
    <button type="button" class="modal-close" aria-label="Cerrar" onclick="closeBiometricVerificationModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-shield-alt"></i> Verificación Biométrica</h3>
      <p class="modal-subtitle">Empleado: <span id="biometric_employee_name"></span></p>
    </div>
    
    <div class="modal-body">
      <!-- Opciones de verificación -->
      <div class="verification-options">
        <div class="verification-option" onclick="selectVerificationMethod('fingerprint')">
          <div class="verification-icon">
            <i class="fas fa-fingerprint"></i>
          </div>
          <div class="verification-content">
            <h4>Verificación por Huella</h4>
            <p>Usa tu lector de huellas dactilares</p>
          </div>
          <div class="verification-status" id="fingerprint_status">
            <span class="status-text">Detectando dispositivo...</span>
            <i class="fas fa-spinner fa-spin status-icon"></i>
          </div>
        </div>
        
        <div class="verification-option" onclick="selectVerificationMethod('facial')">
          <div class="verification-icon">
            <i class="fas fa-user-circle"></i>
          </div>
          <div class="verification-content">
            <h4>Reconocimiento Facial</h4>
            <p>Usa la cámara para verificar tu identidad</p>
          </div>
          <div class="verification-status" id="facial_status">
            <span class="status-text">Detectando cámara...</span>
            <i class="fas fa-spinner fa-spin status-icon"></i>
          </div>
        </div>
        
        <div class="verification-option" onclick="selectVerificationMethod('traditional')">
          <div class="verification-icon">
            <i class="fas fa-camera"></i>
          </div>
          <div class="verification-content">
            <h4>Verificación Tradicional</h4>
            <p>Captura manual de foto</p>
          </div>
          <div class="verification-status" id="traditional_status">
            <span class="status-text">Disponible</span>
            <i class="fas fa-check-circle status-icon available"></i>
          </div>
        </div>
      </div>
      
      <!-- Información del dispositivo seleccionado -->
      <div class="device-info" id="device_info" style="display: none;">
        <h4>Dispositivo Seleccionado</h4>
        <p id="device_details"></p>
        <button type="button" class="btn-primary" id="start_verification_btn" onclick="startVerification()">
          <i class="fas fa-play"></i> Iniciar Verificación
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal para Verificación por Huella -->
<div class="modal" id="fingerprintVerificationModal">
  <div class="modal-content">
    <button type="button" class="modal-close" aria-label="Cerrar" onclick="closeFingerprintVerificationModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-fingerprint"></i> Verificación por Huella</h3>
    </div>
    
    <div class="modal-body">
      <div class="fingerprint-scanner">
        <div class="scanner-animation">
          <div class="fingerprint-icon">
            <i class="fas fa-fingerprint"></i>
          </div>
          <div class="scanning-lines"></div>
        </div>
        <div class="scanner-instructions">
          <h4 id="fingerprint_instruction">Coloca tu dedo en el lector</h4>
          <p id="fingerprint_status_text">Esperando lectura...</p>
        </div>
      </div>
      
      <div class="verification-actions">
        <button type="button" class="btn-secondary" onclick="cancelFingerprintVerification()">
          <i class="fas fa-times"></i> Cancelar
        </button>
        <button type="button" class="btn-primary" id="retry_fingerprint_btn" onclick="retryFingerprintVerification()" style="display: none;">
          <i class="fas fa-redo"></i> Reintentar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal para Reconocimiento Facial -->
<div class="modal" id="facialVerificationModal">
  <div class="modal-content">
    <button type="button" class="modal-close" aria-label="Cerrar" onclick="closeFacialVerificationModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-user-circle"></i> Reconocimiento Facial</h3>
    </div>
    
    <div class="modal-body">
      <div class="facial-recognition">
        <div class="camera-preview">
          <video id="facial_video" autoplay playsinline></video>
          <canvas id="facial_canvas" style="display: none;"></canvas>
          <div class="face-outline">
            <div class="face-frame"></div>
          </div>
          <div class="face-detection-overlay">
            <span id="face_detection_status" class="detection-status none">Iniciando detección...</span>
          </div>
        </div>
        <div class="recognition-instructions">
          <h4 id="facial_instruction">Posiciona tu rostro en el marco</h4>
          <p id="facial_status_text">Iniciando cámara...</p>
        </div>
      </div>
      
      <div class="verification-actions">
        <button type="button" class="btn-secondary" onclick="cancelFacialVerification()">
          <i class="fas fa-times"></i> Cancelar
        </button>
        <button type="button" class="btn-primary" id="capture_face_btn" onclick="captureFaceForVerification()" style="display: none;">
          <i class="fas fa-camera"></i> Verificar
        </button>
      </div>
    </div>
  </div>
</div>