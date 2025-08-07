<!-- Modal para Inscripción Biométrica -->
<div class="modal" id="biometricEnrollmentModal">
  <div class="modal-content large-modal">
    <button type="button" class="modal-close" aria-label="Cerrar" onclick="closeBiometricEnrollmentModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-user-plus"></i> Inscripción Biométrica</h3>
      <p class="modal-subtitle">Registrar datos biométricos de empleados</p>
    </div>
    
    <div class="modal-body">
      <!-- Selector de empleados -->
      <div class="employee-selection-section">
        <h4><i class="fas fa-users"></i> Seleccionar Empleado</h4>
        
        <!-- Filtros de búsqueda -->
        <div class="enrollment-query-box">
          <form class="enrollment-query-form" autocomplete="off">
            <div class="query-row">
              <div class="form-group">
                <label for="enrollment_sede">Sede</label>
                <select id="enrollment_sede" name="sede" class="form-control"></select>
              </div>
              <div class="form-group">
                <label for="enrollment_establecimiento">Establecimiento</label>
                <select id="enrollment_establecimiento" name="establecimiento" class="form-control"></select>
              </div>
              <div class="form-group">
                <label for="enrollment_codigo">Código Empleado</label>
                <input type="text" id="enrollment_codigo" name="codigo" class="form-control" placeholder="Ingrese código">
              </div>
              <div class="form-group query-btns">
                <button type="button" id="btnBuscarEnrollment" class="btn-primary">
                  <i class="fas fa-search"></i> Buscar
                </button>
                <button type="button" id="btnLimpiarEnrollment" class="btn-secondary">
                  <i class="fas fa-redo"></i> Limpiar
                </button>
              </div>
            </div>
          </form>
        </div>
        
        <!-- Tabla de empleados -->
        <div class="enrollment-table-container">
          <table class="enrollment-table">
            <thead>
              <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Establecimiento</th>
                <th>Sede</th>
                <th>Estado Biométrico</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody id="enrollmentTableBody">
              <tr>
                <td colspan="6" class="loading-text">
                  <i class="fas fa-spinner fa-spin"></i> Cargando empleados...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Sección de inscripción biométrica -->
      <div class="biometric-enrollment-section" id="biometric_enrollment_section" style="display: none;">
        <h4><i class="fas fa-fingerprint"></i> Datos Biométricos</h4>
        <div class="selected-employee-info">
          <p><strong>Empleado seleccionado:</strong> <span id="selected_employee_name"></span></p>
          <p><strong>Código:</strong> <span id="selected_employee_code"></span></p>
        </div>
        
        <!-- Opciones de inscripción -->
        <div class="enrollment-options">
          <div class="enrollment-option" onclick="selectEnrollmentType('fingerprint')">
            <div class="enrollment-icon">
              <i class="fas fa-fingerprint"></i>
            </div>
            <div class="enrollment-content">
              <h5>Inscripción de Huella</h5>
              <p>Registrar huellas dactilares</p>
            </div>
            <div class="enrollment-device-status" id="fingerprint_device_status">
              <span class="status-text">Detectando lector...</span>
              <i class="fas fa-spinner fa-spin status-icon"></i>
            </div>
          </div>
          
          <div class="enrollment-option" onclick="selectEnrollmentType('facial')">
            <div class="enrollment-icon">
              <i class="fas fa-user-circle"></i>
            </div>
            <div class="enrollment-content">
              <h5>Inscripción Facial</h5>
              <p>Registrar patrón facial</p>
            </div>
            <div class="enrollment-device-status" id="facial_device_status">
              <span class="status-text">Detectando cámara...</span>
              <i class="fas fa-spinner fa-spin status-icon"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal para Inscripción de Huella -->
