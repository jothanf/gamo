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
    $inquilino_id = $_POST['inquilino_id'];
    $nombre = $_POST['nombre'];
    $clase = $_POST['clase'];
    $descripcion = isset($_POST['descripcion']) ? $_POST['descripcion'] : null;
    $habilidades = isset($_POST['habilidades']) ? $_POST['habilidades'] : [];
    
    // Calcular puntos totales
    $puntos_totales = 0;
    foreach ($habilidades as $habilidad) {
        $puntos_totales += isset($habilidad['puntos']) ? intval($habilidad['puntos']) : 0;
    }
    
    // Verificar que no exceda el máximo de puntos
    if ($puntos_totales > 40) {
        echo "<div style='color: red; text-align: center; margin-top: 20px; font-weight: bold;'>Has excedido el máximo de 40 puntos para habilidades</div>";
        exit;
    }
    
    // Procesar foto si existe
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $foto = procesarFoto($_FILES['foto']);
    }
    
    // Iniciar una transacción
    $conexion->begin_transaction();
    
    try {
        // Insertar el avatar
        $sql = "INSERT INTO avatares (inquilino_id, nombre, descripcion, clase, nivel, puntos, creditos, foto) 
                VALUES (?, ?, ?, ?, 1, 10, 0, ?)";
        
        $stmt = $conexion->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Error en la preparación de la consulta: " . $conexion->error);
        }
        
        $stmt->bind_param("issss", $inquilino_id, $nombre, $descripcion, $clase, $foto);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al crear el avatar: " . $stmt->error);
        }
        
        $avatar_id = $conexion->insert_id;
        $stmt->close();
        
        // Insertar habilidades
        foreach ($habilidades as $key => $habilidad) {
            if (isset($habilidad['puntos']) && intval($habilidad['puntos']) > 0) {
                $nombre_habilidad = $habilidad['nombre'] ?? $key;
                $area = $habilidad['area'] ?? 'autocuidado';
                $puntos = intval($habilidad['puntos']);
                
                $sql_habilidad = "INSERT INTO habilidades (area, nombre, descripcion, puntos, avatar_id) 
                                   VALUES (?, ?, NULL, ?, ?)";
                
                $stmt_habilidad = $conexion->prepare($sql_habilidad);
                
                if ($stmt_habilidad === false) {
                    throw new Exception("Error en la preparación de la consulta de habilidad: " . $conexion->error);
                }
                
                $stmt_habilidad->bind_param("ssii", $area, $nombre_habilidad, $puntos, $avatar_id);
                
                if (!$stmt_habilidad->execute()) {
                    throw new Exception("Error al registrar la habilidad: " . $stmt_habilidad->error);
                }
                
                $stmt_habilidad->close();
            }
        }
        
        // Asignar misiones iniciales según la clase
        $misiones_iniciales = obtenerMisionesIniciales($clase);
        foreach ($misiones_iniciales as $mision_id) {
            $sql_mision = "INSERT INTO mision_asignada (mision_id, avatar_id, estado) 
                          VALUES (?, ?, 'pendiente')";
            
            $stmt_mision = $conexion->prepare($sql_mision);
            
            if ($stmt_mision === false) {
                throw new Exception("Error en la preparación de la consulta de misión: " . $conexion->error);
            }
            
            $stmt_mision->bind_param("ii", $mision_id, $avatar_id);
            
            if (!$stmt_mision->execute()) {
                throw new Exception("Error al asignar misión inicial: " . $stmt_mision->error);
            }
            
            $stmt_mision->close();
        }
        
        // Confirmar la transacción
        $conexion->commit();

        // INICIAR SESIÓN AUTOMÁTICAMENTE
        session_start();
        // Obtener datos del inquilino
        $sql_inq = "SELECT id, nombre, email, foto FROM inquilinos WHERE id = ?";
        $stmt_inq = $conexion->prepare($sql_inq);
        $stmt_inq->bind_param("i", $inquilino_id);
        $stmt_inq->execute();
        $res_inq = $stmt_inq->get_result();
        if ($res_inq && $res_inq->num_rows > 0) {
            $inquilino = $res_inq->fetch_assoc();
            $_SESSION['inquilino_id'] = $inquilino['id'];
            $_SESSION['inquilino_nombre'] = $inquilino['nombre'];
            $_SESSION['inquilino_email'] = $inquilino['email'];
            $_SESSION['inquilino_foto'] = $inquilino['foto'];
            $_SESSION['tipo_usuario'] = 'inquilino';
            $_SESSION['avatar_id'] = $avatar_id;
        }
        $stmt_inq->close();

        // Mostrar mensaje de éxito y redirigir al dashboard
        echo "<div style='color: green; text-align: center; margin-top: 20px; font-weight: bold;'>¡Avatar creado con éxito! Ahora puedes comenzar tu aventura en GAMO.</div>";
        echo "<script>
            setTimeout(function() {
                window.location.href = 'https://gamo.noraagent.com/templates/dashboard/inquilino_dashboard.html';
            }, 1500);
        </script>";
        
    } catch (Exception $e) {
        // Revertir cambios si hay error
        $conexion->rollback();
        
        // Mensaje de error
        echo "<div style='color: red; text-align: center; margin-top: 20px; font-weight: bold;'>" . $e->getMessage() . "</div>";
    }
} else {
    // Si no se envió el formulario por POST
    echo "<div style='color: red; text-align: center; margin-top: 20px; font-weight: bold;'>Error: Método de solicitud incorrecto</div>";
}

