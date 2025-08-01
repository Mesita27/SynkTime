# Módulo de Biometría - SynkTime

## Descripción

Este módulo añade capacidades de inscripción y verificación biométrica al sistema SynkTime existente. Permite a los empleados registrar huellas digitales y reconocimiento facial para una verificación de identidad más segura durante el registro de asistencias.

## Características

### 🔐 Seguridad Avanzada
- Encriptación AES-256 para todos los datos biométricos
- No se almacenan imágenes originales, solo descriptores matemáticos
- Logs completos de auditoría para todas las verificaciones
- Cumplimiento con regulaciones de protección de datos

### 👆 Huella Digital
- Soporte para múltiples tipos de sensores de huella
- Compatibilidad con WebAuthn para dispositivos integrados
- Captura de múltiples muestras para mayor precisión
- Validación de calidad automática

### 👤 Reconocimiento Facial
- Uso de face-api.js para detección y reconocimiento
- Captura de múltiples ángulos del rostro
- Detección automática de posicionamiento correcto
- Funciona con cualquier cámara web estándar

### 🔄 Integración Transparente
- Se integra perfectamente con el sistema de asistencias existente
- Mantiene toda la funcionalidad actual
- Interfaz de usuario consistente con el diseño existente
- Métodos de respaldo siempre disponibles

## Estructura de Archivos

```
/
├── biometric-enrollment.php              # Página principal de inscripción
├── assets/
│   ├── css/
│   │   └── biometric.css                 # Estilos del módulo biométrico
│   └── js/
│       ├── biometric-enrollment.js       # Lógica de inscripción
│       └── biometric-verification.js     # Lógica de verificación
├── components/
│   ├── biometric_fingerprint_modal.php   # Modal de inscripción de huella
│   ├── biometric_facial_modal.php        # Modal de inscripción facial
│   └── biometric_verification_modal.php  # Modal de verificación
├── api/
│   ├── biometric/
│   │   ├── status.php                    # Estado de inscripción
│   │   ├── start-session.php             # Iniciar sesión de inscripción
│   │   ├── capture-sample.php            # Capturar muestra biométrica
│   │   ├── complete-enrollment.php       # Completar inscripción
│   │   └── verify.php                    # Verificar identidad
│   └── employee/
│       └── search.php                    # Buscar empleados
├── database/
│   └── biometric_schema.sql              # Esquema de base de datos
└── docs/
    ├── GUIA_USUARIO_BIOMETRICO.md        # Guía de usuario
    └── DOCUMENTACION_TECNICA.md          # Documentación técnica
```

## Instalación Rápida

### 1. Base de Datos
```sql
SOURCE database/biometric_schema.sql;
```

### 2. Verificar Requisitos
- PHP 8.0+ con extensiones OpenSSL, PDO, JSON
- MySQL 5.7+
- Servidor HTTPS (requerido para acceso a cámara/sensores)
- Navegador moderno (Chrome 80+, Firefox 75+, Safari 13+)

### 3. Acceso
Navegue a `/biometric-enrollment.php` para iniciar inscripciones.

## Uso Básico

### Inscripción de Empleado
1. Acceda al módulo de inscripción biométrica
2. Busque el empleado por código, sede o establecimiento
3. Seleccione el tipo de biometría a inscribir
4. Siga las instrucciones en pantalla
5. Verifique la calidad de las muestras

### Verificación en Asistencias
1. En el módulo de asistencias, seleccione un empleado
2. Si tiene biometría inscrita, aparecerán opciones adicionales
3. Seleccione el método de verificación preferido
4. Complete la verificación o use foto tradicional como respaldo

## APIs Principales

### Obtener Estado de Inscripción
```http
GET /api/biometric/status.php?employee_id=123
```

### Iniciar Sesión de Inscripción
```http
POST /api/biometric/start-session.php
{
    "employee_id": 123,
    "biometric_type": "fingerprint"
}
```

### Verificar Identidad
```http
POST /api/biometric/verify.php
{
    "employee_id": 123,
    "biometric_type": "facial",
    "verification_data": {...}
}
```

## Configuración de Dispositivos

