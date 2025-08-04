# SynkTime Database Migration Guide

## Overview

This guide provides instructions for migrating data to work with the updated SynkTime Node.js/React system. The system has been designed to work with the existing PHP database schema, so minimal migration is required.

## Pre-Migration Checklist

### ✅ System Requirements
- MariaDB 10.3+ or MySQL 5.7+
- Node.js 16+
- Existing PHP SynkTime database
- Backup of current database

### ✅ Backup Procedures
```sql
-- Create a complete backup before migration
mysqldump -u username -p synktime > synktime_backup_$(date +%Y%m%d_%H%M%S).sql

-- Verify backup integrity
mysql -u username -p synktime < synktime_backup_file.sql
```

## Database Schema Validation

### ✅ Required Tables
Ensure these tables exist with correct structure:

```sql
-- Check if core tables exist
SELECT TABLE_NAME 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'synktime' 
AND TABLE_NAME IN (
    'EMPRESA', 'SEDE', 'ESTABLECIMIENTO', 'EMPLEADO', 
    'USUARIO', 'HORARIO', 'EMPLEADO_HORARIO', 'ASISTENCIA'
);
```

### ✅ Table Structure Verification
```sql
-- Verify USUARIO table structure
DESCRIBE USUARIO;

-- Should have these fields:
-- ID_USUARIO, USERNAME, CONTRASENA, NOMBRE_COMPLETO, EMAIL, ROL, ID_EMPRESA, ESTADO

-- Verify EMPLEADO table structure  
DESCRIBE EMPLEADO;

-- Should have these fields:
-- ID_EMPLEADO, NOMBRE, APELLIDO, DNI, CORREO, TELEFONO, ID_ESTABLECIMIENTO, 
-- FECHA_INGRESO, ESTADO, ACTIVO
```

## Data Migration Steps

### Step 1: Update User Passwords (if needed)

If your system uses plain text passwords, they will be automatically upgraded to bcrypt hashes on first login. No manual migration needed.

```sql
-- Check current password format
SELECT USERNAME, 
       CASE 
         WHEN CONTRASENA LIKE '$2%' THEN 'bcrypt' 
         ELSE 'plain_text' 
       END as password_type
FROM USUARIO 
LIMIT 5;
```

### Step 2: Verify Company Hierarchy

Ensure the company-sede-establecimiento-empleado hierarchy is correct:

```sql
-- Verify company structure
SELECT 
    e.NOMBRE as empresa,
    s.NOMBRE as sede,
    est.NOMBRE as establecimiento,
    COUNT(emp.ID_EMPLEADO) as empleados_count
FROM EMPRESA e
LEFT JOIN SEDE s ON e.ID_EMPRESA = s.ID_EMPRESA
LEFT JOIN ESTABLECIMIENTO est ON s.ID_SEDE = est.ID_SEDE
LEFT JOIN EMPLEADO emp ON est.ID_ESTABLECIMIENTO = emp.ID_ESTABLECIMIENTO
WHERE e.ESTADO = 'A'
GROUP BY e.ID_EMPRESA, s.ID_SEDE, est.ID_ESTABLECIMIENTO
ORDER BY e.NOMBRE, s.NOMBRE, est.NOMBRE;
```

### Step 3: Create Biometric Tables (if not exists)

The system will automatically create these tables, but you can create them manually:

```sql
-- Create biometric_data table
CREATE TABLE IF NOT EXISTS biometric_data (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_EMPLEADO INT NOT NULL,
    BIOMETRIC_TYPE ENUM('fingerprint', 'facial') NOT NULL,
    FINGER_TYPE VARCHAR(20),
    BIOMETRIC_DATA LONGTEXT,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ACTIVO TINYINT(1) DEFAULT 1,
    FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO),
    UNIQUE KEY unique_employee_finger (ID_EMPLEADO, FINGER_TYPE)
);

-- Create biometric_logs table
CREATE TABLE IF NOT EXISTS biometric_logs (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_EMPLEADO INT NOT NULL,
    VERIFICATION_METHOD ENUM('fingerprint', 'facial', 'traditional') NOT NULL,
    VERIFICATION_SUCCESS TINYINT(1) DEFAULT 0,
    FECHA DATE,
    HORA TIME,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO)
);
```

