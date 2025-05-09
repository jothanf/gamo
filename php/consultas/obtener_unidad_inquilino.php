<?php
// Iniciar sesión
session_start();

// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir el archivo de conexión a la base de datos
require_once '../coneccionBD.php';

// Configurar la respuesta como JSON
header('Content-Type: application/json');

// Variable para la respuesta
$response = array('success' => false, 'unidad' => null);

// Verificar si se proporcionó un ID de inquilino
if (isset($_GET['inquilino_id']) && !empty($_GET['inquilino_id'])) {
    $inquilino_id = $_GET['inquilino_id'];
    
    try {
        // Consultar la unidad asignada al inquilino
        $sql = "SELECT u.*, e.nombre AS nombre_edificio 
                FROM unidades u
                JOIN inquilinos_unidades iu ON u.id = iu.unidad_id
                JOIN edificios e ON u.edificio_id = e.id
                WHERE iu.inquilino_id = ? AND iu.estado = 'activo'";
        
        $stmt = $conexion->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Error en la preparación de la consulta: " . $conexion->error);
        }
        
        $stmt->bind_param("i", $inquilino_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
        }
        
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0) {
            $response['unidad'] = $resultado->fetch_assoc();
            $response['success'] = true;
        } else {
            // No se encontró ninguna unidad asignada
            $response['message'] = "No se encontró ninguna unidad asignada a este inquilino.";
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'ID de inquilino no proporcionado.';
}

// Enviar la respuesta
echo json_encode($response);

// Cerrar conexión
$conexion->close();
?> 