<?php
/**
 * Horario Repository
 * Handles database operations for employee schedules
 */

require_once __DIR__ . '/BaseRepository.php';

class HorarioRepository extends BaseRepository
{
    protected $tableName = 'horarios';

    /**
     * Get active schedules for an employee on a specific date
     */
    public function getActiveSchedulesForEmployee($idEmpleado, $fecha)
    {
        try {
            $sql = "SELECT h.*, eh.FECHA_INICIO, eh.FECHA_FIN
                    FROM {$this->tableName} h
                    JOIN empleado_horarios eh ON h.ID_HORARIO = eh.ID_HORARIO
                    WHERE eh.ID_EMPLEADO = ? 
                    AND eh.ACTIVO = 1 
                    AND h.ACTIVO = 1
                    AND (eh.FECHA_FIN IS NULL OR eh.FECHA_FIN >= ?)
                    AND eh.FECHA_INICIO <= ?
                    ORDER BY h.HORA_ENTRADA ASC";

            return $this->query($sql, [$idEmpleado, $fecha, $fecha]);
        } catch (Exception $e) {
            error_log("Error getting active schedules for employee: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if employee has active schedules
     */
    public function hasActiveSchedules($idEmpleado)
    {
        try {
            $sql = "SELECT COUNT(*) as count
                    FROM empleado_horarios eh
                    JOIN {$this->tableName} h ON eh.ID_HORARIO = h.ID_HORARIO
                    WHERE eh.ID_EMPLEADO = ? 
                    AND eh.ACTIVO = 1 
                    AND h.ACTIVO = 1
                    AND (eh.FECHA_FIN IS NULL OR eh.FECHA_FIN >= CURDATE())";

            $result = $this->queryFirst($sql, [$idEmpleado]);
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Error checking if employee has active schedules: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get schedule by ID with details
     */
    public function getScheduleDetails($idHorario)
    {
        try {
            $sql = "SELECT h.*,
                           COUNT(eh.ID_EMPLEADO) as empleados_asignados,
                           GROUP_CONCAT(DISTINCT CONCAT(e.NOMBRE, ' ', e.APELLIDO) SEPARATOR ', ') as empleados_nombres
                    FROM {$this->tableName} h
                    LEFT JOIN empleado_horarios eh ON h.ID_HORARIO = eh.ID_HORARIO AND eh.ACTIVO = 1
                    LEFT JOIN empleados e ON eh.ID_EMPLEADO = e.ID_EMPLEADO AND e.ACTIVO = 1
                    WHERE h.ID_HORARIO = ?
                    GROUP BY h.ID_HORARIO";

            return $this->queryFirst($sql, [$idHorario]);
        } catch (Exception $e) {
            error_log("Error getting schedule details: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all schedules with employee count
     */
    public function getAllWithEmployeeCount($idEmpresa = null)
    {
        try {
            $sql = "SELECT h.*,
                           COUNT(DISTINCT eh.ID_EMPLEADO) as empleados_asignados
                    FROM {$this->tableName} h
                    LEFT JOIN empleado_horarios eh ON h.ID_HORARIO = eh.ID_HORARIO AND eh.ACTIVO = 1
                    LEFT JOIN empleados e ON eh.ID_EMPLEADO = e.ID_EMPLEADO";

            $params = [];

            if ($idEmpresa) {
                $sql .= " LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                         LEFT JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                         WHERE h.ACTIVO = 1 AND s.ID_EMPRESA = ?";
                $params[] = $idEmpresa;
            } else {
                $sql .= " WHERE h.ACTIVO = 1";
            }

            $sql .= " GROUP BY h.ID_HORARIO
                     ORDER BY h.NOMBRE";

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting schedules with employee count: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get employees assigned to a schedule
     */
    public function getAssignedEmployees($idHorario)
    {
        try {
            $sql = "SELECT e.*, eh.FECHA_INICIO, eh.FECHA_FIN,
                           est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
                           s.NOMBRE as SEDE_NOMBRE
                    FROM empleados e
                    JOIN empleado_horarios eh ON e.ID_EMPLEADO = eh.ID_EMPLEADO
                    LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                    LEFT JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                    WHERE eh.ID_HORARIO = ? 
                    AND eh.ACTIVO = 1 
                    AND e.ACTIVO = 1
                    ORDER BY e.NOMBRE, e.APELLIDO";

            return $this->query($sql, [$idHorario]);
        } catch (Exception $e) {
            error_log("Error getting assigned employees: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Assign employee to schedule
     */
    public function assignEmployee($idHorario, $idEmpleado, $fechaInicio = null, $fechaFin = null)
    {
        try {
            $fechaInicio = $fechaInicio ?? date('Y-m-d');

            // Check if assignment already exists
            $existing = $this->queryFirst(
                "SELECT ID FROM empleado_horarios 
                 WHERE ID_HORARIO = ? AND ID_EMPLEADO = ? AND ACTIVO = 1",
                [$idHorario, $idEmpleado]
            );

            if ($existing) {
                throw new Exception("El empleado ya está asignado a este horario");
            }

            // Insert new assignment
            $sql = "INSERT INTO empleado_horarios (ID_HORARIO, ID_EMPLEADO, FECHA_INICIO, FECHA_FIN, ACTIVO)
                    VALUES (?, ?, ?, ?, 1)";

            $stmt = $this->connection->prepare($sql);
            return $stmt->execute([$idHorario, $idEmpleado, $fechaInicio, $fechaFin]);
        } catch (Exception $e) {
            error_log("Error assigning employee to schedule: " . $e->getMessage());
            throw new Exception("Error al asignar empleado al horario: " . $e->getMessage());
        }
    }

    /**
     * Remove employee from schedule
     */
    public function removeEmployee($idHorario, $idEmpleado, $fechaFin = null)
    {
        try {
            $fechaFin = $fechaFin ?? date('Y-m-d');

            $sql = "UPDATE empleado_horarios 
                    SET FECHA_FIN = ?, ACTIVO = 0 
                    WHERE ID_HORARIO = ? AND ID_EMPLEADO = ? AND ACTIVO = 1";

            $stmt = $this->connection->prepare($sql);
            return $stmt->execute([$fechaFin, $idHorario, $idEmpleado]);
        } catch (Exception $e) {
            error_log("Error removing employee from schedule: " . $e->getMessage());
            throw new Exception("Error al remover empleado del horario: " . $e->getMessage());
        }
    }

    /**
     * Get schedule conflicts for an employee
     */
    public function getScheduleConflicts($idEmpleado, $fechaInicio, $fechaFin = null)
    {
        try {
            $sql = "SELECT h.*, eh.FECHA_INICIO, eh.FECHA_FIN
                    FROM {$this->tableName} h
                    JOIN empleado_horarios eh ON h.ID_HORARIO = eh.ID_HORARIO
                    WHERE eh.ID_EMPLEADO = ? 
                    AND eh.ACTIVO = 1 
                    AND h.ACTIVO = 1
                    AND eh.FECHA_INICIO <= ?";

            $params = [$idEmpleado, $fechaFin ?? $fechaInicio];

            if ($fechaFin) {
                $sql .= " AND (eh.FECHA_FIN IS NULL OR eh.FECHA_FIN >= ?)";
                $params[] = $fechaInicio;
            } else {
                $sql .= " AND (eh.FECHA_FIN IS NULL OR eh.FECHA_FIN >= ?)";
                $params[] = $fechaInicio;
            }

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting schedule conflicts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get schedules for a specific day of week
     */
    public function getByDayOfWeek($dayOfWeek, $idEmpresa = null)
    {
        try {
            $dayColumn = match($dayOfWeek) {
                1 => 'LUNES',
                2 => 'MARTES',
                3 => 'MIERCOLES',
                4 => 'JUEVES',
                5 => 'VIERNES',
                6 => 'SABADO',
                7 => 'DOMINGO',
                default => 'LUNES'
            };

            $sql = "SELECT DISTINCT h.*,
                           COUNT(DISTINCT eh.ID_EMPLEADO) as empleados_activos
                    FROM {$this->tableName} h
                    LEFT JOIN empleado_horarios eh ON h.ID_HORARIO = eh.ID_HORARIO AND eh.ACTIVO = 1
                    LEFT JOIN empleados e ON eh.ID_EMPLEADO = e.ID_EMPLEADO AND e.ACTIVO = 1
                    WHERE h.ACTIVO = 1 AND h.{$dayColumn} = 1";

            $params = [];

            if ($idEmpresa) {
                $sql .= " AND e.ID_EMPLEADO IN (
                            SELECT emp.ID_EMPLEADO FROM empleados emp
                            JOIN establecimientos est ON emp.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                            JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                            WHERE s.ID_EMPRESA = ?
                         )";
                $params[] = $idEmpresa;
            }

            $sql .= " GROUP BY h.ID_HORARIO
                     ORDER BY h.HORA_ENTRADA";

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting schedules by day of week: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get schedule statistics
     */
    public function getStats($idEmpresa = null)
    {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT h.ID_HORARIO) as total_horarios,
                        COUNT(DISTINCT h.ID_HORARIO) FILTER (WHERE h.ACTIVO = 1) as horarios_activos,
                        COUNT(DISTINCT eh.ID_EMPLEADO) as empleados_con_horario,
                        AVG(TIME_TO_SEC(TIMEDIFF(h.HORA_SALIDA, h.HORA_ENTRADA)) / 3600) as promedio_horas_jornada
                    FROM {$this->tableName} h
                    LEFT JOIN empleado_horarios eh ON h.ID_HORARIO = eh.ID_HORARIO AND eh.ACTIVO = 1
                    LEFT JOIN empleados e ON eh.ID_EMPLEADO = e.ID_EMPLEADO";

            $params = [];

            if ($idEmpresa) {
                $sql .= " LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                         LEFT JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                         WHERE s.ID_EMPRESA = ?";
                $params[] = $idEmpresa;
            }

            return $this->queryFirst($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting schedule stats: " . $e->getMessage());
            return [
                'total_horarios' => 0,
                'horarios_activos' => 0,
                'empleados_con_horario' => 0,
                'promedio_horas_jornada' => 0
            ];
        }
    }

    /**
     * Create new schedule
     */
    public function createSchedule($scheduleData)
    {
        try {
            $requiredFields = ['NOMBRE', 'HORA_ENTRADA', 'HORA_SALIDA'];
            
            foreach ($requiredFields as $field) {
                if (!isset($scheduleData[$field]) || empty($scheduleData[$field])) {
                    throw new Exception("Campo requerido faltante: {$field}");
                }
            }

            // Set default values for days if not provided
            $dayFields = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO'];
            foreach ($dayFields as $day) {
                if (!isset($scheduleData[$day])) {
                    $scheduleData[$day] = 0;
                }
            }

            if (!isset($scheduleData['ACTIVO'])) {
                $scheduleData['ACTIVO'] = 1;
            }

            return $this->insert($scheduleData);
        } catch (Exception $e) {
            error_log("Error creating schedule: " . $e->getMessage());
            throw new Exception("Error al crear horario: " . $e->getMessage());
        }
    }

    /**
     * Update schedule
     */
    public function updateSchedule($idHorario, $scheduleData)
    {
        try {
            return $this->update($idHorario, $scheduleData, 'ID_HORARIO');
        } catch (Exception $e) {
            error_log("Error updating schedule: " . $e->getMessage());
            throw new Exception("Error al actualizar horario: " . $e->getMessage());
        }
    }

    /**
     * Get schedule usage report
     */
    public function getUsageReport($fechaInicio, $fechaFin, $idEmpresa = null)
    {
        try {
            $sql = "SELECT 
                        h.ID_HORARIO,
                        h.NOMBRE,
                        h.HORA_ENTRADA,
                        h.HORA_SALIDA,
                        COUNT(DISTINCT eh.ID_EMPLEADO) as empleados_asignados,
                        COUNT(a.ID_ASISTENCIA) as registros_asistencia,
                        COUNT(CASE WHEN a.TIPO = 'ENTRADA' THEN 1 END) as entradas,
                        COUNT(CASE WHEN a.TIPO = 'SALIDA' THEN 1 END) as salidas
                    FROM {$this->tableName} h
                    LEFT JOIN empleado_horarios eh ON h.ID_HORARIO = eh.ID_HORARIO AND eh.ACTIVO = 1
                    LEFT JOIN asistencias a ON h.ID_HORARIO = a.ID_HORARIO 
                        AND a.FECHA BETWEEN ? AND ?
                    LEFT JOIN empleados e ON eh.ID_EMPLEADO = e.ID_EMPLEADO";

            $params = [$fechaInicio, $fechaFin];

            if ($idEmpresa) {
                $sql .= " LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                         LEFT JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                         WHERE h.ACTIVO = 1 AND s.ID_EMPRESA = ?";
                $params[] = $idEmpresa;
            } else {
                $sql .= " WHERE h.ACTIVO = 1";
            }

            $sql .= " GROUP BY h.ID_HORARIO, h.NOMBRE, h.HORA_ENTRADA, h.HORA_SALIDA
                     ORDER BY h.NOMBRE";

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting usage report: " . $e->getMessage());
            return [];
        }
    }
}