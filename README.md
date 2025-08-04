# SynkTime - Sistema de Asistencia Biométrica Moderno

Este repositorio contiene el sistema SynkTime refactorizado para trabajar con la base de datos SQL existente, migrando de PHP/JS/CSS a una arquitectura moderna Node.js/React.js que mantiene compatibilidad completa con el sistema legacy.

## 🏗️ Arquitectura Refactorizada

### Backend (Node.js/Express)
- **Ubicación**: `/backend/`
- **Stack Tecnológico**: Node.js, Express.js, MariaDB/MySQL, JWT Authentication
- **Compatibilidad**: Integra completamente con el esquema de base de datos PHP existente
- **Características**: 
  - API RESTful con esquema correcto de base de datos
  - Autenticación JWT con tabla USUARIO
  - Servicios biométricos avanzados
  - Manejo de empleados con jerarquía EMPRESA→SEDE→ESTABLECIMIENTO→EMPLEADO
  - Registro de asistencia con tabla ASISTENCIA

### Frontend (React.js)
- **Ubicación**: `/frontend/`
- **Stack Tecnológico**: React.js, Material-UI, Vite, React Query
- **Características**:
  - Interfaz moderna conectada al nuevo backend
  - Detección de dispositivos biométricos en tiempo real
  - Inscripción y verificación biométrica avanzada
  - Diseño responsivo con Material-UI
  - Capacidades de Progressive Web App

## 🔧 Cambios Principales del Refactoring

### ✅ Corrección de Esquema de Base de Datos
**Problema Resuelto**: El backend original usaba nombres de tabla en minúsculas (usuarios, empleados) mientras que el sistema PHP usa mayúsculas (USUARIO, EMPLEADO).

**Solución Implementada**:
- Actualizado `authService` para usar tabla `USUARIO` con campos `ID_USUARIO`, `USERNAME`, `CONTRASENA`
- Actualizado `employeeService` para usar tabla `EMPLEADO` con relaciones correctas
- Actualizado `attendanceService` para usar tabla `ASISTENCIA` con tipos correctos
- Actualizado `biometricService` para usar referencias correctas a `EMPLEADO`

### ✅ Servicios Backend Completos
- **Gestión de Empleados**: CRUD completo con jerarquía de empresa
- **Registro de Asistencia**: Entrada/salida automática con métodos biométricos
- **Autenticación**: JWT con compatibilidad de contraseñas legacy
- **Biométricos**: Inscripción de huellas/facial con auditoría completa

### ✅ API Endpoints Actualizados
```
# Autenticación
POST /api/v1/auth/login          # Login con tabla USUARIO
GET  /api/v1/auth/me             # Info usuario actual
POST /api/v1/auth/logout         # Logout

# Empleados (con tabla EMPLEADO)
GET  /api/v1/employees           # Lista empleados con filtros
GET  /api/v1/employees/:id       # Empleado específico
POST /api/v1/employees           # Crear empleado
PUT  /api/v1/employees/:id       # Actualizar empleado
DELETE /api/v1/employees/:id     # Eliminar empleado

# Asistencia (con tabla ASISTENCIA)
POST /api/v1/attendance/register # Registrar asistencia
GET  /api/v1/attendance/records  # Registros con filtros
GET  /api/v1/attendance/summary  # Resumen para dashboard

# Biométricos
POST /api/v1/biometric/enroll/fingerprint  # Inscribir huella
POST /api/v1/biometric/enroll/facial       # Inscribir facial
POST /api/v1/biometric/verify              # Verificar biométrico
```

## 🚀 Inicio Rápido

### Prerrequisitos
- Node.js 16+ 
- MariaDB/MySQL 5.7+ con base de datos SynkTime existente
- npm o yarn

### Configuración Backend

1. Navegar al directorio backend:
```bash
cd backend
```

2. Instalar dependencias:
```bash
npm install
```

3. Configurar variables de entorno:
```bash
cp .env.example .env
# Editar .env con credenciales de la base de datos PHP existente
```

4. Iniciar servidor de desarrollo:
```bash
npm run dev
```

El backend estará disponible en `http://localhost:3001`

### Configuración Frontend

1. Navegar al directorio frontend:
```bash
cd frontend
```

2. Instalar dependencias:
```bash
npm install
```

3. Iniciar servidor de desarrollo:
```bash
npm run dev
```

El frontend estará disponible en `http://localhost:3000`

## 📊 Estructura de Base de Datos

