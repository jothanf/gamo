<?php
require_once '../coneccionBD.php';

if (!isset($_GET['inquilino_id'])) {
    echo json_encode(['success' => false, 'message' => 'Falta inquilino_id']);
    exit;
}

$inquilino_id = intval($_GET['inquilino_id']);

// Buscar el avatar del inquilino
$sql_avatar = "SELECT id FROM avatares WHERE inquilino_id = ? LIMIT 1";
$stmt_avatar = $conexion->prepare($sql_avatar);
$stmt_avatar->bind_param("i", $inquilino_id);
$stmt_avatar->execute();
$stmt_avatar->bind_result($avatar_id);
if (!$stmt_avatar->fetch()) {
    echo json_encode(['success' => false, 'message' => 'No se encontró avatar']);
    exit;
}
$stmt_avatar->close();

// Buscar insignias ganadas
$sql = "SELECT i.nombre, i.icono FROM insignias_usuario iu
        JOIN insignias i ON iu.insignia_id = i.id
        WHERE iu.avatar_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $avatar_id);
$stmt->execute();
$result = $stmt->get_result();

$insignias = [];
while ($row = $result->fetch_assoc()) {
    $insignias[] = $row;
}
$stmt->close();

echo json_encode(['success' => true, 'insignias' => $insignias]);
?> 