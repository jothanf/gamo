<?php
// Incluir el archivo de conexión a la base de datos
require_once '../coneccionBD.php';

// Verificar si se proporcionó un ID de inquilino
if (isset($_GET['inquilino_id'])) {
    $inquilino_id = $_GET['inquilino_id'];
    
    // Preparar la consulta para obtener el telegram_id del inquilino
    $sql = "SELECT telegram_id FROM inquilinos WHERE id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $inquilino_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();
        
        // Devolver el resultado como JSON
        echo json_encode([
            'success' => true,
            'telegram_id' => $fila['telegram_id']
        ]);
    } else {
        // Si no se encuentra el inquilino
        echo json_encode([
            'success' => false,
            'message' => 'Inquilino no encontrado'
        ]);
    }
    
    $stmt->close();
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