// Función para procesar la foto
function procesarFoto($foto) {
    $directorio = "../../uploads/avatares/";
    
    // Crear el directorio si no existe
    if (!file_exists($directorio)) {
        mkdir($directorio, 0777, true);
    }
    
    // Generar un nombre único para la foto
    $nombre_foto = uniqid() . "_" . basename($foto["name"]);
    $ruta_foto = $directorio . $nombre_foto;
    
    // Mover la foto al directorio destino
    if (move_uploaded_file($foto["tmp_name"], $ruta_foto)) {
        return $ruta_foto;
    } else {
        return null;
    }
}

// Función para obtener misiones iniciales según la clase
function obtenerMisionesIniciales($clase) {
    global $conexion;
    
    // Array para almacenar IDs de las misiones
    $misiones = [];
    
    // Consultar misiones apropiadas para la clase
    $sql = "SELECT id FROM misiones WHERE nivel_requerido = 1";
    
    if ($clase === 'cuidador') {
        $sql .= " AND (habilidad_a_mejorar LIKE '%Empatía%' OR habilidad_a_mejorar LIKE '%Rutina%' OR habilidad_a_mejorar LIKE '%Bienestar%' OR habilidad_a_mejorar LIKE '%Comunicación%')";
    } elseif ($clase === 'sabio') {
        $sql .= " AND (habilidad_a_mejorar LIKE '%Liderazgo%' OR habilidad_a_mejorar LIKE '%Responsabilidad%' OR habilidad_a_mejorar LIKE '%Conservación%' OR habilidad_a_mejorar LIKE '%Transmisión%')";
    } elseif ($clase === 'explorador') {
        $sql .= " AND (habilidad_a_mejorar LIKE '%Curiosidad%' OR habilidad_a_mejorar LIKE '%Tecnología%' OR habilidad_a_mejorar LIKE '%Cultura%' OR habilidad_a_mejorar LIKE '%Resolución%')";
    }
    
    $sql .= " LIMIT 3"; // Asignar 3 misiones iniciales
    
    $resultado = $conexion->query($sql);
    
    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $misiones[] = $fila['id'];
        }
    }
    
    // Si no hay misiones específicas para la clase, asignar misiones genéricas
    if (empty($misiones)) {
        $sql_genericas = "SELECT id FROM misiones WHERE nivel_requerido = 1 LIMIT 3";
        $resultado_genericas = $conexion->query($sql_genericas);
        
        if ($resultado_genericas && $resultado_genericas->num_rows > 0) {
            while ($fila = $resultado_genericas->fetch_assoc()) {
                $misiones[] = $fila['id'];
            }
        }
    }
    
    return $misiones;
}

// Cerrar conexión
$conexion->close();
?>
