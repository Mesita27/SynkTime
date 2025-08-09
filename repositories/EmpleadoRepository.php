<?php
/**
 * Empleado Repository
 * Handles database operations for employee records
 */

require_once __DIR__ . '/BaseRepository.php';

class EmpleadoRepository extends BaseRepository
{
    protected $tableName = 'empleados';

    /**
     * Get employee by ID with additional information
     */
    public function getById($idEmpleado)
    {
        try {
            $sql = "SELECT e.*, 
                           est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
                           est.ID_ESTABLECIMIENTO,
                           s.NOMBRE as SEDE_NOMBRE,
                           s.ID_SEDE,
                           emp.NOMBRE as EMPRESA_NOMBRE,
                           emp.ID_EMPRESA
                    FROM {$this->tableName} e
                    LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                    LEFT JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                    LEFT JOIN empresas emp ON s.ID_EMPRESA = emp.ID_EMPRESA
                    WHERE e.ID_EMPLEADO = ?";

            return $this->queryFirst($sql, [$idEmpleado]);
        } catch (Exception $e) {
            error_log("Error getting employee by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Search employees by criteria
     */
    public function search($criteria)
    {
        try {
            $conditions = [];
            $params = [];

            $sql = "SELECT e.*, 
                           est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
                           s.NOMBRE as SEDE_NOMBRE,
                           emp.NOMBRE as EMPRESA_NOMBRE
                    FROM {$this->tableName} e
                    LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                    LEFT JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                    LEFT JOIN empresas emp ON s.ID_EMPRESA = emp.ID_EMPRESA
                    WHERE e.ACTIVO = 1";

            // Add search conditions
            if (!empty($criteria['nombre'])) {
                $conditions[] = "(e.NOMBRE LIKE ? OR e.APELLIDO LIKE ?)";
                $searchTerm = '%' . $criteria['nombre'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            if (!empty($criteria['dni'])) {
                $conditions[] = "e.DNI LIKE ?";
                $params[] = '%' . $criteria['dni'] . '%';
            }

            if (!empty($criteria['id_empresa'])) {
                $conditions[] = "emp.ID_EMPRESA = ?";
                $params[] = $criteria['id_empresa'];
            }

            if (!empty($criteria['id_sede'])) {
                $conditions[] = "s.ID_SEDE = ?";
                $params[] = $criteria['id_sede'];
            }

            if (!empty($criteria['id_establecimiento'])) {
                $conditions[] = "e.ID_ESTABLECIMIENTO = ?";
                $params[] = $criteria['id_establecimiento'];
            }

            if (!empty($conditions)) {
                $sql .= " AND " . implode(' AND ', $conditions);
            }

            $sql .= " ORDER BY e.NOMBRE, e.APELLIDO";

            if (!empty($criteria['limit'])) {
                $sql .= " LIMIT " . intval($criteria['limit']);
            }

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error searching employees: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get employees by company hierarchy
     */
    public function getByCompany($idEmpresa, $idSede = null, $idEstablecimiento = null)
    {
        try {
            $sql = "SELECT e.*, 
                           est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
                           s.NOMBRE as SEDE_NOMBRE,
                           emp.NOMBRE as EMPRESA_NOMBRE
                    FROM {$this->tableName} e
                    JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                    JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                    JOIN empresas emp ON s.ID_EMPRESA = emp.ID_EMPRESA
                    WHERE e.ACTIVO = 1 AND emp.ID_EMPRESA = ?";

            $params = [$idEmpresa];

            if ($idSede) {
                $sql .= " AND s.ID_SEDE = ?";
                $params[] = $idSede;
            }

            if ($idEstablecimiento) {
                $sql .= " AND e.ID_ESTABLECIMIENTO = ?";
                $params[] = $idEstablecimiento;
            }

            $sql .= " ORDER BY e.NOMBRE, e.APELLIDO";

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting employees by company: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get active employees for autocomplete
     */
    public function getForAutocomplete($searchTerm, $idEmpresa = null, $limit = 20)
    {
        try {
            $sql = "SELECT e.ID_EMPLEADO,
                           e.NOMBRE,
                           e.APELLIDO,
                           e.DNI,
                           est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
                           CONCAT(e.NOMBRE, ' ', e.APELLIDO, ' - ', e.DNI) as display_name
                    FROM {$this->tableName} e
                    LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                    LEFT JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                    WHERE e.ACTIVO = 1 
                    AND (e.NOMBRE LIKE ? OR e.APELLIDO LIKE ? OR e.DNI LIKE ?)";

            $searchPattern = '%' . $searchTerm . '%';
            $params = [$searchPattern, $searchPattern, $searchPattern];

            if ($idEmpresa) {
                $sql .= " AND s.ID_EMPRESA = ?";
                $params[] = $idEmpresa;
            }

            $sql .= " ORDER BY e.NOMBRE, e.APELLIDO LIMIT ?";
            $params[] = $limit;

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting employees for autocomplete: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if employee exists and is active
     */
    public function isActiveEmployee($idEmpleado)
    {
        try {
            $sql = "SELECT ACTIVO FROM {$this->tableName} WHERE ID_EMPLEADO = ?";
            $result = $this->queryFirst($sql, [$idEmpleado]);
            
            return $result && $result['ACTIVO'] == 1;
        } catch (Exception $e) {
            error_log("Error checking if employee is active: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get employees with current schedules
     */
    public function getWithCurrentSchedules($fecha = null, $idEmpresa = null)
    {
        try {
            $fecha = $fecha ?? date('Y-m-d');
            $diaSemana = (new DateTime($fecha))->format('N'); // 1 = Monday, 7 = Sunday

            $diaColumn = match($diaSemana) {
                '1' => 'LUNES',
                '2' => 'MARTES',
                '3' => 'MIERCOLES',
                '4' => 'JUEVES',
                '5' => 'VIERNES',
                '6' => 'SABADO',
                '7' => 'DOMINGO'
            };

            $sql = "SELECT DISTINCT e.*, 
                           est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
                           s.NOMBRE as SEDE_NOMBRE,
                           h.NOMBRE as HORARIO_NOMBRE,
                           h.HORA_ENTRADA,
                           h.HORA_SALIDA
                    FROM {$this->tableName} e
                    JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                    JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                    JOIN empleado_horarios eh ON e.ID_EMPLEADO = eh.ID_EMPLEADO
                    JOIN horarios h ON eh.ID_HORARIO = h.ID_HORARIO
                    WHERE e.ACTIVO = 1 
                    AND eh.ACTIVO = 1 
                    AND h.ACTIVO = 1
                    AND h.{$diaColumn} = 1
                    AND (eh.FECHA_FIN IS NULL OR eh.FECHA_FIN >= ?)";

            $params = [$fecha];

            if ($idEmpresa) {
                $sql .= " AND s.ID_EMPRESA = ?";
                $params[] = $idEmpresa;
            }

            $sql .= " ORDER BY e.NOMBRE, e.APELLIDO";

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting employees with current schedules: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get employee statistics
     */
    public function getStats($idEmpresa = null)
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_empleados,
                        COUNT(CASE WHEN e.ACTIVO = 1 THEN 1 END) as empleados_activos,
                        COUNT(CASE WHEN e.ACTIVO = 0 THEN 1 END) as empleados_inactivos,
                        COUNT(DISTINCT est.ID_ESTABLECIMIENTO) as establecimientos,
                        COUNT(DISTINCT s.ID_SEDE) as sedes
                    FROM {$this->tableName} e
                    LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                    LEFT JOIN sedes s ON est.ID_SEDE = s.ID_SEDE";

            $params = [];

            if ($idEmpresa) {
                $sql .= " WHERE s.ID_EMPRESA = ?";
                $params[] = $idEmpresa;
            }

            return $this->queryFirst($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting employee stats: " . $e->getMessage());
            return [
                'total_empleados' => 0,
                'empleados_activos' => 0,
                'empleados_inactivos' => 0,
                'establecimientos' => 0,
                'sedes' => 0
            ];
        }
    }

    /**
     * Update last activity timestamp
     */
    public function updateLastActivity($idEmpleado)
    {
        try {
            // Note: This assumes there's a LAST_ACTIVITY column
            // If it doesn't exist, this method won't do anything
            $sql = "UPDATE {$this->tableName} 
                    SET LAST_ACTIVITY = NOW() 
                    WHERE ID_EMPLEADO = ?";

            $stmt = $this->connection->prepare($sql);
            return $stmt->execute([$idEmpleado]);
        } catch (Exception $e) {
            // Silently fail if column doesn't exist
            return true;
        }
    }

    /**
     * Get employees by establishment
     */
    public function getByEstablishment($idEstablecimiento)
    {
        try {
            $sql = "SELECT e.*, 
                           est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
                           s.NOMBRE as SEDE_NOMBRE
                    FROM {$this->tableName} e
                    JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                    JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                    WHERE e.ID_ESTABLECIMIENTO = ? AND e.ACTIVO = 1
                    ORDER BY e.NOMBRE, e.APELLIDO";

            return $this->query($sql, [$idEstablecimiento]);
        } catch (Exception $e) {
            error_log("Error getting employees by establishment: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get employees without active schedules
     */
    public function getWithoutActiveSchedules($idEmpresa = null)
    {
        try {
            $sql = "SELECT e.*, 
                           est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
                           s.NOMBRE as SEDE_NOMBRE
                    FROM {$this->tableName} e
                    LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                    LEFT JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
                    LEFT JOIN empleado_horarios eh ON e.ID_EMPLEADO = eh.ID_EMPLEADO AND eh.ACTIVO = 1
                    WHERE e.ACTIVO = 1 AND eh.ID_EMPLEADO IS NULL";

            $params = [];

            if ($idEmpresa) {
                $sql .= " AND s.ID_EMPRESA = ?";
                $params[] = $idEmpresa;
            }

            $sql .= " ORDER BY e.NOMBRE, e.APELLIDO";

            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Error getting employees without schedules: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get employee profile with complete information
     */
    public function getProfile($idEmpleado)
    {
        try {
            $employee = $this->getById($idEmpleado);
            
            if (!$employee) {
                return null;
            }

            // Add schedule information
            $sql = "SELECT h.*, eh.FECHA_INICIO, eh.FECHA_FIN
                    FROM empleado_horarios eh
                    JOIN horarios h ON eh.ID_HORARIO = h.ID_HORARIO
                    WHERE eh.ID_EMPLEADO = ? AND eh.ACTIVO = 1
                    ORDER BY eh.FECHA_INICIO DESC";

            $schedules = $this->query($sql, [$idEmpleado]);
            $employee['horarios'] = $schedules;

            return $employee;
        } catch (Exception $e) {
            error_log("Error getting employee profile: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate employee data for enrollment
     */
    public function validateForEnrollment($idEmpleado)
    {
        try {
            $employee = $this->getById($idEmpleado);
            
            if (!$employee) {
                return ['valid' => false, 'message' => 'Empleado no encontrado'];
            }

            if (!$employee['ACTIVO']) {
                return ['valid' => false, 'message' => 'Empleado inactivo'];
            }

            return ['valid' => true, 'employee' => $employee];
        } catch (Exception $e) {
            return ['valid' => false, 'message' => 'Error de validación: ' . $e->getMessage()];
        }
    }
}