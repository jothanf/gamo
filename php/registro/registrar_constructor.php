<?php
// Mostrar todos los errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir el archivo de conexión a la base de datos
require_once '../coneccionBD.php';

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger los datos del formulario
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = isset($_POST['telefono']) ? $_POST['telefono'] : null;
    $descripcion = isset($_POST['descripcion']) ? $_POST['descripcion'] : null;
    $password = $_POST['password'];
    $password_confirm = $_POST['password-confirm'];
    
    // Validar que las contraseñas coincidan
    if ($password !== $password_confirm) {
        echo "Las contraseñas no coinciden";
        exit;
    }
    
    // Encriptar la contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Preparar la consulta SQL
    $sql = "INSERT INTO constructores (nombre, telefono, email, descripcion, password) 
            VALUES (?, ?, ?, ?, ?)";
    
    // Preparar statement
    $stmt = $conexion->prepare($sql);
    
    // Verificar si la preparación fue exitosa
    if ($stmt === false) {
        die("Error en la preparación de la consulta: " . $conexion->error);
    }
    
    // Vincular parámetros
    $stmt->bind_param("sssss", $nombre, $telefono, $email, $descripcion, $password_hash);
    
    // Ejecutar la consulta
    if ($stmt->execute()) {
        // Registro exitoso
        echo "<div style='color: green; text-align: center; margin-top: 20px; font-weight: bold;'>Constructor registrado correctamente</div>";
    } else {
        // Error en el registro
        echo "<div style='color: red; text-align: center; margin-top: 20px; font-weight: bold;'>Error al registrar el constructor: " . $stmt->error . "</div>";
    }
    
    // Cerrar statement
    $stmt->close();
}

// Cerrar conexión
$conexion->close();
?>
