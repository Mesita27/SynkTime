<?php
/**
 * Database schema initialization for biometric system
 * This script ensures all required tables and columns exist
 */

require_once __DIR__ . '/config/database.php';

function initializeBiometricSchema($conn) {
    $updates = [];
    
    try {
        // 1. Create biometric_data table
        $sql = "
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
            )
        ";
        $conn->exec($sql);
        $updates[] = "biometric_data table created/verified";
        
        // 2. Create biometric_logs table
        $sql = "
            CREATE TABLE IF NOT EXISTS biometric_logs (
                ID INT AUTO_INCREMENT PRIMARY KEY,
                ID_EMPLEADO INT NOT NULL,
                VERIFICATION_METHOD ENUM('fingerprint', 'facial', 'traditional') NOT NULL,
                VERIFICATION_SUCCESS TINYINT(1) DEFAULT 0,
                FECHA DATE,
                HORA TIME,
                CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO)
            )
        ";
        $conn->exec($sql);
        $updates[] = "biometric_logs table created/verified";
        
        // 3. Check if VERIFICATION_METHOD column exists in ASISTENCIA table
        $stmt = $conn->prepare("SHOW COLUMNS FROM ASISTENCIA LIKE 'VERIFICATION_METHOD'");
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            $conn->exec("ALTER TABLE ASISTENCIA ADD COLUMN VERIFICATION_METHOD ENUM('fingerprint', 'facial', 'traditional') DEFAULT 'traditional'");
            $updates[] = "VERIFICATION_METHOD column added to ASISTENCIA table";
        }
        
        // 4. Check if CREATED_AT column exists in ASISTENCIA table
        $stmt = $conn->prepare("SHOW COLUMNS FROM ASISTENCIA LIKE 'CREATED_AT'");
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            $conn->exec("ALTER TABLE ASISTENCIA ADD COLUMN CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
            $updates[] = "CREATED_AT column added to ASISTENCIA table";
        }
        
        // 5. Create indexes for better performance
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_biometric_data_employee ON biometric_data(ID_EMPLEADO)",
            "CREATE INDEX IF NOT EXISTS idx_biometric_data_type ON biometric_data(BIOMETRIC_TYPE)",
            "CREATE INDEX IF NOT EXISTS idx_biometric_logs_employee ON biometric_logs(ID_EMPLEADO)",
            "CREATE INDEX IF NOT EXISTS idx_biometric_logs_method ON biometric_logs(VERIFICATION_METHOD)",
            "CREATE INDEX IF NOT EXISTS idx_asistencia_verification ON ASISTENCIA(VERIFICATION_METHOD)"
        ];
        
        foreach ($indexes as $index_sql) {
            try {
                $conn->exec($index_sql);
            } catch (PDOException $e) {
                // Ignore if index already exists
                if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                    throw $e;
                }
            }
        }
        $updates[] = "Database indexes created/verified";
        
        // 6. Create upload directories
        $dirs = ['uploads', 'uploads/facial'];
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
                $updates[] = "Created directory: $dir";
            }
        }
        
        return [
            'success' => true,
            'updates' => $updates,
            'message' => 'Database schema initialized successfully'
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'updates' => $updates
        ];
    }
}

// If called directly, run the initialization
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $result = initializeBiometricSchema($conn);
    
    if ($result['success']) {
        echo "✅ Database schema initialization completed successfully!\n\n";
        echo "Updates made:\n";
        foreach ($result['updates'] as $update) {
            echo "- $update\n";
        }
    } else {
        echo "❌ Error initializing database schema:\n";
        echo $result['error'] . "\n";
        
        if (!empty($result['updates'])) {
            echo "\nPartial updates completed:\n";
            foreach ($result['updates'] as $update) {
                echo "- $update\n";
            }
        }
    }
}

?>