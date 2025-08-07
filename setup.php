<?php
/**
 * Database Setup Script for SynkTime
 * This script initializes the database with the proper schema for MariaDB compatibility
 */

require_once 'config/database.php';

try {
    echo "<h2>SynkTime Database Setup</h2>";
    
    // Read and execute the schema
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    
    if (!$schema) {
        throw new Exception('Could not read schema file');
    }
    
    // Split schema into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    $executed = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        try {
            $conn->exec($statement);
            $executed++;
            echo "<p>✅ Executed: " . substr($statement, 0, 50) . "...</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "<p>ℹ️ Skipped existing: " . substr($statement, 0, 50) . "...</p>";
            } else {
                echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p>Statement: " . htmlspecialchars(substr($statement, 0, 100)) . "...</p>";
                $errors++;
            }
        }
    }
    
    echo "<h3>Setup Complete!</h3>";
    echo "<p><strong>Statements executed:</strong> $executed</p>";
    echo "<p><strong>Errors:</strong> $errors</p>";
    
    // Test database connection and show some basic info
    echo "<h3>Database Verification</h3>";
    
    // Check tables exist
    $tables = ['EMPRESA', 'SEDE', 'ESTABLECIMIENTO', 'USUARIO', 'EMPLEADO', 'ASISTENCIAS', 'biometric_data', 'biometric_logs'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table");
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<p>✅ Table $table: $count records</p>";
        } catch (PDOException $e) {
            echo "<p>❌ Table $table: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // Test default admin login
    echo "<h3>Test Login</h3>";
    $stmt = $conn->prepare("SELECT USERNAME, ROL, NOMBRE_COMPLETO FROM USUARIO WHERE USERNAME = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<p>✅ Default admin user created successfully</p>";
        echo "<p><strong>Username:</strong> admin</p>";
        echo "<p><strong>Password:</strong> admin123</p>";
        echo "<p><strong>Role:</strong> " . $admin['ROL'] . "</p>";
    } else {
        echo "<p>❌ Default admin user not found</p>";
    }
    
    echo "<hr>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Go to <a href='login.php'>login.php</a> to test the system</li>";
    echo "<li>Use username: <code>admin</code> and password: <code>admin123</code></li>";
    echo "<li>Check the biometric enrollment functionality</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3>❌ Setup Failed</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
}
?>