<div class="modal" id="fingerprintEnrollmentModal">
  <div class="modal-content">
    <button type="button" class="modal-close" aria-label="Cerrar" onclick="closeFingerprintEnrollmentModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-fingerprint"></i> Inscripción de Huella</h3>
      <p class="modal-subtitle">Empleado: <span id="fingerprint_enrollment_employee"></span></p>
    </div>
    
    <div class="modal-body">
      <!-- Selector de dedos -->
      <div class="finger-selection">
        <h4>Seleccionar Dedo</h4>
        <div class="hand-diagram">
          <div class="hand left-hand">
            <div class="finger" data-finger="left_thumb" onclick="selectFinger('left_thumb')">
              <span class="finger-label">Pulgar Izq.</span>
            </div>
            <div class="finger" data-finger="left_index" onclick="selectFinger('left_index')">
              <span class="finger-label">Índice Izq.</span>
            </div>
            <div class="finger" data-finger="left_middle" onclick="selectFinger('left_middle')">
              <span class="finger-label">Medio Izq.</span>
            </div>
            <div class="finger" data-finger="left_ring" onclick="selectFinger('left_ring')">
              <span class="finger-label">Anular Izq.</span>
            </div>
            <div class="finger" data-finger="left_pinky" onclick="selectFinger('left_pinky')">
              <span class="finger-label">Meñique Izq.</span>
            </div>
          </div>
          <div class="hand right-hand">
            <div class="finger" data-finger="right_thumb" onclick="selectFinger('right_thumb')">
              <span class="finger-label">Pulgar Der.</span>
            </div>
            <div class="finger" data-finger="right_index" onclick="selectFinger('right_index')">
              <span class="finger-label">Índice Der.</span>
            </div>
            <div class="finger" data-finger="right_middle" onclick="selectFinger('right_middle')">
              <span class="finger-label">Medio Der.</span>
            </div>
            <div class="finger" data-finger="right_ring" onclick="selectFinger('right_ring')">
              <span class="finger-label">Anular Der.</span>
            </div>
            <div class="finger" data-finger="right_pinky" onclick="selectFinger('right_pinky')">
              <span class="finger-label">Meñique Der.</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Proceso de inscripción -->
      <div class="fingerprint-enrollment-process" id="fingerprint_enrollment_process" style="display: none;">
        <div class="scanner-animation">
          <div class="fingerprint-icon">
            <i class="fas fa-fingerprint"></i>
          </div>
          <div class="scanning-lines"></div>
        </div>
        <div class="enrollment-progress">
          <h4 id="fingerprint_enrollment_instruction">Coloca el dedo seleccionado en el lector</h4>
          <div class="progress-bar">
            <div class="progress-fill" id="fingerprint_progress"></div>
          </div>
          <p id="fingerprint_enrollment_status">Esperando primera lectura...</p>
        </div>
      </div>
      
      <div class="enrollment-actions">
        <button type="button" class="btn-secondary" onclick="cancelFingerprintEnrollment()">
          <i class="fas fa-times"></i> Cancelar
        </button>
        <button type="button" class="btn-primary" id="save_fingerprint_btn" onclick="saveFingerprintEnrollment()" style="display: none;">
          <i class="fas fa-save"></i> Guardar Huella
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal para Inscripción Facial -->
<div class="modal" id="facialEnrollmentModal">
  <div class="modal-content">
    <button type="button" class="modal-close" aria-label="Cerrar" onclick="closeFacialEnrollmentModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-user-circle"></i> Inscripción Facial</h3>
      <p class="modal-subtitle">Empleado: <span id="facial_enrollment_employee"></span></p>
    </div>
    
    <div class="modal-body">
      <div class="facial-enrollment">
        <div class="camera-preview">
          <video id="facial_enrollment_video" autoplay playsinline></video>
          <canvas id="facial_enrollment_canvas" style="display: none;"></canvas>
          <div class="face-outline">
            <div class="face-frame"></div>
          </div>
        </div>
        
        <!-- Instrucciones -->
        <div class="enrollment-instructions">
          <h4 id="facial_enrollment_instruction">Posiciona tu rostro en el marco</h4>
          <div class="instruction-steps">
            <div class="step" id="step_1">
              <i class="fas fa-1"></i>
              <span>Mira directamente a la cámara</span>
            </div>
            <div class="step" id="step_2">
              <i class="fas fa-2"></i>
              <span>Mantén el rostro centrado</span>
            </div>
            <div class="step" id="step_3">
              <i class="fas fa-3"></i>
              <span>Permanece inmóvil</span>
            </div>
          </div>
          <p id="facial_enrollment_status">Iniciando cámara...</p>
        </div>
        
        <!-- Capturas tomadas -->
        <div class="captured-images" id="captured_images" style="display: none;">
          <h5>Capturas Tomadas:</h5>
          <div class="image-gallery" id="facial_captures">
            <!-- Las imágenes capturadas se mostrarán aquí -->
          </div>
        </div>
      </div>
      
      <div class="enrollment-actions">
        <button type="button" class="btn-secondary" onclick="cancelFacialEnrollment()">
          <i class="fas fa-times"></i> Cancelar
        </button>
        <button type="button" class="btn-warning" id="capture_face_enrollment_btn" onclick="captureFaceForEnrollment()" style="display: none;">
          <i class="fas fa-camera"></i> Capturar
        </button>
        <button type="button" class="btn-primary" id="save_facial_btn" onclick="saveFacialEnrollment()" style="display: none;">
          <i class="fas fa-save"></i> Guardar Patrón
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal de Opciones Biométricas -->
<div class="modal" id="biometricOptionsModal">
  <div class="modal-content">
    <button type="button" class="modal-close" aria-label="Cerrar" onclick="closeBiometricOptionsModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-fingerprint"></i> Opciones de Inscripción Biométrica</h3>
      <p class="modal-subtitle">Selecciona el tipo de registro para el empleado</p>
    </div>
    
    <div class="modal-body">
      <div class="selected-employee-summary">
        <div class="employee-card">
          <div class="employee-avatar">
            <i class="fas fa-user-circle"></i>
          </div>
          <div class="employee-details">
            <h4 id="biometric_options_employee_name">Nombre del Empleado</h4>
            <p><strong>Código:</strong> <span id="biometric_options_employee_code">000</span></p>
          </div>
        </div>
      </div>
      
      <div class="biometric-type-selection">
        <h4><i class="fas fa-hand-pointer"></i> Selecciona el tipo de inscripción:</h4>
        
        <div class="biometric-type-options">
          <div class="biometric-type-card" onclick="selectBiometricEnrollmentType('fingerprint')">
            <div class="type-icon fingerprint-icon">
              <i class="fas fa-fingerprint"></i>
            </div>
            <div class="type-content">
              <h5>Solo Huella Dactilar</h5>
              <p>Registrar únicamente las huellas dactilares del empleado</p>
              <div class="type-features">
                <span class="feature"><i class="fas fa-check"></i> Rápido y eficiente</span>
                <span class="feature"><i class="fas fa-check"></i> Alta precisión</span>
              </div>
            </div>
            <div class="device-status-indicator" id="fingerprint_status_indicator">
              <i class="fas fa-spinner fa-spin"></i>
            </div>
          </div>
          
          <div class="biometric-type-card" onclick="selectBiometricEnrollmentType('facial')">
            <div class="type-icon facial-icon">
              <i class="fas fa-user-circle"></i>
            </div>
            <div class="type-content">
              <h5>Solo Reconocimiento Facial</h5>
              <p>Registrar únicamente el patrón facial del empleado</p>
              <div class="type-features">
                <span class="feature"><i class="fas fa-check"></i> Sin contacto físico</span>
                <span class="feature"><i class="fas fa-check"></i> Fácil de usar</span>
              </div>
            </div>
            <div class="device-status-indicator" id="facial_status_indicator">
              <i class="fas fa-spinner fa-spin"></i>
            </div>
          </div>
          
          <div class="biometric-type-card recommended" onclick="selectBiometricEnrollmentType('both')">
            <div class="recommended-badge">
              <i class="fas fa-star"></i> Recomendado
            </div>
            <div class="type-icon combined-icon">
              <i class="fas fa-fingerprint"></i>
              <i class="fas fa-user-circle"></i>
            </div>
            <div class="type-content">
              <h5>Inscripción Completa</h5>
              <p>Registrar tanto huella dactilar como patrón facial para máxima seguridad</p>
              <div class="type-features">
                <span class="feature"><i class="fas fa-check"></i> Máxima seguridad</span>
                <span class="feature"><i class="fas fa-check"></i> Respaldo dual</span>
                <span class="feature"><i class="fas fa-check"></i> Flexibilidad</span>
              </div>
            </div>
            <div class="device-status-indicator" id="combined_status_indicator">
              <i class="fas fa-check-circle available"></i>
            </div>
          </div>
        </div>
      </div>
      
      <div class="enrollment-notes">
        <div class="note-card">
          <i class="fas fa-info-circle"></i>
          <div class="note-content">
            <h6>Nota importante:</h6>
            <p>Se recomienda la inscripción completa para mayor seguridad y respaldo en caso de fallos en un método de verificación.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>