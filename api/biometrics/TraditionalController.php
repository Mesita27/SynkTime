<?php
/**
 * Traditional Photo Capture Controller
 * Handles traditional photo capture for attendance without biometric verification
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../repositories/EmpleadoRepository.php';

// Ensure user is authenticated
requireAuth();

header('Content-Type: application/json');

class TraditionalController
{
    private $empleadoRepo;
    private $config;

    public function __construct()
    {
        global $conn;
        $this->empleadoRepo = new EmpleadoRepository($conn);
        $this->config = require __DIR__ . '/../../config/biometrics.php';
    }

    /**
     * Handle POST requests to traditional endpoints
     */
    public function handleRequest()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $action = $_GET['action'] ?? '';

            switch ($action) {
                case 'capture':
                    return $this->capture();
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
     * Capture traditional photo for attendance
     * POST /api/biometrics/traditional/capture
     */
    private function capture()
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
                throw new Exception('Imagen requerida para captura tradicional');
            }

            // Validate employee
            $empleado = $this->empleadoRepo->getById($idEmpleado);
            if (!$empleado || !$empleado['ACTIVO']) {
                throw new Exception('Empleado no encontrado o inactivo');
            }

            // Save the image
            $photoPath = $this->saveAttendancePhoto($image, $idEmpleado);

            echo json_encode([
                'success' => true,
                'message' => 'Foto capturada exitosamente',
                'employee' => $empleado['NOMBRE'] . ' ' . $empleado['APELLIDO'],
                'photo_path' => $photoPath,
                'method' => 'traditional'
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
     * Save attendance photo
     */
    private function saveAttendancePhoto($imageData, $idEmpleado)
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
                throw new Exception('Imagen muy grande. Máximo ' . ($this->config['MAX_IMAGE_SIZE'] / 1024 / 1024) . 'MB');
            }
            
            // Validate image type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $imageType = $finfo->buffer($imageBinary);
            
            if (!in_array($imageType, $this->config['ALLOWED_IMAGE_TYPES'])) {
                throw new Exception('Tipo de imagen no permitido');
            }
            
            // Generate filename
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "traditional_{$idEmpleado}_{$timestamp}.jpg";
            $filepath = $uploadDir . $filename;
            
            // Save file
            if (file_put_contents($filepath, $imageBinary) === false) {
                throw new Exception('Error al guardar la imagen');
            }
            
            // Return relative path
            return $this->config['UPLOAD_DIRS']['asistencia'] . $filename;
            
        } catch (Exception $e) {
            error_log("Error saving traditional attendance photo: " . $e->getMessage());
            throw new Exception("Error al guardar la foto: " . $e->getMessage());
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
     * Process uploaded image file and convert to base64
     */
    private function processUploadedImage()
    {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir la imagen');
        }

        $fileType = $_FILES['image']['type'];
        
        if (!in_array($fileType, $this->config['ALLOWED_IMAGE_TYPES'])) {
            throw new Exception('Tipo de archivo no permitido');
        }

        if ($_FILES['image']['size'] > $this->config['MAX_IMAGE_SIZE']) {
            throw new Exception('El archivo es muy grande');
        }

        $imageData = file_get_contents($_FILES['image']['tmp_name']);
        return base64_encode($imageData);
    }
}

// Handle the request
$controller = new TraditionalController();
$controller->handleRequest();