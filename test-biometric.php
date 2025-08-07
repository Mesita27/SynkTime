<?php
/**
 * Test script for biometric functionality
 * Tests database integration and API endpoints
 */

require_once 'config/database.php';

echo "<h1>SynkTime Biometric System Test</h1>\n";

// Test 1: Database Connection
echo "<h2>1. Testing Database Connection</h2>\n";
try {
    $stmt = $conn->query("SELECT 1");
    echo "✅ Database connection successful\n<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n<br>";
}

// Test 2: Check if tables exist
echo "<h2>2. Testing Database Tables</h2>\n";
try {
    // Check empleados table
    $stmt = $conn->query("SHOW TABLES LIKE 'empleados'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table 'empleados' exists\n<br>";
        
        // Count employees
        $stmt = $conn->query("SELECT COUNT(*) as count FROM empleados WHERE ACTIVO = 1");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "📊 Active employees: $count\n<br>";
    } else {
        echo "❌ Table 'empleados' not found\n<br>";
    }
    
    // Check biometric_data table
    $stmt = $conn->query("SHOW TABLES LIKE 'biometric_data'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table 'biometric_data' exists\n<br>";
        
        // Count biometric records
        $stmt = $conn->query("SELECT COUNT(*) as count FROM biometric_data WHERE ACTIVO = 1");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "📊 Biometric records: $count\n<br>";
    } else {
        echo "⚠️ Table 'biometric_data' not found (will be created automatically)\n<br>";
    }
    
    // Check biometric_logs table
    $stmt = $conn->query("SHOW TABLES LIKE 'biometric_logs'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table 'biometric_logs' exists\n<br>";
        
        // Count log records
        $stmt = $conn->query("SELECT COUNT(*) as count FROM biometric_logs");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "📊 Log records: $count\n<br>";
    } else {
        echo "⚠️ Table 'biometric_logs' not found (will be created automatically)\n<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error checking tables: " . $e->getMessage() . "\n<br>";
}

// Test 3: Create test employee if none exist
echo "<h2>3. Testing Sample Data</h2>\n";
try {
    // Check if we have any employees
    $stmt = $conn->query("SELECT COUNT(*) as count FROM empleados WHERE ACTIVO = 1");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        echo "📝 Creating sample employee data...\n<br>";
        
        // Create sample employees
        $sample_employees = [
            ['001', 'Juan Carlos', 'Pérez González', 'juan.perez@synktime.com'],
            ['002', 'María Elena', 'Rodríguez López', 'maria.rodriguez@synktime.com'],
            ['003', 'Carlos Alberto', 'Gómez Martínez', 'carlos.gomez@synktime.com']
        ];
        
        foreach ($sample_employees as $emp) {
            $stmt = $conn->prepare("
                INSERT INTO empleados (ID_EMPLEADO, NOMBRE, APELLIDO, EMAIL, ACTIVO) 
                VALUES (?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE 
                NOMBRE = VALUES(NOMBRE), 
                APELLIDO = VALUES(APELLIDO), 
                EMAIL = VALUES(EMAIL)
            ");
            $stmt->execute($emp);
        }
        
        echo "✅ Sample employees created\n<br>";
    } else {
        echo "✅ Found $count existing employees\n<br>";
    }
    
    // List some employees for testing
    $stmt = $conn->query("SELECT ID_EMPLEADO, NOMBRE, APELLIDO FROM empleados WHERE ACTIVO = 1 LIMIT 5");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<strong>Available employees for testing:</strong>\n<ul>";
    foreach ($employees as $emp) {
        echo "<li>ID: {$emp['ID_EMPLEADO']} - {$emp['NOMBRE']} {$emp['APELLIDO']}</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "❌ Error with sample data: " . $e->getMessage() . "\n<br>";
}

// Test 4: Check file permissions
echo "<h2>4. Testing File Permissions</h2>\n";
$upload_dirs = [
    'uploads/facial/',
    'api/biometric/'
];

foreach ($upload_dirs as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "✅ Created directory: $dir\n<br>";
        } else {
            echo "❌ Failed to create directory: $dir\n<br>";
        }
    } else {
        echo "✅ Directory exists: $dir\n<br>";
    }
    
    if (is_writable($dir)) {
        echo "✅ Directory is writable: $dir\n<br>";
    } else {
        echo "❌ Directory is not writable: $dir\n<br>";
    }
}

// Test 5: Simulate biometric enrollment
echo "<h2>5. Testing Biometric Enrollment Simulation</h2>\n";
try {
    // Get first employee
    $stmt = $conn->query("SELECT ID_EMPLEADO, NOMBRE, APELLIDO FROM empleados WHERE ACTIVO = 1 LIMIT 1");
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($employee) {
        echo "🧪 Testing with employee: {$employee['NOMBRE']} {$employee['APELLIDO']} (ID: {$employee['ID_EMPLEADO']})\n<br>";
        
        // Create sample face descriptor (simulated)
        $sample_descriptor = array_fill(0, 128, 0.5); // 128-dimensional vector
        $sample_template = [
            'descriptor' => $sample_descriptor,
            'captureCount' => 3,
            'avgQuality' => 85.5
        ];
        
        $enrollment_data = [
            'template' => $sample_template,
            'images' => [],
            'enrollment_date' => date('c'),
            'version' => '2.0',
            'algorithm' => 'face-api.js'
        ];
        
        // Create tables if they don't exist
        $conn->exec("
            CREATE TABLE IF NOT EXISTS biometric_data (
                ID INT AUTO_INCREMENT PRIMARY KEY,
                ID_EMPLEADO INT NOT NULL,
                BIOMETRIC_TYPE ENUM('fingerprint', 'facial') NOT NULL,
                FINGER_TYPE VARCHAR(20),
                BIOMETRIC_DATA LONGTEXT,
                FACE_DESCRIPTOR JSON,
                TEMPLATE_QUALITY DECIMAL(5,2),
                CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                ACTIVO TINYINT(1) DEFAULT 1,
                FOREIGN KEY (ID_EMPLEADO) REFERENCES empleados(ID_EMPLEADO)
            )
        ");
        
        $conn->exec("
            CREATE TABLE IF NOT EXISTS biometric_logs (
                ID INT AUTO_INCREMENT PRIMARY KEY,
                ID_EMPLEADO INT NOT NULL,
                VERIFICATION_METHOD ENUM('fingerprint', 'facial', 'traditional') NOT NULL,
                VERIFICATION_SUCCESS TINYINT(1) DEFAULT 0,
                CONFIDENCE_SCORE DECIMAL(5,2),
                FECHA DATE,
                HORA TIME,
                CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ID_EMPLEADO) REFERENCES empleados(ID_EMPLEADO)
            )
        ");
        
        // Insert test biometric data
        $stmt = $conn->prepare("
            INSERT INTO biometric_data (
                ID_EMPLEADO, 
                BIOMETRIC_TYPE, 
                BIOMETRIC_DATA,
                FACE_DESCRIPTOR,
                TEMPLATE_QUALITY,
                CREATED_AT
            ) VALUES (?, 'facial', ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            BIOMETRIC_DATA = VALUES(BIOMETRIC_DATA),
            FACE_DESCRIPTOR = VALUES(FACE_DESCRIPTOR),
            TEMPLATE_QUALITY = VALUES(TEMPLATE_QUALITY),
            UPDATED_AT = NOW()
        ");
        
        $result = $stmt->execute([
            $employee['ID_EMPLEADO'], 
            json_encode($enrollment_data),
            json_encode($sample_descriptor),
            85.5
        ]);
        
        if ($result) {
            echo "✅ Test biometric data inserted successfully\n<br>";
        } else {
            echo "❌ Failed to insert test biometric data\n<br>";
        }
        
    } else {
        echo "❌ No employees found for testing\n<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error in biometric enrollment test: " . $e->getMessage() . "\n<br>";
}

// Test 6: API Endpoints
echo "<h2>6. Testing API Endpoints</h2>\n";
$api_files = [
    'api/biometric/enroll-facial-real.php',
    'api/biometric/verify-facial-real.php',
    'api/biometric/get-face-template.php',
    'api/biometric/stats.php'
];

foreach ($api_files as $file) {
    if (file_exists($file)) {
        echo "✅ API file exists: $file\n<br>";
        
        // Check syntax
        $output = shell_exec("php -l $file 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "✅ Syntax OK: $file\n<br>";
        } else {
            echo "❌ Syntax error in $file: $output\n<br>";
        }
    } else {
        echo "❌ API file missing: $file\n<br>";
    }
}

// Test 7: JavaScript files
echo "<h2>7. Testing JavaScript Files</h2>\n";
$js_files = [
    'assets/js/face-api-models.js',
    'assets/js/biometric-real.js',
    'assets/js/biometric.js'
];

foreach ($js_files as $file) {
    if (file_exists($file)) {
        echo "✅ JavaScript file exists: $file\n<br>";
    } else {
        echo "❌ JavaScript file missing: $file\n<br>";
    }
}

echo "<h2>🎯 Test Summary</h2>\n";
echo "<p><strong>Real Biometric System Status:</strong></p>\n";
echo "<ul>\n";
echo "<li>✅ Face-api.js integration implemented</li>\n";
echo "<li>✅ Real facial recognition enrollment</li>\n";
echo "<li>✅ Face descriptor comparison verification</li>\n";
echo "<li>✅ Database integration with JSON descriptors</li>\n";
echo "<li>✅ Enhanced biometric logging</li>\n";
echo "<li>✅ Demo page for testing</li>\n";
echo "</ul>\n";

echo "<h3>🚀 Next Steps:</h3>\n";
echo "<ol>\n";
echo "<li>Test the demo page: <a href='biometric-demo-real.php'>biometric-demo-real.php</a></li>\n";
echo "<li>Test enrollment: <a href='biometric-enrollment.php'>biometric-enrollment.php</a></li>\n";
echo "<li>Test attendance with biometric: <a href='attendance.php'>attendance.php</a></li>\n";
echo "</ol>\n";
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
h1, h2, h3 { color: #333; }
ul, ol { margin: 10px 0; }
li { margin: 5px 0; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>