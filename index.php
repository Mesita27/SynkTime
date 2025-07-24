<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Main Styles -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-background-frame" aria-hidden="true">
        <div class="frame frame1"></div>
        <div class="frame frame2"></div>
        <div class="frame frame3"></div>
    </div>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <img src="assets/img/synktime-logo.png" alt="SynkTime Logo">
            </div>
            <form id="loginForm" autocomplete="off">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Usuario</label>
                    <input type="text" id="username" name="username" required autocomplete="username" placeholder="Ingrese su usuario">
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Contraseña</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Ingrese su contraseña">
                </div>
                <button type="submit" class="btn-primary btn-block">Iniciar sesión</button>
            </form>
            <div id="loginError" class="login-error" style="display:none;">
                Usuario o contraseña incorrectos.
            </div>
        </div>
    </div>
    <script>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();
        if(username && password) {
            window.location.href = "dashboard.php";
        } else {
            document.getElementById('loginError').style.display = 'block';
        }
    });
    </script>
</body>
</html>
