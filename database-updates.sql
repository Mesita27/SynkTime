-- Database schema updates for biometric system enhancements
-- Run these commands in your MySQL database

-- 1. Create biometric_data table if not exists
CREATE TABLE IF NOT EXISTS biometric_data (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_EMPLEADO INT NOT NULL,
    BIOMETRIC_TYPE ENUM('fingerprint', 'facial') NOT NULL,
    FINGER_TYPE VARCHAR(20),
    BIOMETRIC_DATA LONGTEXT,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ACTIVO TINYINT(1) DEFAULT 1,
    FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO),
    UNIQUE KEY unique_employee_finger (ID_EMPLEADO, FINGER_TYPE)
);

-- 2. Create biometric_logs table if not exists
CREATE TABLE IF NOT EXISTS biometric_logs (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_EMPLEADO INT NOT NULL,
    VERIFICATION_METHOD ENUM('fingerprint', 'facial', 'traditional') NOT NULL,
    VERIFICATION_SUCCESS TINYINT(1) DEFAULT 0,
    FECHA DATE,
    HORA TIME,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO)
);

-- 3. Add VERIFICATION_METHOD column to ASISTENCIA table if not exists
ALTER TABLE ASISTENCIA 
ADD COLUMN IF NOT EXISTS VERIFICATION_METHOD ENUM('fingerprint', 'facial', 'traditional') DEFAULT 'traditional';

-- 4. Add CREATED_AT column to ASISTENCIA table if not exists
ALTER TABLE ASISTENCIA 
ADD COLUMN IF NOT EXISTS CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- 5. Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_biometric_data_employee ON biometric_data(ID_EMPLEADO);
CREATE INDEX IF NOT EXISTS idx_biometric_data_type ON biometric_data(BIOMETRIC_TYPE);
CREATE INDEX IF NOT EXISTS idx_biometric_logs_employee ON biometric_logs(ID_EMPLEADO);
CREATE INDEX IF NOT EXISTS idx_biometric_logs_method ON biometric_logs(VERIFICATION_METHOD);
CREATE INDEX IF NOT EXISTS idx_asistencia_verification ON ASISTENCIA(VERIFICATION_METHOD);

-- 6. Create uploads directory structure (to be done manually)
-- Create folders: uploads/facial/ and uploads/ in the project root