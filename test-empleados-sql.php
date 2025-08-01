<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/session.php';

echo "<h2>Debug SQL Empleados</h2>";

if (!isAuthenticated()) {
    echo "<p>❌ No autenticado. <a href='login.php'>Ir al login</a></p>";
    exit;
}

$currentUser = getCurrentUser();
$empresaId = $currentUser['id_empresa'];

echo "<p><strong>Usuario:</strong> " . $currentUser['username'] . "</p>";
echo "<p><strong>Empresa ID:</strong> " . $empresaId . "</p>";

try {
    // Verificar estructura de base de datos
    echo "<h3>Verificando estructura de tablas:</h3>";
    
    // Verificar tabla EMPLEADO
    $stmt = $conn->prepare("DESCRIBE EMPLEADO");
    $stmt->execute();
    $empleadoFields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Campos de EMPLEADO:</h4>";
    echo "<ul>";
    foreach ($empleadoFields as $field) {
        echo "<li>" . $field['Field'] . " (" . $field['Type'] . ")</li>";
    }
    echo "</ul>";
    
    // Probar consulta simplificada
    echo "<h3>Probando consulta:</h3>";
    
    $sql = "
        SELECT DISTINCT
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            e.DNI,
            est.NOMBRE AS ESTABLECIMIENTO_NOMBRE,
            s.NOMBRE AS SEDE_NOMBRE
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
        WHERE emp.ID_EMPRESA = :empresa_id 
        AND e.ESTADO = 'A' 
        AND e.ACTIVO = 'S'
        ORDER BY e.NOMBRE, e.APELLIDO
        LIMIT 5
    ";
    
    echo "<p><strong>SQL:</strong></p>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Resultados (" . count($empleados) . " empleados):</h4>";
    if (count($empleados) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Establecimiento</th><th>Sede</th></tr>";
        foreach ($empleados as $emp) {
            echo "<tr>";
            echo "<td>" . $emp['ID_EMPLEADO'] . "</td>";
            echo "<td>" . htmlspecialchars($emp['NOMBRE']) . "</td>";
            echo "<td>" . htmlspecialchars($emp['APELLIDO']) . "</td>";
            echo "<td>" . htmlspecialchars($emp['ESTABLECIMIENTO_NOMBRE']) . "</td>";
            echo "<td>" . htmlspecialchars($emp['SEDE_NOMBRE']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No se encontraron empleados.</p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='api/horas-trabajadas/get-empleados.php' target='_blank'>🔗 Probar API directamente</a></p>";
?>
