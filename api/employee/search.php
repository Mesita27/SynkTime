<?php
/**
 * Employee Search API Endpoint
 * Provides employee search functionality for autocomplete
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../services/EmployeeService.php';

// Ensure user is authenticated
requireAuth();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $query = $input['query'] ?? '';
    $limit = intval($input['limit'] ?? 20);
    
    if (strlen($query) < 2) {
        echo json_encode([
            'success' => true,
            'employees' => []
        ]);
        exit;
    }
    
    // Initialize service
    $employeeService = new EmployeeService($conn);
    
    // Get current user's company
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;
    
    // Search employees
    $employees = $employeeService->getEmployeesForAutocomplete($query, $empresaId, $limit);
    
    echo json_encode([
        'success' => true,
        'employees' => $employees,
        'total' => count($employees)
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>