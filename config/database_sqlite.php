<?php
// config/database_sqlite.php - SQLite configuration for testing
try {
    $conn = new PDO("sqlite:" . __DIR__ . "/../synktime.db");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables matching the expected schema
    $conn->exec("
        CREATE TABLE IF NOT EXISTS EMPRESA (
            ID_EMPRESA INTEGER PRIMARY KEY AUTOINCREMENT,
            NOMBRE VARCHAR(100),
            RUC VARCHAR(20),
            DIRECCION TEXT,
            ESTADO VARCHAR(1) DEFAULT 'A'
        )
    ");
    
    $conn->exec("
        CREATE TABLE IF NOT EXISTS USUARIO (
            ID_USUARIO INTEGER PRIMARY KEY AUTOINCREMENT,
            USERNAME VARCHAR(50) UNIQUE,
            CONTRASENA VARCHAR(255),
            NOMBRE_COMPLETO VARCHAR(100),
            EMAIL VARCHAR(100),
            ROL VARCHAR(20),
            ID_EMPRESA INTEGER,
            ESTADO VARCHAR(1) DEFAULT 'A'
        )
    ");
    
    $conn->exec("
        CREATE TABLE IF NOT EXISTS SEDE (
            ID_SEDE INTEGER PRIMARY KEY AUTOINCREMENT,
            NOMBRE VARCHAR(100),
            DIRECCION TEXT,
            ID_EMPRESA INTEGER,
            ACTIVO VARCHAR(1) DEFAULT 'S',
            ESTADO VARCHAR(1) DEFAULT 'A'
        )
    ");
    
    $conn->exec("
        CREATE TABLE IF NOT EXISTS ESTABLECIMIENTO (
            ID_ESTABLECIMIENTO INTEGER PRIMARY KEY AUTOINCREMENT,
            NOMBRE VARCHAR(100),
            DIRECCION TEXT,
            ID_SEDE INTEGER,
            ACTIVO VARCHAR(1) DEFAULT 'S',
            ESTADO VARCHAR(1) DEFAULT 'A'
        )
    ");
    
    $conn->exec("
        CREATE TABLE IF NOT EXISTS EMPLEADO (
            ID_EMPLEADO INTEGER PRIMARY KEY AUTOINCREMENT,
            CODIGO VARCHAR(50) UNIQUE,
            NOMBRE VARCHAR(100),
            APELLIDO VARCHAR(100),
            ID_ESTABLECIMIENTO INTEGER,
            ACTIVO VARCHAR(1) DEFAULT 'S',
            ESTADO VARCHAR(1) DEFAULT 'A'
        )
    ");
    
    $conn->exec("
        CREATE TABLE IF NOT EXISTS HORARIO (
            ID_HORARIO INTEGER PRIMARY KEY AUTOINCREMENT,
            NOMBRE VARCHAR(100),
            HORA_ENTRADA TIME,
            HORA_SALIDA TIME,
            TOLERANCIA INTEGER DEFAULT 15,
            ACTIVO VARCHAR(1) DEFAULT 'S'
        )
    ");
    
    $conn->exec("
        CREATE TABLE IF NOT EXISTS ASISTENCIA (
            ID_ASISTENCIA INTEGER PRIMARY KEY AUTOINCREMENT,
            ID_EMPLEADO INTEGER,
            ID_HORARIO INTEGER,
            FECHA DATE,
            HORA TIME,
            TIPO VARCHAR(10),
            TARDANZA INTEGER DEFAULT 0,
            OBSERVACION TEXT,
            VERIFICATION_METHOD VARCHAR(20) DEFAULT 'traditional',
            CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Keep the lowercase tables for biometric compatibility
    $conn->exec("
        CREATE TABLE IF NOT EXISTS empleados (
            ID_EMPLEADO INTEGER PRIMARY KEY AUTOINCREMENT,
            CODIGO VARCHAR(50) UNIQUE,
            NOMBRE VARCHAR(100),
            APELLIDO VARCHAR(100),
            ID_ESTABLECIMIENTO INTEGER,
            ACTIVO INTEGER DEFAULT 1
        )
    ");
    
    $conn->exec("
        CREATE TABLE IF NOT EXISTS sedes (
            ID_SEDE INTEGER PRIMARY KEY AUTOINCREMENT,
            NOMBRE VARCHAR(100),
            ACTIVO INTEGER DEFAULT 1
        )
    ");
    
    $conn->exec("
        CREATE TABLE IF NOT EXISTS establecimientos (
            ID_ESTABLECIMIENTO INTEGER PRIMARY KEY AUTOINCREMENT,
            NOMBRE VARCHAR(100),
            ID_SEDE INTEGER,
            ACTIVO INTEGER DEFAULT 1
        )
    ");
    
    $conn->exec("
        CREATE TABLE IF NOT EXISTS asistencias (
            ID_ASISTENCIA INTEGER PRIMARY KEY AUTOINCREMENT,
            ID_EMPLEADO INTEGER,
            FECHA DATE,
            HORA_ENTRADA TIME,
            HORA_SALIDA TIME,
            VERIFICATION_METHOD VARCHAR(20) DEFAULT 'traditional',
            CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $conn->exec("
        CREATE TABLE IF NOT EXISTS LOG (
            ID_LOG INTEGER PRIMARY KEY AUTOINCREMENT,
            ID_USUARIO INTEGER,
            ACCION VARCHAR(50),
            DETALLE TEXT,
            CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create biometric tables
    $conn->exec("
        CREATE TABLE IF NOT EXISTS biometric_data (
            ID INTEGER PRIMARY KEY AUTOINCREMENT,
            ID_EMPLEADO INTEGER NOT NULL,
            BIOMETRIC_TYPE VARCHAR(20) NOT NULL CHECK (BIOMETRIC_TYPE IN ('fingerprint', 'facial')),
            FINGER_TYPE VARCHAR(20),
            BIOMETRIC_DATA TEXT,
            CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ACTIVO INTEGER DEFAULT 1
        )
    ");
    
    $conn->exec("
        CREATE TABLE IF NOT EXISTS biometric_logs (
            ID INTEGER PRIMARY KEY AUTOINCREMENT,
            ID_EMPLEADO INTEGER NOT NULL,
            VERIFICATION_METHOD VARCHAR(20) NOT NULL CHECK (VERIFICATION_METHOD IN ('fingerprint', 'facial', 'traditional')),
            VERIFICATION_SUCCESS INTEGER DEFAULT 0,
            FECHA DATE,
            HORA TIME,
            CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Insert sample data if tables are empty
    $count = $conn->query("SELECT COUNT(*) FROM EMPLEADO")->fetchColumn();
    if ($count == 0) {
        // Insert sample empresa
        $conn->exec("
            INSERT INTO EMPRESA (NOMBRE, RUC, DIRECCION) VALUES ('SynkTime Corp', '12345678901', 'Av. Principal 123, Lima')
        ");
        
        // Insert sample user
        $conn->exec("
            INSERT INTO USUARIO (USERNAME, CONTRASENA, NOMBRE_COMPLETO, EMAIL, ROL, ID_EMPRESA) VALUES 
            ('admin', 'admin', 'Administrador', 'admin@synktime.com', 'ADMINISTRADOR', 1)
        ");
        
        // Insert sample horarios
        $conn->exec("
            INSERT INTO HORARIO (NOMBRE, HORA_ENTRADA, HORA_SALIDA, TOLERANCIA) VALUES 
            ('Horario Mañana', '08:00:00', '17:00:00', 15),
            ('Horario Tarde', '14:00:00', '23:00:00', 10),
            ('Horario Nocturno', '22:00:00', '06:00:00', 20)
        ");
        
        // Insert sample sedes (both tables)
        $conn->exec("
            INSERT INTO SEDE (NOMBRE, DIRECCION, ID_EMPRESA) VALUES 
            ('Sede Principal', 'Av. Principal 123, Lima', 1),
            ('Sede Norte', 'Av. Norte 456, Lima', 1),
            ('Sede Sur', 'Av. Sur 789, Lima', 1)
        ");
        
        $conn->exec("
            INSERT INTO sedes (NOMBRE) VALUES 
            ('Sede Principal'),
            ('Sede Norte'),
            ('Sede Sur')
        ");
        
        // Insert sample establecimientos (both tables)
        $conn->exec("
            INSERT INTO ESTABLECIMIENTO (NOMBRE, DIRECCION, ID_SEDE) VALUES 
            ('Oficina Central', 'Oficina Principal Piso 1', 1),
            ('Almacén Principal', 'Almacén Central Piso B', 1),
            ('Sucursal Norte 1', 'Local Norte A', 2),
            ('Sucursal Norte 2', 'Local Norte B', 2),
            ('Sucursal Sur 1', 'Local Sur A', 3)
        ");
        
        $conn->exec("
            INSERT INTO establecimientos (NOMBRE, ID_SEDE) VALUES 
            ('Oficina Central', 1),
            ('Almacén Principal', 1),
            ('Sucursal Norte 1', 2),
            ('Sucursal Norte 2', 2),
            ('Sucursal Sur 1', 3)
        ");
        
        // Insert sample empleados (both tables)
        $conn->exec("
            INSERT INTO EMPLEADO (CODIGO, NOMBRE, APELLIDO, ID_ESTABLECIMIENTO) VALUES 
            ('EMP001', 'Juan', 'Pérez', 1),
            ('EMP002', 'María', 'González', 1),
            ('EMP003', 'Carlos', 'Rodríguez', 2),
            ('EMP004', 'Ana', 'Martínez', 3),
            ('EMP005', 'Luis', 'López', 4),
            ('EMP006', 'Carmen', 'Sánchez', 5),
            ('EMP007', 'Roberto', 'García', 1),
            ('EMP008', 'Elena', 'Hernández', 2),
            ('EMP009', 'Miguel', 'Torres', 3),
            ('EMP010', 'Patricia', 'Ruiz', 4)
        ");
        
        $conn->exec("
            INSERT INTO empleados (CODIGO, NOMBRE, APELLIDO, ID_ESTABLECIMIENTO) VALUES 
            ('EMP001', 'Juan', 'Pérez', 1),
            ('EMP002', 'María', 'González', 1),
            ('EMP003', 'Carlos', 'Rodríguez', 2),
            ('EMP004', 'Ana', 'Martínez', 3),
            ('EMP005', 'Luis', 'López', 4),
            ('EMP006', 'Carmen', 'Sánchez', 5),
            ('EMP007', 'Roberto', 'García', 1),
            ('EMP008', 'Elena', 'Hernández', 2),
            ('EMP009', 'Miguel', 'Torres', 3),
            ('EMP010', 'Patricia', 'Ruiz', 4)
        ");
        
        // Insert sample attendance records
        $today = date('Y-m-d');
        $conn->exec("
            INSERT INTO ASISTENCIA (ID_EMPLEADO, ID_HORARIO, FECHA, HORA, TIPO, TARDANZA, VERIFICATION_METHOD) VALUES 
            (1, 1, '$today', '08:00:00', 'ENTRADA', 0, 'traditional'),
            (2, 1, '$today', '08:15:00', 'ENTRADA', 15, 'fingerprint'),
            (3, 1, '$today', '08:30:00', 'ENTRADA', 30, 'facial'),
            (4, 2, '$today', '14:00:00', 'ENTRADA', 0, 'traditional'),
            (5, 2, '$today', '14:10:00', 'ENTRADA', 10, 'fingerprint')
        ");
    }
    
} catch(PDOException $e) {
    echo "Error de conexión SQLite: " . $e->getMessage();
    die();
}
?>