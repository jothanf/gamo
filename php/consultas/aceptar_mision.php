<?php
// Incluir el archivo de conexión a la base de datos
require_once '../coneccionBD.php';

// Iniciar sesión
session_start();

// Verificar si se ha enviado por POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mision_id'])) {
    // Obtener datos del formulario
    $mision_id = $_POST['mision_id'];
    
    // Obtener el ID del usuario de la sesión
    if (isset($_SESSION['inquilino_id'])) {
        $inquilino_id = $_SESSION['inquilino_id'];
    } else {
        // Si no hay sesión activa, devolver error
        echo json_encode([
            'success' => false,
            'message' => 'No hay sesión activa'
        ]);
        exit;
    }
    
    // Buscar el avatar del inquilino
    $sql_avatar = "SELECT id FROM avatares WHERE inquilino_id = ?";
    $stmt_avatar = $conexion->prepare($sql_avatar);
    $stmt_avatar->bind_param("i", $inquilino_id);
    $stmt_avatar->execute();
    $resultado_avatar = $stmt_avatar->get_result();
    
    if ($resultado_avatar->num_rows > 0) {
        $avatar = $resultado_avatar->fetch_assoc();
        $avatar_id = $avatar['id'];
        
        // Iniciar una transacción
        $conexion->begin_transaction();
        
        try {
            // Asignar la misión al avatar con fecha de inicio actual
            $fecha_actual = date('Y-m-d H:i:s');
            $sql_asignar = "INSERT INTO mision_asignada (mision_id, avatar_id, fecha_inicio, estado) 
                            VALUES (?, ?, ?, 'en_progreso')";
            
            $stmt_asignar = $conexion->prepare($sql_asignar);
            $stmt_asignar->bind_param("iis", $mision_id, $avatar_id, $fecha_actual);
            
            if (!$stmt_asignar->execute()) {
                throw new Exception("Error al asignar la misión: " . $stmt_asignar->error);
            }
            
            $stmt_asignar->close();
            
            // Obtener información de la misión para el mensaje
            $stmt = $conexion->prepare("SELECT titulo FROM misiones WHERE id = ?");
            $stmt->bind_param("i", $mision_id);
            $stmt->execute();
            $stmt->bind_result($titulo_mision);
            $stmt->fetch();
            $stmt->close();

            // Insertar notificación
            $mensaje = "Has aceptado la misión: " . $titulo_mision;
            $tipo = "mision";
            $generada_por = 1; // Ahora usamos el id=1
            $estado = "pendiente";

            $fecha_para_enviar = date('Y-m-d H:i:s'); // Fecha y hora actual en PHP
            $intervalo_repeticion = NULL; // No se repite

            $stmt = $conexion->prepare("INSERT INTO notificaciones 
                (generada_por, inquilino_id, mensaje, tipo, estado, fecha_para_enviar, intervalo_repeticion) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissssi", $generada_por, $inquilino_id, $mensaje, $tipo, $estado, $fecha_para_enviar, $intervalo_repeticion);
            $stmt->execute();
            $stmt->close();
            
            // Confirmar la transacción
            $conexion->commit();
            
            // Respuesta de éxito
            echo json_encode([
                'success' => true,
                'message' => 'Misión aceptada correctamente',
                'inquilino_id' => $inquilino_id
            ]);
            
        } catch (Exception $e) {
            // Revertir la transacción en caso de error
            $conexion->rollback();
            
            // Respuesta de error
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        
    } else {
        // Si no se encuentra el avatar, intentar crear uno
        try {
            // Iniciar transacción
            $conexion->begin_transaction();
            
            // Crear avatar por defecto
            $nombre_avatar = "Cuidador";
            $clase_avatar = "cuidador";
            $descripcion = "Avatar cuidador predeterminado";
            
            $sql_crear = "INSERT INTO avatares (inquilino_id, nombre, descripcion, clase) 
                          VALUES (?, ?, ?, ?)";
            
            $stmt_crear = $conexion->prepare($sql_crear);
            $stmt_crear->bind_param("isss", $inquilino_id, $nombre_avatar, $descripcion, $clase_avatar);
            
            if (!$stmt_crear->execute()) {
                throw new Exception("Error al crear el avatar: " . $stmt_crear->error);
            }
            
            $avatar_id = $conexion->insert_id;
            $stmt_crear->close();
            
            // Ahora asignar la misión
            $fecha_actual = date('Y-m-d H:i:s');
            $sql_asignar = "INSERT INTO mision_asignada (mision_id, avatar_id, fecha_inicio, estado) 
                            VALUES (?, ?, ?, 'en_progreso')";
            
            $stmt_asignar = $conexion->prepare($sql_asignar);
            $stmt_asignar->bind_param("iis", $mision_id, $avatar_id, $fecha_actual);
            
            if (!$stmt_asignar->execute()) {
                throw new Exception("Error al asignar la misión: " . $stmt_asignar->error);
            }
            
            $stmt_asignar->close();
            
            // Obtener información de la misión para el mensaje
            $stmt = $conexion->prepare("SELECT titulo FROM misiones WHERE id = ?");
            $stmt->bind_param("i", $mision_id);
            $stmt->execute();
            $stmt->bind_result($titulo_mision);
            $stmt->fetch();
            $stmt->close();

            // Insertar notificación
            $mensaje = "Has aceptado la misión: " . $titulo_mision;
            $tipo = "mision";
            $generada_por = 1; // Ahora usamos el id=1
            $estado = "pendiente";

            $fecha_para_enviar = date('Y-m-d H:i:s'); // Fecha y hora actual en PHP
            $intervalo_repeticion = NULL; // No se repite

            $stmt = $conexion->prepare("INSERT INTO notificaciones 
                (generada_por, inquilino_id, mensaje, tipo, estado, fecha_para_enviar, intervalo_repeticion) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissssi", $generada_por, $inquilino_id, $mensaje, $tipo, $estado, $fecha_para_enviar, $intervalo_repeticion);
            $stmt->execute();
            $stmt->close();
            
            // Confirmar la transacción
            $conexion->commit();
            
            // Respuesta de éxito
            echo json_encode([
                'success' => true,
                'message' => 'Avatar creado y misión aceptada correctamente',
                'inquilino_id' => $inquilino_id
            ]);
            
        } catch (Exception $e) {
            // Revertir cambios
            $conexion->rollback();
            
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    if (isset($stmt_avatar)) {
        $stmt_avatar->close();
    }
    
} else {
    // Si no se enviaron los datos correctamente
    echo json_encode([
        'success' => false,
        'message' => 'Depuración de sesión',
        'session_data' => $_SESSION
    ]);
    exit;
}

// Cerrar la conexión
$conexion->close();
?> 