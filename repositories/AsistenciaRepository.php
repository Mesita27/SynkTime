<?php
/**
 * Asistencia Repository
 * Handles database operations for attendance records
 */

require_once __DIR__ . '/BaseRepository.php';

class AsistenciaRepository extends BaseRepository
{
    protected $tableName = 'asistencias';

    /**
     * Get attendance records by employee and date
     */
    public function getByEmployeeAndDate($idEmpleado, $fecha, $idHorario = null)
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} 
                    WHERE ID_EMPLEADO = ? AND FECHA = ?";
            $params = [$idEmpleado, $fecha];

            if ($idHorario) {
                $sql .= " AND ID_HORARIO = ?";
                $params[] = $idHorario;
            }

            $sql .= " ORDER BY HORA ASC";

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting attendance by employee and date: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get attendance records by employee and date range
     */
    public function getByEmployeeAndDateRange($idEmpleado, $fechaInicio, $fechaFin)
    {
        try {
            $sql = "SELECT a.*, h.NOMBRE as HORARIO_NOMBRE, h.HORA_ENTRADA, h.HORA_SALIDA
                    FROM {$this->tableName} a
                    LEFT JOIN horarios h ON a.ID_HORARIO = h.ID_HORARIO
                    WHERE a.ID_EMPLEADO = ? 
                    AND a.FECHA BETWEEN ? AND ?
                    ORDER BY a.FECHA DESC, a.HORA DESC";

            return $this->query($sql, [$idEmpleado, $fechaInicio, $fechaFin]);
        } catch (Exception $e) {
            error_log("Error getting attendance by date range: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent attendance records for an employee
     */
    public function getRecentByEmployee($idEmpleado, $limit = 10)
    {
        try {
            $sql = "SELECT a.*, h.NOMBRE as HORARIO_NOMBRE
                    FROM {$this->tableName} a
                    LEFT JOIN horarios h ON a.ID_HORARIO = h.ID_HORARIO
                    WHERE a.ID_EMPLEADO = ?
                    ORDER BY a.FECHA DESC, a.HORA DESC
                    LIMIT ?";

            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$idEmpleado, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting recent attendance: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get attendance statistics for a period
     */
    public function getStatsByPeriod($fechaInicio, $fechaFin, $idEmpresa = null)
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_registros,
                        COUNT(CASE WHEN TIPO = 'ENTRADA' THEN 1 END) as total_entradas,
                        COUNT(CASE WHEN TIPO = 'SALIDA' THEN 1 END) as total_salidas,
                        COUNT(CASE WHEN VERIFICATION_METHOD = 'facial' THEN 1 END) as facial_count,
                        COUNT(CASE WHEN VERIFICATION_METHOD = 'fingerprint' THEN 1 END) as fingerprint_count,
                        COUNT(CASE WHEN VERIFICATION_METHOD = 'traditional' THEN 1 END) as traditional_count,
                        COUNT(CASE WHEN TARDANZA != 'N' AND TARDANZA > 0 THEN 1 END) as tardanzas_count,
                        AVG(CASE WHEN TARDANZA != 'N' AND TARDANZA > 0 THEN TARDANZA END) as promedio_tardanza
                    FROM {$this->tableName} a";

            $params = [$fechaInicio, $fechaFin];

            if ($idEmpresa) {
                $sql .= " JOIN empleados e ON a.ID_EMPLEADO = e.ID_EMPLEADO
                         JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                         JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                         WHERE a.FECHA BETWEEN ? AND ? AND s.ID_EMPRESA = ?";
                $params[] = $idEmpresa;
            } else {
                $sql .= " WHERE a.FECHA BETWEEN ? AND ?";
            }

            return $this->queryFirst($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting attendance stats: " . $e->getMessage());
            return [
                'total_registros' => 0,
                'total_entradas' => 0,
                'total_salidas' => 0,
                'facial_count' => 0,
                'fingerprint_count' => 0,
                'traditional_count' => 0,
                'tardanzas_count' => 0,
                'promedio_tardanza' => 0
            ];
        }
    }

    /**
     * Get attendance records with employee details
     */
    public function getWithEmployeeDetails($fechaInicio, $fechaFin, $idEmpresa = null, $limit = null, $offset = null)
    {
        try {
            $sql = "SELECT a.*, 
                           e.NOMBRE as EMPLEADO_NOMBRE, 
                           e.APELLIDO as EMPLEADO_APELLIDO,
                           e.DNI as EMPLEADO_DNI,
                           h.NOMBRE as HORARIO_NOMBRE,
                           est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
                           s.NOMBRE as SEDE_NOMBRE
                    FROM {$this->tableName} a
                    JOIN empleados e ON a.ID_EMPLEADO = e.ID_EMPLEADO
                    LEFT JOIN horarios h ON a.ID_HORARIO = h.ID_HORARIO
                    LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                    LEFT JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                    WHERE a.FECHA BETWEEN ? AND ?";

            $params = [$fechaInicio, $fechaFin];

            if ($idEmpresa) {
                $sql .= " AND s.ID_EMPRESA = ?";
                $params[] = $idEmpresa;
            }

            $sql .= " ORDER BY a.FECHA DESC, a.HORA DESC";

            if ($limit) {
                $sql .= " LIMIT ?";
                $params[] = $limit;
                
                if ($offset) {
                    $sql .= " OFFSET ?";
                    $params[] = $offset;
                }
            }

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting attendance with employee details: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get tardiness report for a period
     */
    public function getTardinessReport($fechaInicio, $fechaFin, $idEmpresa = null)
    {
        try {
            $sql = "SELECT 
                        e.ID_EMPLEADO,
                        e.NOMBRE,
                        e.APELLIDO,
                        e.DNI,
                        COUNT(CASE WHEN a.TARDANZA != 'N' AND a.TARDANZA > 0 THEN 1 END) as total_tardanzas,
                        SUM(CASE WHEN a.TARDANZA != 'N' AND a.TARDANZA > 0 THEN a.TARDANZA ELSE 0 END) as minutos_tardanza,
                        AVG(CASE WHEN a.TARDANZA != 'N' AND a.TARDANZA > 0 THEN a.TARDANZA END) as promedio_tardanza
                    FROM empleados e
                    LEFT JOIN {$this->tableName} a ON e.ID_EMPLEADO = a.ID_EMPLEADO 
                        AND a.FECHA BETWEEN ? AND ? 
                        AND a.TIPO = 'ENTRADA'";

            $params = [$fechaInicio, $fechaFin];

            if ($idEmpresa) {
                $sql .= " JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                         JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                         WHERE s.ID_EMPRESA = ? AND e.ACTIVO = 1";
                $params[] = $idEmpresa;
            } else {
                $sql .= " WHERE e.ACTIVO = 1";
            }

            $sql .= " GROUP BY e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, e.DNI
                     HAVING total_tardanzas > 0
                     ORDER BY total_tardanzas DESC, minutos_tardanza DESC";

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting tardiness report: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get verification method usage statistics
     */
    public function getVerificationMethodStats($fechaInicio, $fechaFin, $idEmpresa = null)
    {
        try {
            $sql = "SELECT 
                        VERIFICATION_METHOD,
                        COUNT(*) as usage_count,
                        COUNT(DISTINCT a.ID_EMPLEADO) as unique_employees,
                        ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM {$this->tableName} a2 WHERE a2.FECHA BETWEEN ? AND ?";

            $params = [$fechaInicio, $fechaFin, $fechaInicio, $fechaFin];

            if ($idEmpresa) {
                $sql .= " AND a2.ID_EMPLEADO IN (
                            SELECT e.ID_EMPLEADO FROM empleados e
                            JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                            JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                            WHERE s.ID_EMPRESA = ?
                         )";
                $params[] = $idEmpresa;
            }

            $sql .= ")), 2) as percentage
                    FROM {$this->tableName} a
                    WHERE a.FECHA BETWEEN ? AND ?";

            $params = array_merge($params, [$fechaInicio, $fechaFin]);

            if ($idEmpresa) {
                $sql .= " AND a.ID_EMPLEADO IN (
                            SELECT e.ID_EMPLEADO FROM empleados e
                            JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                            JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                            WHERE s.ID_EMPRESA = ?
                         )";
                $params[] = $idEmpresa;
            }

            $sql .= " GROUP BY VERIFICATION_METHOD
                     ORDER BY usage_count DESC";

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting verification method stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if employee has attendance record for date and schedule
     */
    public function hasAttendanceForDateAndSchedule($idEmpleado, $fecha, $idHorario, $tipo = null)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->tableName} 
                    WHERE ID_EMPLEADO = ? AND FECHA = ? AND ID_HORARIO = ?";
            $params = [$idEmpleado, $fecha, $idHorario];

            if ($tipo) {
                $sql .= " AND TIPO = ?";
                $params[] = $tipo;
            }

            $result = $this->queryFirst($sql, $params);
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Error checking attendance existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get attendance summary for dashboard
     */
    public function getDashboardSummary($idEmpresa, $fecha = null)
    {
        try {
            $fecha = $fecha ?? date('Y-m-d');
            
            $sql = "SELECT 
                        COUNT(DISTINCT CASE WHEN a.TIPO = 'ENTRADA' THEN a.ID_EMPLEADO END) as empleados_entrada,
                        COUNT(DISTINCT CASE WHEN a.TIPO = 'SALIDA' THEN a.ID_EMPLEADO END) as empleados_salida,
                        COUNT(CASE WHEN a.TARDANZA != 'N' AND a.TARDANZA > 0 THEN 1 END) as tardanzas_hoy,
                        COUNT(CASE WHEN a.VERIFICATION_METHOD = 'facial' THEN 1 END) as facial_hoy,
                        COUNT(CASE WHEN a.VERIFICATION_METHOD = 'fingerprint' THEN 1 END) as fingerprint_hoy,
                        COUNT(CASE WHEN a.VERIFICATION_METHOD = 'traditional' THEN 1 END) as traditional_hoy
                    FROM {$this->tableName} a
                    JOIN empleados e ON a.ID_EMPLEADO = e.ID_EMPLEADO
                    JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                    JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                    WHERE a.FECHA = ? AND s.ID_EMPRESA = ?";

            return $this->queryFirst($sql, [$fecha, $idEmpresa]);
        } catch (Exception $e) {
            error_log("Error getting dashboard summary: " . $e->getMessage());
            return [
                'empleados_entrada' => 0,
                'empleados_salida' => 0,
                'tardanzas_hoy' => 0,
                'facial_hoy' => 0,
                'fingerprint_hoy' => 0,
                'traditional_hoy' => 0
            ];
        }
    }
}