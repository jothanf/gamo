<?php
// Incluir el archivo de conexión a la base de datos
require_once '../coneccionBD.php';

// Verificar si se proporcionó un ID de inquilino
if (isset($_GET['inquilino_id'])) {
    $inquilino_id = $_GET['inquilino_id'];
    
    // Obtener el ID del avatar del inquilino
    $sql_avatar = "SELECT id FROM avatares WHERE inquilino_id = ?";
    $stmt_avatar = $conexion->prepare($sql_avatar);
    $stmt_avatar->bind_param("i", $inquilino_id);
    $stmt_avatar->execute();
    $resultado_avatar = $stmt_avatar->get_result();
    
    if ($resultado_avatar->num_rows > 0) {
        $avatar = $resultado_avatar->fetch_assoc();
        $avatar_id = $avatar['id'];
        
        // Consultar misiones completadas
        $sql_misiones = "
            SELECT m.*, ma.id as asignacion_id, ma.fecha_completada
            FROM mision_asignada ma
            JOIN misiones m ON ma.mision_id = m.id
            WHERE ma.avatar_id = ? AND ma.estado = 'completada'
            ORDER BY ma.fecha_completada DESC
        ";
        
        $stmt_misiones = $conexion->prepare($sql_misiones);
        $stmt_misiones->bind_param("i", $avatar_id);
        $stmt_misiones->execute();
        $resultado_misiones = $stmt_misiones->get_result();
        
        $misiones = [];
        while ($fila = $resultado_misiones->fetch_assoc()) {
            $misiones[] = $fila;
        }
        
        // Devolver resultado como JSON
        echo json_encode([
            'success' => true,
            'misiones' => $misiones
        ]);
        
        $stmt_misiones->close();
    } else {
        // Si no se encuentra el avatar
        echo json_encode([
            'success' => false,
            'message' => 'Avatar no encontrado para este inquilino'
        ]);
    }
    
    $stmt_avatar->close();
} else {
    // Si no se proporcionó un ID de inquilino
    echo json_encode([
        'success' => false,
        'message' => 'ID de inquilino no proporcionado'
    ]);
}

// Cerrar la conexión
$conexion->close();
?> 