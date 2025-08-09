<?php
/**
 * Biometric Verification Modal Component
 * Used in attendance registration for biometric verification
 */
?>

<div id="biometricVerificationModal" class="biometric-modal">
    <div class="biometric-modal-content">
        <div class="biometric-modal-header">
            <h3 class="biometric-modal-title">
                <i class="fas fa-fingerprint"></i>
                Verificación Biométrica
            </h3>
            <button type="button" class="biometric-close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="biometric-modal-body">
            <!-- Device Status -->
            <div class="device-status-section">
                <h4>Estado de Dispositivos</h4>
                <div class="camera-status device-status connecting">
                    <i class="fas fa-camera device-status-icon"></i>
                    <span>Detectando cámara...</span>
                </div>
                <div class="fingerprint-status device-status connecting">
                    <i class="fas fa-fingerprint device-status-icon"></i>
                    <span>Detectando lector de huellas...</span>
                </div>
            </div>

            <!-- Verification Method Selection -->
            <div class="verification-section">
                <h4>Seleccione Método de Verificación</h4>
                <div class="verification-methods">
                    <div class="verification-method" data-method="fingerprint">
                        <i class="fas fa-fingerprint verification-method-icon"></i>
                        <div class="verification-method-title">Huella Dactilar</div>
                        <div class="verification-method-desc">Verificación mediante huella dactilar</div>
                    </div>
                    
                    <div class="verification-method" data-method="facial">
                        <i class="fas fa-user-check verification-method-icon"></i>
                        <div class="verification-method-title">Reconocimiento Facial</div>
                        <div class="verification-method-desc">Captura automática con reconocimiento</div>
                    </div>
                    
                    <div class="verification-method" data-method="traditional">
                        <i class="fas fa-camera verification-method-icon"></i>
                        <div class="verification-method-title">Foto Tradicional</div>
                        <div class="verification-method-desc">Captura manual de fotografía</div>
                    </div>
                </div>
            </div>

            <!-- Biometric Process Area -->
            <div class="biometric-process">
                <div class="initial-state">
                    <i class="fas fa-hand-paper" style="font-size: 4rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                    <p style="color: var(--text-secondary); font-size: 1.1rem;">
                        Seleccione un método de verificación para continuar
                    </p>
                </div>
            </div>

            <!-- Progress Indicator -->
            <div class="biometric-progress" style="display: none;">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%;"></div>
                </div>
                <div class="progress-text">Procesando...</div>
            </div>

            <!-- Status Messages -->
            <div class="biometric-status info" style="display: none;">
                <i class="fas fa-info-circle"></i>
                Mensaje de estado aparecerá aquí
            </div>
        </div>

        <div class="biometric-modal-footer">
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeBiometricModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn-primary" id="proceedTraditional" style="display: none;" onclick="proceedWithTraditionalAttendance()">
                    <i class="fas fa-arrow-right"></i> Continuar sin Biometría
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Additional styles specific to verification modal */
.device-status-section {
    margin-bottom: 2rem;
    padding: 1rem;
    background: var(--background);
    border-radius: var(--border-radius);
}

.device-status-section h4 {
    margin-bottom: 1rem;
    color: var(--text-primary);
    font-size: 1rem;
    font-weight: 600;
}

.verification-section {
    margin-bottom: 2rem;
}

.verification-section h4 {
    margin-bottom: 1rem;
    color: var(--text-primary);
    font-size: 1rem;
    font-weight: 600;
}

.initial-state {
    text-align: center;
    padding: 3rem 1rem;
}

.biometric-modal-footer {
    border-top: 1px solid var(--border);
    padding-top: 1.5rem;
    margin-top: 1.5rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.form-actions .btn-secondary,
.form-actions .btn-primary {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    font-size: 0.875rem;
}

.form-actions .btn-secondary {
    background: var(--background);
    color: var(--text-primary);
    border: 1px solid var(--border);
}

.form-actions .btn-secondary:hover {
    background: var(--border);
}

.form-actions .btn-primary {
    background: var(--primary);
    color: white;
}

.form-actions .btn-primary:hover {
    background: var(--primary-dark);
}

@media (max-width: 768px) {
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn-secondary,
    .form-actions .btn-primary {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
/**
 * Initialize biometric verification modal functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // Function to open biometric verification modal
    window.openBiometricVerification = function() {
        const modal = document.getElementById('biometricVerificationModal');
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
            
            // Initialize device detection
            if (window.biometricSystem) {
                window.biometricSystem.detectDevices();
            }
        }
    };

    // Function to proceed with traditional attendance (fallback)
    window.proceedWithTraditionalAttendance = function() {
        closeBiometricModal();
        
        // Trigger traditional attendance registration
        if (typeof openAttendanceRegisterModal === 'function') {
            openAttendanceRegisterModal();
        }
    };

    // Show fallback option if biometric methods are not available
    setTimeout(() => {
        const proceedButton = document.getElementById('proceedTraditional');
        if (proceedButton) {
            proceedButton.style.display = 'inline-flex';
        }
    }, 5000); // Show after 5 seconds to allow device detection
});
</script>