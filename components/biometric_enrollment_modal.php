<!-- Biometric Enrollment Modal -->
<div id="biometricEnrollmentModal" class="modal">
  <div class="modal-content modal-content-lg" id="biometricModalContent">
    <button type="button" class="modal-close" id="closeBiometricModal">&times;</button>
    <h3 id="biometricModalTitle" style="margin-top:0;">Registro Biométrico</h3>
    
    <div class="biometric-tabs">
      <button type="button" class="tab-button active" data-tab="facial" id="facialTab">
        <i class="fas fa-face-smile"></i> Reconocimiento Facial
      </button>
      <button type="button" class="tab-button" data-tab="fingerprint" id="fingerprintTab">
        <i class="fas fa-fingerprint"></i> Huella Dactilar
      </button>
    </div>

    <!-- Facial Recognition Tab -->
    <div id="facialContent" class="tab-content active">
      <div class="biometric-section">
        <div class="biometric-info">
          <h4><i class="fas fa-info-circle"></i> Reconocimiento Facial</h4>
          <p>Capture una imagen clara de su rostro para el sistema de reconocimiento facial. Asegúrese de tener buena iluminación y mirar directamente a la cámara.</p>
        </div>
        
        <div class="camera-container">
          <video id="facialVideo" autoplay playsinline width="640" height="480"></video>
          <canvas id="facialCanvas" width="640" height="480" style="display:none;"></canvas>
        </div>
        
        <div id="facialPreview" class="biometric-preview"></div>
        
        <div class="biometric-actions">
          <button type="button" class="btn-secondary" id="startFacialCamera">
            <i class="fas fa-video"></i> Iniciar Cámara
          </button>
          <button type="button" class="btn-primary" id="captureFacialBtn" disabled>
            <i class="fas fa-camera"></i> Capturar Rostro
          </button>
          <button type="button" class="btn-success" id="saveFacialBtn" disabled>
            <i class="fas fa-save"></i> Guardar Datos Faciales
          </button>
        </div>
        
        <div id="facialStatus" class="biometric-status"></div>
      </div>
    </div>

    <!-- Fingerprint Tab -->
    <div id="fingerprintContent" class="tab-content">
      <div class="biometric-section">
        <div class="biometric-info">
          <h4><i class="fas fa-info-circle"></i> Huella Dactilar</h4>
          <p>Registre su huella dactilar para acceso rápido y seguro. Coloque su dedo en el lector cuando se le solicite.</p>
        </div>
        
        <div class="fingerprint-container">
          <div class="fingerprint-simulator">
            <div class="fingerprint-icon">
              <i class="fas fa-fingerprint"></i>
            </div>
            <div class="fingerprint-instructions">
              <p>Coloque su dedo en el lector de huellas</p>
              <div class="fingerprint-progress">
                <div class="progress-bar" id="fingerprintProgress"></div>
              </div>
            </div>
          </div>
        </div>
        
        <div id="fingerprintPreview" class="biometric-preview"></div>
        
        <div class="biometric-actions">
          <button type="button" class="btn-secondary" id="startFingerprintBtn">
            <i class="fas fa-fingerprint"></i> Iniciar Lectura
          </button>
          <button type="button" class="btn-primary" id="captureFingerprintBtn" disabled>
            <i class="fas fa-hand-paper"></i> Capturar Huella
          </button>
          <button type="button" class="btn-success" id="saveFingerprintBtn" disabled>
            <i class="fas fa-save"></i> Guardar Huella
          </button>
        </div>
        
        <div id="fingerprintStatus" class="biometric-status"></div>
      </div>
    </div>

    <div class="modal-footer">
      <div class="biometric-summary" id="biometricSummary">
        <h4>Estado del Registro</h4>
        <div class="status-indicators">
          <span class="status-item" id="facialStatus">
            <i class="fas fa-face-smile"></i> Facial: <span class="status-text">No registrado</span>
          </span>
          <span class="status-item" id="fingerprintStatusSummary">
            <i class="fas fa-fingerprint"></i> Huella: <span class="status-text">No registrado</span>
          </span>
        </div>
      </div>
      
      <div class="form-actions">
        <button type="button" class="btn-primary" id="completeBiometricBtn">Completar Registro</button>
        <button type="button" class="btn-secondary" id="cancelBiometricModal">Cancelar</button>
      </div>
    </div>
    
    <input type="hidden" id="biometricEmployeeId" value="">
    <div id="biometricFormError" class="error-message" style="display:none;"></div>
  </div>
</div>

<style>
.modal-content-lg {
  max-width: 800px;
  width: 90%;
}

.biometric-tabs {
  display: flex;
  border-bottom: 1px solid #e0e0e0;
  margin-bottom: 20px;
}

.tab-button {
  flex: 1;
  padding: 12px 20px;
  border: none;
  background: #f5f5f5;
  cursor: pointer;
  border-bottom: 3px solid transparent;
  transition: all 0.3s ease;
}

.tab-button.active {
  background: #fff;
  border-bottom-color: #4a90e2;
  color: #4a90e2;
}

.tab-content {
  display: none;
}

.tab-content.active {
  display: block;
}

.biometric-section {
  padding: 20px 0;
}

.biometric-info {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.biometric-info h4 {
  margin: 0 0 10px 0;
  color: #2c3e50;
}

.camera-container {
  text-align: center;
  background: #000;
  border-radius: 8px;
  overflow: hidden;
  margin-bottom: 20px;
}

.camera-container video {
  max-width: 100%;
  height: auto;
}

.fingerprint-container {
  display: flex;
  justify-content: center;
  margin-bottom: 20px;
}

.fingerprint-simulator {
  background: #f8f9fa;
  border: 2px dashed #ccc;
  border-radius: 8px;
  padding: 40px;
  text-align: center;
  transition: all 0.3s ease;
}

.fingerprint-simulator.active {
  border-color: #4a90e2;
  background: #e8f4fd;
}

.fingerprint-icon {
  font-size: 64px;
  color: #4a90e2;
  margin-bottom: 15px;
}

.fingerprint-progress {
  background: #e0e0e0;
  height: 8px;
  border-radius: 4px;
  overflow: hidden;
  margin-top: 10px;
}

.progress-bar {
  background: #4a90e2;
  height: 100%;
  width: 0%;
  transition: width 0.3s ease;
}

.biometric-preview {
  margin-bottom: 20px;
  text-align: center;
}

.biometric-preview img {
  max-width: 200px;
  height: auto;
  border-radius: 8px;
  border: 2px solid #e0e0e0;
}

.biometric-actions {
  display: flex;
  gap: 10px;
  justify-content: center;
  margin-bottom: 20px;
}

.biometric-status {
  padding: 10px;
  border-radius: 4px;
  text-align: center;
  display: none;
}

.biometric-status.success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.biometric-status.error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.biometric-status.info {
  background: #d1ecf1;
  color: #0c5460;
  border: 1px solid #bee5eb;
}

.modal-footer {
  border-top: 1px solid #e0e0e0;
  padding-top: 20px;
  margin-top: 20px;
}

.biometric-summary {
  margin-bottom: 20px;
}

.status-indicators {
  display: flex;
  gap: 20px;
  justify-content: center;
}

.status-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: #f8f9fa;
  border-radius: 4px;
  border: 1px solid #e0e0e0;
}

.status-item.success {
  background: #d4edda;
  border-color: #c3e6cb;
  color: #155724;
}

.error-message {
  color: #e53e3e;
  text-align: center;
  margin-top: 10px;
  padding: 10px;
  background: #f8d7da;
  border-radius: 4px;
  border: 1px solid #f5c6cb;
}
</style>