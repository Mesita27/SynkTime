// ===================================================================
// BIOMETRIC.JS - Biometric Enrollment Functionality
// ===================================================================

// Global variables for biometric enrollment
let facialStream = null;
let biometricEmployeeId = null;
let capturedFacialData = null;
let capturedFingerprintData = null;

// ===================================================================
// 1. INITIALIZATION AND EVENT LISTENERS
// ===================================================================

document.addEventListener('DOMContentLoaded', function() {
    setupBiometricEventListeners();
});

function setupBiometricEventListeners() {
    // Biometric enrollment button
    const biometricBtn = document.getElementById('biometricEnrollmentBtn');
    if (biometricBtn) {
        biometricBtn.addEventListener('click', function() {
            const employeeId = document.getElementById('id_empleado').value;
            if (employeeId) {
                openBiometricModal(employeeId);
            } else {
                showBiometricError('Debe guardar el empleado antes de configurar datos biométricos.');
            }
        });
    }

    // Modal close listeners
    const closeBiometricBtn = document.getElementById('closeBiometricModal');
    const cancelBiometricBtn = document.getElementById('cancelBiometricModal');
    const biometricModal = document.getElementById('biometricEnrollmentModal');
    
    if (closeBiometricBtn) closeBiometricBtn.addEventListener('click', closeBiometricModal);
    if (cancelBiometricBtn) cancelBiometricBtn.addEventListener('click', closeBiometricModal);
    if (biometricModal) {
        biometricModal.addEventListener('mousedown', function(e) {
            if (e.target === this) closeBiometricModal();
        });
    }

    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tab = this.getAttribute('data-tab');
            switchBiometricTab(tab);
        });
    });

    // Facial recognition buttons
    const startFacialCamera = document.getElementById('startFacialCamera');
    const captureFacialBtn = document.getElementById('captureFacialBtn');
    const saveFacialBtn = document.getElementById('saveFacialBtn');
    
    if (startFacialCamera) startFacialCamera.addEventListener('click', initializeFacialCamera);
    if (captureFacialBtn) captureFacialBtn.addEventListener('click', captureFacialImage);
    if (saveFacialBtn) saveFacialBtn.addEventListener('click', saveFacialData);

    // Fingerprint buttons
    const startFingerprintBtn = document.getElementById('startFingerprintBtn');
    const captureFingerprintBtn = document.getElementById('captureFingerprintBtn');
    const saveFingerprintBtn = document.getElementById('saveFingerprintBtn');
    
    if (startFingerprintBtn) startFingerprintBtn.addEventListener('click', initializeFingerprintReader);
    if (captureFingerprintBtn) captureFingerprintBtn.addEventListener('click', captureFingerprintData);
    if (saveFingerprintBtn) saveFingerprintBtn.addEventListener('click', saveFingerprintData);

    // Complete biometric registration
    const completeBiometricBtn = document.getElementById('completeBiometricBtn');
    if (completeBiometricBtn) completeBiometricBtn.addEventListener('click', completeBiometricRegistration);
}

// ===================================================================
// 2. MODAL MANAGEMENT
// ===================================================================

function openBiometricModal(employeeId) {
    biometricEmployeeId = employeeId;
    document.getElementById('biometricEmployeeId').value = employeeId;
    
    const modal = document.getElementById('biometricEnrollmentModal');
    if (modal) {
        modal.classList.add('show');
        
        // Check if employee should default to facial recognition (IDs 1, 2, 3)
        if (['1', '2', '3'].includes(employeeId)) {
            switchBiometricTab('facial');
            showBiometricInfo('Este empleado tiene habilitado el reconocimiento facial por defecto.', 'info');
        } else {
            switchBiometricTab('facial'); // Default to facial for all
        }
        
        loadExistingBiometricData(employeeId);
    }
}

function closeBiometricModal() {
    const modal = document.getElementById('biometricEnrollmentModal');
    if (modal) {
        modal.classList.remove('show');
    }
    
    // Clean up camera stream
    if (facialStream) {
        facialStream.getTracks().forEach(track => track.stop());
        facialStream = null;
    }
    
    // Reset state
    resetBiometricForm();
}

function switchBiometricTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tab + 'Tab').classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(tab + 'Content').classList.add('active');
}

// ===================================================================
// 3. FACIAL RECOGNITION FUNCTIONALITY
// ===================================================================

async function initializeFacialCamera() {
    try {
        const video = document.getElementById('facialVideo');
        
        // Request camera access
        facialStream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: 'user'
            }
        });
        
        video.srcObject = facialStream;
        
        // Enable capture button when video is ready
        video.addEventListener('loadedmetadata', function() {
            document.getElementById('captureFacialBtn').disabled = false;
            document.getElementById('startFacialCamera').disabled = true;
            showBiometricStatus('facial', 'Cámara iniciada. Posicione su rostro y haga clic en "Capturar Rostro".', 'info');
        });
        
    } catch (error) {
        console.error('Error accessing camera:', error);
        showBiometricStatus('facial', 'Error al acceder a la cámara: ' + error.message, 'error');
    }
}

