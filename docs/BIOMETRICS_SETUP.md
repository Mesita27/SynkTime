# SynkTime Biometric System Setup Guide

## Overview

This guide covers the setup and configuration of the SynkTime biometric system, including facial recognition and fingerprint verification capabilities.

## Prerequisites

- Docker and Docker Compose installed
- PHP 8.0+ with required extensions (PDO, MySQLi, cURL, GD)
- MySQL/MariaDB database
- HTTPS environment (required for camera access in production)

## Biometric APIs Setup

### 1. Start Biometric Services

```bash
# Navigate to the docker directory
cd docker/

# Start the biometric services
docker-compose -f docker-compose.biometrics.yml up -d

# Verify services are running
docker-compose -f docker-compose.biometrics.yml ps
```

### 2. Verify API Endpoints

#### InsightFace-REST (Facial Recognition)
- URL: `http://localhost:18081`
- Health check: `curl http://localhost:18081/info`

#### SourceAFIS (Fingerprint Recognition)  
- URL: `http://localhost:18082`
- Health check: `curl http://localhost:18082/health`

### 3. Configuration

Update the configuration in `config/biometrics.php`:

```php
// API Endpoints
define('BIOMETRICS_FACE_API_BASE', 'http://localhost:18081');
define('BIOMETRICS_FINGER_API_BASE', 'http://localhost:18082');

// Verification Thresholds
define('FACE_MATCH_THRESHOLD', 0.42);  // Adjust based on accuracy needs
define('FINGER_MATCH_THRESHOLD', 40);   // Adjust based on accuracy needs
```

## Database Setup

The biometric system automatically creates required tables on first use:

- `biometric_data` - Stores biometric templates and embeddings
- `biometric_logs` - Audit trail for all biometric operations
- `holidays_cache` - General holidays
- `dias_civicos` - Company-specific holidays

### Manual Table Creation (Optional)

If you prefer to create tables manually:

```sql
-- Biometric data storage
CREATE TABLE biometric_data (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_EMPLEADO INT NOT NULL,
    BIOMETRIC_TYPE ENUM('fingerprint','facial') NOT NULL,
    FINGER_TYPE VARCHAR(20),
    BIOMETRIC_DATA LONGTEXT,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ACTIVO TINYINT(1) DEFAULT 1,
    INDEX idx_empleado (ID_EMPLEADO),
    INDEX idx_type (BIOMETRIC_TYPE),
    UNIQUE KEY unique_employee_finger (ID_EMPLEADO, FINGER_TYPE)
);

-- Biometric operation logs
CREATE TABLE biometric_logs (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_EMPLEADO INT NOT NULL,
    VERIFICATION_METHOD ENUM('fingerprint','facial','traditional') NOT NULL,
    VERIFICATION_SUCCESS TINYINT(1) DEFAULT 0,
    CONFIDENCE_SCORE DECIMAL(5,4) DEFAULT NULL,
    API_SOURCE VARCHAR(50) DEFAULT NULL,
    OPERATION_TYPE ENUM('enrollment','verification') NOT NULL,
    FECHA DATE,
    HORA TIME,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_empleado (ID_EMPLEADO),
    INDEX idx_fecha (FECHA),
    INDEX idx_method (VERIFICATION_METHOD)
);

-- Update asistencias table for biometric support
ALTER TABLE asistencias 
ADD COLUMN VERIFICATION_METHOD ENUM('fingerprint','facial','traditional') DEFAULT 'traditional';
```

## File Permissions

Ensure proper permissions for upload directories:

```bash
# Create upload directories
mkdir -p public/uploads/asistencia
mkdir -p public/uploads/biometrics

# Set permissions
chmod 755 public/uploads
chmod 755 public/uploads/asistencia
chmod 755 public/uploads/biometrics

# For Apache/Nginx
chown -R www-data:www-data public/uploads
```

## Threshold Configuration

### Facial Recognition Thresholds

- **High Security**: 0.5+ (stricter matching, may reject valid users)
- **Balanced**: 0.42 (recommended default)
- **Permissive**: 0.35 (more accepting, higher false positive risk)

### Fingerprint Recognition Thresholds

- **High Security**: 60+ (stricter matching)
- **Balanced**: 40 (recommended default)
- **Permissive**: 20 (more accepting)

## Camera Requirements

### Supported Browsers
- Chrome 60+
- Firefox 55+
- Safari 11+
- Edge 79+

### Camera Specifications
- Minimum resolution: 640x480
- Recommended: 1280x720 or higher
- Auto-focus capability recommended for fingerprint scanning

### HTTPS Requirements
Camera access requires HTTPS in production environments. For development:

