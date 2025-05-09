<?php
// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir el archivo de conexión a la base de datos
require_once '../coneccionBD.php';

// Función para obtener todos los edificios
function obtenerEdificios() {
    global $conexion;
    
    // Preparar la consulta SQL
    $sql = "SELECT id, nombre FROM edificios ORDER BY nombre ASC";
    
    // Ejecutar la consulta
    $resultado = $conexion->query($sql);
    
    // Verificar si hay resultados
    if ($resultado && $resultado->num_rows > 0) {
        // Array para almacenar edificios
        $edificios = array();
        
        // Obtener datos como array asociativo
        while ($fila = $resultado->fetch_assoc()) {
            $edificios[] = $fila;
        }
        
        return $edificios;
    } else {
        // No hay edificios registrados o hubo un error
        error_log("No se encontraron edificios o hubo un error en la consulta: " . $conexion->error);
        return array();
    }
}

// Si se llama directamente a este archivo, devuelve los edificios en formato JSON
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header('Content-Type: application/json');
    $edificios = obtenerEdificios();
    error_log("Enviando datos de edificios: " . json_encode($edificios));
    echo json_encode($edificios);
}
?> 