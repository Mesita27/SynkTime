<?php
/**
 * Biometric Data Repository
 * Handles database operations for biometric enrollment data
 */

require_once __DIR__ . '/BaseRepository.php';

class BiometricDataRepository extends BaseRepository
{
    protected $tableName = 'biometric_data';

    public function __construct($connection)
    {
        parent::__construct($connection);
        $this->ensureTableExists();
    }

    /**
     * Ensure biometric_data table exists
     */
    private function ensureTableExists()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS biometric_data (
                ID INT AUTO_INCREMENT PRIMARY KEY,
                ID_EMPLEADO INT NOT NULL,
                BIOMETRIC_TYPE ENUM('fingerprint', 'facial') NOT NULL,
                FINGER_TYPE VARCHAR(20),
                BIOMETRIC_DATA LONGTEXT,
                CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                ACTIVO TINYINT(1) DEFAULT 1,
                INDEX idx_employee_type (ID_EMPLEADO, BIOMETRIC_TYPE),
                INDEX idx_employee_finger (ID_EMPLEADO, FINGER_TYPE),
                INDEX idx_activo (ACTIVO)
            )";
            
            $this->connection->exec($sql);
        } catch (PDOException $e) {
            error_log("Error creating biometric_data table: " . $e->getMessage());
        }
    }

    /**
     * Get biometric data by employee and type
     */
    public function getByEmployee($idEmpleado, $biometricType = null)
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE ID_EMPLEADO = ? AND ACTIVO = 1";
            $params = [$idEmpleado];

            if ($biometricType) {
                $sql .= " AND BIOMETRIC_TYPE = ?";
                $params[] = $biometricType;
            }

            $sql .= " ORDER BY CREATED_AT DESC";

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting biometric data by employee: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get facial data for an employee
     */
    public function getFacialData($idEmpleado)
    {
        return $this->getByEmployee($idEmpleado, 'facial');
    }

    /**
     * Get fingerprint data for an employee
     */
    public function getFingerprintData($idEmpleado)
    {
        return $this->getByEmployee($idEmpleado, 'fingerprint');
    }

    /**
     * Get fingerprint data by specific finger type
     */
    public function getFingerprintByType($idEmpleado, $fingerType)
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = 'fingerprint' 
                    AND FINGER_TYPE = ? AND ACTIVO = 1
                    ORDER BY CREATED_AT DESC
                    LIMIT 1";

            return $this->queryFirst($sql, [$idEmpleado, $fingerType]);
        } catch (Exception $e) {
            error_log("Error getting fingerprint by type: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Store facial biometric data
     */
    public function storeFacialData($idEmpleado, $biometricData)
    {
        try {
            // Check if facial data already exists
            $existing = $this->queryFirst(
                "SELECT ID FROM {$this->tableName} 
                 WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = 'facial' AND ACTIVO = 1",
                [$idEmpleado]
            );

            if ($existing) {
                // Update existing
                return $this->update($existing['ID'], [
                    'BIOMETRIC_DATA' => json_encode($biometricData),
                    'UPDATED_AT' => date('Y-m-d H:i:s')
                ]);
            } else {
                // Insert new
                return $this->insert([
                    'ID_EMPLEADO' => $idEmpleado,
                    'BIOMETRIC_TYPE' => 'facial',
                    'BIOMETRIC_DATA' => json_encode($biometricData)
                ]);
            }
        } catch (Exception $e) {
            error_log("Error storing facial data: " . $e->getMessage());
            throw new Exception("Error al almacenar datos faciales");
        }
    }

    /**
     * Store fingerprint biometric data
     */
    public function storeFingerprintData($idEmpleado, $fingerType, $template)
    {
        try {
            // Check if fingerprint data for this finger already exists
            $existing = $this->queryFirst(
                "SELECT ID FROM {$this->tableName} 
                 WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = 'fingerprint' 
                 AND FINGER_TYPE = ? AND ACTIVO = 1",
                [$idEmpleado, $fingerType]
            );

            if ($existing) {
                // Update existing
                return $this->update($existing['ID'], [
                    'BIOMETRIC_DATA' => $template,
                    'UPDATED_AT' => date('Y-m-d H:i:s')
                ]);
            } else {
                // Insert new
                return $this->insert([
                    'ID_EMPLEADO' => $idEmpleado,
                    'BIOMETRIC_TYPE' => 'fingerprint',
                    'FINGER_TYPE' => $fingerType,
                    'BIOMETRIC_DATA' => $template
                ]);
            }
        } catch (Exception $e) {
            error_log("Error storing fingerprint data: " . $e->getMessage());
            throw new Exception("Error al almacenar datos de huella");
        }
    }

    /**
     * Deactivate biometric data
     */
    public function deactivate($id)
    {
        return $this->softDelete($id);
    }

    /**
     * Deactivate all biometric data for an employee
     */
    public function deactivateAllForEmployee($idEmpleado, $biometricType = null)
    {
        try {
            $sql = "UPDATE {$this->tableName} SET ACTIVO = 0 
                    WHERE ID_EMPLEADO = ?";
            $params = [$idEmpleado];

            if ($biometricType) {
                $sql .= " AND BIOMETRIC_TYPE = ?";
                $params[] = $biometricType;
            }

            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error deactivating biometric data: " . $e->getMessage());
            throw new Exception("Error al desactivar datos biométricos");
        }
    }

    /**
     * Get biometric enrollment statistics
     */
    public function getEnrollmentStats($idEmpresa = null)
    {
        try {
            $sql = "SELECT 
                        BIOMETRIC_TYPE,
                        COUNT(*) as total_enrolled,
                        COUNT(DISTINCT ID_EMPLEADO) as unique_employees
                    FROM {$this->tableName} bd
                    WHERE bd.ACTIVO = 1";

            $params = [];

            if ($idEmpresa) {
                $sql .= " AND bd.ID_EMPLEADO IN (
                            SELECT e.ID_EMPLEADO FROM empleados e
                            JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                            JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                            WHERE s.ID_EMPRESA = ?
                         )";
                $params[] = $idEmpresa;
            }

            $sql .= " GROUP BY BIOMETRIC_TYPE";

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting enrollment stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get employees with biometric data
     */
    public function getEmployeesWithBiometrics($idEmpresa = null, $biometricType = null)
    {
        try {
            $sql = "SELECT DISTINCT
                        e.ID_EMPLEADO,
                        e.NOMBRE,
                        e.APELLIDO,
                        e.DNI,
                        est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
                        s.NOMBRE as SEDE_NOMBRE,
                        bd.BIOMETRIC_TYPE,
                        bd.CREATED_AT,
                        bd.UPDATED_AT
                    FROM empleados e
                    JOIN {$this->tableName} bd ON e.ID_EMPLEADO = bd.ID_EMPLEADO
                    LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                    LEFT JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                    WHERE e.ACTIVO = 1 AND bd.ACTIVO = 1";

            $params = [];

            if ($biometricType) {
                $sql .= " AND bd.BIOMETRIC_TYPE = ?";
                $params[] = $biometricType;
            }

            if ($idEmpresa) {
                $sql .= " AND s.ID_EMPRESA = ?";
                $params[] = $idEmpresa;
            }

            $sql .= " ORDER BY e.NOMBRE, e.APELLIDO";

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting employees with biometrics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get employees without biometric data
     */
    public function getEmployeesWithoutBiometrics($idEmpresa = null)
    {
        try {
            $sql = "SELECT 
                        e.ID_EMPLEADO,
                        e.NOMBRE,
                        e.APELLIDO,
                        e.DNI,
                        est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
                        s.NOMBRE as SEDE_NOMBRE
                    FROM empleados e
                    LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                    LEFT JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                    LEFT JOIN {$this->tableName} bd ON e.ID_EMPLEADO = bd.ID_EMPLEADO AND bd.ACTIVO = 1
                    WHERE e.ACTIVO = 1 AND bd.ID_EMPLEADO IS NULL";

            $params = [];

            if ($idEmpresa) {
                $sql .= " AND s.ID_EMPRESA = ?";
                $params[] = $idEmpresa;
            }

            $sql .= " ORDER BY e.NOMBRE, e.APELLIDO";

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting employees without biometrics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get fingerprint enrollment summary for an employee
     */
    public function getFingerprintSummary($idEmpleado)
    {
        try {
            $sql = "SELECT 
                        FINGER_TYPE,
                        CREATED_AT,
                        UPDATED_AT
                    FROM {$this->tableName}
                    WHERE ID_EMPLEADO = ? 
                    AND BIOMETRIC_TYPE = 'fingerprint' 
                    AND ACTIVO = 1
                    ORDER BY FINGER_TYPE";

            return $this->query($sql, [$idEmpleado]);
        } catch (Exception $e) {
            error_log("Error getting fingerprint summary: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if employee has specific biometric type enrolled
     */
    public function hasEnrolledBiometric($idEmpleado, $biometricType)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->tableName} 
                    WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = ? AND ACTIVO = 1";

            $result = $this->queryFirst($sql, [$idEmpleado, $biometricType]);
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Error checking enrolled biometric: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get biometric data for verification
     */
    public function getVerificationData($idEmpleado, $biometricType)
    {
        try {
            $data = $this->getByEmployee($idEmpleado, $biometricType);
            $result = [];

            foreach ($data as $item) {
                $decodedData = json_decode($item['BIOMETRIC_DATA'], true);
                
                if ($biometricType === 'facial' && isset($decodedData['embeddings'])) {
                    // For facial recognition, return embeddings
                    $result = array_merge($result, $decodedData['embeddings']);
                } elseif ($biometricType === 'fingerprint') {
                    // For fingerprint, return template data
                    $result[] = [
                        'template' => $item['BIOMETRIC_DATA'],
                        'finger_type' => $item['FINGER_TYPE'],
                        'created_at' => $item['CREATED_AT']
                    ];
                }
            }

            return $result;
        } catch (Exception $e) {
            error_log("Error getting verification data: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clean up old biometric data
     */
    public function cleanupOldData($days = 365)
    {
        try {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            
            $sql = "DELETE FROM {$this->tableName} 
                    WHERE ACTIVO = 0 AND UPDATED_AT < ?";

            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute([$cutoffDate]);
            
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Error cleaning up old biometric data: " . $e->getMessage());
            return 0;
        }
    }
}