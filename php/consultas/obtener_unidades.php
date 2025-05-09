<?php
// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir el archivo de conexión a la base de datos
require_once '../coneccionBD.php';

// Función para obtener todas las unidades disponibles
function obtenerUnidades() {
    global $conexion;
    
    // Preparar la consulta SQL
    // Unimos con la tabla edificios para obtener el nombre del edificio
    $sql = "SELECT u.id, u.numero_unidad, u.piso, u.torre, u.tipo, u.estado, e.nombre as edificio_nombre 
            FROM unidades u 
            JOIN edificios e ON u.edificio_id = e.id 
            WHERE u.estado != 'mantenimiento' 
            ORDER BY e.nombre, u.numero_unidad ASC";
    
    // Ejecutar la consulta
    $resultado = $conexion->query($sql);
    
    // Verificar si hay resultados
    if ($resultado && $resultado->num_rows > 0) {
        // Array para almacenar unidades
        $unidades = array();
        
        // Obtener datos como array asociativo
        while ($fila = $resultado->fetch_assoc()) {
            $unidades[] = $fila;
        }
        
        return $unidades;
    } else {
        // No hay unidades registradas o hubo un error
        error_log("No se encontraron unidades o hubo un error en la consulta: " . $conexion->error);
        return array();
    }
}

// Si se llama directamente a este archivo, devuelve las unidades en formato JSON
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header('Content-Type: application/json');
    $unidades = obtenerUnidades();
    error_log("Enviando datos de unidades: " . json_encode($unidades));
    echo json_encode($unidades);
}
?> 