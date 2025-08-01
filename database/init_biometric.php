<?php
// Database initialization script for biometric recognition system
require_once __DIR__ . '/../config/database.php';

try {
    echo "Inicializando sistema biométrico...\n";
    
    // Read and execute SQL schema
    $sqlSchema = file_get_contents(__DIR__ . '/biometric_schema.sql');
    
    if (!$sqlSchema) {
        throw new Exception("No se pudo leer el archivo de esquema biométrico");
    }
    
    // Split by semicolon and execute each statement
    $statements = explode(';', $sqlSchema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            echo "Ejecutando: " . substr($statement, 0, 50) . "...\n";
            $conn->exec($statement);
        }
    }
    
    echo "Sistema biométrico inicializado exitosamente.\n";
    
    // Add some demo biometric data for testing
    echo "Agregando datos de prueba...\n";
    
    // Check if there are any employees to add demo data to
    $stmt = $conn->query("SELECT ID_EMPLEADO, NOMBRE, APELLIDO FROM EMPLEADO LIMIT 3");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($employees)) {
        foreach ($employees as $employee) {
            // Add demo fingerprint data
            $demoFingerprintData = json_encode([
                'template' => base64_encode("demo_fingerprint_template_" . $employee['ID_EMPLEADO']),
                'features' => array_fill(0, 20, rand(1, 100)),
                'quality' => rand(80, 95)
            ]);
            
            $stmt = $conn->prepare("INSERT INTO EMPLEADO_BIOMETRICO 
                (ID_EMPLEADO, TIPO_BIOMETRICO, DATOS_BIOMETRICO, METADATA) 
                VALUES (?, 'FINGERPRINT', ?, ?) 
                ON DUPLICATE KEY UPDATE DATOS_BIOMETRICO = VALUES(DATOS_BIOMETRICO)");
            $stmt->execute([
                $employee['ID_EMPLEADO'], 
                $demoFingerprintData,
                json_encode(['demo' => true, 'created_by' => 'init_script'])
            ]);
            
            // Add demo facial data
            $demoFacialData = json_encode(array_fill(0, 128, rand(-100, 100) / 100.0));
            
            $stmt = $conn->prepare("INSERT INTO EMPLEADO_BIOMETRICO 
                (ID_EMPLEADO, TIPO_BIOMETRICO, DATOS_BIOMETRICO, METADATA) 
                VALUES (?, 'FACIAL', ?, ?)
                ON DUPLICATE KEY UPDATE DATOS_BIOMETRICO = VALUES(DATOS_BIOMETRICO)");
            $stmt->execute([
                $employee['ID_EMPLEADO'], 
                $demoFacialData,
                json_encode(['demo' => true, 'created_by' => 'init_script'])
            ]);
            
            echo "Datos biométricos demo agregados para empleado: " . $employee['NOMBRE'] . " " . $employee['APELLIDO'] . "\n";
        }
    }
    
    echo "Inicialización completa.\n";
    
} catch (Exception $e) {
    echo "Error durante la inicialización: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>