### Step 4: Add Missing Indexes (for performance)

```sql
-- Add performance indexes if they don't exist
CREATE INDEX IF NOT EXISTS idx_empleado_dni ON EMPLEADO(DNI);
CREATE INDEX IF NOT EXISTS idx_asistencia_empleado_fecha ON ASISTENCIA(ID_EMPLEADO, FECHA);
CREATE INDEX IF NOT EXISTS idx_asistencia_fecha_hora ON ASISTENCIA(FECHA, HORA);
CREATE INDEX IF NOT EXISTS idx_usuario_username ON USUARIO(USERNAME);
CREATE INDEX IF NOT EXISTS idx_biometric_empleado_type ON biometric_data(ID_EMPLEADO, BIOMETRIC_TYPE);
```

## Data Validation

### ✅ User Validation
```sql
-- Check for users without companies
SELECT u.ID_USUARIO, u.USERNAME, u.NOMBRE_COMPLETO
FROM USUARIO u
LEFT JOIN EMPRESA e ON u.ID_EMPRESA = e.ID_EMPRESA
WHERE e.ID_EMPRESA IS NULL OR e.ESTADO != 'A';

-- Fix: Assign users to valid companies or deactivate
UPDATE USUARIO SET ESTADO = 'I' WHERE ID_EMPRESA NOT IN (
    SELECT ID_EMPRESA FROM EMPRESA WHERE ESTADO = 'A'
);
```

### ✅ Employee Validation
```sql
-- Check for employees without valid establishments
SELECT emp.ID_EMPLEADO, emp.NOMBRE, emp.APELLIDO
FROM EMPLEADO emp
LEFT JOIN ESTABLECIMIENTO est ON emp.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
LEFT JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
LEFT JOIN EMPRESA e ON s.ID_EMPRESA = e.ID_EMPRESA
WHERE est.ID_ESTABLECIMIENTO IS NULL 
   OR est.ESTADO != 'A' 
   OR s.ESTADO != 'A' 
   OR e.ESTADO != 'A';

-- Fix: Deactivate employees with invalid establishments
UPDATE EMPLEADO SET ACTIVO = 'N', ESTADO = 'I' 
WHERE ID_ESTABLECIMIENTO NOT IN (
    SELECT est.ID_ESTABLECIMIENTO 
    FROM ESTABLECIMIENTO est
    JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
    JOIN EMPRESA e ON s.ID_EMPRESA = e.ID_EMPRESA
    WHERE est.ESTADO = 'A' AND s.ESTADO = 'A' AND e.ESTADO = 'A'
);
```

### ✅ Attendance Validation
```sql
-- Check for attendance records with invalid employees
SELECT COUNT(*) as invalid_attendance_records
FROM ASISTENCIA a
LEFT JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
WHERE e.ID_EMPLEADO IS NULL;

-- Optional: Clean up invalid attendance records
DELETE FROM ASISTENCIA WHERE ID_EMPLEADO NOT IN (
    SELECT ID_EMPLEADO FROM EMPLEADO WHERE ACTIVO = 'S'
);
```

## Configuration Updates

### ✅ Backend Configuration
Update the backend `.env` file:

```env
# Database Configuration (using same DB as PHP system)
DB_HOST=localhost
DB_USER=your_db_user
DB_PASSWORD=your_db_password
DB_NAME=synktime

# JWT Configuration
JWT_SECRET=your-secure-jwt-secret-key
JWT_EXPIRES_IN=7d

# API Configuration
API_PREFIX=/api/v1
CORS_ORIGIN=http://localhost:3000

# Server Configuration
NODE_ENV=development
PORT=3001
```

### ✅ Frontend Configuration
Create frontend `.env` file:

```env
# API Configuration
VITE_API_BASE_URL=http://localhost:3001/api/v1

# Environment
VITE_NODE_ENV=development
```

## Testing Migration

### ✅ Authentication Test
```bash
# Test login endpoint
curl -X POST http://localhost:3001/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username": "your_username", "password": "your_password"}'
```

### ✅ Employee Data Test
```bash
# Test employee endpoint (requires authentication token)
curl -X GET http://localhost:3001/api/v1/employees \
  -H "Authorization: Bearer your_jwt_token"
```

