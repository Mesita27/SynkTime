<?php
/**
 * Sistema de autorización basado en roles para SynkTime
 */

require_once __DIR__ . '/session.php';

// Definición de roles del sistema
define('ROLE_OWNER_MANAGER', 'GERENTE');
define('ROLE_ATTENDANCE_USER', 'ASISTENCIA');

/**
 * Verifica si el usuario tiene permiso para acceder a una funcionalidad
 */
function hasPermission($permission) {
    if (!isAuthenticated()) {
        return false;
    }
    
    $userRole = $_SESSION['rol'] ?? null;
    
    switch ($permission) {
        case 'dashboard_full':
        case 'employee_management':
        case 'schedules_management':
        case 'reports_access':
        case 'worked_hours_access':
        case 'attendance_full':
            // Solo gerentes/dueños tienen acceso completo
            return $userRole === ROLE_OWNER_MANAGER;
            
        case 'attendance_current_day':
            // Ambos roles pueden registrar asistencias del día actual
            return in_array($userRole, [ROLE_OWNER_MANAGER, ROLE_ATTENDANCE_USER]);
            
        default:
            return false;
    }
}

/**
 * Verifica si el usuario es gerente/dueño
 */
function isOwnerManager() {
    return isAuthenticated() && ($_SESSION['rol'] ?? null) === ROLE_OWNER_MANAGER;
}

/**
 * Verifica si el usuario es de solo asistencias
 */
function isAttendanceUser() {
    return isAuthenticated() && ($_SESSION['rol'] ?? null) === ROLE_ATTENDANCE_USER;
}

/**
 * Requiere permiso específico - redirige si no lo tiene
 */
function requirePermission($permission, $redirectUrl = 'dashboard.php') {
    if (!hasPermission($permission)) {
        if (isAttendanceUser() && $permission === 'attendance_current_day') {
            // Usuario de asistencias puede acceder a registro de asistencias
            return true;
        }
        
        // Log del intento de acceso no autorizado
        logActivity('ACCESO_DENEGADO', "Intento de acceso a: $permission");
        
        header("Location: $redirectUrl");
        exit;
    }
    return true;
}

/**
 * Obtiene las páginas permitidas para el rol actual
 */
function getAllowedPages() {
    if (!isAuthenticated()) {
        return [];
    }
    
    $userRole = $_SESSION['rol'] ?? null;
    
    switch ($userRole) {
        case ROLE_OWNER_MANAGER:
            return [
                'dashboard.php',
                'employee.php', 
                'attendance.php',
                'schedules.php',
                'horas-trabajadas.php',
                'reports.php'
            ];
            
        case ROLE_ATTENDANCE_USER:
            return [
                'attendance.php'
            ];
            
        default:
            return [];
    }
}

/**
 * Verifica si la página actual está permitida para el rol
 */
function isPageAllowed($page = null) {
    if ($page === null) {
        $page = basename($_SERVER['PHP_SELF']);
    }
    
    $allowedPages = getAllowedPages();
    return in_array($page, $allowedPages);
}

/**
 * Middleware para proteger páginas según el rol
 */
function requirePageAccess() {
    requireAuth(); // Primero verificar autenticación
    
    $currentPage = basename($_SERVER['PHP_SELF']);
    
    // Páginas que siempre están permitidas
    $publicPages = ['login.php', 'logout.php', 'index.php'];
    if (in_array($currentPage, $publicPages)) {
        return;
    }
    
    // Verificar si la página está permitida para el rol
    if (!isPageAllowed($currentPage)) {
        // Redirigir según el rol
        if (isAttendanceUser()) {
            header('Location: attendance.php');
        } else {
            header('Location: dashboard.php');
        }
        exit;
    }
}

/**
 * Valida acceso a datos por fecha para usuarios de asistencia
 */
function validateDateAccess($fecha) {
    if (isOwnerManager()) {
        return true; // Gerentes pueden acceder a cualquier fecha
    }
    
    if (isAttendanceUser()) {
        $fechaActual = date('Y-m-d');
        return $fecha === $fechaActual; // Solo día actual
    }
    
    return false;
}

/**
 * Obtiene el filtro de fecha para consultas según el rol
 */
function getDateFilter() {
    if (isOwnerManager()) {
        return null; // Sin restricciones
    }
    
    if (isAttendanceUser()) {
        return date('Y-m-d'); // Solo día actual
    }
    
    return false; // Sin acceso
}

/**
 * Verifica si el usuario puede acceder a datos de una empresa
 */
function canAccessCompanyData($empresaId) {
    if (!isAuthenticated()) {
        return false;
    }
    
    $userEmpresaId = $_SESSION['id_empresa'] ?? null;
    return $userEmpresaId == $empresaId;
}
?>