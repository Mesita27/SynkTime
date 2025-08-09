<?php
/**
 * BiometricLogsRepository
 * Data access layer for biometric operation logs
 */

class BiometricLogsRepository {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->ensureTableExists();
    }
    
    /**
     * Ensure biometric_logs table exists
     */
    private function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS biometric_logs (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            ID_EMPLEADO INT NOT NULL,
            VERIFICATION_METHOD ENUM('fingerprint','facial','traditional') NOT NULL,
            VERIFICATION_SUCCESS TINYINT(1) DEFAULT 0,
            CONFIDENCE_SCORE DECIMAL(5,4) DEFAULT NULL,
            API_SOURCE VARCHAR(50) DEFAULT NULL,
            OPERATION_TYPE ENUM('enrollment','verification') NOT NULL,
            FECHA DATE,
            HORA TIME,
            CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_empleado (ID_EMPLEADO),
            INDEX idx_fecha (FECHA),
            INDEX idx_method (VERIFICATION_METHOD)
        )";
        $this->conn->exec($sql);
    }
    
    /**
     * Log biometric operation
     */
    public function log($data) {
        $sql = "INSERT INTO biometric_logs (
            ID_EMPLEADO, VERIFICATION_METHOD, VERIFICATION_SUCCESS, 
            CONFIDENCE_SCORE, API_SOURCE, OPERATION_TYPE, FECHA, HORA, CREATED_AT
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['id_empleado'],
            $data['verification_method'],
            $data['verification_success'] ? 1 : 0,
            $data['confidence_score'] ?? null,
            $data['api_source'] ?? null,
            $data['operation_type'],
            $data['fecha'] ?? date('Y-m-d'),
            $data['hora'] ?? date('H:i:s')
        ]);
    }
    
    /**
     * Get logs by employee
     */
    public function getByEmployee($employeeId, $limit = 50) {
        $sql = "SELECT * FROM biometric_logs 
                WHERE ID_EMPLEADO = ? 
                ORDER BY CREATED_AT DESC 
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$employeeId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get logs by date range
     */
    public function getByDateRange($startDate, $endDate, $employeeId = null) {
        $sql = "SELECT * FROM biometric_logs WHERE FECHA BETWEEN ? AND ?";
        $params = [$startDate, $endDate];
        
        if ($employeeId) {
            $sql .= " AND ID_EMPLEADO = ?";
            $params[] = $employeeId;
        }
        
        $sql .= " ORDER BY CREATED_AT DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get success rate statistics
     */
    public function getSuccessStats($employeeId = null, $days = 30) {
        $sql = "SELECT 
                    VERIFICATION_METHOD,
                    COUNT(*) as total_attempts,
                    SUM(VERIFICATION_SUCCESS) as successful_attempts,
                    AVG(CONFIDENCE_SCORE) as avg_confidence,
                    ROUND((SUM(VERIFICATION_SUCCESS) / COUNT(*)) * 100, 2) as success_rate
                FROM biometric_logs 
                WHERE FECHA >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
        
        $params = [$days];
        
        if ($employeeId) {
            $sql .= " AND ID_EMPLEADO = ?";
            $params[] = $employeeId;
        }
        
        $sql .= " GROUP BY VERIFICATION_METHOD";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get recent failed attempts
     */
    public function getRecentFailures($hours = 24, $employeeId = null) {
        $sql = "SELECT * FROM biometric_logs 
                WHERE VERIFICATION_SUCCESS = 0 
                AND CREATED_AT >= DATE_SUB(NOW(), INTERVAL ? HOUR)";
        
        $params = [$hours];
        
        if ($employeeId) {
            $sql .= " AND ID_EMPLEADO = ?";
            $params[] = $employeeId;
        }
        
        $sql .= " ORDER BY CREATED_AT DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>