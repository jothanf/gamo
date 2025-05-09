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
    $nombre = $_POST['nombre'];
    $constructor_id = $_POST['constructor_id'];
    $direccion = $_POST['direccion'];
    $ciudad = $_POST['ciudad'];
    $pais = $_POST['pais'];
    $fecha_construccion = $_POST['fecha_construccion'];
    $numero_matricula = isset($_POST['numero_matricula']) ? $_POST['numero_matricula'] : null;
    $descripcion = isset($_POST['descripcion']) ? $_POST['descripcion'] : null;
    
    // Inicializar variables para los archivos
    $estatuto = null;
    $manual_convivencia = null;
    $reglamento_interno = null;
    
    // Procesar archivos adjuntos si existen
    if (isset($_FILES['estatuto']) && $_FILES['estatuto']['error'] == 0) {
        // Procesar el archivo de estatuto
        $estatuto = procesarArchivo($_FILES['estatuto'], 'estatutos');
    }
    
    if (isset($_FILES['manual_convivencia']) && $_FILES['manual_convivencia']['error'] == 0) {
        // Procesar el archivo de manual de convivencia
        $manual_convivencia = procesarArchivo($_FILES['manual_convivencia'], 'manuales');
    }
    
    if (isset($_FILES['reglamento_interno']) && $_FILES['reglamento_interno']['error'] == 0) {
        // Procesar el archivo de reglamento interno
        $reglamento_interno = procesarArchivo($_FILES['reglamento_interno'], 'reglamentos');
    }
    
    // Preparar la consulta SQL
    $sql = "INSERT INTO edificios (constructor_id, nombre, direccion, ciudad, pais, descripcion, 
            fecha_construccion, numero_matricula_inmobiliaria, estatuto, manual_convivencia, reglamento_interno) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    // Preparar statement
    $stmt = $conexion->prepare($sql);
    
    // Verificar si la preparación fue exitosa
    if ($stmt === false) {
        die("Error en la preparación de la consulta: " . $conexion->error);
    }
    
    // Vincular parámetros
    $stmt->bind_param("issssssssss", $constructor_id, $nombre, $direccion, $ciudad, $pais, 
                     $descripcion, $fecha_construccion, $numero_matricula, $estatuto, 
                     $manual_convivencia, $reglamento_interno);
    
    // Ejecutar la consulta
    if ($stmt->execute()) {
        // Registro exitoso
        echo "<div style='color: green; text-align: center; margin-top: 20px; font-weight: bold;'>Edificio registrado correctamente</div>";
    } else {
        // Error en el registro
        echo "<div style='color: red; text-align: center; margin-top: 20px; font-weight: bold;'>Error al registrar el edificio: " . $stmt->error . "</div>";
    }
    
    // Cerrar statement
    $stmt->close();
}

// Función para procesar archivos
function procesarArchivo($archivo, $carpeta) {
    $directorio = "../../uploads/" . $carpeta . "/";
    
    // Crear el directorio si no existe
    if (!file_exists($directorio)) {
        mkdir($directorio, 0777, true);
    }
    
    // Generar un nombre único para el archivo
    $nombre_archivo = uniqid() . "_" . basename($archivo["name"]);
    $ruta_archivo = $directorio . $nombre_archivo;
    
    // Mover el archivo al directorio destino
    if (move_uploaded_file($archivo["tmp_name"], $ruta_archivo)) {
        return $ruta_archivo;
    } else {
        return null;
    }
}

// Cerrar conexión
$conexion->close();
?>
