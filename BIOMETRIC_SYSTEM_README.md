# Sistema de Reconocimiento Biométrico - SynkTime

## Descripción General

El sistema de reconocimiento biométrico para SynkTime permite a los empleados registrar su asistencia mediante huella digital o reconocimiento facial, proporcionando mayor seguridad y precisión en el control de asistencia.

## Características Implementadas

### 1. Frontend (Interfaz de Usuario)
- **Modal de Selección Biométrica**: Permite al usuario elegir entre huella digital y reconocimiento facial
- **Modal de Verificación de Huella Digital**: Interfaz para captura y verificación de huella digital usando WebAuthn API
- **Modal de Reconocimiento Facial**: Interfaz para captura y análisis facial usando face-api.js
- **Estilos CSS Responsivos**: Diseño adaptativo con animaciones y efectos visuales

### 2. Backend (APIs)
- **API de Configuración**: Gestión de configuraciones del sistema biométrico
- **API de Verificación**: Endpoints para verificar huellas digitales y reconocimiento facial
- **API de Registro**: Endpoint para registrar nuevos datos biométricos de empleados
- **API de Disponibilidad**: Verificación de métodos biométricos disponibles por empleado

### 3. Base de Datos
- **Tabla EMPLEADO_BIOMETRICO**: Almacena datos biométricos encriptados de empleados
- **Tabla BIOMETRIC_VERIFICATION_LOG**: Registro de intentos de verificación biométrica
- **Tabla BIOMETRIC_CONFIG**: Configuraciones del sistema biométrico

## Arquitectura del Sistema

### Flujo de Registro de Asistencia

1. **Selección de Método**: El usuario selecciona entre huella digital o reconocimiento facial
2. **Verificación Biométrica**: El sistema verifica la identidad usando el método seleccionado
3. **Registro de Asistencia**: Si la verificación es exitosa, se registra la asistencia
4. **Almacenamiento**: Se guarda la foto (facial) o imagen placeholder (huella digital)

### Tecnologías Utilizadas

- **WebAuthn API**: Para verificación de huella digital (compatible con dispositivos móviles y lectores biométricos)
- **face-api.js**: Librería de reconocimiento facial basada en navegador
- **PHP**: Backend para procesamiento y almacenamiento
- **MySQL**: Base de datos para almacenamiento seguro
- **JavaScript ES6+**: Frontend interactivo
- **CSS3**: Estilos modernos con animaciones

## Archivos Implementados

### Frontend
- `components/biometric_modals.php` - Modales de verificación biométrica
- `assets/css/biometric.css` - Estilos para componentes biométricos
- `assets/js/biometric.js` - Lógica de verificación biométrica

### Backend
- `api/biometric/config.php` - Configuración del sistema
- `api/biometric/check-availability.php` - Verificar disponibilidad de métodos
- `api/biometric/verify-fingerprint.php` - Verificación de huella digital
- `api/biometric/verify-facial.php` - Verificación facial
- `api/biometric/register.php` - Registro de datos biométricos
- `api/attendance/register-biometric.php` - Registro de asistencia con biométrica

### Base de Datos
- `database/biometric_schema.sql` - Esquema de base de datos
- `database/init_biometric.php` - Script de inicialización

## Configuración del Sistema

### Requisitos
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Navegador moderno con soporte para WebAuthn y getUserMedia
- HTTPS (requerido para WebAuthn y acceso a cámara)

### Instalación

1. **Ejecutar Script de Base de Datos**:
   ```bash
   php database/init_biometric.php
   ```

2. **Configurar Servidor HTTPS** (requerido para APIs biométricas):
   ```apache
   # En Apache, habilitar SSL
   SSLEngine on
   SSLCertificateFile /path/to/certificate.crt
   SSLCertificateKeyFile /path/to/private.key
   ```

3. **Verificar Permisos de Directorio**:
   ```bash
   chmod 755 uploads/
   chown www-data:www-data uploads/
   ```

### Configuración de Parámetros

El sistema se configura a través de la tabla `BIOMETRIC_CONFIG`:

- `fingerprint_enabled`: Habilitar verificación por huella digital
- `facial_enabled`: Habilitar reconocimiento facial
- `fingerprint_confidence_threshold`: Umbral de confianza para huellas (0-100)
- `facial_confidence_threshold`: Umbral de confianza para rostros (0-100)
- `max_verification_attempts`: Máximo intentos de verificación
- `verification_timeout`: Tiempo límite de verificación (segundos)

## Seguridad