function captureFacialImage() {
    const video = document.getElementById('facialVideo');
    const canvas = document.getElementById('facialCanvas');
    const context = canvas.getContext('2d');
    
    // Draw video frame to canvas
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    // Convert to base64
    capturedFacialData = canvas.toDataURL('image/jpeg', 0.8);
    
    // Show preview
    const preview = document.getElementById('facialPreview');
    preview.innerHTML = `<img src="${capturedFacialData}" alt="Imagen capturada" style="max-width: 200px; border-radius: 8px;">`;
    
    // Enable save button
    document.getElementById('saveFacialBtn').disabled = false;
    
    showBiometricStatus('facial', 'Imagen facial capturada exitosamente. Haga clic en "Guardar" para almacenar.', 'success');
}

async function saveFacialData() {
    if (!capturedFacialData || !biometricEmployeeId) {
        showBiometricStatus('facial', 'No hay datos faciales para guardar.', 'error');
        return;
    }
    
    try {
        showBiometricStatus('facial', 'Guardando datos faciales...', 'info');
        
        const response = await fetch('api/employee/save-biometric.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                employee_id: biometricEmployeeId,
                type: 'facial',
                data: capturedFacialData
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showBiometricStatus('facial', 'Datos faciales guardados exitosamente.', 'success');
            updateBiometricSummary('facial', true);
            
            // Stop camera stream
            if (facialStream) {
                facialStream.getTracks().forEach(track => track.stop());
                facialStream = null;
            }
            
            // Reset camera UI
            document.getElementById('startFacialCamera').disabled = false;
            document.getElementById('captureFacialBtn').disabled = true;
            document.getElementById('saveFacialBtn').disabled = true;
            
        } else {
            showBiometricStatus('facial', result.message || 'Error al guardar datos faciales.', 'error');
        }
        
    } catch (error) {
        console.error('Error saving facial data:', error);
        showBiometricStatus('facial', 'Error de conexión al guardar datos faciales.', 'error');
    }
}

// ===================================================================
// 4. FINGERPRINT FUNCTIONALITY
// ===================================================================

function initializeFingerprintReader() {
    const simulator = document.querySelector('.fingerprint-simulator');
    const progressBar = document.getElementById('fingerprintProgress');
    
    simulator.classList.add('active');
    document.getElementById('startFingerprintBtn').disabled = true;
    
    showBiometricStatus('fingerprint', 'Simulando lectura de huella dactilar...', 'info');
    
    // Simulate fingerprint reading progress
    let progress = 0;
    const interval = setInterval(() => {
        progress += 10;
        progressBar.style.width = progress + '%';
        
        if (progress >= 100) {
            clearInterval(interval);
            document.getElementById('captureFingerprintBtn').disabled = false;
            showBiometricStatus('fingerprint', 'Lector listo. Haga clic en "Capturar Huella".', 'success');
        }
    }, 200);
}

function captureFingerprintData() {
    // Simulate fingerprint capture
    showBiometricStatus('fingerprint', 'Capturando datos de huella dactilar...', 'info');
    
    setTimeout(() => {
        // Generate simulated fingerprint data
        capturedFingerprintData = generateSimulatedFingerprintData();
        
        // Show preview
        const preview = document.getElementById('fingerprintPreview');
        preview.innerHTML = `
            <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <i class="fas fa-fingerprint" style="font-size: 48px; color: #28a745;"></i>
                <p>Huella dactilar capturada exitosamente</p>
                <small>Datos encriptados: ${capturedFingerprintData.substring(0, 20)}...</small>
            </div>
        `;
        
        document.getElementById('saveFingerprintBtn').disabled = false;
        showBiometricStatus('fingerprint', 'Huella dactilar capturada exitosamente.', 'success');
        
    }, 1500);
}

async function saveFingerprintData() {
    if (!capturedFingerprintData || !biometricEmployeeId) {
        showBiometricStatus('fingerprint', 'No hay datos de huella para guardar.', 'error');
        return;
    }
    
    try {
        showBiometricStatus('fingerprint', 'Guardando datos de huella dactilar...', 'info');
        
        const response = await fetch('api/employee/save-biometric.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                employee_id: biometricEmployeeId,
                type: 'fingerprint',
                data: capturedFingerprintData
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showBiometricStatus('fingerprint', 'Datos de huella guardados exitosamente.', 'success');
            updateBiometricSummary('fingerprint', true);
            
            // Reset fingerprint UI
            const simulator = document.querySelector('.fingerprint-simulator');
            simulator.classList.remove('active');
            document.getElementById('fingerprintProgress').style.width = '0%';
            document.getElementById('startFingerprintBtn').disabled = false;
            document.getElementById('captureFingerprintBtn').disabled = true;
            document.getElementById('saveFingerprintBtn').disabled = true;
            
        } else {
            showBiometricStatus('fingerprint', result.message || 'Error al guardar datos de huella.', 'error');
        }
        
    } catch (error) {
        console.error('Error saving fingerprint data:', error);
        showBiometricStatus('fingerprint', 'Error de conexión al guardar datos de huella.', 'error');
    }
}

