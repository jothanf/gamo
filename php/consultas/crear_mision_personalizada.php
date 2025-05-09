<?php
// #mis - Crear misión personalizada por el usuario
require_once '../coneccionBD.php';
session_start();

function limpiar($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $inquilino_id = isset($_POST['inquilino_id']) ? intval($_POST['inquilino_id']) : 0;
    $titulo = limpiar($_POST['titulo'] ?? '');
    $descripcion = limpiar($_POST['descripcion'] ?? '');
    $habilidad = limpiar($_POST['habilidad'] ?? '');
    $puntos = intval($_POST['puntos'] ?? 0);

    if (!$inquilino_id || !$titulo || !$descripcion || !$habilidad || !$puntos) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
        exit;
    }

    // Buscar el avatar del inquilino
    $sql_avatar = "SELECT id FROM avatares WHERE inquilino_id = ?";
    $stmt_avatar = $conexion->prepare($sql_avatar);
    $stmt_avatar->bind_param("i", $inquilino_id);
    $stmt_avatar->execute();
    $res_avatar = $stmt_avatar->get_result();
    $avatar_id = null;

    if ($res_avatar->num_rows > 0) {
        $avatar_id = $res_avatar->fetch_assoc()['id'];
    } else {
        // Crear avatar por defecto si no existe
        $sql_crear = "INSERT INTO avatares (inquilino_id, nombre, descripcion, clase) VALUES (?, 'Cuidador', 'Avatar creado automáticamente', 'cuidador')";
        $stmt_crear = $conexion->prepare($sql_crear);
        $stmt_crear->bind_param("i", $inquilino_id);
        if ($stmt_crear->execute()) {
            $avatar_id = $conexion->insert_id;
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo crear el avatar']);
            exit;
        }
        $stmt_crear->close();
    }
    $stmt_avatar->close();

    // Insertar la misión personalizada en la tabla misiones
    $iconos = [
        'autocuidado' => '🧘',
        'cuidado_de_tu_hogar' => '🏠',
        'cuidado_del_otro' => '🤝',
        'responsabilidad' => '🕒',
        'especial' => '🌟'
    ];
    $icono = $iconos[$habilidad] ?? '🌟';

    $sql_mision = "INSERT INTO misiones (habilidad_a_mejorar, titulo, descripcion, puntos_a_ganar, icono) VALUES (?, ?, ?, ?, ?)";
    $stmt_mision = $conexion->prepare($sql_mision);
    $stmt_mision->bind_param("sssis", $habilidad, $titulo, $descripcion, $puntos, $icono);

    if (!$stmt_mision->execute()) {
        echo json_encode(['success' => false, 'message' => 'No se pudo crear la misión: ' . $stmt_mision->error]);
        exit;
    }
    $mision_id = $conexion->insert_id;
    $stmt_mision->close();

    // Asignar la misión al avatar del usuario (en progreso)
    $fecha_actual = date('Y-m-d H:i:s');
    $sql_asignar = "INSERT INTO mision_asignada (mision_id, avatar_id, fecha_inicio, estado) VALUES (?, ?, ?, 'en_progreso')";
    $stmt_asignar = $conexion->prepare($sql_asignar);
    $stmt_asignar->bind_param("iis", $mision_id, $avatar_id, $fecha_actual);
    if (!$stmt_asignar->execute()) {
        echo json_encode(['success' => false, 'message' => 'No se pudo asignar la misión: ' . $stmt_asignar->error]);
        exit;
    }
    $stmt_asignar->close();

    // Crear notificación para el usuario
    $mensaje = "¡Has creado y aceptado una nueva misión: $titulo! $icono";
    $tipo = "mision_personalizada";
    $generada_por = 1; // O el id del sistema/admin
    $estado = "pendiente";
    $fecha_para_enviar = $fecha_actual;
    $intervalo_repeticion = NULL;

    $stmt_notif = $conexion->prepare("INSERT INTO notificaciones 
        (generada_por, inquilino_id, mensaje, tipo, estado, fecha_para_enviar, intervalo_repeticion) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_notif->bind_param("iissssi", $generada_por, $inquilino_id, $mensaje, $tipo, $estado, $fecha_para_enviar, $intervalo_repeticion);
    $stmt_notif->execute();
    $stmt_notif->close();

    echo json_encode(['success' => true, 'message' => 'Misión creada y asignada con éxito']);
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
$conexion->close();
?> 