<?php
/**
 * Attendance Service
 * Handles attendance registration logic, tardiness calculation, and file storage
 */

class AsistenciaService
{
    private $config;
    private $asistenciaRepo;
    private $empleadoRepo;
    private $horarioRepo;
    private $holidayRepo;
    private $biometricLogsRepo;

    public function __construct($asistenciaRepo, $empleadoRepo, $horarioRepo, $holidayRepo, $biometricLogsRepo)
    {
        $this->config = require __DIR__ . '/../config/biometrics.php';
        $this->asistenciaRepo = $asistenciaRepo;
        $this->empleadoRepo = $empleadoRepo;
        $this->horarioRepo = $horarioRepo;
        $this->holidayRepo = $holidayRepo;
        $this->biometricLogsRepo = $biometricLogsRepo;
    }

    /**
     * Register attendance for an employee
     * 
     * @param int $idEmpleado Employee ID
     * @param string $tipo ENTRADA or SALIDA
     * @param string $method facial, fingerprint, or traditional
     * @param array $payload Additional data (image, verification results, etc.)
     * @return array Registration result
     */
    public function registrarAsistencia($idEmpleado, $tipo, $method, $payload = [])
    {
        try {
            // Validate input
            if (!in_array($tipo, ['ENTRADA', 'SALIDA'])) {
                throw new Exception('Tipo de registro inválido');
            }

            if (!in_array($method, ['facial', 'fingerprint', 'traditional'])) {
                throw new Exception('Método de verificación inválido');
            }

            // Get employee information
            $empleado = $this->empleadoRepo->getById($idEmpleado);
            if (!$empleado || !$empleado['ACTIVO']) {
                throw new Exception('Empleado no encontrado o inactivo');
            }

            // Get current date and time in Colombia timezone
            $fechaActual = new DateTime('now', new DateTimeZone('America/Bogota'));
            $fecha = $fechaActual->format('Y-m-d');
            $hora = $fechaActual->format('H:i:s');

            // Resolve schedule for today
            $horario = $this->resolveScheduleForDate($idEmpleado, $fecha);
            if (!$horario) {
                throw new Exception('El empleado no tiene horarios activos para hoy');
            }

            // Determine registration type if not specified
            if (!$tipo) {
                $tipo = $this->determineRegistrationType($idEmpleado, $fecha, $horario['ID_HORARIO']);
            }

            // Check for holiday
            $isHoliday = $this->isHoliday($fecha, $empleado['ID_EMPRESA']);

            // Calculate tardiness
            $tardanza = $this->calculateTardiness($tipo, $hora, $horario, $isHoliday);

            // Handle photo storage
            $fotoPath = null;
            if (isset($payload['image_data']) && !empty($payload['image_data'])) {
                if ($method === 'fingerprint') {
                    // Use placeholder for fingerprint verification
                    $fotoPath = $this->config['PLACEHOLDER_FINGERPRINT'];
                } else {
                    // Save actual photo for facial and traditional
                    $fotoPath = $this->saveAttendancePhoto($payload['image_data'], $idEmpleado, $tipo);
                }
            }

            // Insert attendance record
            $attendanceData = [
                'ID_EMPLEADO' => $idEmpleado,
                'FECHA' => $fecha,
                'HORA' => $hora,
                'TIPO' => $tipo,
                'ID_HORARIO' => $horario['ID_HORARIO'],
                'TARDANZA' => $tardanza,
                'FOTO' => $fotoPath,
                'VERIFICATION_METHOD' => $method
            ];

            $attendanceId = $this->asistenciaRepo->insert($attendanceData);

            // Log biometric operation
            if ($method !== 'traditional') {
                $this->logBiometricOperation($idEmpleado, $method, true, $fecha, $hora);
            }

            return [
                'success' => true,
                'attendance_id' => $attendanceId,
                'tipo' => $tipo,
                'hora' => $hora,
                'tardanza' => $tardanza,
                'is_holiday' => $isHoliday,
                'method' => $method,
                'employee' => $empleado['NOMBRE'] . ' ' . $empleado['APELLIDO'],
                'message' => 'Asistencia registrada correctamente'
            ];

        } catch (Exception $e) {
            // Log failed biometric operation
            if (isset($idEmpleado) && $method !== 'traditional') {
                $this->logBiometricOperation($idEmpleado, $method, false, $fecha ?? date('Y-m-d'), $hora ?? date('H:i:s'));
            }

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Resolve the appropriate schedule for a given date
     */
    private function resolveScheduleForDate($idEmpleado, $fecha)
    {
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
    }

    /**
     * Determine registration type based on existing records
     */
    private function determineRegistrationType($idEmpleado, $fecha, $idHorario)
    {
        $existing = $this->asistenciaRepo->getByEmployeeAndDate($idEmpleado, $fecha, $idHorario);

        $hasEntrada = false;
        $hasSalida = false;

        foreach ($existing as $record) {
            if ($record['TIPO'] === 'ENTRADA') $hasEntrada = true;
            if ($record['TIPO'] === 'SALIDA') $hasSalida = true;
        }

        if (!$hasEntrada) {
            return 'ENTRADA';
        } elseif (!$hasSalida) {
            return 'SALIDA';
        } else {
            throw new Exception('El empleado ya tiene registros completos para este horario');
        }
    }

    /**
     * Calculate tardiness in minutes
     */
    private function calculateTardiness($tipo, $hora, $horario, $isHoliday)
    {
        // No tardiness for exits or holidays
        if ($tipo === 'SALIDA' || $isHoliday) {
            return 'N';
        }

        // Only calculate for entries
        if ($tipo === 'ENTRADA') {
            $horaEntradaProgramada = new DateTime($horario['HORA_ENTRADA']);
            $horaActual = new DateTime($hora);
            
            // Add tolerance if specified
            if (isset($horario['TOLERANCIA']) && $horario['TOLERANCIA'] > 0) {
                $horaEntradaProgramada->add(new DateInterval('PT' . $horario['TOLERANCIA'] . 'M'));
            }
            
            if ($horaActual > $horaEntradaProgramada) {
                $diff = $horaActual->diff($horaEntradaProgramada);
                return ($diff->h * 60) + $diff->i; // Return minutes late
            }
        }

        return 'N'; // No tardiness
    }

    /**
     * Check if date is a holiday
     */
    private function isHoliday($fecha, $idEmpresa)
    {
        try {
            return $this->holidayRepo->isHoliday($fecha, $idEmpresa);
        } catch (Exception $e) {
            // If holiday check fails, assume not a holiday
            return false;
        }
    }

    /**
     * Save attendance photo
     */
    private function saveAttendancePhoto($imageData, $idEmpleado, $tipo)
    {
        try {
            $uploadDir = $this->config['UPLOAD_DIRS']['asistencia'];
            
            // Ensure directory exists
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Clean image data
            $imageData = $this->cleanImageData($imageData);
            $imageBinary = base64_decode($imageData);
            
            if ($imageBinary === false) {
                throw new Exception('Datos de imagen inválidos');
            }
            
            // Validate image size
            if (strlen($imageBinary) > $this->config['MAX_IMAGE_SIZE']) {
                throw new Exception('Imagen muy grande');
            }
            
            // Generate filename
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "attendance_{$idEmpleado}_{$tipo}_{$timestamp}.jpg";
            $filepath = $uploadDir . $filename;
            
            // Save file
            if (file_put_contents($filepath, $imageBinary) === false) {
                throw new Exception('Error al guardar la imagen');
            }
            
            // Return relative path
            return $this->config['UPLOAD_DIRS']['asistencia'] . $filename;
            
        } catch (Exception $e) {
            error_log("Error saving attendance photo: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Clean base64 image data
     */
    private function cleanImageData($imageData)
    {
        // Remove data URL prefix if present
        $imageData = preg_replace('/^data:image\/[^;]+;base64,/', '', $imageData);
        
        // Replace spaces with plus signs
        $imageData = str_replace(' ', '+', $imageData);
        
        return $imageData;
    }

    /**
     * Log biometric operation
     */
    private function logBiometricOperation($idEmpleado, $method, $success, $fecha, $hora)
    {
        try {
            $logData = [
                'ID_EMPLEADO' => $idEmpleado,
                'VERIFICATION_METHOD' => $method,
                'VERIFICATION_SUCCESS' => $success ? 1 : 0,
                'FECHA' => $fecha,
                'HORA' => $hora
            ];
            
            $this->biometricLogsRepo->insert($logData);
            
        } catch (Exception $e) {
            error_log("Error logging biometric operation: " . $e->getMessage());
        }
    }

    /**
     * Get attendance summary for an employee
     */
    public function getAttendanceSummary($idEmpleado, $fechaInicio, $fechaFin)
    {
        try {
            $registros = $this->asistenciaRepo->getByEmployeeAndDateRange($idEmpleado, $fechaInicio, $fechaFin);
            
            $summary = [
                'total_registros' => count($registros),
                'entradas' => 0,
                'salidas' => 0,
                'tardanzas' => 0,
                'total_minutos_tardanza' => 0,
                'metodos_verificacion' => [
                    'facial' => 0,
                    'fingerprint' => 0,
                    'traditional' => 0
                ]
            ];
            
            foreach ($registros as $registro) {
                if ($registro['TIPO'] === 'ENTRADA') {
                    $summary['entradas']++;
                    
                    if (is_numeric($registro['TARDANZA']) && $registro['TARDANZA'] > 0) {
                        $summary['tardanzas']++;
                        $summary['total_minutos_tardanza'] += $registro['TARDANZA'];
                    }
                } else {
                    $summary['salidas']++;
                }
                
                if (isset($summary['metodos_verificacion'][$registro['VERIFICATION_METHOD']])) {
                    $summary['metodos_verificacion'][$registro['VERIFICATION_METHOD']]++;
                }
            }
            
            return $summary;
            
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'total_registros' => 0
            ];
        }
    }

    /**
     * Validate employee can register attendance
     */
    public function validateEmployeeCanRegister($idEmpleado, $fecha = null)
    {
        try {
            $fecha = $fecha ?? date('Y-m-d');
            
            // Check if employee exists and is active
            $empleado = $this->empleadoRepo->getById($idEmpleado);
            if (!$empleado || !$empleado['ACTIVO']) {
                return [
                    'valid' => false,
                    'message' => 'Empleado no encontrado o inactivo'
                ];
            }
            
            // Check if employee has active schedules
            $horario = $this->resolveScheduleForDate($idEmpleado, $fecha);
            if (!$horario) {
                return [
                    'valid' => false,
                    'message' => 'El empleado no tiene horarios activos para esta fecha'
                ];
            }
            
            return [
                'valid' => true,
                'employee' => $empleado,
                'schedule' => $horario,
                'message' => 'Empleado válido para registro'
            ];
            
        } catch (Exception $e) {
            return [
                'valid' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}