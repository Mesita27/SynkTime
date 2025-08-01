# SynkTime Biometric System Documentation

## Overview
The SynkTime Biometric System enhances the existing attendance management system with advanced biometric verification and enrollment capabilities. This system integrates fingerprint verification, facial recognition, and traditional photo-based verification methods.

## Features Implemented

### 1. Enhanced Attendance Registration
- **Multiple Verification Methods**: Employees can register attendance using three different methods:
  - **Fingerprint Verification**: Uses connected fingerprint scanners for secure verification
  - **Facial Recognition**: Automatic photo capture with facial recognition processing
  - **Traditional Verification**: Manual photo capture (existing functionality maintained)

### 2. Biometric Enrollment Module
- **Dedicated Enrollment Page**: New `biometric-enrollment.php` page for managing employee biometric data
- **Dynamic Employee Loading**: AJAX-based employee search and filtering
- **Device Detection**: Automatic detection of connected biometric devices
- **Separate Enrollment Processes**: 
  - Fingerprint enrollment with finger selection interface
  - Facial pattern enrollment with multiple capture process

### 3. Device Management
- **Automatic Device Detection**: Detects available cameras and fingerprint readers
- **Device Selection Interface**: Users can choose from available devices
- **Real-time Status Updates**: Live feedback on device availability and connection status

## File Structure

### Frontend Files
```
/assets/css/biometric.css              # Biometric system styles
/assets/js/biometric.js                # Main biometric functionality
/assets/js/biometric-enrollment-page.js # Enrollment page specific logic
/biometric-enrollment.php              # Biometric enrollment interface
/attendance.php                        # Enhanced with biometric verification
```

### Component Files
```
/components/biometric_verification_modal.php  # Verification modal components
/components/biometric_enrollment_modal.php    # Enrollment modal components
```

### API Endpoints
```
/api/biometric/enroll-fingerprint.php    # Fingerprint enrollment endpoint
/api/biometric/enroll-facial.php         # Facial enrollment endpoint
/api/biometric/stats.php                 # Biometric statistics
/api/biometric/summary.php               # Employee biometric summary
/api/attendance/register-biometric.php   # Biometric attendance registration
```

## Database Schema

### New Tables Created
The system automatically creates the following tables if they don't exist:

#### `biometric_data`
```sql
CREATE TABLE biometric_data (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_EMPLEADO INT NOT NULL,
    BIOMETRIC_TYPE ENUM('fingerprint', 'facial') NOT NULL,
    FINGER_TYPE VARCHAR(20),
    BIOMETRIC_DATA LONGTEXT,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ACTIVO TINYINT(1) DEFAULT 1,
    FOREIGN KEY (ID_EMPLEADO) REFERENCES empleados(ID_EMPLEADO),
    UNIQUE KEY unique_employee_finger (ID_EMPLEADO, FINGER_TYPE)
);
```

#### `biometric_logs`
```sql
CREATE TABLE biometric_logs (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_EMPLEADO INT NOT NULL,
    VERIFICATION_METHOD ENUM('fingerprint', 'facial', 'traditional') NOT NULL,
    VERIFICATION_SUCCESS TINYINT(1) DEFAULT 0,
    FECHA DATE,
    HORA TIME,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_EMPLEADO) REFERENCES empleados(ID_EMPLEADO)
);
```

### Enhanced Existing Tables
The `asistencias` table has been enhanced with:
- `VERIFICATION_METHOD` field to track verification type
- Compatibility with biometric verification workflows

## Usage Guide

### For Administrators

#### Setting Up Biometric Enrollment
1. Navigate to **Inscripción Biométrica** in the sidebar
2. Use filters to find specific employees or view all
3. Click **Inscribir Empleado** to open the enrollment modal
4. Select an employee from the list
5. Choose fingerprint or facial enrollment
6. Follow the guided enrollment process

#### Monitoring Enrollment Status
- View real-time statistics on the enrollment dashboard
- Track completion rates by sede/establecimiento
- Monitor device connectivity status

### For Employees (Attendance Registration)

#### Using Biometric Verification
1. Go to **Asistencias** page
2. Click **Registrar Asistencia**
3. Select employee and click **Verificar** (biometric) or **Tradicional**
4. Choose verification method:
   - **Fingerprint**: Place finger on reader when prompted
   - **Facial**: Position face in camera frame for automatic capture
   - **Traditional**: Manual photo capture process

## Technical Implementation

### Device Detection
- **Camera Detection**: Uses `navigator.mediaDevices.getUserMedia()` to detect cameras
- **Fingerprint Detection**: Simulated using `window.PublicKeyCredential` (expandable for real hardware)
- **Real-time Status**: Live updates on device availability

### Security Features
- **Session Validation**: All API endpoints require active user sessions
- **Data Encryption**: Biometric data is base64 encoded for storage
- **Audit Logging**: All biometric operations are logged with timestamps

### Error Handling
- **Graceful Degradation**: Falls back to traditional methods if biometric devices unavailable
- **User Feedback**: Clear error messages and retry options
- **Timeout Management**: Automatic handling of device connection timeouts

## Browser Compatibility
- **Modern Browsers**: Chrome 60+, Firefox 55+, Safari 11+, Edge 79+
- **Camera API**: Requires HTTPS in production environments
- **Device APIs**: Progressive enhancement for biometric hardware

## Future Enhancements
- Integration with hardware-specific SDKs
- Advanced facial recognition algorithms
- Multi-factor authentication options
- Biometric template encryption
- Mobile device support

## Troubleshooting

### Common Issues
1. **Camera Access Denied**: Check browser permissions and HTTPS requirements
2. **Fingerprint Device Not Detected**: Verify device drivers and browser compatibility
3. **Enrollment Fails**: Check database permissions and storage space

### API Error Codes
- `400`: Bad Request - Invalid or missing parameters
- `401`: Unauthorized - Session expired or invalid
- `500`: Internal Server Error - Database or file system issues

## Configuration
No additional configuration required. The system integrates seamlessly with existing SynkTime installation and automatically creates required database tables on first use.

## Support
For technical support or feature requests, refer to the SynkTime system documentation or contact the development team.