### Sensores de Huella Digital
- **WebAuthn**: Automáticamente detectado en navegadores compatibles
- **Futronic FS88**: Requiere drivers del fabricante
- **Digital Persona**: Requiere SDK específico
- **Suprema BioMini**: Integración via WebUSB o drivers

### Cámaras
- Cualquier cámara compatible con MediaDevices API
- Resolución mínima recomendada: 640x480
- Framerate mínimo: 15 FPS
- Buena iluminación requerida para mejores resultados

## Seguridad

### Encriptación de Datos
```php
// Todos los datos biométricos se encriptan antes del almacenamiento
$encrypted = openssl_encrypt(
    $biometricData, 
    'AES-256-CBC', 
    $encryptionKey, 
    0, 
    $initializationVector
);
```

### Validación de Calidad
- Huella digital: Mínimo 70% de calidad
- Reconocimiento facial: Validación automática de características
- Rechazo automático de muestras de baja calidad

### Logs de Auditoría
Todos los intentos de verificación se registran con:
- Timestamp
- Empleado
- Método utilizado
- Resultado (éxito/fallo)
- Nivel de confianza
- IP del usuario

## Resolución de Problemas

### Problemas Comunes

#### "No se detectaron dispositivos"
- Verificar conexión USB del sensor
- Instalar drivers del fabricante
- Asegurar que el navegador soporte WebAuthn

#### "Error al acceder a la cámara"
- Permitir acceso a cámara en el navegador
- Verificar que no esté en uso por otra aplicación
- Asegurar conexión HTTPS

#### "Calidad de muestra insuficiente"
- Limpiar sensor de huella o lente de cámara
- Mejorar iluminación para reconocimiento facial
- Seguir correctamente las instrucciones de posicionamiento

### Logs de Error
Los errores se registran en:
- Consola del navegador (JavaScript)
- Logs del servidor web (PHP)
- Base de datos (tabla biometric_verification_logs)

## Rendimiento

### Métricas Típicas
- **Inscripción**: 30-60 segundos por empleado
- **Verificación de huella**: 1-3 segundos
- **Verificación facial**: 2-5 segundos
- **Carga inicial de modelos**: 5-10 segundos

### Optimizaciones
- Cache de modelos de IA en cliente
- Compresión de imágenes antes de envío
- Indexación optimizada en base de datos
- Limpieza automática de datos temporales

## Compatibilidad

### Navegadores Soportados
- Chrome 80+ (recomendado)
- Firefox 75+
- Safari 13+ (macOS/iOS)
- Edge 80+

### Dispositivos Móviles
- Android 8+ con Chrome
- iOS 13+ con Safari
- Funcionalidad limitada en navegadores móviles antiguos

## Mantenimiento

### Rutinas Recomendadas
- **Diario**: Limpieza de sensores físicos
- **Semanal**: Revisión de logs de error
- **Mensual**: Análisis de métricas de calidad
- **Anual**: Re-inscripción de empleados para mantener precisión

### Backups
```sql
-- Backup de datos biométricos (mantener encriptación)
mysqldump --single-transaction synktime employee_biometrics biometric_verification_logs > biometric_backup.sql
```

## Soporte

### Documentación
- [Guía de Usuario](docs/GUIA_USUARIO_BIOMETRICO.md)
- [Documentación Técnica](docs/DOCUMENTACION_TECNICA.md)

### Contacto
- **Email**: soporte@synktime.com
- **Documentación Online**: https://docs.synktime.com/biometric
- **Issues**: https://github.com/synktime/biometric/issues

---

## Licencia

Este módulo se distribuye bajo la misma licencia que el sistema SynkTime principal.

## Changelog

### v1.0.0 (Agosto 2024)
- ✅ Inscripción de huellas digitales
- ✅ Inscripción de reconocimiento facial
- ✅ Verificación biométrica en asistencias
- ✅ Encriptación AES-256 de datos
- ✅ Integración con sistema existente
- ✅ Documentación completa
- ✅ Soporte multi-dispositivo

---

*Desarrollado como parte del proyecto de mejora del sistema SynkTime*