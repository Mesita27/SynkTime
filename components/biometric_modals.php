<!-- Modal Selección de Método Biométrico -->
<div class="modal" id="biometricSelectionModal">
  <div class="modal-content biometric-selection-modal">
    <button type="button" class="modal-close" aria-label="Cerrar" onclick="closeBiometricSelectionModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-fingerprint"></i> Verificación Biométrica</h3>
      <p class="modal-subtitle">Seleccione el método de verificación para registrar asistencia</p>
    </div>
    
    <div class="modal-body">
      <div class="employee-info-display" id="biometricEmployeeInfo" style="display: none;">
        <div class="employee-card">
          <div class="employee-avatar">
            <i class="fas fa-user-circle"></i>
          </div>
          <div class="employee-details">
            <h4 id="biometric_empleado_nombre">Nombre del Empleado</h4>
            <p><span class="label">Código:</span> <span id="biometric_empleado_codigo">COD001</span></p>
            <p><span class="label">Establecimiento:</span> <span id="biometric_empleado_establecimiento">Establecimiento</span></p>
            <p><span class="label">Sede:</span> <span id="biometric_empleado_sede">Sede</span></p>
          </div>
        </div>
      </div>

      <div class="biometric-options">
        <div class="biometric-option" id="fingerprintOption" onclick="selectBiometricMethod('fingerprint')">
          <div class="biometric-icon">
            <i class="fas fa-fingerprint"></i>
          </div>
          <div class="biometric-content">
            <h4>Huella Digital</h4>
            <p>Verificación rápida y segura mediante huella dactilar</p>
            <div class="biometric-status" id="fingerprintStatus">
              <i class="fas fa-check-circle"></i> Disponible
            </div>
          </div>
        </div>

        <div class="biometric-option" id="facialOption" onclick="selectBiometricMethod('facial')">
          <div class="biometric-icon">
            <i class="fas fa-user-check"></i>
          </div>
          <div class="biometric-content">
            <h4>Reconocimiento Facial</h4>
            <p>Verificación mediante análisis facial avanzado</p>
            <div class="biometric-status" id="facialStatus">
              <i class="fas fa-check-circle"></i> Disponible
            </div>
          </div>
        </div>
      </div>

      <div class="biometric-info">
        <div class="info-item">
          <i class="fas fa-shield-alt"></i>
          <span>Sus datos biométricos están protegidos y encriptados</span>
        </div>
        <div class="info-item">
          <i class="fas fa-clock"></i>
          <span>El proceso de verificación toma solo unos segundos</span>
        </div>
      </div>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn-secondary" onclick="closeBiometricSelectionModal()">
        <i class="fas fa-times"></i> Cancelar
      </button>
      <button type="button" class="btn-primary" onclick="useFallbackMethod()" style="display: none;" id="fallbackBtn">
        <i class="fas fa-camera"></i> Usar Método Tradicional
      </button>
    </div>
  </div>
</div>

<!-- Modal Verificación de Huella Digital -->
<div class="modal" id="fingerprintVerificationModal">
  <div class="modal-content fingerprint-modal">
    <button type="button" class="modal-close" aria-label="Cerrar" onclick="closeFingerprintModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-fingerprint"></i> Verificación de Huella Digital</h3>
      <p class="modal-subtitle">Coloque su dedo en el sensor biométrico</p>
    </div>
    
    <div class="modal-body">
      <div class="fingerprint-verification-area">
        <div class="fingerprint-scanner" id="fingerprintScanner">
          <div class="fingerprint-animation">
            <i class="fas fa-fingerprint"></i>
          </div>
          <div class="scanner-status" id="fingerprintScannerStatus">
            <span class="status-text">Esperando huella digital...</span>
          </div>
        </div>

        <div class="verification-progress" id="fingerprintProgress" style="display: none;">
          <div class="progress-bar">
            <div class="progress-fill" id="fingerprintProgressFill"></div>
          </div>
          <span class="progress-text">Verificando...</span>
        </div>

        <div class="verification-result" id="fingerprintResult" style="display: none;">
          <div class="result-icon" id="fingerprintResultIcon"></div>
          <div class="result-message" id="fingerprintResultMessage"></div>
        </div>
      </div>

      <div class="fingerprint-instructions">
        <div class="instruction-item">
          <i class="fas fa-hand-point-up"></i>
          <span>Coloque firmemente el dedo en el sensor</span>
        </div>
        <div class="instruction-item">
          <i class="fas fa-exclamation-triangle"></i>
          <span>Mantenga el dedo inmóvil durante la verificación</span>
        </div>
      </div>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn-secondary" onclick="closeFingerprintModal()">
        <i class="fas fa-times"></i> Cancelar
      </button>
      <button type="button" class="btn-warning" onclick="retryFingerprintVerification()" id="retryFingerprintBtn" style="display: none;">
        <i class="fas fa-redo"></i> Reintentar
      </button>
      <button type="button" class="btn-primary" onclick="startFingerprintVerification()" id="startFingerprintBtn">
        <i class="fas fa-fingerprint"></i> Iniciar Verificación
      </button>
    </div>
  </div>
</div>

<!-- Modal Reconocimiento Facial -->
<div class="modal" id="facialVerificationModal">
  <div class="modal-content facial-modal">
    <button type="button" class="modal-close" aria-label="Cerrar" onclick="closeFacialModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-user-check"></i> Reconocimiento Facial</h3>
      <p class="modal-subtitle">Posicione su rostro frente a la cámara</p>
    </div>
    
    <div class="modal-body">
      <div class="facial-verification-area">
        <div class="camera-container" id="facialCameraContainer">
          <video id="facialVideo" autoplay muted playsinline style="display: none;"></video>
          <canvas id="facialCanvas" style="display: none;"></canvas>
          <div class="camera-overlay">
            <div class="face-outline"></div>
            <div class="scanning-line" id="facialScanningLine" style="display: none;"></div>
          </div>
          <div class="camera-status" id="facialCameraStatus">
            <i class="fas fa-video"></i>
            <span>Preparando cámara...</span>
          </div>
        </div>

        <div class="verification-progress" id="facialProgress" style="display: none;">
          <div class="progress-bar">
            <div class="progress-fill" id="facialProgressFill"></div>
          </div>
          <span class="progress-text">Analizando rostro...</span>
        </div>

        <div class="verification-result" id="facialResult" style="display: none;">
          <div class="result-icon" id="facialResultIcon"></div>
          <div class="result-message" id="facialResultMessage"></div>
        </div>
      </div>

      <div class="facial-instructions">
        <div class="instruction-item">
          <i class="fas fa-eye"></i>
          <span>Mire directamente a la cámara</span>
        </div>
        <div class="instruction-item">
          <i class="fas fa-lightbulb"></i>
          <span>Asegúrese de tener buena iluminación</span>
        </div>
        <div class="instruction-item">
          <i class="fas fa-ban"></i>
          <span>Evite usar gafas de sol o mascarillas</span>
        </div>
      </div>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn-secondary" onclick="closeFacialModal()">
        <i class="fas fa-times"></i> Cancelar
      </button>
      <button type="button" class="btn-warning" onclick="retryFacialVerification()" id="retryFacialBtn" style="display: none;">
        <i class="fas fa-redo"></i> Reintentar
      </button>
      <button type="button" class="btn-primary" onclick="startFacialVerification()" id="startFacialBtn">
        <i class="fas fa-video"></i> Iniciar Verificación
      </button>
    </div>
  </div>
</div>