### Tablas Principales (Esquema PHP Mantenido)
- **EMPRESA** - Empresas/organizaciones
- **SEDE** - Oficinas de la empresa
- **ESTABLECIMIENTO** - Establecimientos específicos
- **EMPLEADO** - Registros de empleados
- **USUARIO** - Usuarios del sistema
- **ASISTENCIA** - Registros de asistencia
- **HORARIO** / **EMPLEADO_HORARIO** - Gestión de horarios
- **biometric_data** / **biometric_logs** - Datos biométricos

Ver documentación completa en `DATABASE_SCHEMA.md`

## 🔐 Características de Seguridad

- Autenticación basada en tokens JWT
- Validación de entrada con express-validator
- Protección CORS
- Headers de seguridad con Helmet.js
- Hashing de contraseñas con Bcrypt
- Aislamiento por empresa (usuarios solo ven datos de su empresa)

## 📱 Características del Frontend

### Inscripción Biométrica
- Interfaz de selección de dedos interactiva
- Feed de cámara en tiempo real para reconocimiento facial
- Seguimiento de progreso y actualizaciones de estado
- Detección de disponibilidad de dispositivos

### Registro de Asistencia
- Asistente de verificación en múltiples pasos
- Soporte para huella dactilar, facial y métodos tradicionales
- Retroalimentación en tiempo real y manejo de errores

### Dashboard
- Estadísticas en tiempo real y gráficos
- Monitoreo de estado de dispositivos
- Seguimiento de actividad reciente
- Diseño responsivo

## 📂 Documentación

- `DATABASE_SCHEMA.md` - Documentación completa del esquema de base de datos
- `MIGRATION_DOCUMENTATION.md` - Detalles técnicos de la migración
- `MIGRATION_GUIDE.md` - Guía paso a paso para migración de datos
- `BIOMETRIC_DOCUMENTATION.md` - Documentación del sistema biométrico

## 🔄 Compatibilidad con Sistema PHP

El sistema refactorizado mantiene **100% compatibilidad** con el sistema PHP existente:

1. **Misma Base de Datos**: Usa exactamente las mismas tablas y estructura
2. **Usuarios Existentes**: Los usuarios pueden iniciar sesión sin cambios
3. **Datos Existentes**: Todos los datos históricos se mantienen
4. **Operación Paralela**: Puede ejecutarse junto al sistema PHP
5. **Migración Gradual**: Permite transición por fases

## 🧪 Testing

### Backend
```bash
cd backend
npm test
```

### Frontend
```bash
cd frontend
npm test
```

## 🚀 Despliegue en Producción

### Backend
```bash
cd backend
npm run build
npm start
```

### Frontend
```bash
cd frontend
npm run build
# Servir el directorio dist/ con nginx/apache
```

## 📈 Mejoras Implementadas

### Para Desarrolladores
- Tooling moderno con hot reload
- Arquitectura basada en componentes
- Separación clara de responsabilidades
- Listo para microservicios

### Para Usuarios
- Navegación SPA fluida
- Interfaz móvil optimizada
- Características de accesibilidad
- Carga de páginas más rápida

### Para Administradores
- Dashboard con analytics en tiempo real
- Seguridad mejorada con JWT
- Logs de auditoría completos
- Fácil monitoreo y despliegue

## 🛠️ Stack Tecnológico

### Dependencias Backend
- **Express.js** - Framework web
- **MySQL2** - Driver de base de datos para MariaDB/MySQL
- **jsonwebtoken** - Implementación JWT
- **bcryptjs** - Hashing de contraseñas
- **express-validator** - Validación de entrada
- **helmet** - Headers de seguridad
- **cors** - Intercambio de recursos entre orígenes

### Dependencias Frontend
- **React 18** - Librería UI
- **Material-UI v5** - Librería de componentes
- **React Router v6** - Enrutamiento del lado del cliente
- **React Query** - Obtención y caché de datos
- **Axios** - Cliente HTTP
- **React Webcam** - Integración de cámara

## 🤝 Contribuir

1. Hacer fork del repositorio
2. Crear una rama de característica
3. Hacer commit de los cambios
4. Push a la rama
5. Crear un Pull Request

## 📄 Licencia

Este proyecto está licenciado bajo la Licencia MIT - ver el archivo LICENSE para detalles.

---

Construido con ❤️ para gestión moderna de asistencia manteniendo compatibilidad total con sistemas legacy.