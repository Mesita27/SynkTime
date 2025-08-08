<?php
// Sample standalone biometric enrollment page
// This demonstrates how to integrate biometric enrollment with the existing SynkTime system
require_once 'auth/session.php';
// requireModuleAccess('employee'); // Uncomment if you have permission checks
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Enrolamiento Biométrico | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Use your existing stylesheets -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-fingerprint"></i> Enrolamiento Biométrico</h2>
            <a href="employee.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Empleados
            </a>
        </div>

        <!-- Employee Selection -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Seleccionar Empleado</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">ID del Empleado</label>
                        <input type="number" id="employee-id" class="form-control" placeholder="Ingrese ID del empleado">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="button" id="load-employee" class="btn btn-primary">
                            <i class="fas fa-search"></i> Buscar Empleado
                        </button>
                    </div>
                </div>
                
                <!-- Employee Info Display -->
                <div id="employee-info" class="mt-3 d-none">
                    <div class="alert alert-info">
                        <strong>Empleado encontrado:</strong> <span id="employee-name"></span>
                        <div class="mt-2">
                            <small>Estado biométrico: <span id="biometric-status"></span></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enrollment Interface -->
        <div id="enrollment-section" class="d-none">
            <?php include 'views/biometrics/enroll.php'; ?>
        </div>

        <!-- Status Messages -->
        <div id="status-messages"></div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/biometrics.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const employeeIdInput = document.getElementById('employee-id');
        const loadEmployeeBtn = document.getElementById('load-employee');
        const employeeInfo = document.getElementById('employee-info');
        const enrollmentSection = document.getElementById('enrollment-section');
        const statusMessages = document.getElementById('status-messages');

        // Load employee data and biometric status
        loadEmployeeBtn.addEventListener('click', async function() {
            const employeeId = employeeIdInput.value.trim();
            if (!employeeId) {
                showMessage('Por favor ingrese un ID de empleado válido.', 'warning');
                return;
            }

            try {
                loadEmployeeBtn.disabled = true;
                loadEmployeeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';

                // Check if employee exists and get biometric status
                const response = await fetch(`api/biometrics/employee_status.php?employee_id=${employeeId}`);
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Error al cargar empleado');
                }

                // Show employee info (you would fetch actual employee data from your employee API)
                document.getElementById('employee-name').textContent = `Empleado ID: ${employeeId}`;
                
                // Show biometric status
                const status = data.biometric_status;
                let statusText = 'Sin enrolar';
                if (status.enrolled) {
                    const enrolled = [];
                    if (status.face_enrolled) enrolled.push('Rostro');
                    if (status.fingerprint_enrolled) enrolled.push('Huella');
                    statusText = `Enrolado: ${enrolled.join(', ')}`;
                }
                document.getElementById('biometric-status').textContent = statusText;

                // Update enrollment form
                document.getElementById('enroll-employee-id').value = employeeId;

                // Show sections
                employeeInfo.classList.remove('d-none');
                enrollmentSection.classList.remove('d-none');

                showMessage('Empleado cargado exitosamente. Puede proceder con el enrolamiento.', 'success');

            } catch (error) {
                showMessage('Error: ' + error.message, 'danger');
            } finally {
                loadEmployeeBtn.disabled = false;
                loadEmployeeBtn.innerHTML = '<i class="fas fa-search"></i> Buscar Empleado';
            }
        });

        // Enter key support
        employeeIdInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                loadEmployeeBtn.click();
            }
        });

        function showMessage(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            statusMessages.appendChild(alertDiv);

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    });
    </script>
</body>
</html>