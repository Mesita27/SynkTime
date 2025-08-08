# SynkTime Biometric Integration Guide

## Quick Start

### 1. Database Setup
Execute the migration script to create biometric tables:
```sql
SOURCE scripts/db/migrations/20250808_biometrics.sql;
```

### 2. Start Fingerprint API
```bash
cd integrations/fingerprint-api/
dotnet restore
dotnet run --urls=http://localhost:5058
```

### 3. Configure External Face API
Set environment variables or update `config/biometrics.php`:
```bash
export FACE_API_BASE_URL="http://your-compreface-server:8000"
export FACE_API_KEY="your-api-key"
export FINGERPRINT_API_BASE_URL="http://localhost:5058"
```

### 4. Integration with Existing Attendance

To integrate with the existing attendance system, update your attendance registration endpoint to call the biometric verification:

```php
// In your existing attendance registration logic
require_once 'lib/db.php';

function register_attendance_with_biometric($employeeId, $verification_method = 'traditional', $biometric_data = null) {
    $pdo = db();
    
    // Your existing attendance logic here
    $attendanceId = insert_attendance_record($employeeId, $verification_method);
    
    // Link biometric event if provided
    if ($biometric_data && isset($biometric_data['event_id'])) {
        $stmt = $pdo->prepare("UPDATE biometric_event SET attendance_id = ? WHERE id = ?");
        $stmt->execute([$attendanceId, $biometric_data['event_id']]);
    }
    
    return $attendanceId;
}
```

### 5. Frontend Integration

Add biometric verification to your existing attendance modal by including the biometric views:

```php
<!-- In your existing attendance registration modal -->
<div class="modal-body">
    <!-- Your existing form fields -->
    
    <!-- Add biometric verification tabs -->
    <?php include 'views/biometrics/attendance.php'; ?>
</div>
```

### 6. Employee Enrollment

Create a new page or add to existing employee management:

```php
<!-- biometric-enrollment.php -->
<?php
require_once 'auth/session.php';
requireModuleAccess('employee'); // or appropriate permission
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Enrolamiento Biométrico | SynkTime</title>
    <!-- Your existing head includes -->
</head>
<body>
    <div class="app-container">
        <?php include 'components/sidebar.php'; ?>
        <div class="main-wrapper">
            <?php include 'components/header.php'; ?>
            <main class="main-content">
                <?php include 'views/biometrics/enroll.php'; ?>
            </main>
        </div>
    </div>
    <!-- Your existing scripts -->
</body>
</html>
```

## API Endpoints Summary

### Face Recognition
- `POST /api/biometrics/enroll_face.php` - Enroll employee face
- `POST /api/biometrics/recognize_face.php` - Recognize face for attendance

### Fingerprint
- `POST /api/biometrics/enroll_fingerprint.php` - Enroll employee fingerprint  
- `POST /api/biometrics/identify_fingerprint.php` - Identify fingerprint for attendance

### Attendance
- `POST /api/biometrics/mark_attendance.php` - Mark attendance with biometric verification

## Security Considerations

1. **HTTPS Required**: Camera and biometric APIs require HTTPS in production
2. **API Keys**: Secure your CompreFace API keys in environment variables
3. **File Permissions**: Ensure storage directories have proper permissions (0775)
4. **Database Access**: Use prepared statements (already implemented)

## Troubleshooting

### Camera Access Issues
- Ensure HTTPS is enabled in production
- Check browser permissions for camera access
- Verify getUserMedia API support

### Fingerprint API Issues
- Check if .NET 8 SDK is installed: `dotnet --version`
- Verify API is running: `curl http://localhost:5058/health`
- Check API logs for specific errors

### Database Issues
- Verify tables were created: `SHOW TABLES LIKE 'biometric_%';`
- Check foreign key constraints match your employee table structure
- Ensure database user has CREATE and INSERT permissions

## Monitoring and Maintenance

### Biometric Event Logs
Query attendance patterns:
```sql
SELECT 
    e.NOMBRE, e.APELLIDO,
    be.type as verification_method,
    be.created_at,
    be.score
FROM biometric_event be
JOIN empleado e ON be.employee_id = e.ID_EMPLEADO
WHERE be.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY be.created_at DESC;
```

### System Health Checks
- Monitor fingerprint API uptime
- Check external face API connectivity
- Verify storage directory disk space
- Review biometric event success rates