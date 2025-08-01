<?php
// Simple database migration runner for biometric features
require_once __DIR__ . '/../config/database.php';

try {
    echo "Running biometric migration...\n";
    
    // Read and execute the migration
    $migrationFile = __DIR__ . '/migrations/add_biometric_fields.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Split by semicolons and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $conn->beginTransaction();
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty lines and comments
        }
        
        echo "Executing: " . substr($statement, 0, 50) . "...\n";
        $conn->exec($statement);
    }
    
    $conn->commit();
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>