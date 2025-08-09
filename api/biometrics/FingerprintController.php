<?php
/**
 * Fingerprint Recognition Controller
 * Handles fingerprint enrollment and verification endpoints
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../services/Biometrics/FingerprintService.php';
require_once __DIR__ . '/../../repositories/BiometricDataRepository.php';
require_once __DIR__ . '/../../repositories/BiometricLogsRepository.php';
require_once __DIR__ . '/../../repositories/EmpleadoRepository.php';

// Ensure user is authenticated
requireAuth();

header('Content-Type: application/json');

class FingerprintController
{
    private $fingerprintService;
    private $biometricDataRepo;
    private $biometricLogsRepo;
    private $empleadoRepo;

    public function __construct()
    {
        global $conn;
        $this->fingerprintService = new FingerprintService();
        $this->biometricDataRepo = new BiometricDataRepository($conn);
        $this->biometricLogsRepo = new BiometricLogsRepository($conn);
        $this->empleadoRepo = new EmpleadoRepository($conn);
    }

    /**
     * Handle POST requests to fingerprint endpoints
     */
    public function handleRequest()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $action = $_GET['action'] ?? '';

            switch ($action) {
                case 'enroll':
                    return $this->enroll();
                case 'verify':
                    return $this->verify();
                default:
                    throw new Exception('Acción no válida');
            }

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enroll fingerprint template for an employee
     * POST /api/biometrics/fingerprint/enroll
     */
    private function enroll()
    {
        try {
            // Get input data
            $input = $this->getInputData();
            
            $idEmpleado = $input['id_empleado'] ?? null;
            $fingerType = $input['finger_type'] ?? null;
            $image = $input['image'] ?? null;

            if (!$idEmpleado) {
                throw new Exception('ID de empleado requerido');
            }

            if (!$fingerType) {
                throw new Exception('Tipo de dedo requerido');
            }

            if (!$image) {
                throw new Exception('Imagen de huella requerida');
            }

            // Validate employee
            $empleado = $this->empleadoRepo->getById($idEmpleado);
            if (!$empleado || !$empleado['ACTIVO']) {
                throw new Exception('Empleado no encontrado o inactivo');
            }

            // Process fingerprint with service
            $fingerprintResult = $this->fingerprintService->enrollFingerprint($idEmpleado, $fingerType, $image);
            
            if (!$fingerprintResult['success']) {
                throw new Exception($fingerprintResult['message']);
            }

            // Store fingerprint template
            $this->biometricDataRepo->storeFingerprintData($idEmpleado, $fingerType, $fingerprintResult['template']);

            // Log the enrollment
            $this->biometricLogsRepo->logOperation($idEmpleado, 'fingerprint', true);

            echo json_encode([
                'success' => true,
                'message' => 'Inscripción de huella completada exitosamente',
                'employee' => $empleado['NOMBRE'] . ' ' . $empleado['APELLIDO'],
                'finger_type' => $fingerType,
                'quality_score' => $fingerprintResult['quality_score'] ?? 0
            ]);

        } catch (Exception $e) {
            // Log failed enrollment
            if (isset($idEmpleado)) {
                $this->biometricLogsRepo->logOperation($idEmpleado, 'fingerprint', false);
            }

            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Verify fingerprint
     * POST /api/biometrics/fingerprint/verify
     */
    private function verify()
    {
        try {
            // Get input data
            $input = $this->getInputData();
            
            $idEmpleado = $input['id_empleado'] ?? null;
            $image = $input['image'] ?? null;

            if (!$idEmpleado) {
                throw new Exception('ID de empleado requerido');
            }

            if (!$image) {
                throw new Exception('Imagen de huella requerida para verificación');
            }

            // Validate employee
            $empleado = $this->empleadoRepo->getById($idEmpleado);
            if (!$empleado || !$empleado['ACTIVO']) {
                throw new Exception('Empleado no encontrado o inactivo');
            }

            // Get stored fingerprint templates
            $storedTemplates = $this->biometricDataRepo->getVerificationData($idEmpleado, 'fingerprint');
            
            if (empty($storedTemplates)) {
                throw new Exception('El empleado no tiene huellas registradas');
            }

            // Verify with fingerprint service
            $verificationResult = $this->fingerprintService->verifyFingerprint($idEmpleado, $image, $storedTemplates);

            // Log verification attempt
            $this->biometricLogsRepo->logOperation($idEmpleado, 'fingerprint', $verificationResult['match']);

            echo json_encode([
                'success' => true,
                'verified' => $verificationResult['match'],
                'confidence' => $verificationResult['confidence'] ?? 0,
                'score' => $verificationResult['score'] ?? 0,
                'threshold' => $verificationResult['threshold'] ?? 0,
                'finger_type' => $verificationResult['finger_type'] ?? null,
                'employee' => $empleado['NOMBRE'] . ' ' . $empleado['APELLIDO'],
                'message' => $verificationResult['message']
            ]);

        } catch (Exception $e) {
            // Log failed verification
            if (isset($idEmpleado)) {
                $this->biometricLogsRepo->logOperation($idEmpleado, 'fingerprint', false);
            }

            http_response_code(400);
            echo json_encode([
                'success' => false,
                'verified' => false,
                'message' => $e->getMessage()
            ]);
        }
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
            
            // Handle file upload for image
            if (!empty($_FILES) && isset($_FILES['image'])) {
                $data['image'] = $this->processUploadedImage();
            }
            
            return $data;
        }
    }

    /**
     * Process uploaded fingerprint image and convert to base64
     */
    private function processUploadedImage()
    {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir la imagen de huella');
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/bmp'];
        $fileType = $_FILES['image']['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Tipo de archivo no permitido. Use JPEG, PNG o BMP');
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($_FILES['image']['size'] > $maxSize) {
            throw new Exception('El archivo es muy grande. Máximo 5MB');
        }

        $imageData = file_get_contents($_FILES['image']['tmp_name']);
        return base64_encode($imageData);
    }
}

// Handle the request
$controller = new FingerprintController();
$controller->handleRequest();