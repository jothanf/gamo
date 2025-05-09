<?php
// Iniciar sesión para acceder a las variables de sesión
session_start();

// Incluir el archivo de conexión a la base de datos
require_once '../coneccionBD.php';

// Verificar si se ha enviado por POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['asignacion_id'])) {
    $asignacion_id = $_POST['asignacion_id'];
    
    // Verificar que el usuario está autenticado
    if (!isset($_SESSION['inquilino_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No hay sesión activa'
        ]);
        exit;
    }
    
    $inquilino_id = $_SESSION['inquilino_id'];
    
    // Iniciar una transacción
    $conexion->begin_transaction();
    
    try {
        // Obtener información de la misión y verificar que pertenece al inquilino
        $sql_verificar = "
            SELECT ma.*, m.puntos_a_ganar, a.id as avatar_id 
            FROM mision_asignada ma
            JOIN misiones m ON ma.mision_id = m.id
            JOIN avatares a ON ma.avatar_id = a.id
            WHERE ma.id = ? AND a.inquilino_id = ?
        ";
        
        $stmt_verificar = $conexion->prepare($sql_verificar);
        $stmt_verificar->bind_param("ii", $asignacion_id, $inquilino_id);
        $stmt_verificar->execute();
        $resultado = $stmt_verificar->get_result();
        
        if ($resultado->num_rows === 0) {
            throw new Exception("La misión no existe o no pertenece a este inquilino");
        }
        
        $mision = $resultado->fetch_assoc();
        $stmt_verificar->close();
        
        // Verificar que la misión esté en progreso
        if ($mision['estado'] !== 'en_progreso') {
            throw new Exception("La misión no está en progreso");
        }
        
        // Actualizar el estado de la misión a completada
        $fecha_actual = date('Y-m-d H:i:s');
        $sql_completar = "
            UPDATE mision_asignada 
            SET estado = 'completada', fecha_completada = ? 
            WHERE id = ?
        ";
        
        $stmt_completar = $conexion->prepare($sql_completar);
        $stmt_completar->bind_param("si", $fecha_actual, $asignacion_id);
        
        if (!$stmt_completar->execute()) {
            throw new Exception("Error al completar la misión: " . $stmt_completar->error);
        }
        
        $stmt_completar->close();
        
        // Actualizar los puntos del avatar
        $puntos_ganados = $mision['puntos_a_ganar'];
        $avatar_id = $mision['avatar_id'];
        
        $sql_actualizar = "
            UPDATE avatares 
            SET puntos = puntos + ? 
            WHERE id = ?
        ";
        
        $stmt_actualizar = $conexion->prepare($sql_actualizar);
        $stmt_actualizar->bind_param("ii", $puntos_ganados, $avatar_id);
        
        if (!$stmt_actualizar->execute()) {
            throw new Exception("Error al actualizar los puntos: " . $stmt_actualizar->error);
        }
        
        $stmt_actualizar->close();

        // #mis - Sumar puntos a la habilidad correspondiente
        // Obtener la habilidad asociada a la misión
        $sql_habilidad = "SELECT habilidad_a_mejorar FROM misiones WHERE id = ?";
        $stmt_habilidad = $conexion->prepare($sql_habilidad);
        $stmt_habilidad->bind_param("i", $mision['mision_id']);
        $stmt_habilidad->execute();
        $stmt_habilidad->bind_result($habilidad_area);
        $stmt_habilidad->fetch();
        $stmt_habilidad->close();

        if ($habilidad_area) {
            // Actualizar los puntos de la habilidad del avatar
            $sql_update_skill = "UPDATE habilidades SET puntos = puntos + ? WHERE avatar_id = ? AND area = ?";
            $stmt_update_skill = $conexion->prepare($sql_update_skill);
            $stmt_update_skill->bind_param("iis", $puntos_ganados, $avatar_id, $habilidad_area);
            $stmt_update_skill->execute();
            $stmt_update_skill->close();
        }

        // NUEVO: Asignar insignia por clase/categoría de la misión
        // Buscar una insignia de la clase correspondiente
        $sql_insignia = "SELECT id FROM insignias WHERE clase = ? LIMIT 1";
        $stmt_insignia = $conexion->prepare($sql_insignia);
        $stmt_insignia->bind_param("s", $habilidad_area);
        $stmt_insignia->execute();
        $stmt_insignia->bind_result($insignia_id);
        if ($stmt_insignia->fetch()) {
            // Verificar si ya tiene la insignia
            $stmt_insignia->close();
            $sql_check = "SELECT id FROM insignias_usuario WHERE avatar_id = ? AND insignia_id = ?";
            $stmt_check = $conexion->prepare($sql_check);
            $stmt_check->bind_param("ii", $avatar_id, $insignia_id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows == 0) {
                // Asignar la insignia
                $stmt_check->close();
                $sql_ganar = "INSERT INTO insignias_usuario (avatar_id, insignia_id) VALUES (?, ?)";
                $stmt_ganar = $conexion->prepare($sql_ganar);
                $stmt_ganar->bind_param("ii", $avatar_id, $insignia_id);
                $stmt_ganar->execute();
                $stmt_ganar->close();
            } else {
                $stmt_check->close();
            }
        } else {
            $stmt_insignia->close();
        }
        
        // Confirmar la transacción
        $conexion->commit();
        
        // Respuesta de éxito
        echo json_encode([
            'success' => true,
            'message' => 'Misión completada correctamente',
            'puntos' => $puntos_ganados
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
    // Si no se enviaron los datos correctamente
    echo json_encode([
        'success' => false,
        'message' => 'Datos incompletos o método de solicitud incorrecto'
    ]);
}

// Cerrar la conexión
$conexion->close();
?> 