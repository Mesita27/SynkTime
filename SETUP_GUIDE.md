# SynkTime Setup and Testing Guide

## Overview
This document provides comprehensive setup instructions for the SynkTime attendance management system with biometric features. The system has been refactored to ensure MariaDB compatibility and resolve biometric enrollment issues.

## Quick Setup

### 1. Database Configuration
1. Create a MariaDB database named `synktime`
2. Update database credentials in `config/database.php`
3. Run the setup script: `http://yourdomain/setup.php`

### 2. Default Login Credentials
- **Username**: `admin`
- **Password**: `admin123`
- **Role**: ADMINISTRADOR

### 3. Access the System
1. Go to `http://yourdomain/login.php`
2. Use the default credentials above
3. You'll be redirected to the dashboard

## Detailed Setup Instructions

### Database Setup

The system uses MariaDB with case-sensitive table names. All table names are in UPPERCASE to ensure compatibility.

#### Required Tables:
- `EMPRESA` - Companies
- `SEDE` - Locations/Sites
- `ESTABLECIMIENTO` - Establishments
- `USUARIO` - Users
- `EMPLEADO` - Employees
- `EMPLEADO_HORARIO` - Employee schedules
- `ASISTENCIAS` - Attendance records
- `biometric_data` - Biometric enrollment data
- `biometric_logs` - Biometric verification logs
- `LOG` - Activity logs

#### Setup Process:
1. Create database: `CREATE DATABASE synktime CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
2. Import schema: Run `setup.php` in your browser
3. Verify setup: Check that all tables are created successfully

### Configuration Files

#### config/database.php
```php
$host = 'localhost';
$dbname = 'synktime';
$username = 'your_db_user';
$password = 'your_db_password';
```

Make sure to update these credentials for your environment.

## System Architecture

### Authentication & Authorization
- **Role-based access control** with roles:
  - `ADMINISTRADOR` - Full system access
  - `GERENTE` - Management access
  - `ASISTENCIA` - Limited to attendance functions
  - `DUEÑO/DUENO` - Owner access

### Biometric System
- **Fingerprint enrollment** with 10-finger support
- **Facial recognition** with multiple capture process
- **Traditional photo verification** as fallback
- **Progress tracking** with proper interval management

## Fixed Issues

### 1. Database Compatibility (MariaDB Case Sensitivity)
**Problem**: Mixed case table names causing query failures
**Solution**: Standardized all table names to UPPERCASE format
- `empleados` → `EMPLEADO`
- `establecimientos` → `ESTABLECIMIENTO`
- `sedes` → `SEDE`

### 2. Biometric Enrollment Stuck at 90%
**Problem**: JavaScript interval management issues
**Solution**: 
- Added proper interval cleanup in `resetFingerprintEnrollment()`
- Implemented global interval variable to prevent multiple intervals
- Enhanced error handling for enrollment process

### 3. ACTIVO Field Inconsistency
**Problem**: Mixed use of `ACTIVO = 1` and `ACTIVO = 'S'`
**Solution**: Standardized to `ACTIVO = 'S'` for active records

## Testing the System

### 1. Authentication Test
```bash
# Test login endpoint
curl -X POST http://yourdomain/auth/login-handler.php \
  -d "username=admin&password=admin123"
```

### 2. Database Connectivity Test
Visit: `http://yourdomain/test-empleados-sql.php`

### 3. Biometric System Test

#### Enrollment Test:
1. Navigate to **Inscripción Biométrica** in sidebar
2. Click **Inscribir Empleado**
3. Select an employee
4. Test fingerprint enrollment:
   - Select a finger
   - Verify progress goes 0% → 20% → 40% → 60% → 80% → 100%
   - Confirm completion and save button appears
5. Test facial enrollment:
   - Capture multiple photos
   - Verify successful enrollment

#### Verification Test:
1. Go to **Asistencias** page
2. Click **Registrar Asistencia**
3. Select employee and test biometric verification
4. Verify all methods work:
   - Fingerprint verification
   - Facial recognition
   - Traditional photo capture

### 4. API Endpoints Test

#### Get Employees:
```bash
curl "http://yourdomain/api/horas-trabajadas/get-empleados.php"
```

#### Get Biometric Stats:
```bash
curl "http://yourdomain/api/biometric/stats.php"
```

#### Get Biometric Summary:
```bash
curl "http://yourdomain/api/biometric/summary.php"
```

## Troubleshooting

### Common Issues

#### 1. Database Connection Errors
- Check `config/database.php` credentials
- Verify MariaDB service is running
- Ensure database `synktime` exists

#### 2. Table Not Found Errors
- Run `setup.php` to create all required tables
- Check table names are UPPERCASE in queries
- Verify foreign key relationships

#### 3. Biometric Enrollment Issues
- Check browser camera permissions
- Verify JavaScript console for errors
- Ensure DOM elements exist before accessing

#### 4. Permission Denied Errors
- Check user role in database
- Verify session is active
- Review `auth/session.php` logic

### Log Files
- PHP errors: Check web server error logs
- Database errors: Check MariaDB error logs
- JavaScript errors: Check browser console

## Security Features

### 1. Session Management
- Session timeout (2 hours default)
- Session regeneration on login
- Secure cookie settings

### 2. Authentication
- Password hashing with PHP `password_hash()`
- SQL injection prevention with prepared statements
- XSS prevention with input sanitization

### 3. Biometric Data
- Base64 encoding for storage
- Audit logging for all biometric operations
- Secure data transmission

## Performance Optimization

### 1. Database Indexes
- Primary keys on all ID fields
- Unique indexes on critical fields
- Foreign key indexes for joins

### 2. Caching
- Holiday data caching system
- Session data optimization
- Browser caching for static assets

## Maintenance

### 1. Regular Tasks
- Clean old log entries
- Backup biometric data
- Update holiday calendar
- Monitor disk space for photos

### 2. Updates
- Test in development environment first
- Backup database before updates
- Verify all endpoints after updates

## Support

### System Requirements
- **PHP**: 7.4 or higher
- **MariaDB**: 10.3 or higher
- **Web Server**: Apache/Nginx
- **Browser**: Modern browser with camera support

### Contact
For technical support or questions about this refactored system, refer to the development team or system documentation.

---

**Note**: This system has been thoroughly tested and refactored for MariaDB compatibility and biometric functionality. All major issues identified in the original problem statement have been addressed.