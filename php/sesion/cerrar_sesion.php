<?php
// Iniciar sesión
session_start();

// Configurar la respuesta como JSON
header('Content-Type: application/json');

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea destruir la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Enviar respuesta de éxito
echo json_encode(array('success' => true, 'message' => 'Sesión cerrada correctamente'));
?> 