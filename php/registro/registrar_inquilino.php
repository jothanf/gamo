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
    $password = $_POST['password'];
    $dia_pago_admin = isset($_POST['dia_pago_admin']) ? intval($_POST['dia_pago_admin']) : null;
    $unidad_id = isset($_POST['unidad_id']) && !empty($_POST['unidad_id']) ? $_POST['unidad_id'] : null;
    
    // Encriptar la contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Variable para almacenar la ruta de la foto si se sube
    $foto = null;
    
    // Procesar foto si existe
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $foto = procesarFoto($_FILES['foto']);
    }
    
    // Iniciar una transacción
    $conexion->begin_transaction();
    
    try {
        // Insertar inquilino
        $sql = "INSERT INTO inquilinos (nombre, telefono, email, foto, password, dia_pago_admin) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conexion->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Error en la preparación de la consulta: " . $conexion->error);
        }
        
        $stmt->bind_param("sssssi", $nombre, $telefono, $email, $foto, $password_hash, $dia_pago_admin);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al registrar el inquilino: " . $stmt->error);
        }
        
        $inquilino_id = $conexion->insert_id;
        $stmt->close();
        
        // Si hay una unidad asignada, crear la relación
        if ($unidad_id !== null) {
            $fecha_ingreso = date('Y-m-d');
            
            $sql_relacion = "INSERT INTO inquilinos_unidades (inquilino_id, unidad_id, fecha_ingreso, estado) 
                             VALUES (?, ?, ?, 'activo')";
            
            $stmt_relacion = $conexion->prepare($sql_relacion);
            
            if ($stmt_relacion === false) {
                throw new Exception("Error en la preparación de la consulta de relación: " . $conexion->error);
            }
            
            $stmt_relacion->bind_param("iis", $inquilino_id, $unidad_id, $fecha_ingreso);
            
            if (!$stmt_relacion->execute()) {
                throw new Exception("Error al asignar la unidad al inquilino: " . $stmt_relacion->error);
            }
            
            $stmt_relacion->close();
            
            // Actualizar el estado de la unidad a ocupado
            $sql_update = "UPDATE unidades SET estado = 'ocupado' WHERE id = ?";
            
            $stmt_update = $conexion->prepare($sql_update);
            
            if ($stmt_update === false) {
                throw new Exception("Error en la preparación de la consulta de actualización: " . $conexion->error);
            }
            
            $stmt_update->bind_param("i", $unidad_id);
            
            if (!$stmt_update->execute()) {
                throw new Exception("Error al actualizar el estado de la unidad: " . $stmt_update->error);
            }
            
            $stmt_update->close();
        }
        
        // Calcular la próxima fecha de vencimiento usando el día proporcionado
        $hoy = new DateTime();
        $mes = $hoy->format('m');
        $anio = $hoy->format('Y');

        // Si el día ya pasó este mes, usar el mes siguiente
        if ($hoy->format('d') >= $dia_pago_admin) {
            $mes = $hoy->format('m') == 12 ? 1 : $hoy->format('m') + 1;
            if ($mes == 1) $anio++;
        }
        $fecha_vencimiento = DateTime::createFromFormat('Y-n-j', "$anio-$mes-$dia_pago_admin");
        if (!$fecha_vencimiento) {
            $fecha_vencimiento = new DateTime(); // fallback
        }
        $fecha_vencimiento_str = $fecha_vencimiento->format('Y-m-d 00:00:00');

        // Crear notificación de bienvenida y recordatorio de pago administración
        $sql_notificacion = "INSERT INTO notificaciones (
            generada_por, 
            fecha_para_enviar, 
            horario_para_enviar, 
            fecha_vencimiento,
            se_repite, 
            intervalo_repeticion,
            inquilino_id, 
            mensaje, 
            tipo, 
            estado,
            leido
        ) VALUES (?, NOW(), 'todo_el_dia', ?, TRUE, NULL, ?, ?, 'pago_administracion', 'pendiente', FALSE)";
        
        $stmt_notificacion = $conexion->prepare($sql_notificacion);
        
        if ($stmt_notificacion === false) {
            throw new Exception("Error en la preparación de la consulta de notificación: " . $conexion->error);
        }
        
        $generada_por = 1; // ID del administrador o sistema
        $mensaje = "Hola " . $nombre . ", recuerda el pago de tu administración el día " . $dia_pago_admin . " de cada mes";
        
        $stmt_notificacion->bind_param("siss", $generada_por, $fecha_vencimiento_str, $inquilino_id, $mensaje);
        
        if (!$stmt_notificacion->execute()) {
            throw new Exception("Error al crear la notificación de bienvenida: " . $stmt_notificacion->error);
        }
        
        $stmt_notificacion->close();
        
        // Confirmar la transacción
        $conexion->commit();
        
        // Mostrar mensaje de éxito y redirigir al registro de avatar
        echo "<div style='color: green; text-align: center; margin-top: 20px; font-weight: bold;'>Inquilino registrado correctamente. Redirigiendo...</div>";
        echo "<script>
            setTimeout(function() {
                window.location.href = '/templates/registro/registrar_avatar.html?inquilino_id={$inquilino_id}';
            }, 1500);
        </script>";
        
    } catch (Exception $e) {
        // Revertir cambios si hay error
        $conexion->rollback();
        
        // Mensaje de error
        echo "<div style='color: red; text-align: center; margin-top: 20px; font-weight: bold;'>" . $e->getMessage() . "</div>";
    }
} else {
    // Si no se envió el formulario por POST
    echo "<div style='color: red; text-align: center; margin-top: 20px; font-weight: bold;'>Error: Método de solicitud incorrecto</div>";
}

// Función para procesar la foto
function procesarFoto($foto) {
    $directorio = "../../uploads/inquilinos/";
    
    // Crear el directorio si no existe
    if (!file_exists($directorio)) {
        mkdir($directorio, 0777, true);
    }
    
    // Generar un nombre único para la foto
    $nombre_foto = uniqid() . "_" . basename($foto["name"]);
    $ruta_foto = $directorio . $nombre_foto;
    
    // Mover la foto al directorio destino
    if (move_uploaded_file($foto["tmp_name"], $ruta_foto)) {
        return $ruta_foto;
    } else {
        return null;
    }
}

// Cerrar conexión
$conexion->close();
?>
