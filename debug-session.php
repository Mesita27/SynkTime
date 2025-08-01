<?php
// Habilitar reporte de errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'auth/session.php';

// Debug de la sesión actual
echo "<h2>Debug de Sesión</h2>";
echo "<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>";

if (isAuthenticated()) {
    echo "<p><strong>✅ Usuario autenticado</strong></p>";
    
    $currentUser = getCurrentUser();
    echo "<h3>Datos del usuario:</h3>";
    echo "<pre>";
    print_r($currentUser);
    echo "</pre>";
    
    echo "<h3>Variables de sesión completas:</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    echo "<h3>Pruebas de acceso a módulos:</h3>";
    $modules = ['dashboard', 'asistencia', 'attendance', 'empleados', 'horarios', 'reportes'];
    
    foreach ($modules as $module) {
        $access = hasModuleAccess($module) ? '✅ Permitido' : '❌ Denegado';
        echo "<p><strong>$module:</strong> $access</p>";
    }
    
    echo "<h3>Permisos especiales:</h3>";
    echo "<p><strong>hasFullAccess():</strong> " . (hasFullAccess() ? '✅ Sí' : '❌ No') . "</p>";
    echo "<p><strong>isAdmin():</strong> " . (isAdmin() ? '✅ Sí' : '❌ No') . "</p>";
    
} else {
    echo "<p><strong>❌ Usuario NO autenticado</strong></p>";
    echo "<h3>Variables de sesión:</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}

echo "<h3>Enlaces de prueba:</h3>";
echo "<ul>";
echo "<li><a href='dashboard.php' target='_blank'>Dashboard</a></li>";
echo "<li><a href='attendance.php' target='_blank'>Asistencias</a></li>";
echo "<li><a href='employee.php' target='_blank'>Empleados</a></li>";
echo "</ul>";
?>
