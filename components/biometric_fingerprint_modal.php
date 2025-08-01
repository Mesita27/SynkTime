<!-- Fingerprint Enrollment Modal -->
<div class="biometric-modal" id="fingerprintEnrollmentModal">
    <div class="biometric-modal-content">
        <button type="button" class="biometric-modal-close" onclick="closeFingerprintModal()">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="biometric-modal-header">
            <h3><i class="fas fa-fingerprint"></i> Inscripción de Huella Digital</h3>
        </div>
        
        <div class="biometric-modal-body">
            <div id="fingerprintEnrollmentContent">
                <!-- Device Selection -->
                <div id="fingerprintDeviceSelection" class="enrollment-step">
                    <h4>Seleccionar Dispositivo</h4>
                    <div id="fingerprintDeviceList" class="device-list">
                        <!-- Devices will be populated here -->
                    </div>
                </div>
                
                <!-- Enrollment Instructions -->
                <div id="fingerprintInstructions" class="enrollment-step" style="display: none;">
                    <h4>Instrucciones</h4>
                    <div class="instructions-content">
                        <ul>
                            <li>Mantenga el dedo limpio y seco</li>
                            <li>Coloque el dedo firmemente sobre el sensor</li>
                            <li>No mueva el dedo durante la captura</li>
                            <li>Repita el proceso 3 veces para mayor precisión</li>
                        </ul>
                    </div>
                    <button type="button" id="btnStartFingerprintCapture" class="btn-primary">
                        <i class="fas fa-play"></i> Comenzar Captura
                    </button>
                </div>
                
                <!-- Sample Collection -->
                <div id="fingerprintCapture" class="enrollment-step" style="display: none;">
                    <h4>Captura de Muestras</h4>
                    
                    <div class="enrollment-progress">
                        <div class="progress-bar">
                            <div id="fingerprintProgressFill" class="progress-fill" style="width: 0%;"></div>
                        </div>
                        <div class="progress-text">
                            Muestra <span id="fingerprintCurrentSample">1</span> de <span id="fingerprintTotalSamples">3</span>
                        </div>
                    </div>
                    
                    <div class="sample-collection">
                        <div class="fingerprint-sensor-visual">
                            <i class="fas fa-fingerprint" style="font-size: 4rem; color: #667eea;"></i>
                        </div>
                        
                        <div id="fingerprintStatus" class="sample-status-text">
                            Coloque el dedo sobre el sensor
                        </div>
                        
                        <div class="sample-status">
                            <div class="sample-dot" id="sample1"></div>
                            <div class="sample-dot" id="sample2"></div>
                            <div class="sample-dot" id="sample3"></div>
                        </div>
                        
                        <button type="button" id="btnCaptureFingerprintSample" class="btn-primary">
                            <i class="fas fa-scan"></i> Capturar Muestra
                        </button>
                    </div>
                </div>
                
                <!-- Enrollment Complete -->
                <div id="fingerprintComplete" class="enrollment-step" style="display: none;">
                    <h4>Inscripción Completada</h4>
                    <div class="completion-message">
                        <i class="fas fa-check-circle" style="font-size: 3rem; color: #10b981; margin-bottom: 1rem;"></i>
                        <p>La huella digital se ha registrado exitosamente.</p>
                        <div class="quality-score">
                            <strong>Calidad de la muestra:</strong> 
                            <span id="fingerprintQualityScore">--</span>%
                        </div>
                    </div>
                    <div class="enrollment-actions">
                        <button type="button" id="btnTestFingerprint" class="btn-secondary">
                            <i class="fas fa-vial"></i> Probar Reconocimiento
                        </button>
                        <button type="button" id="btnFinishFingerprint" class="btn-primary">
                            <i class="fas fa-check"></i> Finalizar
                        </button>
                    </div>
                </div>
                
                <!-- Error State -->
                <div id="fingerprintError" class="enrollment-step" style="display: none;">
                    <h4>Error en la Inscripción</h4>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #dc2626; margin-bottom: 1rem;"></i>
                        <p id="fingerprintErrorText">Ha ocurrido un error durante la inscripción.</p>
                    </div>
                    <div class="error-actions">
                        <button type="button" id="btnRetryFingerprint" class="btn-secondary">
                            <i class="fas fa-redo"></i> Reintentar
                        </button>
                        <button type="button" id="btnCancelFingerprint" class="btn-primary">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>