<?php
// Incluir el archivo de conexión a la base de datos
require_once '../coneccionBD.php';

// Función para obtener todos los constructores
function obtenerConstructores() {
    global $conexion;
    
    // Preparar la consulta SQL
    $sql = "SELECT id, nombre FROM constructores ORDER BY nombre ASC";
    
    // Ejecutar la consulta
    $resultado = $conexion->query($sql);
    
    // Verificar si hay resultados
    if ($resultado && $resultado->num_rows > 0) {
        // Array para almacenar constructores
        $constructores = array();
        
        // Obtener datos como array asociativo
        while ($fila = $resultado->fetch_assoc()) {
            $constructores[] = $fila;
        }
        
        return $constructores;
    } else {
        // No hay constructores registrados
        return array();
    }
}

// Si se llama directamente a este archivo, devuelve los constructores en formato JSON
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header('Content-Type: application/json');
    echo json_encode(obtenerConstructores());
}
?> 