// ===================================================================
// 5. UTILITY FUNCTIONS
// ===================================================================

function generateSimulatedFingerprintData() {
    // Generate a simulated fingerprint hash
    const timestamp = Date.now();
    const random = Math.random().toString(36).substring(7);
    const employeeId = biometricEmployeeId;
    
    // Create a hash-like string for simulation
    return btoa(`fingerprint_${employeeId}_${timestamp}_${random}`);
}

function showBiometricStatus(type, message, statusType) {
    const statusElement = document.getElementById(type + 'Status');
    if (statusElement) {
        statusElement.className = `biometric-status ${statusType}`;
        statusElement.textContent = message;
        statusElement.style.display = 'block';
        
        // Auto-hide info messages after 5 seconds
        if (statusType === 'info') {
            setTimeout(() => {
                statusElement.style.display = 'none';
            }, 5000);
        }
    }
}

function showBiometricError(message) {
    const errorDiv = document.getElementById('biometricFormError');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        
        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 5000);
    }
}

function updateBiometricSummary(type, isRegistered) {
    const statusElement = document.getElementById(type + 'StatusSummary') || 
                         document.querySelector(`#biometricSummary .status-item:has(i.fa-${type === 'facial' ? 'face-smile' : 'fingerprint'})`);
    
    if (statusElement) {
        const statusText = statusElement.querySelector('.status-text');
        if (statusText) {
            statusText.textContent = isRegistered ? 'Registrado' : 'No registrado';
        }
        
        if (isRegistered) {
            statusElement.classList.add('success');
        } else {
            statusElement.classList.remove('success');
        }
    }
}

async function loadExistingBiometricData(employeeId) {
    try {
        const response = await fetch(`api/employee/get-biometric.php?id=${employeeId}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            // Update summary based on existing data
            updateBiometricSummary('facial', data.facial_recognition_enabled === 'Y');
            updateBiometricSummary('fingerprint', data.fingerprint_enabled === 'Y');
            
            if (data.facial_recognition_enabled === 'Y') {
                showBiometricStatus('facial', 'Reconocimiento facial ya configurado para este empleado.', 'success');
            }
            
            if (data.fingerprint_enabled === 'Y') {
                showBiometricStatus('fingerprint', 'Huella dactilar ya configurada para este empleado.', 'success');
            }
        }
        
    } catch (error) {
        console.error('Error loading biometric data:', error);
    }
}

function resetBiometricForm() {
    // Reset all form elements
    capturedFacialData = null;
    capturedFingerprintData = null;
    biometricEmployeeId = null;
    
    // Reset UI elements
    document.getElementById('facialPreview').innerHTML = '';
    document.getElementById('fingerprintPreview').innerHTML = '';
    
    // Reset button states
    document.getElementById('startFacialCamera').disabled = false;
    document.getElementById('captureFacialBtn').disabled = true;
    document.getElementById('saveFacialBtn').disabled = true;
    document.getElementById('startFingerprintBtn').disabled = false;
    document.getElementById('captureFingerprintBtn').disabled = true;
    document.getElementById('saveFingerprintBtn').disabled = true;
    
    // Hide status messages
    document.querySelectorAll('.biometric-status').forEach(status => {
        status.style.display = 'none';
    });
    
    // Reset summary
    updateBiometricSummary('facial', false);
    updateBiometricSummary('fingerprint', false);
}

async function completeBiometricRegistration() {
    // Close the modal and optionally refresh employee data
    closeBiometricModal();
    
    // Reload employee table to reflect biometric status changes
    if (typeof loadEmployees === 'function') {
        loadEmployees();
    }
    
    alert('Registro biométrico completado exitosamente.');
}

// ===================================================================
// 6. INTEGRATION WITH EMPLOYEE MODULE
// ===================================================================

// Function to show/hide biometric button based on employee mode
function toggleBiometricButton(mode, employeeData = null) {
    const biometricBtn = document.getElementById('biometricEnrollmentBtn');
    if (biometricBtn) {
        if (mode === 'editar' && employeeData && employeeData.id) {
            biometricBtn.style.display = 'inline-block';
        } else if (mode === 'crear') {
            biometricBtn.style.display = 'none';
        } else {
            biometricBtn.style.display = 'none';
        }
    }
}

// Export functions for use in employee.js
window.toggleBiometricButton = toggleBiometricButton;
window.openBiometricModal = openBiometricModal;