### ✅ Database Connectivity Test
```bash
# Test health endpoint
curl http://localhost:3001/health
```

## Common Migration Issues

### Issue 1: Password Authentication Fails
**Problem**: Users can't log in after migration
**Solution**: Check password format and ensure CONTRASENA field is correct

```sql
-- Check password field
SELECT ID_USUARIO, USERNAME, LENGTH(CONTRASENA), 
       SUBSTRING(CONTRASENA, 1, 10) as password_preview
FROM USUARIO WHERE USERNAME = 'your_test_user';
```

### Issue 2: Employee Not Found
**Problem**: Employee queries return no results
**Solution**: Verify company hierarchy and status fields

```sql
-- Debug employee query
SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, 
       e.ESTADO, e.ACTIVO,
       est.NOMBRE as establecimiento,
       est.ESTADO as est_estado,
       s.NOMBRE as sede,
       s.ESTADO as sede_estado,
       emp.NOMBRE as empresa,
       emp.ESTADO as empresa_estado
FROM EMPLEADO e
JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
WHERE e.DNI = 'employee_dni';
```

### Issue 3: Foreign Key Constraints
**Problem**: Cannot insert/update records due to foreign key constraints
**Solution**: Ensure parent records exist and are active

```sql
-- Check foreign key relationships
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'synktime'
AND REFERENCED_TABLE_NAME IS NOT NULL;
```

## Post-Migration Validation

### ✅ Complete System Test
1. **Authentication**: Test user login with both PHP and Node.js systems
2. **Employee Management**: Verify employee CRUD operations
3. **Attendance**: Test attendance registration with different verification methods
4. **Biometric**: Test biometric enrollment and verification
5. **Reports**: Verify dashboard statistics and reports

### ✅ Performance Verification
```sql
-- Check query performance with EXPLAIN
EXPLAIN SELECT 
    e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO,
    est.NOMBRE as establecimiento
FROM EMPLEADO e
JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
WHERE s.ID_EMPRESA = 1 AND e.ACTIVO = 'S';
```

### ✅ Data Integrity Check
```sql
-- Verify no orphaned records
SELECT 'Employees without establishments' as issue, COUNT(*) as count
FROM EMPLEADO e
LEFT JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
WHERE est.ID_ESTABLECIMIENTO IS NULL

UNION ALL

SELECT 'Attendance without employees' as issue, COUNT(*) as count
FROM ASISTENCIA a
LEFT JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
WHERE e.ID_EMPLEADO IS NULL

UNION ALL

SELECT 'Users without companies' as issue, COUNT(*) as count
FROM USUARIO u
LEFT JOIN EMPRESA emp ON u.ID_EMPRESA = emp.ID_EMPRESA
WHERE emp.ID_EMPRESA IS NULL;
```

## Rollback Procedures

### ✅ Emergency Rollback
If issues occur, you can rollback to the PHP system:

```bash
# Stop Node.js services
pm2 stop synktime-backend
pm2 stop synktime-frontend

# Restore database backup if needed
mysql -u username -p synktime < synktime_backup_file.sql

# Restart PHP services (Apache/Nginx)
systemctl restart apache2  # or nginx
```

### ✅ Selective Rollback
```sql
-- Rollback only biometric tables if needed
DROP TABLE IF EXISTS biometric_logs;
DROP TABLE IF EXISTS biometric_data;
```

## Support and Troubleshooting

### ✅ Log Files
- **Backend Logs**: Check console output or log files
- **Database Logs**: Check MySQL/MariaDB error logs
- **Frontend Logs**: Check browser console for errors

### ✅ Common Commands
```bash
# Check database connections
mysql -u username -p -e "SELECT 1"

# Check Node.js service status
pm2 status

# Check frontend build
cd frontend && npm run build

# Test API endpoints
curl -I http://localhost:3001/health
```

## Conclusion

This migration maintains full compatibility with existing data while providing enhanced functionality through the modern Node.js/React architecture. The system can run in parallel with the PHP system during transition, ensuring minimal disruption to operations.

For additional support, refer to:
- `DATABASE_SCHEMA.md` - Complete database documentation
- `MIGRATION_DOCUMENTATION.md` - Technical architecture details
- `README.md` - General system information