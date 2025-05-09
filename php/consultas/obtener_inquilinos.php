<?php
// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir el archivo de conexión a la base de datos
require_once '../coneccionBD.php';

// Función para obtener los inquilinos disponibles
function obtenerInquilinos() {
    global $conexion;
    
    // Preparar la consulta SQL uniendo con unidades para mostrar información completa
    $sql = "SELECT i.id, i.nombre, i.email, u.numero_unidad as unidad 
            FROM inquilinos i 
            LEFT JOIN inquilinos_unidades iu ON i.id = iu.inquilino_id AND iu.estado = 'activo'
            LEFT JOIN unidades u ON iu.unidad_id = u.id
            WHERE NOT EXISTS (
                SELECT 1 FROM avatares a WHERE a.inquilino_id = i.id
            )
            ORDER BY i.nombre ASC";
    
    // Ejecutar la consulta
    $resultado = $conexion->query($sql);
    
    // Verificar si hay resultados
    if ($resultado && $resultado->num_rows > 0) {
        // Array para almacenar inquilinos
        $inquilinos = array();
        
        // Obtener datos como array asociativo
        while ($fila = $resultado->fetch_assoc()) {
            $inquilinos[] = $fila;
        }
        
        return $inquilinos;
    } else {
        // No hay inquilinos disponibles o hubo un error
        error_log("No se encontraron inquilinos sin avatar o hubo un error en la consulta: " . $conexion->error);
        return array();
    }
}

// Si se llama directamente a este archivo, devuelve los inquilinos en formato JSON
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header('Content-Type: application/json');
    $inquilinos = obtenerInquilinos();
    error_log("Enviando datos de inquilinos: " . json_encode($inquilinos));
    echo json_encode($inquilinos);
}
?> 