<?php
/**
 * BiometricDataRepository
 * Data access layer for biometric data storage
 */

class BiometricDataRepository {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->ensureTablesExist();
    }
    
    /**
     * Ensure biometric tables exist
     */
    private function ensureTablesExist() {
        // Create biometric_data table
        $sql = "CREATE TABLE IF NOT EXISTS biometric_data (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            ID_EMPLEADO INT NOT NULL,
            BIOMETRIC_TYPE ENUM('fingerprint','facial') NOT NULL,
            FINGER_TYPE VARCHAR(20),
            BIOMETRIC_DATA LONGTEXT,
            CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            ACTIVO TINYINT(1) DEFAULT 1,
            INDEX idx_empleado (ID_EMPLEADO),
            INDEX idx_type (BIOMETRIC_TYPE),
            UNIQUE KEY unique_employee_finger (ID_EMPLEADO, FINGER_TYPE)
        )";
        $this->conn->exec($sql);
    }
    
    /**
     * Store biometric data
     */
    public function store($employeeId, $biometricType, $data, $fingerType = null) {
        $sql = "INSERT INTO biometric_data (
            ID_EMPLEADO, BIOMETRIC_TYPE, FINGER_TYPE, BIOMETRIC_DATA, CREATED_AT
        ) VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$employeeId, $biometricType, $fingerType, $data]);
    }
    
    /**
     * Update existing biometric data
     */
    public function update($id, $data) {
        $sql = "UPDATE biometric_data 
                SET BIOMETRIC_DATA = ?, UPDATED_AT = NOW() 
                WHERE ID = ?";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$data, $id]);
    }
    
    /**
     * Get biometric data by employee and type
     */
    public function getByEmployeeAndType($employeeId, $biometricType, $fingerType = null) {
        $sql = "SELECT * FROM biometric_data 
                WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = ? AND ACTIVO = 1";
        $params = [$employeeId, $biometricType];
        
        if ($fingerType) {
            $sql .= " AND FINGER_TYPE = ?";
            $params[] = $fingerType;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all biometric data for employee
     */
    public function getByEmployee($employeeId) {
        $sql = "SELECT * FROM biometric_data 
                WHERE ID_EMPLEADO = ? AND ACTIVO = 1 
                ORDER BY BIOMETRIC_TYPE, FINGER_TYPE";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Deactivate biometric data
     */
    public function deactivate($id) {
        $sql = "UPDATE biometric_data SET ACTIVO = 0 WHERE ID = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Delete biometric data
     */
    public function delete($id) {
        $sql = "DELETE FROM biometric_data WHERE ID = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Check if employee has biometric data of specific type
     */
    public function hasData($employeeId, $biometricType) {
        $sql = "SELECT COUNT(*) as count FROM biometric_data 
                WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = ? AND ACTIVO = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$employeeId, $biometricType]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
    
    /**
     * Get enrollment statistics
     */
    public function getStats() {
        $sql = "SELECT 
                    BIOMETRIC_TYPE,
                    COUNT(DISTINCT ID_EMPLEADO) as employee_count,
                    COUNT(*) as total_records
                FROM biometric_data 
                WHERE ACTIVO = 1 
                GROUP BY BIOMETRIC_TYPE";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>