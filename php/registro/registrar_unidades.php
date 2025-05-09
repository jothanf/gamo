<?php
// Mostrar todos los errores para depuración (quitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir el archivo de conexión a la base de datos
require_once '../coneccionBD.php';

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger los datos del formulario
    $edificio_id = $_POST['edificio_id'];
    $numero_unidad = $_POST['numero_unidad'];
    $piso = isset($_POST['piso']) ? $_POST['piso'] : null;
    $torre = isset($_POST['torre']) ? $_POST['torre'] : null;
    $tipo = $_POST['tipo'];
    $estado = isset($_POST['estado']) ? $_POST['estado'] : 'vacante';
    $dueno_id = isset($_POST['dueño_id']) && !empty($_POST['dueño_id']) ? $_POST['dueño_id'] : null;
    $descripcion = isset($_POST['descripcion']) ? $_POST['descripcion'] : null;
    
    // Preparar la consulta SQL
    $sql = "INSERT INTO unidades (edificio_id, numero_unidad, piso, torre, tipo, estado, dueño_id, descripcion) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    // Preparar statement
    $stmt = $conexion->prepare($sql);
    
    // Verificar si la preparación fue exitosa
    if ($stmt === false) {
        die("Error en la preparación de la consulta: " . $conexion->error);
    }
    
    // Vincular parámetros
    $stmt->bind_param("isiissss", $edificio_id, $numero_unidad, $piso, $torre, $tipo, $estado, $dueno_id, $descripcion);
    
    // Ejecutar la consulta
    if ($stmt->execute()) {
        $unidad_id = $conexion->insert_id;
        
        // Procesar fotos si existen
        if (isset($_FILES['fotos']) && $_FILES['fotos']['error'][0] != 4) {
            $imagenes = reordenarArrayFiles($_FILES['fotos']);
            
            foreach ($imagenes as $imagen) {
                if ($imagen['error'] == 0) {
                    $ruta_imagen = procesarImagen($imagen);
                    
                    if ($ruta_imagen) {
                        // Insertar registro en multimedia_edificio
                        $sql_imagen = "INSERT INTO multimedia_edificio (nombre, edificio_id, tipo, unidad_id, url) 
                                      VALUES (?, ?, 'imagen', ?, ?)";
                        
                        $stmt_imagen = $conexion->prepare($sql_imagen);
                        
                        if ($stmt_imagen === false) {
                            die("Error en la preparación de la consulta de imagen: " . $conexion->error);
                        }
                        
                        $nombre_imagen = basename($imagen['name']);
                        
                        $stmt_imagen->bind_param("siis", $nombre_imagen, $edificio_id, $unidad_id, $ruta_imagen);
                        
                        if (!$stmt_imagen->execute()) {
                            echo "<div style='color: red; text-align: center; margin-top: 20px; font-weight: bold;'>Error al guardar la imagen: " . $stmt_imagen->error . "</div>";
                        }
                        
                        $stmt_imagen->close();
                    }
                }
            }
        }
        
        // Registro exitoso
        echo "<div style='color: green; text-align: center; margin-top: 20px; font-weight: bold;'>Unidad registrada correctamente</div>";
    } else {
        // Error en el registro
        echo "<div style='color: red; text-align: center; margin-top: 20px; font-weight: bold;'>Error al registrar la unidad: " . $stmt->error . "</div>";
    }
    
    // Cerrar statement
    $stmt->close();
}

// Función para procesar imágenes
function procesarImagen($imagen) {
    $directorio = "../../uploads/unidades/";
    
    // Crear el directorio si no existe
    if (!file_exists($directorio)) {
        mkdir($directorio, 0777, true);
    }
    
    // Generar un nombre único para la imagen
    $nombre_imagen = uniqid() . "_" . basename($imagen["name"]);
    $ruta_imagen = $directorio . $nombre_imagen;
    
    // Mover la imagen al directorio destino
    if (move_uploaded_file($imagen["tmp_name"], $ruta_imagen)) {
        return $ruta_imagen;
    } else {
        return null;
    }
}

// Función para reordenar el array de archivos múltiples
function reordenarArrayFiles($file_post) {
    $file_ary = array();
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post);
    
    for ($i=0; $i<$file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_ary[$i][$key] = $file_post[$key][$i];
        }
    }
    
    return $file_ary;
}

// Cerrar conexión
$conexion->close();
?>
