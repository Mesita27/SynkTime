<?php
/**
 * Biometric Logs Repository
 * Handles database operations for biometric operation logs
 */

require_once __DIR__ . '/BaseRepository.php';

class BiometricLogsRepository extends BaseRepository
{
    protected $tableName = 'biometric_logs';

    public function __construct($connection)
    {
        parent::__construct($connection);
        $this->ensureTableExists();
    }

    /**
     * Ensure biometric_logs table exists
     */
    private function ensureTableExists()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS biometric_logs (
                ID INT AUTO_INCREMENT PRIMARY KEY,
                ID_EMPLEADO INT NOT NULL,
                VERIFICATION_METHOD ENUM('fingerprint', 'facial', 'traditional') NOT NULL,
                VERIFICATION_SUCCESS TINYINT(1) DEFAULT 0,
                FECHA DATE,
                HORA TIME,
                CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_employee_date (ID_EMPLEADO, FECHA),
                INDEX idx_method (VERIFICATION_METHOD),
                INDEX idx_success (VERIFICATION_SUCCESS),
                INDEX idx_created (CREATED_AT)
            )";
            
            $this->connection->exec($sql);
        } catch (PDOException $e) {
            error_log("Error creating biometric_logs table: " . $e->getMessage());
        }
    }

    /**
     * Log biometric operation
     */
    public function logOperation($idEmpleado, $verificationMethod, $success, $fecha = null, $hora = null)
    {
        try {
            $fecha = $fecha ?? date('Y-m-d');
            $hora = $hora ?? date('H:i:s');

            return $this->insert([
                'ID_EMPLEADO' => $idEmpleado,
                'VERIFICATION_METHOD' => $verificationMethod,
                'VERIFICATION_SUCCESS' => $success ? 1 : 0,
                'FECHA' => $fecha,
                'HORA' => $hora
            ]);
        } catch (Exception $e) {
            error_log("Error logging biometric operation: " . $e->getMessage());
            throw new Exception("Error al registrar log biométrico");
        }
    }

    /**
     * Get logs by employee and date range
     */
    public function getByEmployeeAndDateRange($idEmpleado, $fechaInicio, $fechaFin)
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE ID_EMPLEADO = ? 
                    AND FECHA BETWEEN ? AND ?
                    ORDER BY FECHA DESC, HORA DESC";

            return $this->query($sql, [$idEmpleado, $fechaInicio, $fechaFin]);
        } catch (Exception $e) {
            error_log("Error getting logs by employee and date range: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent logs for an employee
     */
    public function getRecentByEmployee($idEmpleado, $limit = 20)
    {
        try {
            $sql = "SELECT bl.*, e.NOMBRE, e.APELLIDO
                    FROM {$this->tableName} bl
                    JOIN empleados e ON bl.ID_EMPLEADO = e.ID_EMPLEADO
                    WHERE bl.ID_EMPLEADO = ?
                    ORDER BY bl.CREATED_AT DESC
                    LIMIT ?";

            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$idEmpleado, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting recent logs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get verification statistics for a period
     */
    public function getVerificationStats($fechaInicio, $fechaFin, $idEmpresa = null)
    {
        try {
            $sql = "SELECT 
                        VERIFICATION_METHOD,
                        COUNT(*) as total_attempts,
                        SUM(VERIFICATION_SUCCESS) as successful_attempts,
                        COUNT(DISTINCT ID_EMPLEADO) as unique_employees,
                        ROUND((SUM(VERIFICATION_SUCCESS) * 100.0 / COUNT(*)), 2) as success_rate
                    FROM {$this->tableName} bl
                    WHERE bl.FECHA BETWEEN ? AND ?";

            $params = [$fechaInicio, $fechaFin];

            if ($idEmpresa) {
                $sql .= " AND bl.ID_EMPLEADO IN (
                            SELECT e.ID_EMPLEADO FROM empleados e
                            JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                            JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                            WHERE s.ID_EMPRESA = ?
                         )";
                $params[] = $idEmpresa;
            }

            $sql .= " GROUP BY VERIFICATION_METHOD
                     ORDER BY total_attempts DESC";

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting verification stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get daily verification trends
     */
    public function getDailyTrends($fechaInicio, $fechaFin, $idEmpresa = null)
    {
        try {
            $sql = "SELECT 
                        FECHA,
                        VERIFICATION_METHOD,
                        COUNT(*) as attempts,
                        SUM(VERIFICATION_SUCCESS) as successes,
                        ROUND((SUM(VERIFICATION_SUCCESS) * 100.0 / COUNT(*)), 2) as success_rate
                    FROM {$this->tableName} bl
                    WHERE bl.FECHA BETWEEN ? AND ?";

            $params = [$fechaInicio, $fechaFin];

            if ($idEmpresa) {
                $sql .= " AND bl.ID_EMPLEADO IN (
                            SELECT e.ID_EMPLEADO FROM empleados e
                            JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                            JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                            WHERE s.ID_EMPRESA = ?
                         )";
                $params[] = $idEmpresa;
            }

            $sql .= " GROUP BY FECHA, VERIFICATION_METHOD
                     ORDER BY FECHA DESC, VERIFICATION_METHOD";

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting daily trends: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get failure analysis
     */
    public function getFailureAnalysis($fechaInicio, $fechaFin, $idEmpresa = null)
    {
        try {
            $sql = "SELECT 
                        e.ID_EMPLEADO,
                        e.NOMBRE,
                        e.APELLIDO,
                        bl.VERIFICATION_METHOD,
                        COUNT(*) as total_attempts,
                        SUM(bl.VERIFICATION_SUCCESS) as successful_attempts,
                        COUNT(*) - SUM(bl.VERIFICATION_SUCCESS) as failed_attempts,
                        ROUND(((COUNT(*) - SUM(bl.VERIFICATION_SUCCESS)) * 100.0 / COUNT(*)), 2) as failure_rate
                    FROM {$this->tableName} bl
                    JOIN empleados e ON bl.ID_EMPLEADO = e.ID_EMPLEADO";

            $params = [$fechaInicio, $fechaFin];

            if ($idEmpresa) {
                $sql .= " JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                         JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                         WHERE bl.FECHA BETWEEN ? AND ? AND s.ID_EMPRESA = ?";
                $params[] = $idEmpresa;
            } else {
                $sql .= " WHERE bl.FECHA BETWEEN ? AND ?";
            }

            $sql .= " GROUP BY e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, bl.VERIFICATION_METHOD
                     HAVING failed_attempts > 0
                     ORDER BY failure_rate DESC, failed_attempts DESC";

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting failure analysis: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get hourly usage patterns
     */
    public function getHourlyUsage($fechaInicio, $fechaFin, $idEmpresa = null)
    {
        try {
            $sql = "SELECT 
                        HOUR(bl.HORA) as hour,
                        VERIFICATION_METHOD,
                        COUNT(*) as usage_count,
                        COUNT(DISTINCT bl.ID_EMPLEADO) as unique_employees
                    FROM {$this->tableName} bl
                    WHERE bl.FECHA BETWEEN ? AND ?";

            $params = [$fechaInicio, $fechaFin];

            if ($idEmpresa) {
                $sql .= " AND bl.ID_EMPLEADO IN (
                            SELECT e.ID_EMPLEADO FROM empleados e
                            JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                            JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                            WHERE s.ID_EMPRESA = ?
                         )";
                $params[] = $idEmpresa;
            }

            $sql .= " GROUP BY hour, VERIFICATION_METHOD
                     ORDER BY hour, VERIFICATION_METHOD";

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting hourly usage: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get most active employees with biometrics
     */
    public function getMostActiveEmployees($fechaInicio, $fechaFin, $idEmpresa = null, $limit = 10)
    {
        try {
            $sql = "SELECT 
                        e.ID_EMPLEADO,
                        e.NOMBRE,
                        e.APELLIDO,
                        est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
                        COUNT(*) as total_biometric_operations,
                        SUM(bl.VERIFICATION_SUCCESS) as successful_operations,
                        COUNT(DISTINCT bl.FECHA) as active_days,
                        ROUND((SUM(bl.VERIFICATION_SUCCESS) * 100.0 / COUNT(*)), 2) as success_rate
                    FROM {$this->tableName} bl
                    JOIN empleados e ON bl.ID_EMPLEADO = e.ID_EMPLEADO
                    LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                    LEFT JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                    WHERE bl.FECHA BETWEEN ? AND ?";

            $params = [$fechaInicio, $fechaFin];

            if ($idEmpresa) {
                $sql .= " AND s.ID_EMPRESA = ?";
                $params[] = $idEmpresa;
            }

            $sql .= " GROUP BY e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, est.NOMBRE
                     ORDER BY total_biometric_operations DESC
                     LIMIT ?";

            $params[] = $limit;

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting most active employees: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get system health metrics
     */
    public function getSystemHealthMetrics($fecha = null)
    {
        try {
            $fecha = $fecha ?? date('Y-m-d');

            $sql = "SELECT 
                        COUNT(*) as total_operations,
                        SUM(VERIFICATION_SUCCESS) as successful_operations,
                        COUNT(DISTINCT ID_EMPLEADO) as active_employees,
                        COUNT(CASE WHEN VERIFICATION_METHOD = 'facial' THEN 1 END) as facial_operations,
                        COUNT(CASE WHEN VERIFICATION_METHOD = 'fingerprint' THEN 1 END) as fingerprint_operations,
                        COUNT(CASE WHEN VERIFICATION_METHOD = 'traditional' THEN 1 END) as traditional_operations,
                        ROUND((SUM(VERIFICATION_SUCCESS) * 100.0 / COUNT(*)), 2) as overall_success_rate
                    FROM {$this->tableName}
                    WHERE FECHA = ?";

            return $this->queryFirst($sql, [$fecha]);
        } catch (Exception $e) {
            error_log("Error getting system health metrics: " . $e->getMessage());
            return [
                'total_operations' => 0,
                'successful_operations' => 0,
                'active_employees' => 0,
                'facial_operations' => 0,
                'fingerprint_operations' => 0,
                'traditional_operations' => 0,
                'overall_success_rate' => 0
            ];
        }
    }

    /**
     * Clean up old logs
     */
    public function cleanupOldLogs($retentionDays = 90)
    {
        try {
            $cutoffDate = date('Y-m-d', strtotime("-{$retentionDays} days"));
            
            $sql = "DELETE FROM {$this->tableName} WHERE FECHA < ?";

            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute([$cutoffDate]);
            
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Error cleaning up old logs: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get employee verification history
     */
    public function getEmployeeHistory($idEmpleado, $limit = 50)
    {
        try {
            $sql = "SELECT 
                        bl.*,
                        CASE 
                            WHEN bl.VERIFICATION_SUCCESS = 1 THEN 'Exitoso'
                            ELSE 'Fallido'
                        END as status_text,
                        CASE bl.VERIFICATION_METHOD
                            WHEN 'facial' THEN 'Reconocimiento Facial'
                            WHEN 'fingerprint' THEN 'Huella Dactilar'
                            WHEN 'traditional' THEN 'Tradicional'
                        END as method_text
                    FROM {$this->tableName} bl
                    WHERE bl.ID_EMPLEADO = ?
                    ORDER BY bl.CREATED_AT DESC
                    LIMIT ?";

            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$idEmpleado, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting employee history: " . $e->getMessage());
            return [];
        }
    }
}