# SynkTime Database Schema Documentation

## Overview

This document describes the database structure used by the SynkTime attendance management system. The system uses MariaDB/MySQL with table names in UPPERCASE following the legacy PHP system conventions.

## Database Tables

### Core Business Tables

#### 1. EMPRESA (Companies)
Main company/organization table.

```sql
CREATE TABLE EMPRESA (
    ID_EMPRESA INT AUTO_INCREMENT PRIMARY KEY,
    NOMBRE VARCHAR(255) NOT NULL,
    RUC VARCHAR(20) UNIQUE,
    DIRECCION TEXT,
    TELEFONO VARCHAR(20),
    EMAIL VARCHAR(100),
    ESTADO ENUM('A', 'I') DEFAULT 'A',
    FECHA_CREACION TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FECHA_ACTUALIZACION TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Fields:**
- `ID_EMPRESA`: Primary key
- `NOMBRE`: Company name
- `RUC`: Tax identification number (unique)
- `DIRECCION`: Company address
- `TELEFONO`: Phone number
- `EMAIL`: Email address
- `ESTADO`: Status ('A'=Active, 'I'=Inactive)

#### 2. SEDE (Offices/Sites)
Company locations/offices.

```sql
CREATE TABLE SEDE (
    ID_SEDE INT AUTO_INCREMENT PRIMARY KEY,
    ID_EMPRESA INT NOT NULL,
    NOMBRE VARCHAR(255) NOT NULL,
    DIRECCION TEXT,
    TELEFONO VARCHAR(20),
    ESTADO ENUM('A', 'I') DEFAULT 'A',
    FECHA_CREACION TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_EMPRESA) REFERENCES EMPRESA(ID_EMPRESA)
);
```

**Fields:**
- `ID_SEDE`: Primary key
- `ID_EMPRESA`: Foreign key to EMPRESA
- `NOMBRE`: Office/site name
- `DIRECCION`: Office address
- `TELEFONO`: Office phone
- `ESTADO`: Status ('A'=Active, 'I'=Inactive)

#### 3. ESTABLECIMIENTO (Establishments)
Specific establishments within offices.

```sql
CREATE TABLE ESTABLECIMIENTO (
    ID_ESTABLECIMIENTO INT AUTO_INCREMENT PRIMARY KEY,
    ID_SEDE INT NOT NULL,
    NOMBRE VARCHAR(255) NOT NULL,
    DIRECCION TEXT,
    ESTADO ENUM('A', 'I') DEFAULT 'A',
    FECHA_CREACION TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_SEDE) REFERENCES SEDE(ID_SEDE)
);
```

**Fields:**
- `ID_ESTABLECIMIENTO`: Primary key
- `ID_SEDE`: Foreign key to SEDE
- `NOMBRE`: Establishment name
- `DIRECCION`: Establishment address
- `ESTADO`: Status ('A'=Active, 'I'=Inactive)

### User Management Tables

#### 4. USUARIO (Users)
System users with authentication.

```sql
CREATE TABLE USUARIO (
    ID_USUARIO INT AUTO_INCREMENT PRIMARY KEY,
    USERNAME VARCHAR(50) UNIQUE NOT NULL,
    CONTRASENA VARCHAR(255) NOT NULL,
    NOMBRE_COMPLETO VARCHAR(255) NOT NULL,
    EMAIL VARCHAR(100),
    ROL ENUM('ADMIN', 'SUPERVISOR', 'ASISTENCIA') NOT NULL,
    ID_EMPRESA INT NOT NULL,
    ESTADO ENUM('A', 'I') DEFAULT 'A',
    FECHA_CREACION TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ULTIMO_ACCESO TIMESTAMP NULL,
    FOREIGN KEY (ID_EMPRESA) REFERENCES EMPRESA(ID_EMPRESA)
);
```

**Fields:**
- `ID_USUARIO`: Primary key
- `USERNAME`: Login username (unique)
- `CONTRASENA`: Password (hashed with bcrypt)
- `NOMBRE_COMPLETO`: Full name
- `EMAIL`: Email address
- `ROL`: User role (ADMIN, SUPERVISOR, ASISTENCIA)
- `ID_EMPRESA`: Foreign key to EMPRESA
- `ESTADO`: Status ('A'=Active, 'I'=Inactive)

#### 5. EMPLEADO (Employees)
Employee records.

```sql
CREATE TABLE EMPLEADO (
    ID_EMPLEADO INT AUTO_INCREMENT PRIMARY KEY,
    NOMBRE VARCHAR(100) NOT NULL,
    APELLIDO VARCHAR(100) NOT NULL,
    DNI VARCHAR(20) UNIQUE NOT NULL,
    CORREO VARCHAR(100),
    TELEFONO VARCHAR(20),
    ID_ESTABLECIMIENTO INT NOT NULL,
    FECHA_INGRESO DATE NOT NULL,
    ESTADO ENUM('A', 'I') DEFAULT 'A',
    ACTIVO ENUM('S', 'N') DEFAULT 'S',
    FECHA_CREACION TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FECHA_ACTUALIZACION TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_ESTABLECIMIENTO) REFERENCES ESTABLECIMIENTO(ID_ESTABLECIMIENTO)
);
```

**Fields:**
- `ID_EMPLEADO`: Primary key
- `NOMBRE`: First name
- `APELLIDO`: Last name
- `DNI`: National ID number (unique)
- `CORREO`: Email address
- `TELEFONO`: Phone number
- `ID_ESTABLECIMIENTO`: Foreign key to ESTABLECIMIENTO
- `FECHA_INGRESO`: Hire date
- `ESTADO`: Status ('A'=Active, 'I'=Inactive)
- `ACTIVO`: Active flag ('S'=Yes, 'N'=No)

### Schedule Management Tables

#### 6. HORARIO (Schedules)
Work schedule definitions.

```sql
CREATE TABLE HORARIO (
    ID_HORARIO INT AUTO_INCREMENT PRIMARY KEY,
    NOMBRE VARCHAR(255) NOT NULL,
    HORA_ENTRADA TIME NOT NULL,
    HORA_SALIDA TIME NOT NULL,
    TOLERANCIA INT DEFAULT 15,
    ESTADO ENUM('A', 'I') DEFAULT 'A',
    FECHA_CREACION TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Fields:**
- `ID_HORARIO`: Primary key
- `NOMBRE`: Schedule name
- `HORA_ENTRADA`: Start time
- `HORA_SALIDA`: End time
- `TOLERANCIA`: Tolerance in minutes
- `ESTADO`: Status ('A'=Active, 'I'=Inactive)

#### 7. EMPLEADO_HORARIO (Employee Schedule Assignments)
Links employees to their schedules.

```sql
CREATE TABLE EMPLEADO_HORARIO (
    ID_EMPLEADO_HORARIO INT AUTO_INCREMENT PRIMARY KEY,
    ID_EMPLEADO INT NOT NULL,
    ID_HORARIO INT NOT NULL,
    FECHA_DESDE DATE NOT NULL,
    FECHA_HASTA DATE NULL,
    FECHA_CREACION TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO),
    FOREIGN KEY (ID_HORARIO) REFERENCES HORARIO(ID_HORARIO)
);
```

**Fields:**
- `ID_EMPLEADO_HORARIO`: Primary key
- `ID_EMPLEADO`: Foreign key to EMPLEADO
- `ID_HORARIO`: Foreign key to HORARIO
- `FECHA_DESDE`: Start date for schedule assignment
- `FECHA_HASTA`: End date (NULL for ongoing)

### Attendance Tables

#### 8. ASISTENCIA (Attendance Records)
Daily attendance records.

```sql
CREATE TABLE ASISTENCIA (
    ID_ASISTENCIA INT AUTO_INCREMENT PRIMARY KEY,
    ID_EMPLEADO INT NOT NULL,
    FECHA DATE NOT NULL,
    HORA TIME NOT NULL,
    TIPO_ASISTENCIA ENUM('ENTRADA', 'SALIDA') NOT NULL,
    VERIFICATION_METHOD ENUM('traditional', 'fingerprint', 'facial') DEFAULT 'traditional',
    OBSERVACIONES TEXT,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO),
    INDEX idx_empleado_fecha (ID_EMPLEADO, FECHA),
    INDEX idx_fecha_hora (FECHA, HORA)
);
```

**Fields:**
- `ID_ASISTENCIA`: Primary key
- `ID_EMPLEADO`: Foreign key to EMPLEADO
- `FECHA`: Attendance date
- `HORA`: Attendance time
- `TIPO_ASISTENCIA`: Type ('ENTRADA'=Entry, 'SALIDA'=Exit)
- `VERIFICATION_METHOD`: How attendance was verified
- `OBSERVACIONES`: Optional notes

### Biometric Tables

#### 9. biometric_data (Biometric Data Storage)
Stores enrolled biometric data.

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
    FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO),
    UNIQUE KEY unique_employee_finger (ID_EMPLEADO, FINGER_TYPE)
);
```

**Fields:**
- `ID`: Primary key
- `ID_EMPLEADO`: Foreign key to EMPLEADO
- `BIOMETRIC_TYPE`: Type of biometric ('fingerprint', 'facial')
- `FINGER_TYPE`: Specific finger for fingerprints
- `BIOMETRIC_DATA`: Encoded biometric data
- `ACTIVO`: Active flag

#### 10. biometric_logs (Biometric Verification Logs)
Logs all biometric verification attempts.

```sql
CREATE TABLE biometric_logs (
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

**Fields:**
- `ID`: Primary key
- `ID_EMPLEADO`: Foreign key to EMPLEADO
- `VERIFICATION_METHOD`: Method used for verification
- `VERIFICATION_SUCCESS`: Success flag (1=Success, 0=Failed)
- `FECHA`: Verification date
- `HORA`: Verification time

### Audit Tables

#### 11. LOG (System Logs)
System activity logs.

```sql
CREATE TABLE LOG (
    ID_LOG INT AUTO_INCREMENT PRIMARY KEY,
    ID_USUARIO INT,
    ACCION VARCHAR(100) NOT NULL,
    DETALLE TEXT,
    FECHA_HORA TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    IP_ADDRESS VARCHAR(45),
    USER_AGENT TEXT,
    FOREIGN KEY (ID_USUARIO) REFERENCES USUARIO(ID_USUARIO)
);
```

**Fields:**
- `ID_LOG`: Primary key
- `ID_USUARIO`: Foreign key to USUARIO (can be NULL for system actions)
- `ACCION`: Action performed
- `DETALLE`: Action details
- `FECHA_HORA`: Timestamp
- `IP_ADDRESS`: Client IP address
- `USER_AGENT`: Client user agent

## Database Relationships

### Hierarchical Structure
```
EMPRESA (Company)
└── SEDE (Office)
    └── ESTABLECIMIENTO (Establishment)
        └── EMPLEADO (Employee)
            ├── ASISTENCIA (Attendance)
            ├── biometric_data (Biometric Data)
            ├── biometric_logs (Biometric Logs)
            └── EMPLEADO_HORARIO (Schedule Assignment)
                └── HORARIO (Schedule)
```

### Key Relationships

1. **Company Structure**: EMPRESA → SEDE → ESTABLECIMIENTO → EMPLEADO
2. **User Management**: USUARIO belongs to EMPRESA
3. **Schedule Management**: EMPLEADO ↔ HORARIO (many-to-many via EMPLEADO_HORARIO)
4. **Attendance**: EMPLEADO → ASISTENCIA (one-to-many)
5. **Biometric**: EMPLEADO → biometric_data, biometric_logs (one-to-many)

## Data Conventions

### Status Fields
- **ESTADO**: 'A' = Active, 'I' = Inactive
- **ACTIVO**: 'S' = Yes (Sí), 'N' = No

### Date/Time Fields
- **Dates**: Stored as DATE type (YYYY-MM-DD)
- **Times**: Stored as TIME type (HH:MM:SS)
- **Timestamps**: Stored as TIMESTAMP type with automatic updates

### Text Encoding
- All tables use UTF-8 encoding
- VARCHAR fields sized appropriately for Spanish text
- TEXT fields for longer content

## Indexes

### Performance Indexes
```sql
-- Employee lookup by DNI
CREATE INDEX idx_empleado_dni ON EMPLEADO(DNI);

-- Attendance queries
CREATE INDEX idx_asistencia_empleado_fecha ON ASISTENCIA(ID_EMPLEADO, FECHA);
CREATE INDEX idx_asistencia_fecha_hora ON ASISTENCIA(FECHA, HORA);

-- User authentication
CREATE INDEX idx_usuario_username ON USUARIO(USERNAME);

-- Biometric lookups
CREATE INDEX idx_biometric_empleado_type ON biometric_data(ID_EMPLEADO, BIOMETRIC_TYPE);

-- Log queries
CREATE INDEX idx_log_fecha ON LOG(FECHA_HORA);
CREATE INDEX idx_log_usuario ON LOG(ID_USUARIO);
```

## Security Considerations

### Data Protection
- **Passwords**: Stored using bcrypt hashing
- **Biometric Data**: Base64 encoded, encrypted at rest
- **Personal Information**: Access controlled by user roles

### Access Control
- **Role-based Access**: ADMIN, SUPERVISOR, ASISTENCIA roles
- **Company Isolation**: Users can only access their company's data
- **Audit Trail**: All actions logged in LOG table

## Migration Notes

### From Legacy System
If migrating from an existing system:

1. **Table Names**: System uses UPPERCASE table names (EMPLEADO vs empleados)
2. **Field Names**: UPPERCASE field names (ID_EMPLEADO vs id_empleado)
3. **Status Values**: 'A'/'I' for ESTADO, 'S'/'N' for ACTIVO
4. **Relationships**: Maintain foreign key integrity
5. **Data Validation**: Ensure DNI uniqueness across employees

### Backup Recommendations
- Daily automated backups
- Transaction logs for point-in-time recovery
- Regular integrity checks
- Test restoration procedures

## API Integration

The database integrates with the Node.js/Express backend API:

### Endpoints Structure
- **Authentication**: `/api/v1/auth/*`
- **Employees**: `/api/v1/employees/*`
- **Attendance**: `/api/v1/attendance/*`
- **Biometric**: `/api/v1/biometric/*`

### Data Flow
1. Frontend (React) → API (Node.js) → Database (MariaDB/MySQL)
2. JWT token-based authentication
3. Role-based authorization
4. Real-time data updates
5. Comprehensive error handling

This schema provides a solid foundation for a modern attendance management system with biometric capabilities while maintaining compatibility with the existing PHP system structure.