<?php
/**
 * Biometric Enrollment Modal Component
 * Used for enrolling employee biometric data
 */
?>

<div id="biometricEnrollmentModal" class="biometric-modal">
    <div class="biometric-modal-content" style="max-width: 700px;">
        <div class="biometric-modal-header">
            <h3 class="biometric-modal-title">
                <i class="fas fa-user-plus"></i>
                Inscripción Biométrica
            </h3>
            <button type="button" class="biometric-close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="biometric-modal-body">
            <!-- Employee Selection Step -->
            <div id="employeeSelectionStep" class="enrollment-step">
                <h4>Paso 1: Seleccionar Empleado</h4>
                <div class="employee-selection-area">
                    <div class="form-group">
                        <label for="enrollmentEmployeeSelect">Empleado a inscribir</label>
                        <select id="enrollmentEmployeeSelect" class="form-control">
                            <option value="">Seleccione un empleado</option>
                        </select>
                    </div>
                    
                    <div id="selectedEmployeeInfo" class="selected-employee-info" style="display: none;">
                        <div class="employee-card">
                            <div class="employee-photo">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="employee-details">
                                <h5 class="employee-name">Nombre del empleado</h5>
                                <p class="employee-code">Código: </p>
                                <p class="employee-establishment">Establecimiento: </p>
                            </div>
                            <div class="current-biometric-status">
                                <div class="biometric-status-item">
                                    <i class="fas fa-fingerprint"></i>
                                    <span class="fingerprint-status-text">No inscrito</span>
                                </div>
                                <div class="biometric-status-item">
                                    <i class="fas fa-user-check"></i>
                                    <span class="facial-status-text">No inscrito</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="step-actions">
                    <button type="button" class="btn-primary" onclick="proceedToMethodSelection()" disabled>
                        <i class="fas fa-arrow-right"></i> Continuar
                    </button>
                </div>
            </div>

            <!-- Method Selection Step -->
            <div id="methodSelectionStep" class="enrollment-step" style="display: none;">
                <h4>Paso 2: Seleccionar Tipo de Inscripción</h4>
                <div class="enrollment-methods">
                    <div class="enrollment-method" data-method="fingerprint">
                        <i class="fas fa-fingerprint enrollment-method-icon"></i>
                        <div class="enrollment-method-title">Huella Dactilar</div>
                        <div class="enrollment-method-desc">
                            Inscribir patrones de huella dactilar para verificación segura
                        </div>
                        <div class="enrollment-method-status">
                            <span class="status-badge not-enrolled">No inscrito</span>
                        </div>
                    </div>
                    
                    <div class="enrollment-method" data-method="facial">
                        <i class="fas fa-user-check enrollment-method-icon"></i>
                        <div class="enrollment-method-title">Reconocimiento Facial</div>
                        <div class="enrollment-method-desc">
                            Inscribir patrones faciales para reconocimiento automático
                        </div>
                        <div class="enrollment-method-status">
                            <span class="status-badge not-enrolled">No inscrito</span>
                        </div>
                    </div>
                </div>
                
                <div class="step-actions">
                    <button type="button" class="btn-secondary" onclick="backToEmployeeSelection()">
                        <i class="fas fa-arrow-left"></i> Atrás
                    </button>
                    <button type="button" class="btn-primary" onclick="proceedToEnrollment()" disabled>
                        <i class="fas fa-arrow-right"></i> Inscribir
                    </button>
                </div>
            </div>

            <!-- Enrollment Process Step -->
            <div id="enrollmentProcessStep" class="enrollment-step" style="display: none;">
                <h4 id="enrollmentProcessTitle">Paso 3: Proceso de Inscripción</h4>
                
                <!-- Fingerprint Enrollment -->
                <div id="fingerprintEnrollmentArea" class="enrollment-area" style="display: none;">
                    <div class="finger-selection-section">
                        <h5>Seleccione el dedo a inscribir</h5>
                        <div class="finger-selection">
                            <div class="finger-option" data-finger="thumb">Pulgar</div>
                            <div class="finger-option" data-finger="index">Índice</div>
                            <div class="finger-option" data-finger="middle">Medio</div>
                            <div class="finger-option" data-finger="ring">Anular</div>
                            <div class="finger-option" data-finger="little">Meñique</div>
                        </div>
                    </div>
                    
                    <div class="fingerprint-enrollment-process">
                        <div class="fingerprint-scanner">
                            <i class="fas fa-fingerprint fingerprint-icon"></i>
                        </div>
                        <div class="enrollment-instructions">
                            <p>Coloque el dedo seleccionado en el lector y manténgalo presionado hasta que complete la captura.</p>
                        </div>
                        <div class="enrollment-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 0%;"></div>
                            </div>
                            <div class="progress-text">Progreso: 0/3 capturas</div>
                        </div>
                    </div>
                </div>

                <!-- Facial Enrollment -->
                <div id="facialEnrollmentArea" class="enrollment-area" style="display: none;">
                    <div class="facial-enrollment-process">
                        <div class="biometric-camera-area">
                            <video class="biometric-video" autoplay playsinline></video>
                            <div class="camera-overlay"></div>
                        </div>
                        <div class="enrollment-instructions">
                            <p>Posicione su rostro dentro del círculo. Se tomarán múltiples capturas desde diferentes ángulos.</p>
                        </div>
                        <div class="enrollment-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 0%;"></div>
                            </div>
                            <div class="progress-text">Progreso: 0/5 capturas</div>
                        </div>
                    </div>
                </div>

                <!-- Status Messages -->
                <div class="biometric-status info" style="display: none;">
                    <i class="fas fa-info-circle"></i>
                    Mensaje de estado aparecerá aquí
                </div>
                
                <div class="step-actions">
                    <button type="button" class="btn-secondary" onclick="backToMethodSelection()">
                        <i class="fas fa-arrow-left"></i> Atrás
                    </button>
                    <button type="button" class="btn-primary" id="startEnrollmentBtn" onclick="startEnrollmentProcess()">
                        <i class="fas fa-play"></i> Iniciar Inscripción
                    </button>
                    <button type="button" class="btn-success" id="completeEnrollmentBtn" onclick="completeEnrollment()" style="display: none;">
                        <i class="fas fa-check"></i> Completar
                    </button>
                </div>
            </div>

            <!-- Success Step -->
            <div id="enrollmentSuccessStep" class="enrollment-step" style="display: none;">
                <div class="success-message">
                    <i class="fas fa-check-circle success-icon"></i>
                    <h4>¡Inscripción Completada!</h4>
                    <p>Los datos biométricos han sido registrados correctamente.</p>
                    
                    <div class="enrollment-summary">
                        <h5>Resumen de la inscripción:</h5>
                        <div class="summary-item">
                            <strong>Empleado:</strong> <span id="summaryEmployeeName"></span>
                        </div>
                        <div class="summary-item">
                            <strong>Método:</strong> <span id="summaryMethod"></span>
                        </div>
                        <div class="summary-item">
                            <strong>Fecha:</strong> <span id="summaryDate"></span>
                        </div>
                    </div>
                </div>
                
                <div class="step-actions">
                    <button type="button" class="btn-secondary" onclick="closeEnrollmentModal()">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                    <button type="button" class="btn-primary" onclick="enrollAnotherEmployee()">
                        <i class="fas fa-plus"></i> Inscribir Otro Empleado
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Enrollment Modal Specific Styles */
.enrollment-step {
    min-height: 400px;
}

