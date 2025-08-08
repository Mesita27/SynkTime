<?php
/**
 * Migration runner for biometric database tables
 * Creates EMPLEADO_BIOMETRICO table and related structures
 */

require_once '../config/database.php';

try {
    echo "=== INICIANDO MIGRACIÓN BIOMÉTRICA ===\n";
    
    // Read and execute migration SQL
    $migration_sql = file_get_contents(__DIR__ . '/create_empleado_biometrico.sql');
    
    if (!$migration_sql) {
        throw new Exception('No se pudo leer el archivo de migración');
    }
    
    // Split SQL statements and execute each one
    $statements = explode(';', $migration_sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $conn->exec($statement);
            echo "✓ Ejecutado: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            // Skip errors for ALTER TABLE IF NOT EXISTS (MySQL doesn't support it natively)
            if (strpos($e->getMessage(), 'Duplicate column name') !== false || 
                strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "⚠ Saltado (ya existe): " . substr($statement, 0, 50) . "...\n";
                continue;
            }
            throw $e;
        }
    }
    
    echo "\n=== MIGRACIÓN COMPLETADA EXITOSAMENTE ===\n";
    echo "✓ Tabla EMPLEADO_BIOMETRICO creada\n";
    echo "✓ Tabla VERIFICACION_BIOMETRICA creada\n";
    echo "✓ Campos agregados a tabla asistencias\n";
    echo "✓ Datos migrados de biometric_data (si existían)\n";
    
} catch (Exception $e) {
    echo "❌ Error en migración: " . $e->getMessage() . "\n";
    exit(1);
}
?>