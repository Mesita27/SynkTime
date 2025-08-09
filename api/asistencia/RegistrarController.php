<?php
/**
 * Unified Attendance Registration Controller
 * Handles attendance registration with biometric verification integration
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../services/AsistenciaService.php';
require_once __DIR__ . '/../../services/Biometrics/FaceService.php';
require_once __DIR__ . '/../../services/Biometrics/FingerprintService.php';
require_once __DIR__ . '/../../repositories/AsistenciaRepository.php';
require_once __DIR__ . '/../../repositories/EmpleadoRepository.php';
require_once __DIR__ . '/../../repositories/HorarioRepository.php';
require_once __DIR__ . '/../../repositories/HolidayRepository.php';
require_once __DIR__ . '/../../repositories/BiometricLogsRepository.php';
require_once __DIR__ . '/../../repositories/BiometricDataRepository.php';

// Ensure user is authenticated
requireAuth();

header('Content-Type: application/json');

class RegistrarController
{
    private $asistenciaService;
    private $faceService;
    private $fingerprintService;
    private $biometricDataRepo;

    public function __construct()
    {
        global $conn;
        
        // Initialize repositories
        $asistenciaRepo = new AsistenciaRepository($conn);
        $empleadoRepo = new EmpleadoRepository($conn);
        $horarioRepo = new HorarioRepository($conn);
        $holidayRepo = new HolidayRepository($conn);
        $biometricLogsRepo = new BiometricLogsRepository($conn);
        
        // Initialize services
        $this->asistenciaService = new AsistenciaService(
            $asistenciaRepo,
            $empleadoRepo, 
            $horarioRepo,
            $holidayRepo,
            $biometricLogsRepo
        );
        
        $this->faceService = new FaceService();
        $this->fingerprintService = new FingerprintService();
        $this->biometricDataRepo = new BiometricDataRepository($conn);
    }

    /**
     * Handle POST request to register attendance
     */
    public function handleRequest()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            return $this->registrar();

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Register attendance with biometric verification
     * POST /api/asistencia/registrar
     */
    private function registrar()
    {
        try {
            // Get input data
            $input = $this->getInputData();
            
            $idEmpleado = $input['id_empleado'] ?? null;
            $tipo = $input['tipo'] ?? null; // ENTRADA or SALIDA
            $method = $input['method'] ?? null; // facial, fingerprint, traditional
            $payload = $input['payload'] ?? [];

            if (!$idEmpleado) {
                throw new Exception('ID de empleado requerido');
            }

            if (!in_array($method, ['facial', 'fingerprint', 'traditional'])) {
                throw new Exception('Método de verificación inválido');
            }

            // Validate employee can register
            $validation = $this->asistenciaService->validateEmployeeCanRegister($idEmpleado);
            if (!$validation['valid']) {
                throw new Exception($validation['message']);
            }

            // Perform biometric verification if required
            if ($method !== 'traditional') {
                $verificationResult = $this->performBiometricVerification($idEmpleado, $method, $payload);
                
                if (!$verificationResult['success']) {
                    throw new Exception($verificationResult['message']);
                }

                if (!$verificationResult['verified']) {
                    throw new Exception('Verificación biométrica fallida: ' . $verificationResult['message']);
                }

                // Add verification data to payload
                $payload['verification_result'] = $verificationResult;
            }

            // Determine registration type if not specified
            if (!$tipo) {
                // Let the service determine based on existing records
                $tipo = null;
            }

            // Register attendance
            $registrationResult = $this->asistenciaService->registrarAsistencia(
                $idEmpleado,
                $tipo,
                $method,
                $payload
            );

            if (!$registrationResult['success']) {
                throw new Exception($registrationResult['message']);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Asistencia registrada exitosamente',
                'data' => $registrationResult,
                'verification_method' => $method,
                'biometric_verified' => $method !== 'traditional'
            ]);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Perform biometric verification based on method
     */
    private function performBiometricVerification($idEmpleado, $method, $payload)
    {
        try {
            switch ($method) {
                case 'facial':
                    return $this->verifyFacial($idEmpleado, $payload);
                case 'fingerprint':
                    return $this->verifyFingerprint($idEmpleado, $payload);
                default:
                    throw new Exception('Método de verificación no soportado');
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'verified' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify facial recognition
     */
    private function verifyFacial($idEmpleado, $payload)
    {
        if (!isset($payload['image_data'])) {
            throw new Exception('Imagen requerida para verificación facial');
        }

        // Get stored facial data
        $storedEmbeddings = $this->biometricDataRepo->getVerificationData($idEmpleado, 'facial');
        
        if (empty($storedEmbeddings)) {
            throw new Exception('El empleado no tiene patrones faciales registrados');
        }

        // Verify with face service
        $result = $this->faceService->verifyFace($idEmpleado, $payload['image_data'], $storedEmbeddings);
        
        return [
            'success' => $result['success'],
            'verified' => $result['match'],
            'confidence' => $result['confidence'] ?? 0,
            'score' => $result['score'] ?? 0,
            'message' => $result['message']
        ];
    }

    /**
     * Verify fingerprint
     */
    private function verifyFingerprint($idEmpleado, $payload)
    {
        if (!isset($payload['image_data'])) {
            throw new Exception('Imagen de huella requerida para verificación');
        }

        // Get stored fingerprint templates
        $storedTemplates = $this->biometricDataRepo->getVerificationData($idEmpleado, 'fingerprint');
        
        if (empty($storedTemplates)) {
            throw new Exception('El empleado no tiene huellas registradas');
        }

        // Verify with fingerprint service
        $result = $this->fingerprintService->verifyFingerprint($idEmpleado, $payload['image_data'], $storedTemplates);
        
        return [
            'success' => $result['success'],
            'verified' => $result['match'],
            'confidence' => $result['confidence'] ?? 0,
            'score' => $result['score'] ?? 0,
            'finger_type' => $result['finger_type'] ?? null,
            'message' => $result['message']
        ];
    }

    /**
     * Get input data from POST request (supports both JSON and form data)
     */
    private function getInputData()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON inválido');
            }
            
            return $data;
        } else {
            // Handle form data
            $data = $_POST;
            
            // Handle file uploads
            if (!empty($_FILES)) {
                if (isset($_FILES['image'])) {
                    $data['payload']['image_data'] = $this->processUploadedImage($_FILES['image']);
                }
            }
            
            return $data;
        }
    }

    /**
     * Process uploaded image file and convert to base64
     */
    private function processUploadedImage($file)
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir la imagen');
        }

        $config = require __DIR__ . '/../../config/biometrics.php';
        
        $fileType = $file['type'];
        if (!in_array($fileType, $config['ALLOWED_IMAGE_TYPES'])) {
            throw new Exception('Tipo de archivo no permitido');
        }

        if ($file['size'] > $config['MAX_IMAGE_SIZE']) {
            throw new Exception('El archivo es muy grande');
        }

        $imageData = file_get_contents($file['tmp_name']);
        return base64_encode($imageData);
    }
}

// Handle the request
$controller = new RegistrarController();
$controller->handleRequest();