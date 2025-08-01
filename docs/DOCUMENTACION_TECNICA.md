# Documentación Técnica - Sistema Biométrico SynkTime

## Arquitectura del Sistema

### Componentes Principales

#### Frontend
- **biometric-enrollment.php**: Página principal de inscripción biométrica
- **components/biometric_fingerprint_modal.php**: Modal para inscripción de huellas
- **components/biometric_facial_modal.php**: Modal para inscripción facial
- **components/biometric_verification_modal.php**: Modal para verificación en asistencias
- **assets/css/biometric.css**: Estilos específicos del módulo biométrico
- **assets/js/biometric-enrollment.js**: Lógica de inscripción biométrica
- **assets/js/biometric-verification.js**: Lógica de verificación biométrica

#### Backend
- **api/biometric/status.php**: Consulta estado de inscripción de empleados
- **api/biometric/start-session.php**: Inicia sesión de inscripción
- **api/biometric/capture-sample.php**: Procesa muestras biométricas
- **api/biometric/complete-enrollment.php**: Completa proceso de inscripción
- **api/biometric/verify.php**: Verifica identidad biométrica
- **api/employee/search.php**: Búsqueda de empleados para inscripción

#### Base de Datos
- **database/biometric_schema.sql**: Esquema completo de tablas biométricas

## Esquema de Base de Datos

### Tabla: employee_biometrics
Almacena los datos biométricos encriptados de los empleados.

```sql
CREATE TABLE employee_biometrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    biometric_type ENUM('fingerprint', 'facial') NOT NULL,
    biometric_data TEXT NOT NULL, -- Datos encriptados AES-256
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    device_info JSON, -- Información del dispositivo usado
    quality_score DECIMAL(3,2), -- Puntuación de calidad (0-100)
    created_by INT, -- Usuario que creó la inscripción
    
    INDEX idx_employee_biometric (employee_id, biometric_type),
    INDEX idx_enrollment_date (enrollment_date),
    UNIQUE KEY unique_employee_biometric (employee_id, biometric_type)
);
```

### Tabla: biometric_verification_logs
Registra todos los intentos de verificación para auditoría.

```sql
CREATE TABLE biometric_verification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    biometric_type ENUM('fingerprint', 'facial') NOT NULL,
    verification_result ENUM('success', 'failed', 'retry') NOT NULL,
    confidence_score DECIMAL(3,2), -- Nivel de confianza (0-100)
    verification_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    attendance_id INT NULL, -- Link a registro de asistencia
    
    INDEX idx_employee_verification (employee_id, verification_date),
    INDEX idx_verification_result (verification_result),
    INDEX idx_attendance_link (attendance_id)
);
```

### Tabla: biometric_devices
Catalogo de dispositivos biométricos disponibles.

```sql
CREATE TABLE biometric_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_name VARCHAR(255) NOT NULL,
    device_type ENUM('fingerprint_scanner', 'camera', 'integrated') NOT NULL,
    device_model VARCHAR(255),
    supported_types SET('fingerprint', 'facial') NOT NULL,
    connection_info JSON, -- Configuración de conexión
    is_active BOOLEAN DEFAULT TRUE,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_device_type (device_type),
    INDEX idx_device_active (is_active)
);
```

### Tabla: biometric_enrollment_sessions
Maneja sesiones temporales de inscripción.

```sql
CREATE TABLE biometric_enrollment_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    biometric_type ENUM('fingerprint', 'facial') NOT NULL,
    session_status ENUM('started', 'in_progress', 'completed', 'failed') DEFAULT 'started',
    samples_collected INT DEFAULT 0,
    required_samples INT DEFAULT 3,
    session_data JSON, -- Datos temporales de la sesión
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    created_by INT,
    
    INDEX idx_session_status (session_status),
    INDEX idx_employee_session (employee_id, biometric_type)
);
```

## APIs Endpoint

### GET /api/biometric/status.php
Obtiene el estado de inscripción biométrica de un empleado.

**Parámetros:**
- `employee_id` (required): ID del empleado

**Respuesta exitosa:**
```json
{
    "success": true,
    "employee_id": 123,
    "fingerprint_enrolled": true,
    "facial_enrolled": false,
    "status": {
        "fingerprint_enrolled": true,
        "facial_enrolled": false,
        "fingerprint_quality": 85.5,
        "facial_quality": null,
        "fingerprint_date": "2024-08-01 10:30:00",
        "facial_date": null
    }
}
```

### POST /api/biometric/start-session.php
Inicia una nueva sesión de inscripción biométrica.

**Payload:**
```json
{
    "employee_id": 123,
    "biometric_type": "fingerprint|facial",
    "device_id": "webauthn",
    "required_samples": 3
}
```

