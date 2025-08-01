-- Biometric Data Tables for SynkTime System
-- This script adds biometric functionality to the existing system

-- Table for storing employee biometric enrollment data
CREATE TABLE IF NOT EXISTS employee_biometrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    biometric_type ENUM('fingerprint', 'facial') NOT NULL,
    biometric_data TEXT NOT NULL, -- Encrypted biometric template/hash
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    device_info JSON, -- Store device compatibility info
    quality_score DECIMAL(3,2), -- Quality score of the biometric sample
    created_by INT,
    INDEX idx_employee_biometric (employee_id, biometric_type),
    INDEX idx_enrollment_date (enrollment_date),
    UNIQUE KEY unique_employee_biometric (employee_id, biometric_type)
);

-- Table for biometric verification logs
CREATE TABLE IF NOT EXISTS biometric_verification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    biometric_type ENUM('fingerprint', 'facial') NOT NULL,
    verification_result ENUM('success', 'failed', 'retry') NOT NULL,
    confidence_score DECIMAL(3,2),
    verification_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    attendance_id INT NULL, -- Link to attendance record if applicable
    INDEX idx_employee_verification (employee_id, verification_date),
    INDEX idx_verification_result (verification_result),
    INDEX idx_attendance_link (attendance_id)
);

-- Table for biometric device registry
CREATE TABLE IF NOT EXISTS biometric_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_name VARCHAR(255) NOT NULL,
    device_type ENUM('fingerprint_scanner', 'camera', 'integrated') NOT NULL,
    device_model VARCHAR(255),
    supported_types SET('fingerprint', 'facial') NOT NULL,
    connection_info JSON, -- API endpoints, drivers, etc.
    is_active BOOLEAN DEFAULT TRUE,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_device_type (device_type),
    INDEX idx_device_active (is_active)
);

-- Table for biometric enrollment sessions
CREATE TABLE IF NOT EXISTS biometric_enrollment_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    biometric_type ENUM('fingerprint', 'facial') NOT NULL,
    session_status ENUM('started', 'in_progress', 'completed', 'failed') DEFAULT 'started',
    samples_collected INT DEFAULT 0,
    required_samples INT DEFAULT 3,
    session_data JSON, -- Store temporary enrollment data
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    created_by INT,
    INDEX idx_session_status (session_status),
    INDEX idx_employee_session (employee_id, biometric_type)
);

-- Add indexes for foreign key relationships (assuming employees table exists)
-- Note: These will be created if the referenced tables exist
-- ALTER TABLE employee_biometrics ADD FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE;
-- ALTER TABLE biometric_verification_logs ADD FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE;
-- ALTER TABLE biometric_enrollment_sessions ADD FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE;

-- Create views for easier data access
CREATE OR REPLACE VIEW biometric_enrollment_status AS
SELECT 
    e.id as employee_id,
    e.nombre as employee_name,
    CASE WHEN eb_fp.id IS NOT NULL THEN 'enrolled' ELSE 'not_enrolled' END as fingerprint_status,
    CASE WHEN eb_face.id IS NOT NULL THEN 'enrolled' ELSE 'not_enrolled' END as facial_status,
    eb_fp.enrollment_date as fingerprint_enrolled_date,
    eb_face.enrollment_date as facial_enrolled_date,
    eb_fp.quality_score as fingerprint_quality,
    eb_face.quality_score as facial_quality
FROM employees e
LEFT JOIN employee_biometrics eb_fp ON e.id = eb_fp.employee_id AND eb_fp.biometric_type = 'fingerprint' AND eb_fp.is_active = TRUE
LEFT JOIN employee_biometrics eb_face ON e.id = eb_face.employee_id AND eb_face.biometric_type = 'facial' AND eb_face.is_active = TRUE;