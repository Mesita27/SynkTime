-- ===================================================================
-- SYNKTIME BIOMETRIC SYSTEM - MYSQL PRODUCTION SCHEMA
-- Updated schema to support biometric enrollment and attendance verification
-- ===================================================================

-- Create database (if needed)
-- CREATE DATABASE IF NOT EXISTS synktime CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE synktime;

-- ===================================================================
-- CORE BUSINESS TABLES
-- ===================================================================

-- Company information
CREATE TABLE IF NOT EXISTS EMPRESA (
    ID_EMPRESA INT AUTO_INCREMENT PRIMARY KEY,
    NOMBRE VARCHAR(100) NOT NULL,
    RUC VARCHAR(20),
    DIRECCION TEXT,
    ESTADO ENUM('A', 'I') DEFAULT 'A',
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users and authentication
CREATE TABLE IF NOT EXISTS USUARIO (
    ID_USUARIO INT AUTO_INCREMENT PRIMARY KEY,
    USERNAME VARCHAR(50) UNIQUE NOT NULL,
    CONTRASENA VARCHAR(255) NOT NULL,
    NOMBRE_COMPLETO VARCHAR(100) NOT NULL,
    EMAIL VARCHAR(100),
    ROL ENUM('ADMINISTRADOR', 'GERENTE', 'ASISTENCIA', 'DUEÑO') NOT NULL,
    ID_EMPRESA INT NOT NULL,
    ESTADO ENUM('A', 'I') DEFAULT 'A',
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_EMPRESA) REFERENCES EMPRESA(ID_EMPRESA)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Company locations
CREATE TABLE IF NOT EXISTS SEDE (
    ID_SEDE INT AUTO_INCREMENT PRIMARY KEY,
    NOMBRE VARCHAR(100) NOT NULL,
    DIRECCION TEXT,
    ID_EMPRESA INT NOT NULL,
    ACTIVO ENUM('S', 'N') DEFAULT 'S',
    ESTADO ENUM('A', 'I') DEFAULT 'A',
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_EMPRESA) REFERENCES EMPRESA(ID_EMPRESA)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Establishments within locations
CREATE TABLE IF NOT EXISTS ESTABLECIMIENTO (
    ID_ESTABLECIMIENTO INT AUTO_INCREMENT PRIMARY KEY,
    NOMBRE VARCHAR(100) NOT NULL,
    DIRECCION TEXT,
    ID_SEDE INT NOT NULL,
    ACTIVO ENUM('S', 'N') DEFAULT 'S',
    ESTADO ENUM('A', 'I') DEFAULT 'A',
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_SEDE) REFERENCES SEDE(ID_SEDE)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employee information
CREATE TABLE IF NOT EXISTS EMPLEADO (
    ID_EMPLEADO INT AUTO_INCREMENT PRIMARY KEY,
    CODIGO VARCHAR(50) UNIQUE NOT NULL,
    NOMBRE VARCHAR(100) NOT NULL,
    APELLIDO VARCHAR(100) NOT NULL,
    ID_ESTABLECIMIENTO INT NOT NULL,
    ACTIVO ENUM('S', 'N') DEFAULT 'S',
    ESTADO ENUM('A', 'I') DEFAULT 'A',
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_ESTABLECIMIENTO) REFERENCES ESTABLECIMIENTO(ID_ESTABLECIMIENTO)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Work schedules
CREATE TABLE IF NOT EXISTS HORARIO (
    ID_HORARIO INT AUTO_INCREMENT PRIMARY KEY,
    NOMBRE VARCHAR(100) NOT NULL,
    HORA_ENTRADA TIME NOT NULL,
    HORA_SALIDA TIME NOT NULL,
    TOLERANCIA INT DEFAULT 15,
    ACTIVO ENUM('S', 'N') DEFAULT 'S',
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- ATTENDANCE SYSTEM TABLES
-- ===================================================================

-- Attendance records with biometric verification support
CREATE TABLE IF NOT EXISTS ASISTENCIA (
    ID_ASISTENCIA INT AUTO_INCREMENT PRIMARY KEY,
    ID_EMPLEADO INT NOT NULL,
    ID_HORARIO INT,
    FECHA DATE NOT NULL,
    HORA TIME NOT NULL,
    TIPO ENUM('ENTRADA', 'SALIDA') NOT NULL,
    TARDANZA INT DEFAULT 0,
    OBSERVACION TEXT,
    VERIFICATION_METHOD ENUM('traditional', 'fingerprint', 'facial') DEFAULT 'traditional',
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO),
    FOREIGN KEY (ID_HORARIO) REFERENCES HORARIO(ID_HORARIO),
    INDEX idx_empleado_fecha (ID_EMPLEADO, FECHA),
    INDEX idx_fecha_tipo (FECHA, TIPO),
    INDEX idx_verification_method (VERIFICATION_METHOD)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- BIOMETRIC SYSTEM TABLES
-- ===================================================================

-- Biometric data storage for fingerprints and facial patterns
CREATE TABLE IF NOT EXISTS biometric_data (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_EMPLEADO INT NOT NULL,
    BIOMETRIC_TYPE ENUM('fingerprint', 'facial') NOT NULL,
    FINGER_TYPE VARCHAR(20),
    BIOMETRIC_DATA LONGTEXT,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ACTIVO TINYINT(1) DEFAULT 1,
    FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO) ON DELETE CASCADE,
    UNIQUE KEY unique_employee_finger (ID_EMPLEADO, FINGER_TYPE),
    INDEX idx_biometric_type (BIOMETRIC_TYPE),
    INDEX idx_empleado_activo (ID_EMPLEADO, ACTIVO)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Biometric verification logs
CREATE TABLE IF NOT EXISTS biometric_logs (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_EMPLEADO INT NOT NULL,
    VERIFICATION_METHOD ENUM('fingerprint', 'facial', 'traditional') NOT NULL,
    VERIFICATION_SUCCESS TINYINT(1) DEFAULT 0,
    FECHA DATE NOT NULL,
    HORA TIME NOT NULL,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO) ON DELETE CASCADE,
    INDEX idx_empleado_fecha (ID_EMPLEADO, FECHA),
    INDEX idx_verification_method (VERIFICATION_METHOD),
    INDEX idx_verification_success (VERIFICATION_SUCCESS)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- AUDIT AND LOGGING TABLES
-- ===================================================================

-- System activity logs
CREATE TABLE IF NOT EXISTS LOG (
    ID_LOG INT AUTO_INCREMENT PRIMARY KEY,
    ID_USUARIO INT,
    ACCION VARCHAR(50) NOT NULL,
    DETALLE TEXT,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_USUARIO) REFERENCES USUARIO(ID_USUARIO) ON DELETE SET NULL,
    INDEX idx_usuario_fecha (ID_USUARIO, CREATED_AT),
    INDEX idx_accion (ACCION)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- COMPATIBILITY TABLES (lowercase for existing biometric APIs)
-- ===================================================================

-- Lowercase employee table for biometric API compatibility
CREATE TABLE IF NOT EXISTS empleados (
    ID_EMPLEADO INT AUTO_INCREMENT PRIMARY KEY,
    CODIGO VARCHAR(50) UNIQUE,
    NOMBRE VARCHAR(100),
    APELLIDO VARCHAR(100),
    ID_ESTABLECIMIENTO INT,
    ACTIVO TINYINT(1) DEFAULT 1,
    FOREIGN KEY (ID_ESTABLECIMIENTO) REFERENCES ESTABLECIMIENTO(ID_ESTABLECIMIENTO)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lowercase locations for biometric API compatibility
CREATE TABLE IF NOT EXISTS sedes (
    ID_SEDE INT AUTO_INCREMENT PRIMARY KEY,
    NOMBRE VARCHAR(100),
    ACTIVO TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lowercase establishments for biometric API compatibility
CREATE TABLE IF NOT EXISTS establecimientos (
    ID_ESTABLECIMIENTO INT AUTO_INCREMENT PRIMARY KEY,
    NOMBRE VARCHAR(100),
    ID_SEDE INT,
    ACTIVO TINYINT(1) DEFAULT 1,
    FOREIGN KEY (ID_SEDE) REFERENCES sedes(ID_SEDE)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lowercase attendance for biometric API compatibility
CREATE TABLE IF NOT EXISTS asistencias (
    ID_ASISTENCIA INT AUTO_INCREMENT PRIMARY KEY,
    ID_EMPLEADO INT,
    FECHA DATE,
    HORA_ENTRADA TIME,
    HORA_SALIDA TIME,
    VERIFICATION_METHOD VARCHAR(20) DEFAULT 'traditional',
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_EMPLEADO) REFERENCES empleados(ID_EMPLEADO)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- INDEXES FOR PERFORMANCE
-- ===================================================================

-- Additional indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_empleado_establecimiento ON EMPLEADO(ID_ESTABLECIMIENTO);
CREATE INDEX IF NOT EXISTS idx_establecimiento_sede ON ESTABLECIMIENTO(ID_SEDE);
CREATE INDEX IF NOT EXISTS idx_sede_empresa ON SEDE(ID_EMPRESA);
CREATE INDEX IF NOT EXISTS idx_usuario_empresa ON USUARIO(ID_EMPRESA);

-- ===================================================================
-- SAMPLE DATA (for development/testing)
-- ===================================================================

/*
-- Uncomment to insert sample data

-- Sample company
INSERT IGNORE INTO EMPRESA (NOMBRE, RUC, DIRECCION) VALUES 
('SynkTime Corp', '12345678901', 'Av. Principal 123, Lima');

-- Sample user
INSERT IGNORE INTO USUARIO (USERNAME, CONTRASENA, NOMBRE_COMPLETO, EMAIL, ROL, ID_EMPRESA) VALUES 
('admin', 'admin', 'Administrador', 'admin@synktime.com', 'ADMINISTRADOR', 1);

-- Sample schedules
INSERT IGNORE INTO HORARIO (NOMBRE, HORA_ENTRADA, HORA_SALIDA, TOLERANCIA) VALUES 
('Horario Mañana', '08:00:00', '17:00:00', 15),
('Horario Tarde', '14:00:00', '23:00:00', 10),
('Horario Nocturno', '22:00:00', '06:00:00', 20);

-- Sample locations
INSERT IGNORE INTO SEDE (NOMBRE, DIRECCION, ID_EMPRESA) VALUES 
('Sede Principal', 'Av. Principal 123, Lima', 1),
('Sede Norte', 'Av. Norte 456, Lima', 1),
('Sede Sur', 'Av. Sur 789, Lima', 1);

-- Sample establishments
INSERT IGNORE INTO ESTABLECIMIENTO (NOMBRE, DIRECCION, ID_SEDE) VALUES 
('Oficina Central', 'Oficina Principal Piso 1', 1),
('Almacén Principal', 'Almacén Central Piso B', 1),
('Sucursal Norte 1', 'Local Norte A', 2),
('Sucursal Norte 2', 'Local Norte B', 2),
('Sucursal Sur 1', 'Local Sur A', 3);

-- Sample employees
INSERT IGNORE INTO EMPLEADO (CODIGO, NOMBRE, APELLIDO, ID_ESTABLECIMIENTO) VALUES 
('EMP001', 'Juan', 'Pérez', 1),
('EMP002', 'María', 'González', 1),
('EMP003', 'Carlos', 'Rodríguez', 2),
('EMP004', 'Ana', 'Martínez', 3),
('EMP005', 'Luis', 'López', 4),
('EMP006', 'Carmen', 'Sánchez', 5),
('EMP007', 'Roberto', 'García', 1),
('EMP008', 'Elena', 'Hernández', 2),
('EMP009', 'Miguel', 'Torres', 3),
('EMP010', 'Patricia', 'Ruiz', 4);
*/

-- ===================================================================
-- END OF SCHEMA
-- ===================================================================