**Respuesta exitosa:**
```json
{
    "success": true,
    "message": "Enrollment session started",
    "session": {
        "id": 456,
        "employee_id": 123,
        "biometric_type": "fingerprint",
        "session_status": "started",
        "samples_collected": 0,
        "required_samples": 3,
        "started_at": "2024-08-01 10:30:00"
    }
}
```

### POST /api/biometric/capture-sample.php
Procesa y almacena una muestra biométrica.

**Payload:**
```json
{
    "session_id": 456,
    "sample_data": {
        "template": "encrypted_fingerprint_template",
        "quality": 87.5
    },
    "sample_number": 1
}
```

**Respuesta exitosa:**
```json
{
    "success": true,
    "message": "Sample captured successfully",
    "sample_quality": 87.5,
    "session": {
        "id": 456,
        "samples_collected": 1,
        "session_status": "in_progress"
    }
}
```

### POST /api/biometric/complete-enrollment.php
Completa el proceso de inscripción y genera el template final.

**Payload:**
```json
{
    "session_id": 456
}
```

**Respuesta exitosa:**
```json
{
    "success": true,
    "message": "Biometric enrollment completed successfully",
    "quality_score": 89.2,
    "biometric_id": 789
}
```

### POST /api/biometric/verify.php
Verifica la identidad biométrica de un empleado.

**Payload:**
```json
{
    "employee_id": 123,
    "biometric_type": "fingerprint|facial",
    "verification_data": {
        "template": "verification_template_data",
        "timestamp": 1627834200000
    }
}
```

**Respuesta exitosa:**
```json
{
    "success": true,
    "verified": true,
    "confidence": 92.5,
    "message": "Fingerprint verified successfully"
}
```

## Algoritmos de Procesamiento

### Procesamiento de Huellas Digitales

#### Encriptación de Templates
```php
function encryptFingerprintTemplate($template) {
    $key = 'biometric_key_' . date('Y');
    $iv = substr(md5('synktime_biometric'), 0, 16);
    
    return base64_encode(openssl_encrypt(
        $template, 
        'AES-256-CBC', 
        $key, 
        0, 
        $iv
    ));
}
```

#### Verificación de Huellas
```php
function verifyFingerprint($enrolledTemplate, $verificationData) {
    // Desencriptar template inscrito
    $decrypted = decryptBiometricData($enrolledTemplate);
    
    // Simular algoritmo de matching (en producción usar SDK específico)
    $matchProbability = calculateFingerprintMatch($decrypted, $verificationData);
    
    return [
        'verified' => $matchProbability > 0.8,
        'confidence' => $matchProbability * 100,
        'message' => $matchProbability > 0.8 ? 'Match found' : 'No match'
    ];
}
```

### Procesamiento de Reconocimiento Facial

#### Extracción de Descriptores
```javascript
async function extractFaceDescriptor(video) {
    const detection = await faceapi
        .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
        .withFaceLandmarks()
        .withFaceDescriptor();
    
    return detection ? detection.descriptor : null;
}
```

#### Verificación Facial
```php
function verifyFacial($enrolledDescriptor, $verificationData) {
    $enrolledVector = decryptFacialDescriptor($enrolledDescriptor);
    $verificationVector = $verificationData['descriptor'];
    
    // Calcular distancia euclidiana
    $distance = calculateEuclideanDistance($enrolledVector, $verificationVector);
    
    // Threshold típico para face-api.js
    $threshold = 0.6;
    $similarity = max(0, (1 - ($distance / $threshold)) * 100);
    
    return [
        'verified' => $distance < $threshold,
        'confidence' => min(100, max(0, $similarity)),
        'message' => $distance < $threshold ? 'Face match' : 'No face match'
    ];
}
```

## Seguridad Implementada

### Encriptación de Datos
- **Algoritmo**: AES-256-CBC
- **Rotación de Claves**: Anual automática
- **IV**: Derivado de hash MD5 de clave secreta
- **Almacenamiento**: Solo datos encriptados en base de datos

### Validación de Datos
```php
function validateBiometricSample($sampleData, $biometricType) {
    if ($biometricType === 'fingerprint') {
        // Validar formato de template
        if (empty($sampleData['template']) || strlen($sampleData['template']) < 10) {
            return ['valid' => false, 'error' => 'Invalid template format'];
        }
        
        // Validar calidad mínima
        if ($sampleData['quality'] < 70) {
            return ['valid' => false, 'error' => 'Quality too low'];
        }
    }
    
    if ($biometricType === 'facial') {
        // Validar descriptor facial
        if (!is_array($sampleData['descriptor']) || count($sampleData['descriptor']) !== 128) {
            return ['valid' => false, 'error' => 'Invalid descriptor format'];
        }
    }
    
    return ['valid' => true];
}
```

