<!-- Facial Recognition Enrollment Modal -->
<div class="biometric-modal" id="facialEnrollmentModal">
    <div class="biometric-modal-content">
        <button type="button" class="biometric-modal-close" onclick="closeFacialModal()">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="biometric-modal-header">
            <h3><i class="fas fa-face-smile"></i> Inscripción de Reconocimiento Facial</h3>
        </div>
        
        <div class="biometric-modal-body">
            <div id="facialEnrollmentContent">
                <!-- Camera Setup -->
                <div id="facialCameraSetup" class="enrollment-step">
                    <h4>Configuración de Cámara</h4>
                    <div class="camera-selection">
                        <label for="cameraSelect">Seleccionar Cámara:</label>
                        <select id="cameraSelect" class="form-control">
                            <option value="">Detectando cámaras...</option>
                        </select>
                    </div>
                    <div class="camera-test">
                        <button type="button" id="btnTestCamera" class="btn-secondary" disabled>
                            <i class="fas fa-video"></i> Probar Cámara
                        </button>
                        <button type="button" id="btnStartFacialCapture" class="btn-primary" disabled>
                            <i class="fas fa-play"></i> Iniciar Inscripción
                        </button>
                    </div>
                </div>
                
                <!-- Enrollment Instructions -->
                <div id="facialInstructions" class="enrollment-step" style="display: none;">
                    <h4>Instrucciones para la Inscripción</h4>
                    <div class="instructions-content">
                        <ul>
                            <li>Mantenga el rostro centrado en el marco</li>
                            <li>Asegúrese de tener buena iluminación</li>
                            <li>Mire directamente a la cámara</li>
                            <li>No use gafas de sol o sombreros</li>
                            <li>Capture diferentes ángulos del rostro</li>
                        </ul>
                    </div>
                    <button type="button" id="btnProceedToCapture" class="btn-primary">
                        <i class="fas fa-camera"></i> Continuar
                    </button>
                </div>
                
                <!-- Face Capture -->
                <div id="facialCapture" class="enrollment-step" style="display: none;">
                    <h4>Captura de Rostro</h4>
                    
                    <div class="enrollment-progress">
                        <div class="progress-bar">
                            <div id="facialProgressFill" class="progress-fill" style="width: 0%;"></div>
                        </div>
                        <div class="progress-text">
                            Captura <span id="facialCurrentSample">1</span> de <span id="facialTotalSamples">5</span>
                        </div>
                    </div>
                    
                    <div class="camera-preview">
                        <video id="facialVideo" class="camera-video" autoplay playsinline></video>
                        <canvas id="facialCanvas" style="display: none;"></canvas>
                        <div class="camera-overlay">
                            <div id="faceDetectionBox" class="face-detection-box" style="display: none;"></div>
                        </div>
                    </div>
                    
                    <div id="facialStatus" class="sample-status-text">
                        Posicione su rostro en el centro del marco
                    </div>
                    
                    <div class="sample-status">
                        <div class="sample-dot" id="faceSample1"></div>
                        <div class="sample-dot" id="faceSample2"></div>
                        <div class="sample-dot" id="faceSample3"></div>
                        <div class="sample-dot" id="faceSample4"></div>
                        <div class="sample-dot" id="faceSample5"></div>
                    </div>
                    
                    <div class="capture-instructions">
                        <div id="currentInstruction" class="instruction-text">
                            Mire directamente a la cámara
                        </div>
                    </div>
                    
                    <div class="capture-actions">
                        <button type="button" id="btnCaptureFaceSample" class="btn-primary" disabled>
                            <i class="fas fa-camera"></i> Capturar
                        </button>
                        <button type="button" id="btnSkipSample" class="btn-secondary" style="display: none;">
                            <i class="fas fa-forward"></i> Omitir
                        </button>
                    </div>
                </div>
                
                <!-- Processing -->
                <div id="facialProcessing" class="enrollment-step" style="display: none;">
                    <h4>Procesando Datos Faciales</h4>
                    <div class="processing-content">
                        <i class="fas fa-spinner fa-spin" style="font-size: 3rem; color: #667eea; margin-bottom: 1rem;"></i>
                        <p>Generando modelo de reconocimiento facial...</p>
                        <div class="processing-steps">
                            <div class="processing-step">
                                <i class="fas fa-check" style="color: #10b981;"></i>
                                <span>Detectando características faciales</span>
                            </div>
                            <div class="processing-step">
                                <i class="fas fa-spinner fa-spin" style="color: #f59e0b;"></i>
                                <span>Creando modelo biométrico</span>
                            </div>
                            <div class="processing-step">
                                <i class="fas fa-clock" style="color: #94a3b8;"></i>
                                <span>Validando calidad</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Enrollment Complete -->
                <div id="facialComplete" class="enrollment-step" style="display: none;">
                    <h4>Inscripción Completada</h4>
                    <div class="completion-message">
                        <i class="fas fa-check-circle" style="font-size: 3rem; color: #10b981; margin-bottom: 1rem;"></i>
                        <p>El reconocimiento facial se ha registrado exitosamente.</p>
                        <div class="quality-metrics">
                            <div class="metric">
                                <strong>Calidad del modelo:</strong> 
                                <span id="facialQualityScore">--</span>%
                            </div>
                            <div class="metric">
                                <strong>Muestras procesadas:</strong> 
                                <span id="facialSamplesProcessed">--</span>
                            </div>
                        </div>
                    </div>
                    <div class="enrollment-actions">
                        <button type="button" id="btnTestFacial" class="btn-secondary">
                            <i class="fas fa-vial"></i> Probar Reconocimiento
                        </button>
                        <button type="button" id="btnFinishFacial" class="btn-primary">
                            <i class="fas fa-check"></i> Finalizar
                        </button>
                    </div>
                </div>
                
                <!-- Error State -->
                <div id="facialError" class="enrollment-step" style="display: none;">
                    <h4>Error en la Inscripción</h4>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #dc2626; margin-bottom: 1rem;"></i>
                        <p id="facialErrorText">Ha ocurrido un error durante la inscripción.</p>
                        <div class="error-details">
                            <details>
                                <summary>Detalles técnicos</summary>
                                <pre id="facialErrorDetails"></pre>
                            </details>
                        </div>
                    </div>
                    <div class="error-actions">
                        <button type="button" id="btnRetryFacial" class="btn-secondary">
                            <i class="fas fa-redo"></i> Reintentar
                        </button>
                        <button type="button" id="btnCancelFacial" class="btn-primary">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>