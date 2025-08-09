<?php
/**
 * Face Recognition Controller
 * Handles facial recognition enrollment and verification endpoints
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../services/Biometrics/FaceService.php';
require_once __DIR__ . '/../../repositories/BiometricDataRepository.php';
require_once __DIR__ . '/../../repositories/BiometricLogsRepository.php';
require_once __DIR__ . '/../../repositories/EmpleadoRepository.php';

// Ensure user is authenticated
requireAuth();

header('Content-Type: application/json');

class FaceController
{
    private $faceService;
    private $biometricDataRepo;
    private $biometricLogsRepo;
    private $empleadoRepo;

    public function __construct()
    {
        global $conn;
        $this->faceService = new FaceService();
        $this->biometricDataRepo = new BiometricDataRepository($conn);
        $this->biometricLogsRepo = new BiometricLogsRepository($conn);
        $this->empleadoRepo = new EmpleadoRepository($conn);
    }

    /**
     * Handle POST requests to face endpoints
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
     * Enroll facial patterns for an employee
     * POST /api/biometrics/face/enroll
     */
    private function enroll()
    {
        try {
            // Get input data
            $input = $this->getInputData();
            
            $idEmpleado = $input['id_empleado'] ?? null;
            $images = $input['images'] ?? null;

            if (!$idEmpleado) {
                throw new Exception('ID de empleado requerido');
            }

            if (!$images || !is_array($images)) {
                throw new Exception('Imágenes requeridas para inscripción facial');
            }

            // Validate employee
            $empleado = $this->empleadoRepo->getById($idEmpleado);
            if (!$empleado || !$empleado['ACTIVO']) {
                throw new Exception('Empleado no encontrado o inactivo');
            }

            // Process images with face service
            $faceResult = $this->faceService->enrollFace($idEmpleado, $images);
            
            if (!$faceResult['success']) {
                throw new Exception($faceResult['message']);
            }

            // Store facial data
            $biometricData = [
                'embeddings' => $faceResult['embeddings'],
                'enrollment_date' => date('Y-m-d H:i:s'),
                'image_count' => $faceResult['count']
            ];

            $this->biometricDataRepo->storeFacialData($idEmpleado, $biometricData);

            // Log the enrollment
            $this->biometricLogsRepo->logOperation($idEmpleado, 'facial', true);

            echo json_encode([
                'success' => true,
                'message' => 'Inscripción facial completada exitosamente',
                'employee' => $empleado['NOMBRE'] . ' ' . $empleado['APELLIDO'],
                'image_count' => $faceResult['count']
            ]);

        } catch (Exception $e) {
            // Log failed enrollment
            if (isset($idEmpleado)) {
                $this->biometricLogsRepo->logOperation($idEmpleado, 'facial', false);
            }

            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Verify facial recognition
     * POST /api/biometrics/face/verify
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
                throw new Exception('Imagen requerida para verificación');
            }

            // Validate employee
            $empleado = $this->empleadoRepo->getById($idEmpleado);
            if (!$empleado || !$empleado['ACTIVO']) {
                throw new Exception('Empleado no encontrado o inactivo');
            }

            // Get stored facial data
            $storedEmbeddings = $this->biometricDataRepo->getVerificationData($idEmpleado, 'facial');
            
            if (empty($storedEmbeddings)) {
                throw new Exception('El empleado no tiene patrones faciales registrados');
            }

            // Verify with face service
            $verificationResult = $this->faceService->verifyFace($idEmpleado, $image, $storedEmbeddings);

            // Log verification attempt
            $this->biometricLogsRepo->logOperation($idEmpleado, 'facial', $verificationResult['match']);

            echo json_encode([
                'success' => true,
                'verified' => $verificationResult['match'],
                'confidence' => $verificationResult['confidence'] ?? 0,
                'score' => $verificationResult['score'] ?? 0,
                'threshold' => $verificationResult['threshold'] ?? 0,
                'employee' => $empleado['NOMBRE'] . ' ' . $empleado['APELLIDO'],
                'message' => $verificationResult['message']
            ]);

        } catch (Exception $e) {
            // Log failed verification
            if (isset($idEmpleado)) {
                $this->biometricLogsRepo->logOperation($idEmpleado, 'facial', false);
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
            
            // Handle file uploads for images
            if (!empty($_FILES)) {
                $data['images'] = $this->processUploadedImages();
            }
            
            return $data;
        }
    }

    /**
     * Process uploaded image files and convert to base64
     */
    private function processUploadedImages()
    {
        $images = [];
        
        if (isset($_FILES['images'])) {
            $files = $_FILES['images'];
            
            // Handle multiple files
            if (is_array($files['tmp_name'])) {
                for ($i = 0; $i < count($files['tmp_name']); $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $imageData = file_get_contents($files['tmp_name'][$i]);
                        $images[] = base64_encode($imageData);
                    }
                }
            } else {
                // Handle single file
                if ($files['error'] === UPLOAD_ERR_OK) {
                    $imageData = file_get_contents($files['tmp_name']);
                    $images[] = base64_encode($imageData);
                }
            }
        }
        
        return $images;
    }
}

// Handle the request
$controller = new FaceController();
$controller->handleRequest();