.enrollment-step h4 {
    color: var(--text-primary);
    margin-bottom: 1.5rem;
    font-size: 1.2rem;
    font-weight: 600;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--primary);
}

.employee-selection-area {
    margin-bottom: 2rem;
}

.selected-employee-info {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--background);
    border-radius: var(--border-radius);
    border: 1px solid var(--border);
}

.employee-card {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.employee-photo {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--primary-lighter);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 1.5rem;
}

.employee-details {
    flex: 1;
}

.employee-details h5 {
    margin: 0 0 0.25rem 0;
    color: var(--text-primary);
    font-weight: 600;
}

.employee-details p {
    margin: 0.25rem 0;
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.current-biometric-status {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.biometric-status-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.enrollment-methods {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.enrollment-method {
    padding: 1.5rem;
    border: 2px solid var(--border);
    border-radius: var(--border-radius);
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: var(--surface);
    position: relative;
}

.enrollment-method:hover {
    border-color: var(--primary-light);
    background: var(--primary-lighter);
}

.enrollment-method.selected {
    border-color: var(--primary);
    background: var(--primary-lighter);
}

.enrollment-method.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.enrollment-method-icon {
    font-size: 2.5rem;
    color: var(--primary);
    margin-bottom: 1rem;
    display: block;
}

.enrollment-method-title {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
}

.enrollment-method-desc {
    font-size: 0.875rem;
    color: var(--text-secondary);
    line-height: 1.4;
    margin-bottom: 1rem;
}

.enrollment-method-status {
    position: absolute;
    top: 1rem;
    right: 1rem;
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.status-badge.enrolled {
    background: rgba(72, 187, 120, 0.1);
    color: var(--success);
}

.status-badge.not-enrolled {
    background: rgba(245, 101, 101, 0.1);
    color: var(--danger);
}

.enrollment-area {
    margin-bottom: 2rem;
}

.finger-selection-section {
    margin-bottom: 2rem;
}

.finger-selection-section h5 {
    margin-bottom: 1rem;
    color: var(--text-primary);
    font-weight: 600;
}

.fingerprint-enrollment-process,
.facial-enrollment-process {
    text-align: center;
}

.enrollment-instructions {
    margin: 1rem 0;
    padding: 1rem;
    background: var(--primary-lighter);
    border-radius: var(--border-radius);
    color: var(--primary);
    font-weight: 500;
}

.step-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border);
}

.success-message {
    text-align: center;
    padding: 2rem;
}

.success-icon {
    font-size: 4rem;
    color: var(--success);
    margin-bottom: 1rem;
}

.success-message h4 {
    color: var(--success);
    margin-bottom: 1rem;
    border: none;
    padding: 0;
}

.enrollment-summary {
    background: var(--background);
    padding: 1.5rem;
    border-radius: var(--border-radius);
    margin-top: 2rem;
    text-align: left;
}

.enrollment-summary h5 {
    margin-bottom: 1rem;
    color: var(--text-primary);
    font-weight: 600;
}

.summary-item {
    margin-bottom: 0.5rem;
    color: var(--text-secondary);
}

.summary-item strong {
    color: var(--text-primary);
}

@media (max-width: 768px) {
    .employee-card {
        flex-direction: column;
        text-align: center;
    }
    
    .step-actions {
        flex-direction: column;
        gap: 1rem;
    }
    
    .step-actions .btn-secondary,
    .step-actions .btn-primary,
    .step-actions .btn-success {
        width: 100%;
        justify-content: center;
    }
}
</style>