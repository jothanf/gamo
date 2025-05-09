<?php
// Mostrar todos los errores para depuración (quitar en producción)
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
    $rol = isset($_POST['rol']) ? $_POST['rol'] : 'copropietario';
    $password = $_POST['password'];
    $password_confirm = $_POST['password-confirm'];
    
    // Validar que las contraseñas coincidan
    if ($password !== $password_confirm) {
        echo "<div style='color: red; text-align: center; margin-top: 20px; font-weight: bold;'>Las contraseñas no coinciden</div>";
        exit;
    }
    
    // Encriptar la contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Preparar la consulta SQL
    $sql = "INSERT INTO propietarios (nombre, telefono, email, rol, password) 
            VALUES (?, ?, ?, ?, ?)";
    
    // Preparar statement
    $stmt = $conexion->prepare($sql);
    
    // Verificar si la preparación fue exitosa
    if ($stmt === false) {
        die("Error en la preparación de la consulta: " . $conexion->error);
    }
    
    // Vincular parámetros
    $stmt->bind_param("sssss", $nombre, $telefono, $email, $rol, $password_hash);
    
    // Ejecutar la consulta
    if ($stmt->execute()) {
        // Si hay un edificio seleccionado, crear la relación en dueños_edificios
        if (isset($_POST['edificio_id']) && !empty($_POST['edificio_id'])) {
            $edificio_id = $_POST['edificio_id'];
            $porcentaje = isset($_POST['porcentaje_propiedad']) ? $_POST['porcentaje_propiedad'] : 100.00;
            $fecha_adquisicion = isset($_POST['fecha_adquisicion']) ? $_POST['fecha_adquisicion'] : date('Y-m-d');
            
            // Obtener el ID del propietario recién insertado
            $dueno_id = $conexion->insert_id;
            
            // Insertar la relación en dueños_edificios
            $sql_relacion = "INSERT INTO dueños_edificios (dueño_id, edificio_id, porcentaje_propiedad, fecha_adquisicion) 
                             VALUES (?, ?, ?, ?)";
            
            $stmt_relacion = $conexion->prepare($sql_relacion);
            
            if ($stmt_relacion === false) {
                die("Error en la preparación de la consulta de relación: " . $conexion->error);
            }
            
            $stmt_relacion->bind_param("iisd", $dueno_id, $edificio_id, $porcentaje, $fecha_adquisicion);
            
            if (!$stmt_relacion->execute()) {
                echo "<div style='color: red; text-align: center; margin-top: 20px; font-weight: bold;'>Error al establecer la relación con el edificio: " . $stmt_relacion->error . "</div>";
            }
            
            $stmt_relacion->close();
        }
        
        // Registro exitoso
        echo "<div style='color: green; text-align: center; margin-top: 20px; font-weight: bold;'>Propietario registrado correctamente</div>";
    } else {
        // Error en el registro
        echo "<div style='color: red; text-align: center; margin-top: 20px; font-weight: bold;'>Error al registrar el propietario: " . $stmt->error . "</div>";
    }
    
    // Cerrar statement
    $stmt->close();
}

// Cerrar conexión
$conexion->close();
?>
