<?php
// Iniciar sesión
session_start();

// Configurar la respuesta como JSON
header('Content-Type: application/json');

// Variable para la respuesta
$response = array(
    'logged_in' => false,
    'tipo_usuario' => null,
    'id' => null,
    'nombre' => null,
    'email' => null,
    'foto' => null
);

// Verificar si hay una sesión activa de inquilino
if (isset($_SESSION['inquilino_id']) && isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'inquilino') {
    $response['logged_in'] = true;
    $response['tipo_usuario'] = 'inquilino';
    $response['id'] = $_SESSION['inquilino_id'];
    $response['nombre'] = $_SESSION['inquilino_nombre'];
    $response['email'] = $_SESSION['inquilino_email'];
    $response['foto'] = $_SESSION['inquilino_foto'];
    
    // Si hay un avatar asociado, incluir su ID
    if (isset($_SESSION['avatar_id'])) {
        $response['avatar_id'] = $_SESSION['avatar_id'];
    }
}

// Enviar la respuesta
echo json_encode($response);
?> 