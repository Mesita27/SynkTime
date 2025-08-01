<?php
// Test directo de la API de empleados
echo "<h2>Test API Empleados</h2>";

// Incluir sesión
require_once __DIR__ . '/auth/session.php';

echo "<h3>Estado de Sesión:</h3>";
if (isAuthenticated()) {
    $user = getCurrentUser();
    echo "<p>✅ Usuario autenticado: " . $user['username'] . "</p>";
    echo "<p>Rol: " . $user['rol'] . "</p>";
    echo "<p>Empresa ID: " . $user['id_empresa'] . "</p>";
} else {
    echo "<p>❌ Usuario NO autenticado</p>";
}

echo "<h3>Test API:</h3>";
echo "<p><a href='api/horas-trabajadas/get-empleados.php' target='_blank'>📋 Test API Empleados</a></p>";
echo "<p><a href='api/get-sedes.php' target='_blank'>🏢 Test API Sedes</a></p>";

echo "<h3>Enlaces de navegación:</h3>";
echo "<p><a href='horas-trabajadas.php' target='_blank'>🕒 Ir a Horas Trabajadas</a></p>";
echo "<p><a href='debug-session.php' target='_blank'>🔍 Debug Sesión</a></p>";
?>
