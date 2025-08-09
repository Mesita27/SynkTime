<?php
/**
 * AsistenciaService
 * Business logic service for attendance management
 */

require_once __DIR__ . '/../repositories/AsistenciaRepository.php';
require_once __DIR__ . '/../repositories/HorarioRepository.php';
require_once __DIR__ . '/../repositories/HolidayRepository.php';
require_once __DIR__ . '/../repositories/EmpleadoRepository.php';
require_once __DIR__ . '/../repositories/BiometricLogsRepository.php';
require_once __DIR__ . '/../config/biometrics.php';

class AsistenciaService {
    private $asistenciaRepo;
    private $horarioRepo;
    private $holidayRepo;
    private $empleadoRepo;
    private $logsRepo;
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->asistenciaRepo = new AsistenciaRepository($connection);
        $this->horarioRepo = new HorarioRepository($connection);
        $this->holidayRepo = new HolidayRepository($connection);
        $this->empleadoRepo = new EmpleadoRepository($connection);
        $this->logsRepo = new BiometricLogsRepository($connection);
    }
    
    /**
     * Register attendance with full business logic
     */
    public function registerAttendance($employeeId, $verificationMethod, $imageData = null, $biometricData = null) {
        // Validate employee
        $employee = $this->empleadoRepo->getById($employeeId);
        if (!$employee) {
            throw new Exception('Empleado no encontrado o inactivo');
        }
        
        // Get current date/time in Colombia timezone
        $now = new DateTime('now', new DateTimeZone('America/Bogota'));
        $fecha = $now->format('Y-m-d');
        $hora = $now->format('H:i:s');
        
        // Get active schedules for today
        $schedules = $this->horarioRepo->getActiveSchedulesForEmployee($employeeId, $fecha);
        if (empty($schedules)) {
            throw new Exception('El empleado no tiene horarios activos para hoy');
        }
        
        // Determine attendance type and schedule
        $attendanceInfo = $this->determineAttendanceType($employeeId, $schedules, $fecha);
        
        // Check for holidays
        $isHoliday = $this->holidayRepo->isHoliday($fecha, $employee['ID_EMPRESA']);
        
        // Calculate tardiness
        $tardanza = $this->calculateTardiness(
            $attendanceInfo['tipo'], 
            $attendanceInfo['horario'], 
            $hora, 
            $fecha, 
            $isHoliday
        );
        
        // Handle photo storage
        $fotoPath = $this->handlePhotoStorage($imageData, $employeeId, $attendanceInfo['tipo'], $verificationMethod);
        
        // Create attendance record
        $attendanceData = [
            'id_empleado' => $employeeId,
            'fecha' => $fecha,
            'hora' => $hora,
            'tipo' => $attendanceInfo['tipo'],
            'tardanza' => $tardanza,
            'foto' => $fotoPath,
            'id_horario' => $attendanceInfo['horario']['ID_HORARIO'],
            'verification_method' => $verificationMethod,
            'registro_manual' => 'N'
        ];
        
        $success = $this->asistenciaRepo->create($attendanceData);
        
        if (!$success) {
            throw new Exception('Error al registrar la asistencia');
        }
        
        // Log biometric operation
        $this->logBiometricOperation($employeeId, $verificationMethod, true, $biometricData);
        
        return [
            'success' => true,
            'tipo' => $attendanceInfo['tipo'],
            'hora' => $hora,
            'tardanza' => $tardanza,
            'es_feriado' => $isHoliday,
            'horario' => $attendanceInfo['horario']['NOMBRE'],
            'empleado' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO']
        ];
    }
    
    /**
     * Determine attendance type (ENTRADA/SALIDA) and which schedule to use
     */
    private function determineAttendanceType($employeeId, $schedules, $fecha) {
        foreach ($schedules as $schedule) {
            $count = $this->asistenciaRepo->countByEmployeeScheduleDate(
                $employeeId, 
                $schedule['ID_HORARIO'], 
                $fecha
            );
            
            if ($count['entradas'] == 0) {
                return [
                    'tipo' => 'ENTRADA',
                    'horario' => $schedule
                ];
            } elseif ($count['salidas'] == 0) {
                return [
                    'tipo' => 'SALIDA',
                    'horario' => $schedule
                ];
            }
        }
        
        throw new Exception('No se puede determinar el tipo de registro para este empleado');
    }
    
    /**
     * Calculate tardiness
     */
    private function calculateTardiness($tipo, $horario, $hora, $fecha, $isHoliday) {
        // If it's a holiday, no tardiness
        if ($isHoliday) {
            return 'N';
        }
        
        // Only calculate tardiness for entries
        if ($tipo !== 'ENTRADA') {
            return 'N';
        }
        
        $entryTime = new DateTime($fecha . ' ' . $horario['HORA_ENTRADA']);
        $tolerance = intval($horario['TOLERANCIA'] ?? 0);
        $entryTime->add(new DateInterval("PT{$tolerance}M"));
        
        $actualTime = new DateTime($fecha . ' ' . $hora);
        
        return $actualTime > $entryTime ? 'S' : 'N';
    }
    
    /**
     * Handle photo storage based on verification method
     */
    private function handlePhotoStorage($imageData, $employeeId, $tipo, $verificationMethod) {
        if (!$imageData) {
            if ($verificationMethod === 'fingerprint') {
                return FINGERPRINT_PLACEHOLDER;
            }
            return null;
        }
        
        // Create upload directory if it doesn't exist
        $uploadDir = ATTENDANCE_UPLOAD_PATH;
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Clean image data
        $imageData = preg_replace('/^data:image\/[^;]+;base64,/', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);
        $imageBinary = base64_decode($imageData);
        
        if ($imageBinary === false) {
            throw new Exception('Datos de imagen inválidos');
        }
        
        // Generate filename
        $timestamp = date('Ymd_His');
        $prefix = $verificationMethod === 'facial' ? 'facial' : 'trad';
        $filename = "{$prefix}_{$employeeId}_{$timestamp}.jpg";
        $filepath = $uploadDir . $filename;
        
        if (file_put_contents($filepath, $imageBinary) === false) {
            throw new Exception('Error al guardar la imagen');
        }
        
        return 'uploads/asistencia/' . $filename;
    }
    
    /**
     * Log biometric operation
     */
    private function logBiometricOperation($employeeId, $verificationMethod, $success, $biometricData = null) {
        $logData = [
            'id_empleado' => $employeeId,
            'verification_method' => $verificationMethod,
            'verification_success' => $success,
            'operation_type' => 'verification',
            'fecha' => date('Y-m-d'),
            'hora' => date('H:i:s')
        ];
        
        // Add confidence score if available
        if ($biometricData && isset($biometricData['confidence'])) {
            $logData['confidence_score'] = min(1.0, max(0.0, $biometricData['confidence']));
        }
        
        // Add API source
        if ($verificationMethod === 'facial') {
            $logData['api_source'] = 'insightface-rest';
        } elseif ($verificationMethod === 'fingerprint') {
            $logData['api_source'] = 'sourceafis';
        } else {
            $logData['api_source'] = 'traditional';
        }
        
        $this->logsRepo->log($logData);
    }
    
    /**
     * Get employee attendance summary for date
     */
    public function getEmployeeAttendanceSummary($employeeId, $fecha) {
        $attendances = $this->asistenciaRepo->getByEmployeeAndDate($employeeId, $fecha);
        $schedules = $this->horarioRepo->getActiveSchedulesForEmployee($employeeId, $fecha);
        
        return [
            'attendances' => $attendances,
            'schedules' => $schedules,
            'total_entries' => count(array_filter($attendances, fn($a) => $a['TIPO'] === 'ENTRADA')),
            'total_exits' => count(array_filter($attendances, fn($a) => $a['TIPO'] === 'SALIDA')),
            'has_tardiness' => count(array_filter($attendances, fn($a) => $a['TARDANZA'] === 'S')) > 0
        ];
    }
    
    /**
     * Validate attendance registration is possible
     */
    public function canRegisterAttendance($employeeId, $fecha = null) {
        if (!$fecha) {
            $fecha = date('Y-m-d');
        }
        
        $employee = $this->empleadoRepo->getById($employeeId);
        if (!$employee) {
            return ['can_register' => false, 'reason' => 'Empleado no encontrado'];
        }
        
        $schedules = $this->horarioRepo->getActiveSchedulesForEmployee($employeeId, $fecha);
        if (empty($schedules)) {
            return ['can_register' => false, 'reason' => 'Sin horarios activos'];
        }
        
        try {
            $this->determineAttendanceType($employeeId, $schedules, $fecha);
            return ['can_register' => true];
        } catch (Exception $e) {
            return ['can_register' => false, 'reason' => $e->getMessage()];
        }
    }
}
?>