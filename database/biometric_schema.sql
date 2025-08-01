-- ===========================================================================
-- BIOMETRIC RECOGNITION SYSTEM DATABASE SCHEMA
-- SynkTime - Attendance Management System
-- ===========================================================================

-- Table for storing employee biometric data
CREATE TABLE IF NOT EXISTS EMPLEADO_BIOMETRICO (
    ID_BIOMETRICO INT PRIMARY KEY AUTO_INCREMENT,
    ID_EMPLEADO INT NOT NULL,
    TIPO_BIOMETRICO ENUM('FINGERPRINT', 'FACIAL') NOT NULL,
    DATOS_BIOMETRICO TEXT NOT NULL, -- Encrypted biometric template/data
    METADATA JSON, -- Additional metadata like confidence threshold, device info
    FECHA_REGISTRO DATETIME DEFAULT CURRENT_TIMESTAMP,
    FECHA_ACTUALIZACION DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ACTIVO ENUM('S', 'N') DEFAULT 'S',
    CREADO_POR INT,
    INDEX idx_empleado_tipo (ID_EMPLEADO, TIPO_BIOMETRICO),
    INDEX idx_activo (ACTIVO),
    FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO) ON DELETE CASCADE
);

-- Table for biometric verification logging
CREATE TABLE IF NOT EXISTS BIOMETRIC_VERIFICATION_LOG (
    ID_LOG INT PRIMARY KEY AUTO_INCREMENT,
    ID_EMPLEADO INT,
    TIPO_BIOMETRICO ENUM('FINGERPRINT', 'FACIAL') NOT NULL,
    RESULTADO ENUM('SUCCESS', 'FAILED', 'ERROR') NOT NULL,
    CONFIDENCE_SCORE DECIMAL(5,2), -- Confidence percentage (0.00 to 100.00)
    DETALLE_ERROR TEXT, -- Error details if verification failed
    FECHA_VERIFICACION DATETIME DEFAULT CURRENT_TIMESTAMP,
    IP_ADDRESS VARCHAR(45),
    USER_AGENT TEXT,
    ID_ASISTENCIA INT, -- Link to attendance record if successful
    INDEX idx_empleado_fecha (ID_EMPLEADO, FECHA_VERIFICACION),
    INDEX idx_resultado (RESULTADO),
    INDEX idx_tipo_fecha (TIPO_BIOMETRICO, FECHA_VERIFICACION),
    FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO) ON DELETE SET NULL,
    FOREIGN KEY (ID_ASISTENCIA) REFERENCES ASISTENCIA(ID_ASISTENCIA) ON DELETE SET NULL
);

-- Table for biometric system configuration
CREATE TABLE IF NOT EXISTS BIOMETRIC_CONFIG (
    ID_CONFIG INT PRIMARY KEY AUTO_INCREMENT,
    CONFIG_KEY VARCHAR(100) NOT NULL UNIQUE,
    CONFIG_VALUE TEXT,
    DESCRIPCION TEXT,
    FECHA_CREACION DATETIME DEFAULT CURRENT_TIMESTAMP,
    FECHA_ACTUALIZACION DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default biometric system configuration
INSERT INTO BIOMETRIC_CONFIG (CONFIG_KEY, CONFIG_VALUE, DESCRIPCION) VALUES 
('fingerprint_enabled', 'true', 'Enable fingerprint recognition system'),
('facial_enabled', 'true', 'Enable facial recognition system'),
('fingerprint_confidence_threshold', '80.0', 'Minimum confidence threshold for fingerprint verification (0-100)'),
('facial_confidence_threshold', '85.0', 'Minimum confidence threshold for facial recognition (0-100)'),
('max_verification_attempts', '3', 'Maximum verification attempts before lockout'),
('verification_timeout', '30', 'Verification timeout in seconds'),
('biometric_data_encryption', 'true', 'Enable encryption for biometric data storage')
ON DUPLICATE KEY UPDATE 
    CONFIG_VALUE = VALUES(CONFIG_VALUE),
    FECHA_ACTUALIZACION = CURRENT_TIMESTAMP;