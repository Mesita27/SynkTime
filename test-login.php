<?php
// Quick test login for development
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['nombre_completo'] = 'Administrador';
$_SESSION['email'] = 'admin@synktime.com';
$_SESSION['rol'] = 'ADMINISTRADOR';
$_SESSION['id_empresa'] = 1;
$_SESSION['empresa_nombre'] = 'SynkTime Corp';
$_SESSION['login_time'] = time();
$_SESSION['last_activity'] = time();

header('Location: dashboard.php');
exit;
?>