### Auditoría y Logs
```php
function logVerificationAttempt($employeeId, $biometricType, $result, $confidence) {
    $stmt = $conn->prepare("
        INSERT INTO biometric_verification_logs 
        (employee_id, biometric_type, verification_result, confidence_score, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $employeeId,
        $biometricType,
        $result,
        $confidence,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
}
```

## Integración con Dispositivos

### WebAuthn (Dispositivos Integrados)
```javascript
async function checkWebAuthnSupport() {
    if (window.PublicKeyCredential) {
        const available = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
        return available;
    }
    return false;
}
```

### Sensores de Huella Externos
```javascript
// Ejemplo de integración con SDK de fabricante
class FingerprintDevice {
    constructor(deviceType) {
        this.deviceType = deviceType;
        this.initialized = false;
    }
    
    async initialize() {
        switch(this.deviceType) {
            case 'futronic':
                return await this.initializeFutronic();
            case 'digital_persona':
                return await this.initializeDigitalPersona();
            default:
                return await this.initializeWebAuthn();
        }
    }
    
    async capture() {
        // Implementación específica del dispositivo
        return await this.deviceCapture();
    }
}
```

### Cámaras Web
```javascript
async function initializeCamera(deviceId) {
    const constraints = {
        video: {
            deviceId: deviceId,
            width: { ideal: 640 },
            height: { ideal: 480 },
            frameRate: { ideal: 30 }
        }
    };
    
    return await navigator.mediaDevices.getUserMedia(constraints);
}
```

## Métricas de Rendimiento

### Tiempos de Respuesta Típicos
- **Inscripción de Huella**: 2-5 segundos por muestra
- **Inscripción Facial**: 3-7 segundos por muestra
- **Verificación de Huella**: 1-3 segundos
- **Verificación Facial**: 2-5 segundos
- **Carga de Modelos Face-API**: 5-10 segundos (primera vez)

### Precisión Esperada
- **Huellas Digitales**: 
  - FAR (False Accept Rate): < 0.001%
  - FRR (False Reject Rate): < 1%
  - Calidad mínima requerida: 70%

- **Reconocimiento Facial**:
  - FAR: < 0.1%
  - FRR: < 5%
  - Threshold recomendado: 0.6 (distancia euclidiana)

### Optimizaciones
```javascript
// Cache de modelos Face-API
const modelCache = new Map();

async function loadCachedModel(modelName) {
    if (modelCache.has(modelName)) {
        return modelCache.get(modelName);
    }
    
    const model = await faceapi.nets[modelName].loadFromUri(MODEL_PATH);
    modelCache.set(modelName, model);
    return model;
}
```

## Mantenimiento y Monitoreo

### Queries de Monitoreo
```sql
-- Verificaciones fallidas por empleado (último mes)
SELECT 
    e.nombre,
    COUNT(*) as failed_attempts,
    b.biometric_type
FROM biometric_verification_logs b
JOIN employees e ON b.employee_id = e.id
WHERE b.verification_result = 'failed' 
    AND b.verification_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
GROUP BY b.employee_id, b.biometric_type
HAVING failed_attempts > 5;

-- Calidad promedio de inscripciones
SELECT 
    biometric_type,
    AVG(quality_score) as avg_quality,
    COUNT(*) as total_enrollments
FROM employee_biometrics 
WHERE is_active = TRUE
GROUP BY biometric_type;

-- Sesiones de inscripción no completadas
SELECT 
    s.*,
    e.nombre as employee_name
FROM biometric_enrollment_sessions s
JOIN employees e ON s.employee_id = e.id
WHERE s.session_status IN ('started', 'in_progress')
    AND s.started_at < DATE_SUB(NOW(), INTERVAL 1 HOUR);
```

### Rutinas de Limpieza
```sql
-- Eliminar sesiones antiguas (ejecutar diariamente)
DELETE FROM biometric_enrollment_sessions 
WHERE session_status IN ('completed', 'failed') 
    AND completed_at < DATE_SUB(NOW(), INTERVAL 7 DAY);

-- Archivar logs antiguos (ejecutar mensualmente)
INSERT INTO biometric_verification_logs_archive 
SELECT * FROM biometric_verification_logs 
WHERE verification_date < DATE_SUB(NOW(), INTERVAL 2 YEAR);

DELETE FROM biometric_verification_logs 
WHERE verification_date < DATE_SUB(NOW(), INTERVAL 2 YEAR);
```

---

*Documentación Técnica - SynkTime Biometric v1.0*
*Última actualización: Agosto 2024*