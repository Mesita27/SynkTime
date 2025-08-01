# Guía de Usuario - Sistema de Inscripción Biométrica SynkTime

## Tabla de Contenidos
1. [Introducción](#introducción)
2. [Instalación y Configuración](#instalación-y-configuración)
3. [Inscripción de Empleados](#inscripción-de-empleados)
4. [Verificación Biométrica en Asistencias](#verificación-biométrica-en-asistencias)
5. [Resolución de Problemas](#resolución-de-problemas)
6. [Seguridad y Mejores Prácticas](#seguridad-y-mejores-prácticas)

## Introducción

El Sistema de Inscripción Biométrica de SynkTime permite registrar y verificar empleados utilizando datos biométricos seguros. El sistema soporta dos tipos principales de biometría:

- **Huella Digital**: Verificación rápida y precisa mediante sensores de huella
- **Reconocimiento Facial**: Verificación mediante análisis facial con cámara

### Características Principales

✅ **Compatibilidad Multi-dispositivo**: Soporte para diversos sensores de huella digital y cámaras
✅ **Almacenamiento Seguro**: Encriptación AES-256 para todos los datos biométricos
✅ **Integración Transparente**: Se integra perfectamente con el sistema de asistencias existente
✅ **Interfaz Intuitiva**: Modales y guías paso a paso para facilitar el uso
✅ **Calidad Garantizada**: Validación de calidad de muestras biométricas
✅ **Logs de Auditoría**: Registro completo de todas las verificaciones

## Instalación y Configuración

### Requisitos del Sistema

#### Requisitos del Servidor
- PHP 8.0 o superior
- MySQL 5.7 o superior
- Extensiones PHP requeridas:
  - PDO
  - OpenSSL
  - JSON
  - MySQLi

#### Requisitos del Cliente
- Navegador web moderno (Chrome 80+, Firefox 75+, Safari 13+)
- HTTPS habilitado (requerido para acceso a cámara y sensores)
- JavaScript habilitado

#### Dispositivos Biométricos Soportados
- **Huellas Digitales**:
  - Sensores integrados (WebAuthn compatible)
  - Futronic FS88
  - Digital Persona U.are.U
  - Suprema BioMini
  - Cualquier dispositivo compatible con WebAuthn

- **Cámaras**:
  - Cámaras web USB estándar
  - Cámaras integradas de laptop
  - Cámaras IP compatibles con MediaDevices API

### Pasos de Instalación

#### 1. Configuración de Base de Datos

Ejecute el script SQL de configuración:

```sql
-- Ejecutar en MySQL
SOURCE database/biometric_schema.sql;
```

Este script creará las siguientes tablas:
- `employee_biometrics`: Almacena datos biométricos encriptados
- `biometric_verification_logs`: Registra intentos de verificación
- `biometric_devices`: Catalogo de dispositivos disponibles
- `biometric_enrollment_sessions`: Sesiones de inscripción temporales

#### 2. Configuración del Servidor Web

Asegurese de que su servidor web soporte HTTPS:

```apache
# Apache .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

#### 3. Permisos de Archivos

Configure los permisos apropiados:

```bash
# Directorio de uploads (donde se almacenan fotos)
chmod 755 uploads/
chown www-data:www-data uploads/

# Archivos de configuración
chmod 600 config/database.php
```

#### 4. Verificación de Instalación

Acceda a la página de inscripción biométrica:
```
https://su-dominio.com/biometric-enrollment.php
```

Si ve la página sin errores, la instalación fue exitosa.

## Inscripción de Empleados

### Acceso al Módulo de Inscripción

1. Inicie sesión en SynkTime
2. Navegue a "Inscripción Biométrica" en el menú principal
3. Verá la interfaz de inscripción biométrica

### Proceso de Inscripción

#### Paso 1: Selección de Empleado

1. **Filtros de Búsqueda**:
   - Seleccione la sede del empleado
   - Seleccione el establecimiento
   - Ingrese el código del empleado

2. **Búsqueda**:
   - Haga clic en "Buscar"
   - El sistema mostrará la información del empleado
   - Se mostrarán los estados biométricos actuales

#### Paso 2: Inscripción de Huella Digital

1. **Preparación**:
   - Asegúrese de que el sensor de huella esté conectado
   - Verifique que aparezca "dispositivos detectados"
   - Haga clic en "Iniciar Inscripción"

2. **Proceso de Captura**:
   - Siga las instrucciones en pantalla
   - Mantenga el dedo limpio y seco
   - Coloque el dedo firmemente sobre el sensor
   - No mueva el dedo durante la captura
   - Repita 3 veces para mayor precisión

3. **Calidad de Muestra**:
   - El sistema evalúa automáticamente la calidad
   - Se requiere un mínimo de 70% de calidad
   - Las muestras de baja calidad se rechazarán automáticamente

#### Paso 3: Inscripción de Reconocimiento Facial

1. **Configuración de Cámara**:
   - Seleccione la cámara a utilizar
   - Haga clic en "Probar Cámara" para verificar funcionamiento
   - Asegúrese de tener buena iluminación

2. **Proceso de Captura**:
   - Mantenga el rostro centrado en el marco
   - Mire directamente a la cámara
   - No use gafas de sol o sombreros
   - Capture 5 muestras desde diferentes ángulos:
     - Vista frontal
     - Ligeramente hacia la izquierda
     - Ligeramente hacia la derecha
     - Ligeramente hacia arriba
     - Vista frontal nuevamente

3. **Procesamiento**:
   - El sistema creará automáticamente el modelo facial
   - Se mostrará la calidad del modelo generado
   - Se validará la calidad antes de guardar

### Verificación de Inscripción

Después de completar la inscripción:

1. **Prueba de Reconocimiento**:
   - Use el botón "Probar Reconocimiento"
   - Verifique que el sistema reconoce correctamente

2. **Estado Actualizado**:
   - Los badges de estado se actualizarán a "Inscrito"
   - Se mostrará la fecha de inscripción
   - Se indicará la calidad de las muestras

## Verificación Biométrica en Asistencias

### Integración con el Sistema Existente

La verificación biométrica se integra transparentemente con el proceso de registro de asistencias:

#### Proceso Mejorado de Asistencia

1. **Registro Tradicional**:
   - Acceda al módulo de Asistencias
   - Haga clic en "Registrar Asistencia"
   - Busque y seleccione el empleado

2. **Verificación Biométrica** (Nuevo):
   - Si el empleado tiene datos biométricos inscritos, aparecerán opciones adicionales
   - En la columna "Biométrico" verá indicadores de métodos disponibles
   - Haga clic en el botón de registro para iniciar verificación

#### Métodos de Verificación Disponibles

**Huella Digital**:
- Método más rápido (2-3 segundos)
- Alta precisión (>90% confianza típica)
- Funciona en cualquier condición de iluminación

**Reconocimiento Facial**:
- Verificación sin contacto
- Requiere buena iluminación
- Precisión alta (>85% confianza típica)

**Foto Tradicional**:
- Método de respaldo
- Siempre disponible
- Para casos donde falla la biometría

#### Proceso de Verificación

1. **Selección de Método**:
   - El sistema muestra métodos disponibles para el empleado
   - Seleccione huella digital o reconocimiento facial
   - O use foto tradicional como respaldo

2. **Verificación de Huella**:
   - Coloque el dedo en el sensor
   - Mantenga firme hasta completar verificación
   - El sistema mostrará resultado en 2-3 segundos

3. **Verificación Facial**:
   - Permita acceso a la cámara
   - Posicione el rostro en el marco verde
   - Mantenga la posición hasta completar análisis
   - El sistema mostrará resultado en 3-5 segundos

4. **Resultado**:
   - **Verificación Exitosa**: Se registra automáticamente la asistencia
   - **Verificación Fallida**: Opción de reintentar o usar foto tradicional
   - **Error de Dispositivo**: Automáticamente usa foto tradicional

### Logs y Auditoría

Todas las verificaciones se registran automáticamente:
- Fecha y hora del intento
- Empleado verificado
- Método utilizado
- Resultado (exitoso/fallido)
- Nivel de confianza
- Dirección IP del usuario

## Resolución de Problemas

### Problemas Comunes de Huella Digital

#### "No se detectaron dispositivos de huella digital"

**Causas posibles**:
- Sensor no conectado
- Drivers no instalados
- Navegador no compatible

**Soluciones**:
1. Verifique la conexión física del sensor
2. Instale los drivers del fabricante
3. Use Chrome o Firefox actualizados
4. Asegúrese de que el sitio use HTTPS

#### "Calidad de muestra insuficiente"

**Causas posibles**:
- Dedo sucio o húmedo
- Presión insuficiente
- Movimiento durante captura

**Soluciones**:
1. Limpie el dedo y el sensor
2. Seque completamente el dedo
3. Aplique presión firme pero no excesiva
4. Mantenga el dedo inmóvil durante captura

#### "Error en la verificación de huella digital"

**Causas posibles**:
- Calidad de inscripción baja
- Cambios en la huella (heridas, etc.)
- Sensor con interferencia

**Soluciones**:
1. Re-inscriba la huella digital
2. Use un dedo diferente
3. Limpie el sensor
4. Use reconocimiento facial como alternativa

### Problemas Comunes de Reconocimiento Facial

#### "Error al acceder a la cámara"

**Causas posibles**:
- Permisos de cámara denegados
- Cámara en uso por otra aplicación
- Drivers de cámara desactualizados

**Soluciones**:
1. Permita acceso a cámara en el navegador
2. Cierre otras aplicaciones que usen la cámara
3. Actualice drivers de cámara
4. Recargue la página y vuelva a intentar

#### "No se pudieron extraer características faciales"

**Causas posibles**:
- Iluminación insuficiente
- Rostro parcialmente oculto
- Calidad de cámara baja

**Soluciones**:
1. Mejore la iluminación del área
2. Retire gafas, gorros o mascarillas
3. Use una cámara de mejor calidad
4. Posicione el rostro más cerca de la cámara

#### "Rostro no reconocido"

**Causas posibles**:
- Cambios significativos en apariencia
- Iluminación muy diferente a la inscripción
- Calidad de inscripción baja

**Soluciones**:
1. Re-inscriba el reconocimiento facial
2. Ajuste la iluminación
3. Tome múltiples muestras durante inscripción
4. Use huella digital como alternativa

### Problemas de Rendimiento

#### "El sistema está lento durante verificación"

**Soluciones**:
1. Verifique la conexión a internet
2. Cierre pestañas innecesarias del navegador
3. Actualice el navegador
4. Contacte al administrador del sistema

#### "Error de conexión durante inscripción"

**Soluciones**:
1. Verifique la conexión a internet
2. Recargue la página
3. Inicie el proceso nuevamente
4. Contacte soporte técnico si persiste

### Códigos de Error Comunes

| Código | Descripción | Solución |
|--------|-------------|----------|
| BIO001 | Dispositivo no detectado | Verificar conexión y drivers |
| BIO002 | Calidad insuficiente | Limpiar sensor/cámara y repetir |
| BIO003 | Tiempo de espera agotado | Reintentar verificación |
| BIO004 | Error de encriptación | Contactar administrador |
| BIO005 | Base de datos no disponible | Contactar soporte técnico |

## Seguridad y Mejores Prácticas

### Seguridad de Datos Biométricos

#### Encriptación
- **Algoritmo**: AES-256-CBC
- **Claves**: Rotación anual automática
- **Almacenamiento**: Nunca se almacenan datos en texto plano
- **Transmisión**: HTTPS obligatorio para todas las comunicaciones

#### Privacidad
- Los datos biométricos no son reversibles
- No se almacenan imágenes originales de rostros
- Solo se guardan descriptores matemáticos
- Cumplimiento con regulaciones de protección de datos

### Mejores Prácticas de Uso

#### Para Administradores

1. **Mantenimiento Regular**:
   - Limpie sensores de huella semanalmente
   - Verifique calidad de iluminación mensualmente
   - Actualice drivers de dispositivos regularmente

2. **Gestión de Usuarios**:
   - Re-inscriba empleados cada 2 años
   - Monitoree logs de verificación fallida
   - Mantenga métodos de respaldo disponibles

3. **Seguridad**:
   - Use HTTPS en todo momento
   - Mantenga backups encriptados de la base de datos
   - Revise logs de acceso regularmente

#### Para Empleados

1. **Higiene**:
   - Mantenga dedos limpios y secos
   - No use cremas o lociones antes de verificación
   - Reporte cortes o heridas en dedos inscritos

2. **Uso Responsable**:
   - No comparta información de acceso
   - Reporte inmediatamente problemas de verificación
   - Use métodos alternativos si la biometría falla

3. **Cuidado del Equipo**:
   - No use fuerza excesiva en sensores
   - Mantenga limpias las cámaras
   - Reporte daños en dispositivos inmediatamente

### Política de Retención de Datos

- **Datos de Inscripción**: Se mantienen mientras el empleado esté activo
- **Logs de Verificación**: Se mantienen por 2 años para auditoría
- **Sesiones Temporales**: Se eliminan automáticamente después de 24 horas
- **Datos de Empleados Inactivos**: Se archivan después de 1 año, se eliminan después de 5 años

### Cumplimiento Legal

El sistema cumple con:
- **GDPR**: Derecho al olvido y portabilidad de datos
- **LOPD**: Protección de datos personales
- **ISO 27001**: Gestión de seguridad de la información
- **Normas Locales**: Adaptable a regulaciones específicas del país

---

## Soporte Técnico

Para asistencia técnica contacte:
- **Email**: soporte@synktime.com
- **Teléfono**: +1-800-SYNKTIME
- **Portal**: https://support.synktime.com

### Horarios de Soporte
- **Lunes a Viernes**: 8:00 AM - 6:00 PM
- **Sábados**: 9:00 AM - 2:00 PM
- **Emergencias**: 24/7 (solo clientes Premium)

---

*Documentación actualizada: Agosto 2024*
*Versión del Sistema: SynkTime Biometric v1.0*