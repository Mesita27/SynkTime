<?php
/**
 * EmployeeService
 * Business logic service for employee operations
 */

require_once __DIR__ . '/../repositories/EmpleadoRepository.php';
require_once __DIR__ . '/../repositories/HorarioRepository.php';
require_once __DIR__ . '/../repositories/BiometricDataRepository.php';

class EmployeeService {
    private $empleadoRepo;
    private $horarioRepo;
    private $biometricRepo;
    
    public function __construct($connection) {
        $this->empleadoRepo = new EmpleadoRepository($connection);
        $this->horarioRepo = new HorarioRepository($connection);
        $this->biometricRepo = new BiometricDataRepository($connection);
    }
    
    /**
     * Get employee with schedule and biometric information
     */
    public function getEmployeeFullInfo($employeeId, $date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        $employee = $this->empleadoRepo->getById($employeeId);
        if (!$employee) {
            throw new Exception('Empleado no encontrado');
        }
        
        $schedules = $this->horarioRepo->getActiveSchedulesForEmployee($employeeId, $date);
        $biometricData = $this->biometricRepo->getByEmployee($employeeId);
        
        return [
            'employee' => $employee,
            'schedules' => $schedules,
            'biometric_data' => $biometricData,
            'has_fingerprint' => $this->biometricRepo->hasData($employeeId, 'fingerprint'),
            'has_facial' => $this->biometricRepo->hasData($employeeId, 'facial'),
            'has_active_schedule' => !empty($schedules)
        ];
    }
    
    /**
     * Search employees with filters
     */
    public function searchEmployees($criteria = []) {
        return $this->empleadoRepo->search($criteria);
    }
    
    /**
     * Get employees for autocomplete
     */
    public function getEmployeesForAutocomplete($query, $empresaId = null, $limit = 20) {
        return $this->empleadoRepo->getForAutocomplete($query, $empresaId, $limit);
    }
    
    /**
     * Validate employee can use biometric verification
     */
    public function canUseBiometric($employeeId, $method) {
        $employee = $this->empleadoRepo->getById($employeeId);
        if (!$employee) {
            return ['can_use' => false, 'reason' => 'Empleado no encontrado'];
        }
        
        if (!$this->horarioRepo->hasActiveSchedule($employeeId)) {
            return ['can_use' => false, 'reason' => 'Sin horarios activos'];
        }
        
        if ($method === 'fingerprint') {
            $hasFingerprint = $this->biometricRepo->hasData($employeeId, 'fingerprint');
            if (!$hasFingerprint) {
                return ['can_use' => false, 'reason' => 'Sin huellas registradas'];
            }
        } elseif ($method === 'facial') {
            $hasFacial = $this->biometricRepo->hasData($employeeId, 'facial');
            if (!$hasFacial) {
                return ['can_use' => false, 'reason' => 'Sin patrón facial registrado'];
            }
        }
        
        return ['can_use' => true];
    }
    
    /**
     * Get employee biometric enrollment status
     */
    public function getBiometricStatus($employeeId) {
        $biometricData = $this->biometricRepo->getByEmployee($employeeId);
        
        $status = [
            'fingerprint' => [
                'enrolled' => false,
                'count' => 0,
                'fingers' => []
            ],
            'facial' => [
                'enrolled' => false,
                'count' => 0,
                'last_updated' => null
            ]
        ];
        
        foreach ($biometricData as $data) {
            if ($data['BIOMETRIC_TYPE'] === 'fingerprint') {
                $status['fingerprint']['enrolled'] = true;
                $status['fingerprint']['count']++;
                $status['fingerprint']['fingers'][] = [
                    'id' => $data['ID'],
                    'finger_type' => $data['FINGER_TYPE'],
                    'created_at' => $data['CREATED_AT'],
                    'updated_at' => $data['UPDATED_AT']
                ];
            } elseif ($data['BIOMETRIC_TYPE'] === 'facial') {
                $status['facial']['enrolled'] = true;
                $status['facial']['count']++;
                $status['facial']['last_updated'] = $data['UPDATED_AT'];
            }
        }
        
        return $status;
    }
    
    /**
     * Get employee schedule for specific date
     */
    public function getEmployeeSchedule($employeeId, $date) {
        return $this->horarioRepo->getActiveSchedulesForEmployee($employeeId, $date);
    }
    
    /**
     * Get enrollment statistics by location
     */
    public function getEnrollmentStats($filters = []) {
        $employees = $this->empleadoRepo->search($filters);
        $stats = [
            'total_employees' => count($employees),
            'fingerprint_enrolled' => 0,
            'facial_enrolled' => 0,
            'fully_enrolled' => 0,
            'not_enrolled' => 0
        ];
        
        foreach ($employees as $employee) {
            $status = $this->getBiometricStatus($employee['ID_EMPLEADO']);
            
            if ($status['fingerprint']['enrolled']) {
                $stats['fingerprint_enrolled']++;
            }
            
            if ($status['facial']['enrolled']) {
                $stats['facial_enrolled']++;
            }
            
            if ($status['fingerprint']['enrolled'] && $status['facial']['enrolled']) {
                $stats['fully_enrolled']++;
            } elseif (!$status['fingerprint']['enrolled'] && !$status['facial']['enrolled']) {
                $stats['not_enrolled']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Get employees ready for biometric enrollment
     */
    public function getEmployeesForEnrollment($filters = []) {
        $employees = $this->empleadoRepo->search($filters);
        $result = [];
        
        foreach ($employees as $employee) {
            $hasSchedule = $this->horarioRepo->hasActiveSchedule($employee['ID_EMPLEADO']);
            $biometricStatus = $this->getBiometricStatus($employee['ID_EMPLEADO']);
            
            $employee['has_schedule'] = $hasSchedule;
            $employee['biometric_status'] = $biometricStatus;
            $employee['enrollment_ready'] = $hasSchedule;
            
            $result[] = $employee;
        }
        
        return $result;
    }
    
    /**
     * Validate employee data for attendance
     */
    public function validateForAttendance($employeeId) {
        $employee = $this->empleadoRepo->getById($employeeId);
        
        if (!$employee) {
            return ['valid' => false, 'reason' => 'Empleado no encontrado'];
        }
        
        if (!$employee['ACTIVO']) {
            return ['valid' => false, 'reason' => 'Empleado inactivo'];
        }
        
        $hasSchedule = $this->horarioRepo->hasActiveSchedule($employeeId);
        if (!$hasSchedule) {
            return ['valid' => false, 'reason' => 'Sin horarios asignados'];
        }
        
        return ['valid' => true, 'employee' => $employee];
    }
}
?>