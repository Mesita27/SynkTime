# SynkTime Biometric System Setup

This guide provides quick setup instructions for the SynkTime biometric attendance system with facial recognition and fingerprint verification capabilities.

## Prerequisites

- PHP 7.4+ (recommended 8.1+)
- MySQL 5.7+ / MariaDB 10.3+
- Docker & Docker Compose (for biometric services)
- Web server (Apache/Nginx)
- Modern web browser with camera support

## Quick Start

### 1. Configure Biometric Services

Copy and edit the configuration file:
```bash
cp config/biometrics.php.example config/biometrics.php
# Edit the API endpoints if needed
```

Default configuration:
- Face API: `http://localhost:18081` (InsightFace-REST)
- Fingerprint API: `http://localhost:18082` (SourceAFIS HTTP)

### 2. Start Biometric Services with Docker

```bash
# Start the biometric services
docker-compose -f docker/docker-compose.biometrics.yml up -d

# Verify services are running
curl http://localhost:18081/docs  # InsightFace-REST API docs
curl http://localhost:18082/      # SourceAFIS HTTP status
```

### 3. Setup Database

The system will automatically create required tables:
- `biometric_data` - Stores facial patterns and fingerprint templates
- `biometric_logs` - Audit trail for biometric operations

### 4. Configure Upload Directories

```bash
# Create upload directories with proper permissions
mkdir -p uploads/asistencia uploads/biometrics
chmod 755 uploads/asistencia uploads/biometrics
```

### 5. Configure Web Server

Ensure your web server serves the application and has:
- PHP modules: PDO, GD, cURL, JSON
- Upload limits: `upload_max_filesize = 10M`, `post_max_size = 10M`
- For production: HTTPS enabled (required for camera access)

## Docker Services Configuration

### InsightFace-REST (Facial Recognition)

```yaml
# docker/docker-compose.biometrics.yml (excerpt)
insightface-rest:
  image: mesita27/insightface-rest:latest
  ports:
    - "18081:18080"
  environment:
    - PYTHONUNBUFFERED=1
    - FORCE_CPU_ONLY=1  # Set to 0 for GPU acceleration
```

Key endpoints:
- `POST /extract` - Extract facial embeddings
- `POST /detect` - Detect faces in image

### SourceAFIS HTTP (Fingerprint Recognition)

```yaml
# docker/docker-compose.biometrics.yml (excerpt)
sourceafis-http:
  image: mesita27/sourceafis-http:latest
  ports:
    - "18082:8080"
  environment:
    - JAVA_OPTS=-Xmx512m
```

Key endpoints:
- `POST /template` - Generate fingerprint template
- `POST /verify` - Verify fingerprint against template

## Configuration Options

### Biometric Thresholds

Adjust matching sensitivity in `config/biometrics.php`:

```php
'FACE_MATCH_THRESHOLD' => 0.42,    // Lower = more strict
'FINGER_MATCH_THRESHOLD' => 40,    // Higher = more strict
```

### Upload Settings

```php
'MAX_IMAGE_SIZE' => 5 * 1024 * 1024,  // 5MB max file size
'ALLOWED_IMAGE_TYPES' => ['image/jpeg', 'image/png', 'image/webp'],
```

### Feature Toggles

```php
'FEATURES' => [
    'face_enrollment' => true,
    'face_verification' => true,
    'fingerprint_enrollment' => true,
    'fingerprint_verification' => true,
    'traditional_capture' => true,
]
```

## Testing the Installation

### 1. Verify Services

```bash
# Test facial recognition service
curl -X POST http://localhost:18081/extract \
  -H "Content-Type: application/json" \
  -d '{"images": {"data": ["base64_image_data_here"]}}'

# Test fingerprint service
curl -X POST http://localhost:18082/template \
  -H "Content-Type: application/json" \
  -d '{"image": "base64_fingerprint_data_here"}'
```

### 2. Test Web Interface

1. Navigate to the enrollment page: `/views/biometria/enrolamiento.php`
2. Select an employee and try facial enrollment
3. Navigate to attendance page: `/views/asistencia/index.php`
4. Test biometric verification

## Troubleshooting

### Common Issues

**Services not responding:**
```bash
# Check if containers are running
docker ps | grep -E "(insightface|sourceafis)"

# Check logs
docker-compose -f docker/docker-compose.biometrics.yml logs
```

**Camera access denied:**
- Ensure HTTPS in production
- Check browser permissions
- Verify camera is not used by another application

**Database errors:**
- Check MySQL connection in `config/database.php`
- Verify user permissions for table creation
- Ensure sufficient disk space

**Upload failures:**
- Check directory permissions: `chmod 755 uploads/`
- Verify PHP upload limits
- Check available disk space

### Performance Optimization

**For CPU-only deployment:**
- Face recognition: ~2-3 seconds per verification
- Fingerprint: ~1-2 seconds per verification

**For GPU acceleration:**
- Edit docker-compose.yml: `FORCE_CPU_ONLY=0`
- Requires NVIDIA Docker runtime
- Face recognition: ~0.5-1 second per verification

### Security Considerations

1. **HTTPS Required**: Camera access requires HTTPS in production
2. **API Security**: Consider implementing API authentication for biometric services
3. **Data Protection**: Biometric templates are irreversible but consider encryption at rest
4. **Network Security**: Restrict access to biometric service ports (18081, 18082)

## Development Mode

For development without Docker services:

```php
// In config/biometrics.php, set features to false or use mock services
'FEATURES' => [
    'face_verification' => false,    // Disable if no face API
    'fingerprint_verification' => false, // Disable if no fingerprint API
    'traditional_capture' => true,      // Always available
]
```

## Support

For technical support:
- Check application logs in `biometric_logs` table
- Review Docker service logs
- Verify network connectivity between services
- Test API endpoints individually

For feature requests or bug reports, refer to the project documentation.