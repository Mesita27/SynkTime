# Biometría en SynkTime

Este módulo agrega:
- Identificación facial (REST) y captura de foto en reconocimiento.
- Identificación por huella (REST) con placeholder visual.
- Registro tradicional (solo foto).
- Enrolamiento de biometría por empleado.
- Auditoría de eventos biométricos.

## Proveedor facial recomendado
CompreFace (auto-hospedado), expone API REST con gestión de subjects (personas) y reconocimiento 1:N. Este repo no incluye Docker, pero se integra por HTTP con un servicio externo.

Configura `config/biometrics.php` con:
- FACE_API_BASE_URL (p. ej. http://compreface.local:8000)
- FACE_API_KEY (API key del servicio de reconocimiento)
- Umbrales.

## Huella (fingerprint-api)
Microservicio .NET 8 sin Docker que envuelve SourceAFIS.
- Ubicado en `integrations/fingerprint-api/`
- Endpoints:
  - POST /enroll
  - POST /identify
  - POST /verify
- Almacena plantillas en `data/templates/` por defecto (filesystem).

Ejecutar:
1) Instala .NET 8 SDK.
2) Desde `integrations/fingerprint-api/`:
   ```
   dotnet restore
   dotnet run --urls=http://localhost:5058
   ```
3) Configura `config/biometrics.php` → FINGERPRINT_API_BASE_URL=http://localhost:5058

## Flujo de uso

### Enrolamiento
1) Ir a Módulo → Biometría → Enrolar.
2) Seleccionar empleado.
3) Rostro: capturar 3 fotos → se crea subject en facial y se guardan ejemplos.
4) Huella: subir 2-3 imágenes de huella → se crea plantilla en fingerprint-api.
5) Se guarda el vínculo en `biometric_identity`.

### Verificación en marcaje de asistencia
- Elegir método:
  - Facial: se reconoce en tiempo real y se captura foto al confirmar.
  - Huella: se identifica por imagen de huella y se guarda foto placeholder.
  - Tradicional: captura de foto normal.
- Se registra evento en `biometric_event` y se llama al flujo de asistencia existente (ver `api/biometrics/mark_attendance.php`).

## Seguridad y privacidad
- Guardar solo lo necesario. Para huella, se guardan plantillas en el microservicio (no imágenes) y en SynkTime solo IDs.
- Cifrado TLS recomendado entre servicios.
- Parametrizar umbrales (false accept/reject) en `config/biometrics.php`.

## Migraciones
Ejecutar `scripts/db/migrations/20250808_biometrics.sql`. Ajusta nombres de tablas FK si tu esquema tiene nombres distintos para empleados/asistencias.