<?php
// Configuración de la conexión a la base de datos
$host = "localhost"; // Por defecto, cambia si tu host es diferente
$usuario = "u439962818_gamouser"; // Usuario de la base de datos
$contraseña = "gamoAgent2025$"; // Contraseña de la base de datos
$nombreBD = "u439962818_gamo"; // Nombre de la base de datos

// Crear conexión
$conexion = new mysqli($host, $usuario, $contraseña, $nombreBD);

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Establecer conjunto de caracteres a utf8
$conexion->set_charset("utf8");

// Opcional: Mensaje de éxito (comenta esta línea en producción)
// echo "Conexión exitosa a la base de datos";
?>
