<?php
/**
 * Employee Service
 * Handles employee lookups and schedule resolution
 */

class EmployeeService
{
    private $empleadoRepo;
    private $horarioRepo;
    private $biometricDataRepo;

    public function __construct($empleadoRepo, $horarioRepo, $biometricDataRepo)
    {
        $this->empleadoRepo = $empleadoRepo;
        $this->horarioRepo = $horarioRepo;
        $this->biometricDataRepo = $biometricDataRepo;
    }

    /**
     * Get employee by ID with complete information
     */
    public function getEmployeeById($idEmpleado)
    {
        try {
            $empleado = $this->empleadoRepo->getById($idEmpleado);
            
            if (!$empleado) {
                return null;
            }

            // Add biometric enrollment status
            $empleado['biometric_status'] = $this->getBiometricStatus($idEmpleado);
            
            // Add current schedule info
            $empleado['current_schedule'] = $this->getCurrentSchedule($idEmpleado);
            
            return $empleado;

        } catch (Exception $e) {
            error_log("Error getting employee by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Search employees by various criteria
     */
    public function searchEmployees($criteria)
    {
        try {
            $employees = $this->empleadoRepo->search($criteria);
            
            // Add biometric status for each employee
            foreach ($employees as &$employee) {
                $employee['biometric_status'] = $this->getBiometricStatus($employee['ID_EMPLEADO']);
            }
            
            return $employees;

        } catch (Exception $e) {
            error_log("Error searching employees: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get employees by company/sede/establecimiento
     */
    public function getEmployeesByCompany($idEmpresa, $idSede = null, $idEstablecimiento = null)
    {
        try {
            $employees = $this->empleadoRepo->getByCompany($idEmpresa, $idSede, $idEstablecimiento);
            
            // Add biometric enrollment status
            foreach ($employees as &$employee) {
                $employee['biometric_status'] = $this->getBiometricStatus($employee['ID_EMPLEADO']);
            }
            
            return $employees;

        } catch (Exception $e) {
            error_log("Error getting employees by company: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get biometric enrollment status for an employee
     */
    public function getBiometricStatus($idEmpleado)
    {
        try {
            $faceData = $this->biometricDataRepo->getByEmployee($idEmpleado, 'facial');
            $fingerprintData = $this->biometricDataRepo->getByEmployee($idEmpleado, 'fingerprint');
            
            return [
                'face_enrolled' => !empty($faceData),
                'face_count' => count($faceData),
                'fingerprint_enrolled' => !empty($fingerprintData),
                'fingerprint_count' => count($fingerprintData),
                'total_enrolled' => count($faceData) + count($fingerprintData),
                'can_use_biometric' => !empty($faceData) || !empty($fingerprintData)
            ];

        } catch (Exception $e) {
            return [
                'face_enrolled' => false,
                'face_count' => 0,
                'fingerprint_enrolled' => false,
                'fingerprint_count' => 0,
                'total_enrolled' => 0,
                'can_use_biometric' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get current active schedule for an employee
     */
    public function getCurrentSchedule($idEmpleado, $fecha = null)
    {
        try {
            $fecha = $fecha ?? date('Y-m-d');
            $fechaObj = new DateTime($fecha);
            $diaSemana = (int)$fechaObj->format('N'); // 1 = Monday, 7 = Sunday

            $horarios = $this->horarioRepo->getActiveSchedulesForEmployee($idEmpleado, $fecha);

            foreach ($horarios as $horario) {
                $activoHoy = false;
                
                switch ($diaSemana) {
                    case 1: $activoHoy = $horario['LUNES']; break;
                    case 2: $activoHoy = $horario['MARTES']; break;
                    case 3: $activoHoy = $horario['MIERCOLES']; break;
                    case 4: $activoHoy = $horario['JUEVES']; break;
                    case 5: $activoHoy = $horario['VIERNES']; break;
                    case 6: $activoHoy = $horario['SABADO']; break;
                    case 7: $activoHoy = $horario['DOMINGO']; break;
                }

                if ($activoHoy) {
                    return $horario;
                }
            }

            return null;

        } catch (Exception $e) {
            error_log("Error getting current schedule: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get employee schedule for a date range
     */
    public function getScheduleForDateRange($idEmpleado, $fechaInicio, $fechaFin)
    {
        try {
            $schedules = [];
            $current = new DateTime($fechaInicio);
            $end = new DateTime($fechaFin);

            while ($current <= $end) {
                $fecha = $current->format('Y-m-d');
                $schedule = $this->getCurrentSchedule($idEmpleado, $fecha);
                
                if ($schedule) {
                    $schedules[$fecha] = $schedule;
                }
                
                $current->add(new DateInterval('P1D'));
            }

            return $schedules;

        } catch (Exception $e) {
            error_log("Error getting schedule for date range: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate employee for biometric enrollment
     */
    public function validateForEnrollment($idEmpleado)
    {
        try {
            $empleado = $this->empleadoRepo->getById($idEmpleado);
            
            if (!$empleado) {
                return [
                    'valid' => false,
                    'message' => 'Empleado no encontrado'
                ];
            }

            if (!$empleado['ACTIVO']) {
                return [
                    'valid' => false,
                    'message' => 'El empleado no está activo'
                ];
            }

            // Check if employee has any active schedules
            $hasActiveSchedules = $this->horarioRepo->hasActiveSchedules($idEmpleado);
            
            if (!$hasActiveSchedules) {
                return [
                    'valid' => false,
                    'message' => 'El empleado no tiene horarios asignados'
                ];
            }

            return [
                'valid' => true,
                'employee' => $empleado,
                'message' => 'Empleado válido para inscripción biométrica'
            ];

        } catch (Exception $e) {
            return [
                'valid' => false,
                'message' => 'Error de validación: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get employees eligible for biometric enrollment
     */
    public function getEligibleForEnrollment($idEmpresa, $filterMissingBiometrics = true)
    {
        try {
            $employees = $this->getEmployeesByCompany($idEmpresa);
            $eligible = [];

            foreach ($employees as $employee) {
                // Check if employee has active schedules
                $hasSchedules = $this->horarioRepo->hasActiveSchedules($employee['ID_EMPLEADO']);
                
                if (!$hasSchedules) {
                    continue;
                }

                // If filtering for missing biometrics
                if ($filterMissingBiometrics) {
                    $biometricStatus = $employee['biometric_status'];
                    
                    // Include if they don't have any biometric data
                    if (!$biometricStatus['can_use_biometric']) {
                        $eligible[] = $employee;
                    }
                } else {
                    $eligible[] = $employee;
                }
            }

            return $eligible;

        } catch (Exception $e) {
            error_log("Error getting eligible employees: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get employee attendance statistics
     */
    public function getAttendanceStats($idEmpleado, $periodo = '30d')
    {
        try {
            $fechaFin = new DateTime();
            $fechaInicio = clone $fechaFin;
            
            switch ($periodo) {
                case '7d':
                    $fechaInicio->sub(new DateInterval('P7D'));
                    break;
                case '30d':
                    $fechaInicio->sub(new DateInterval('P30D'));
                    break;
                case '90d':
                    $fechaInicio->sub(new DateInterval('P90D'));
                    break;
                default:
                    $fechaInicio->sub(new DateInterval('P30D'));
            }

            // Get attendance records through AsistenciaService
            // This would be injected in a real implementation
            $asistenciaService = new AsistenciaService(
                $this->asistenciaRepo ?? null,
                $this->empleadoRepo,
                $this->horarioRepo,
                $this->holidayRepo ?? null,
                $this->biometricLogsRepo ?? null
            );
            
            return $asistenciaService->getAttendanceSummary(
                $idEmpleado, 
                $fechaInicio->format('Y-m-d'), 
                $fechaFin->format('Y-m-d')
            );

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'total_registros' => 0
            ];
        }
    }

    /**
     * Get employee's biometric data summary
     */
    public function getBiometricDataSummary($idEmpleado)
    {
        try {
            $faceData = $this->biometricDataRepo->getByEmployee($idEmpleado, 'facial');
            $fingerprintData = $this->biometricDataRepo->getByEmployee($idEmpleado, 'fingerprint');
            
            $summary = [
                'face_enrollment' => [
                    'enrolled' => !empty($faceData),
                    'count' => count($faceData),
                    'last_update' => null
                ],
                'fingerprint_enrollment' => [
                    'enrolled' => !empty($fingerprintData),
                    'count' => count($fingerprintData),
                    'fingers' => [],
                    'last_update' => null
                ]
            ];

            // Process face data
            if (!empty($faceData)) {
                $latest = max(array_column($faceData, 'UPDATED_AT'));
                $summary['face_enrollment']['last_update'] = $latest;
            }

            // Process fingerprint data
            if (!empty($fingerprintData)) {
                $latest = max(array_column($fingerprintData, 'UPDATED_AT'));
                $summary['fingerprint_enrollment']['last_update'] = $latest;
                
                foreach ($fingerprintData as $fp) {
                    $summary['fingerprint_enrollment']['fingers'][] = $fp['FINGER_TYPE'];
                }
            }

            return $summary;

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'face_enrollment' => ['enrolled' => false, 'count' => 0],
                'fingerprint_enrollment' => ['enrolled' => false, 'count' => 0, 'fingers' => []]
            ];
        }
    }

    /**
     * Update employee's last activity timestamp
     */
    public function updateLastActivity($idEmpleado)
    {
        try {
            return $this->empleadoRepo->updateLastActivity($idEmpleado);
        } catch (Exception $e) {
            error_log("Error updating employee last activity: " . $e->getMessage());
            return false;
        }
    }
}