### Encriptación de Datos
- Los datos biométricos se almacenan encriptados usando AES-128-CBC
- Las claves de encriptación deben configurarse en el servidor
- Los templates biométricos nunca se transmiten sin encriptar

### Logging de Auditoría
- Todos los intentos de verificación se registran en `BIOMETRIC_VERIFICATION_LOG`
- Incluye información de IP, User-Agent y resultados de verificación
- Permite auditorías de seguridad y detección de intentos fraudulentos

### Privacidad
- Los datos biométricos se almacenan como templates matemáticos, no como imágenes
- Cumple con mejores prácticas de protección de datos biométricos
- Opción de eliminación de datos biométricos por empleado

## Uso del Sistema

### Para Empleados

1. **Registro Inicial de Biometría** (por administrador):
   - Acceder a gestión de empleados
   - Seleccionar empleado y registrar huella digital/facial
   - Sistema guarda template biométrico encriptado

2. **Registro de Asistencia**:
   - Hacer clic en "Registrar Asistencia"
   - Elegir método de verificación (huella o facial)
   - Completar verificación biométrica
   - Sistema registra asistencia automáticamente

### Para Administradores

1. **Configuración del Sistema**:
   - Modificar parámetros en tabla `BIOMETRIC_CONFIG`
   - Ajustar umbrales de confianza según necesidades
   - Habilitar/deshabilitar métodos biométricos

2. **Gestión de Datos Biométricos**:
   - Registrar nuevos datos biométricos de empleados
   - Actualizar templates existentes
   - Eliminar datos biométricos si es necesario

3. **Monitoreo y Auditoría**:
   - Revisar logs de verificación en `BIOMETRIC_VERIFICATION_LOG`
   - Identificar intentos fallidos o patrones sospechosos
   - Generar reportes de uso del sistema

## Compatibilidad

### Navegadores Soportados
- **WebAuthn (Huella Digital)**:
  - Chrome 67+
  - Firefox 60+
  - Safari 14+
  - Edge 18+

- **WebRTC (Cámara para Facial)**:
  - Chrome 21+
  - Firefox 36+
  - Safari 11+
  - Edge 12+

### Dispositivos Soportados
- **Huella Digital**:
  - Dispositivos con Touch ID (iPhone/iPad)
  - Dispositivos con Windows Hello
  - Lectores de huella USB
  - Sensores biométricos integrados

- **Reconocimiento Facial**:
  - Cualquier dispositivo con cámara web
  - Smartphones y tablets
  - Laptops con cámara integrada

## Troubleshooting

### Problemas Comunes

1. **Error "WebAuthn no soportado"**:
   - Verificar que el sitio esté en HTTPS
   - Actualizar navegador a versión compatible
   - Verificar que el dispositivo tenga sensor biométrico

2. **Error "No se pudo acceder a la cámara"**:
   - Verificar permisos de cámara en navegador
   - Asegurar que el sitio esté en HTTPS
   - Cerrar otras aplicaciones que usen la cámara

3. **Verificación biométrica fallida**:
   - Verificar que el empleado tenga datos biométricos registrados
   - Revisar umbral de confianza en configuración
   - Comprobar calidad de la captura biométrica

4. **Error de base de datos**:
   - Verificar que las tablas biométricas existan
   - Ejecutar script de inicialización si es necesario
   - Revisar permisos de usuario de base de datos

## Próximas Mejoras

### Funcionalidades Planificadas
- **Registro Biométrico en Autoservicio**: Permitir que empleados registren sus propios datos
- **Verificación Multimodal**: Combinar huella digital y facial para mayor seguridad
- **Integración con Active Directory**: Sincronización automática de empleados
- **App Móvil**: Aplicación nativa para registro desde dispositivos móviles
- **Reportes Avanzados**: Dashboard de analíticas biométricas

### Mejoras de Seguridad
- **Detección de Vida**: Verificar que la biometría provenga de una persona viva
- **Cifrado de Extremo a Extremo**: Encriptación adicional durante transmisión
- **Tokens de Sesión**: Autenticación por tokens para mayor seguridad
- **Blockchain**: Inmutabilidad de registros de asistencia

## Soporte

Para soporte técnico o consultas sobre el sistema biométrico:
- Revisar logs en `BIOMETRIC_VERIFICATION_LOG`
- Verificar configuración en `BIOMETRIC_CONFIG`
- Consultar documentación de APIs en cada endpoint
- Contactar al administrador del sistema

---

**Nota**: Este sistema está diseñado siguiendo las mejores prácticas de seguridad y privacidad para datos biométricos. Se recomienda realizar pruebas exhaustivas antes de implementar en producción.