```apache
# Apache virtual host example
<VirtualHost *:443>
    ServerName synktime.local
    DocumentRoot /path/to/synktime
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    # Enable camera API
    Header always set Permissions-Policy "camera=*, microphone=()"
</VirtualHost>
```

## Testing the Setup

### 1. API Connectivity Test

Visit the biometric status endpoint in your browser:
- Facial API: `http://your-domain/api/biometrics/face/status`
- Fingerprint API: `http://your-domain/api/biometrics/fingerprint/status`

### 2. Camera Access Test

1. Navigate to the attendance registration page
2. Select an employee
3. Try opening the facial recognition modal
4. Verify camera permissions are requested and granted

### 3. Enrollment Test

1. Go to the biometric enrollment page (`views/biometria/enrolamiento.php`)
2. Select an employee
3. Complete facial or fingerprint enrollment
4. Verify data is stored in `biometric_data` table

### 4. Verification Test

1. After enrolling biometric data
2. Use the attendance registration page
3. Test biometric verification
4. Check `biometric_logs` for audit trail

## Troubleshooting

### Common Issues

#### Camera Not Working
```
Error: "No se puede acceder a la cámara"
```
**Solutions:**
- Ensure HTTPS is enabled
- Check browser permissions
- Verify camera is not in use by another application

#### API Connection Failed
```
Error: "Error de conexión con API facial/huellas"
```
**Solutions:**
- Verify Docker containers are running
- Check port availability (18081, 18082)
- Verify firewall settings

#### Database Errors
```
Error: "Error de base de datos: Table doesn't exist"
```
**Solutions:**
- Ensure proper database permissions
- Check table creation scripts
- Verify database connection settings

### Performance Optimization

#### Database Optimization
```sql
-- Add indexes for better performance
CREATE INDEX idx_biometric_employee_type ON biometric_data(ID_EMPLEADO, BIOMETRIC_TYPE);
CREATE INDEX idx_logs_employee_date ON biometric_logs(ID_EMPLEADO, FECHA);
CREATE INDEX idx_asistencia_verification ON asistencias(VERIFICATION_METHOD, FECHA);
```

#### API Performance
- Use Redis for caching biometric templates
- Configure API worker processes based on server capacity
- Monitor API response times and adjust timeouts

### Security Considerations

1. **Data Encryption**: Consider encrypting biometric templates at rest
2. **Access Control**: Implement proper role-based access for enrollment
3. **Audit Logging**: Monitor all biometric operations via `biometric_logs`
4. **Regular Backups**: Backup biometric data securely
5. **Template Rotation**: Consider periodic re-enrollment for security

## Monitoring and Maintenance

### Log Monitoring
```bash
# Monitor Docker containers
docker-compose -f docker-compose.biometrics.yml logs -f

# Check API health
curl -f http://localhost:18081/info
curl -f http://localhost:18082/health
```

### Database Maintenance
```sql
-- Clean old logs (keep last 90 days)
DELETE FROM biometric_logs WHERE CREATED_AT < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Optimize tables
OPTIMIZE TABLE biometric_data, biometric_logs, asistencias;
```

### Backup Strategy
```bash
# Backup biometric data
mysqldump -u user -p database_name biometric_data biometric_logs > biometric_backup.sql

# Backup uploaded photos
tar -czf uploads_backup.tar.gz public/uploads/
```

## Support and Documentation

For additional support:
- Check application logs in browser developer tools
- Review Docker container logs
- Consult API documentation for InsightFace-REST and SourceAFIS
- Monitor system performance and adjust thresholds as needed

## API Documentation

### Facial Recognition Endpoints

#### POST /api/biometrics/face/enroll
Enroll facial patterns for an employee.

**Request:**
```json
{
  "id_empleado": 123,
  "images": ["base64_image_1", "base64_image_2", "base64_image_3"]
}
```

#### POST /api/biometrics/face/verify
Verify facial pattern against stored data.

**Request:**
```json
{
  "id_empleado": 123,
  "image": "base64_image_data"
}
```

### Fingerprint Recognition Endpoints

#### POST /api/biometrics/fingerprint/enroll
Enroll fingerprint template for an employee.

**Request:**
```json
{
  "id_empleado": 123,
  "finger_type": "right_index",
  "image": "base64_fingerprint_image"
}
```

#### POST /api/biometrics/fingerprint/verify
Verify fingerprint against stored templates.

**Request:**
```json
{
  "id_empleado": 123,
  "image": "base64_fingerprint_image"
}
```

### Attendance Registration

#### POST /api/asistencia/registrar
Register attendance with biometric verification.

**Request:**
```json
{
  "id_empleado": 123,
  "method": "facial|fingerprint|traditional",
  "payload": {
    "image": "base64_image_data"
  }
}
```