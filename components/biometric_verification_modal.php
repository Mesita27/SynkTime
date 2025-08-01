<!-- Biometric Verification Modal for Attendance -->
<div class="biometric-modal" id="biometricVerificationModal">
    <div class="biometric-modal-content">
        <button type="button" class="biometric-modal-close" onclick="closeBiometricVerificationModal()">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="biometric-modal-header">
            <h3><i class="fas fa-shield-alt"></i> Verificación Biométrica</h3>
        </div>
        
        <div class="biometric-modal-body">
            <div id="biometricVerificationContent">
                <!-- Employee Info -->
                <div id="verificationEmployeeInfo" class="verification-employee-info">
                    <h4 id="verificationEmployeeName"></h4>
                    <p><strong>Código:</strong> <span id="verificationEmployeeCode"></span></p>
                </div>
                
                <!-- Verification Method Selection -->
                <div id="verificationMethodSelection" class="verification-step">
                    <h4>Seleccionar Método de Verificación</h4>
                    <div class="verification-methods">
                        <div class="method-card" id="fingerprintVerificationCard" style="display: none;">
                            <div class="method-info">
                                <i class="fas fa-fingerprint"></i>
                                <div>
                                    <h5>Huella Digital</h5>
                                    <p>Verificación rápida y segura</p>
                                </div>
                            </div>
                            <button type="button" class="btn-primary" onclick="startFingerprintVerification()">
                                <i class="fas fa-fingerprint"></i> Verificar
                            </button>
                        </div>
                        
                        <div class="method-card" id="facialVerificationCard" style="display: none;">
                            <div class="method-info">
                                <i class="fas fa-face-smile"></i>
                                <div>
                                    <h5>Reconocimiento Facial</h5>
                                    <p>Verificación por cámara</p>
                                </div>
                            </div>
                            <button type="button" class="btn-primary" onclick="startFacialVerification()">
                                <i class="fas fa-camera"></i> Verificar
                            </button>
                        </div>
                        
                        <div class="method-card">
                            <div class="method-info">
                                <i class="fas fa-camera"></i>
                                <div>
                                    <h5>Foto Tradicional</h5>
                                    <p>Captura de foto manual</p>
                                </div>
                            </div>
                            <button type="button" class="btn-secondary" onclick="useTraditionalPhoto()">
                                <i class="fas fa-camera"></i> Usar Foto
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Fingerprint Verification -->
                <div id="fingerprintVerificationStep" class="verification-step" style="display: none;">
                    <h4>Verificación de Huella Digital</h4>
                    <div class="verification-content">
                        <div class="fingerprint-verification-visual">
                            <i class="fas fa-fingerprint" style="font-size: 4rem; color: #667eea;"></i>
                        </div>
                        <div id="fingerprintVerificationStatus" class="verification-status">
                            Coloque el dedo sobre el sensor
                        </div>
                        <div class="verification-progress" id="fingerprintVerificationProgress" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Verificando...
                        </div>
                    </div>
                    <div class="verification-actions">
                        <button type="button" id="btnStartFingerprintVerification" class="btn-primary">
                            <i class="fas fa-scan"></i> Escanear
                        </button>
                        <button type="button" class="btn-secondary" onclick="backToMethodSelection()">
                            <i class="fas fa-arrow-left"></i> Volver
                        </button>
                    </div>
                </div>
                
                <!-- Facial Verification -->
                <div id="facialVerificationStep" class="verification-step" style="display: none;">
                    <h4>Verificación Facial</h4>
                    <div class="verification-content">
                        <div class="camera-preview">
                            <video id="verificationVideo" class="camera-video" autoplay playsinline></video>
                            <canvas id="verificationCanvas" style="display: none;"></canvas>
                            <div class="camera-overlay">
                                <div id="verificationFaceBox" class="face-detection-box" style="display: none;"></div>
                            </div>
                        </div>
                        <div id="facialVerificationStatus" class="verification-status">
                            Posicione su rostro en el centro del marco
                        </div>
                        <div class="verification-progress" id="facialVerificationProgress" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Analizando rostro...
                        </div>
                    </div>
                    <div class="verification-actions">
                        <button type="button" id="btnStartFacialVerification" class="btn-primary" disabled>
                            <i class="fas fa-camera"></i> Verificar
                        </button>
                        <button type="button" class="btn-secondary" onclick="backToMethodSelection()">
                            <i class="fas fa-arrow-left"></i> Volver
                        </button>
                    </div>
                </div>
                
                <!-- Verification Success -->
                <div id="verificationSuccess" class="verification-step" style="display: none;">
                    <h4>Verificación Exitosa</h4>
                    <div class="verification-result">
                        <i class="fas fa-check-circle" style="font-size: 3rem; color: #10b981; margin-bottom: 1rem;"></i>
                        <p>Identidad verificada correctamente</p>
                        <div class="verification-score">
                            <strong>Confianza:</strong> <span id="verificationConfidence">--</span>%
                        </div>
                        <div class="verification-method-used">
                            <strong>Método:</strong> <span id="verificationMethodUsed">--</span>
                        </div>
                    </div>
                    <div class="verification-actions">
                        <button type="button" id="btnProceedWithVerification" class="btn-primary">
                            <i class="fas fa-check"></i> Continuar con Registro
                        </button>
                    </div>
                </div>
                
                <!-- Verification Failed -->
                <div id="verificationFailed" class="verification-step" style="display: none;">
                    <h4>Verificación Fallida</h4>
                    <div class="verification-result">
                        <i class="fas fa-times-circle" style="font-size: 3rem; color: #dc2626; margin-bottom: 1rem;"></i>
                        <p id="verificationErrorMessage">No se pudo verificar la identidad</p>
                        <div class="verification-attempts">
                            <strong>Intentos restantes:</strong> <span id="verificationAttemptsLeft">2</span>
                        </div>
                    </div>
                    <div class="verification-actions">
                        <button type="button" id="btnRetryVerification" class="btn-secondary">
                            <i class="fas fa-redo"></i> Reintentar
                        </button>
                        <button type="button" class="btn-primary" onclick="useTraditionalPhoto()">
                            <i class="fas fa-camera"></i> Usar Foto Manual
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>