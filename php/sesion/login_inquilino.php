<?php
// Iniciar sesión
session_start();

// Mostrar todos los errores para depuración (quitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir el archivo de conexión a la base de datos
require_once '../coneccionBD.php';

// Array para respuesta JSON
$response = array('success' => false, 'message' => '');

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger los datos del formulario
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Validaciones básicas
    if (empty($email) || empty($password)) {
        $response['message'] = 'Por favor, completa todos los campos.';
        echo json_encode($response);
        exit;
    }
    
    // Sanitizar entradas
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    
    // Verificar si el email es válido
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Por favor, introduce un correo electrónico válido.';
        echo json_encode($response);
        exit;
    }
    
    try {
        // Consultar la base de datos para el inquilino
        $sql = "SELECT id, nombre, email, password, foto FROM inquilinos WHERE email = ?";
        $stmt = $conexion->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Error en la preparación de la consulta: " . $conexion->error);
        }
        
        $stmt->bind_param("s", $email);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
        }
        
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows == 1) {
            // Obtener datos del inquilino
            $inquilino = $resultado->fetch_assoc();
            
            // Verificar contraseña
            if (password_verify($password, $inquilino['password'])) {
                // Contraseña correcta - Iniciar sesión
                $_SESSION['inquilino_id'] = $inquilino['id'];
                $_SESSION['inquilino_nombre'] = $inquilino['nombre'];
                $_SESSION['inquilino_email'] = $inquilino['email'];
                $_SESSION['inquilino_foto'] = $inquilino['foto'];
                $_SESSION['tipo_usuario'] = 'inquilino';
                
                // Consultar si el inquilino tiene un avatar asociado
                $sql_avatar = "SELECT id FROM avatares WHERE inquilino_id = ?";
                $stmt_avatar = $conexion->prepare($sql_avatar);
                $stmt_avatar->bind_param("i", $inquilino['id']);
                $stmt_avatar->execute();
                $resultado_avatar = $stmt_avatar->get_result();
                
                if ($resultado_avatar->num_rows > 0) {
                    $avatar = $resultado_avatar->fetch_assoc();
                    $_SESSION['avatar_id'] = $avatar['id'];
                }
                
                // Respuesta exitosa
                $response['success'] = true;
                $response['message'] = '¡Inicio de sesión exitoso! Redirigiendo...';
            } else {
                // Contraseña incorrecta
                $response['message'] = 'Contraseña incorrecta. Por favor, intenta de nuevo.';
            }
        } else {
            // Usuario no encontrado
            $response['message'] = 'No existe una cuenta con ese correo electrónico.';
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $response['message'] = 'Error en el servidor: ' . $e->getMessage();
    }
} else {
    // Si no se envió el formulario por POST
    $response['message'] = 'Método de solicitud incorrecto.';
}

// Enviar respuesta JSON
header('Content-Type: application/json');
echo json_encode($response);

// Cerrar conexión
$conexion->close();
?> 