<?php
// Incluir el archivo de conexión a la base de datos
require_once '../coneccionBD.php';

// Verificar si se proporcionó un ID de inquilino
if (isset($_GET['inquilino_id'])) {
    $inquilino_id = $_GET['inquilino_id'];
    
    // Preparar la consulta para obtener información del avatar
    $sql = "SELECT a.* FROM avatares a WHERE a.inquilino_id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $inquilino_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $avatar = $resultado->fetch_assoc();
        
        // Obtener habilidades del avatar
        $sql_habilidades = "SELECT * FROM habilidades WHERE avatar_id = ?";
        $stmt_habilidades = $conexion->prepare($sql_habilidades);
        $stmt_habilidades->bind_param("i", $avatar['id']);
        $stmt_habilidades->execute();
        $resultado_habilidades = $stmt_habilidades->get_result();
        
        $habilidades = [];
        while ($fila = $resultado_habilidades->fetch_assoc()) {
            $habilidades[] = $fila;
        }
        
        // Devolver el resultado como JSON
        echo json_encode([
            'success' => true,
            'avatar' => $avatar,
            'habilidades' => $habilidades
        ]);
        
        $stmt_habilidades->close();
    } else {
        // Si no se encuentra el avatar, intentar crear uno predeterminado
        $nombre_avatar = "Cuidador";
        $clase_avatar = "cuidador";
        $descripcion = "Avatar cuidador predeterminado";
        
        // Iniciar transacción
        $conexion->begin_transaction();
        
        try {
            // Crear avatar
            $sql_crear = "INSERT INTO avatares (inquilino_id, nombre, descripcion, clase) 
                          VALUES (?, ?, ?, ?)";
            
            $stmt_crear = $conexion->prepare($sql_crear);
            $stmt_crear->bind_param("isss", $inquilino_id, $nombre_avatar, $descripcion, $clase_avatar);
            
            if ($stmt_crear->execute()) {
                $avatar_id = $conexion->insert_id;
                
                // Crear habilidades básicas
                $areas = ['autocuidado', 'cuidado_de_tu_hogar', 'cuidado_del_otro', 'responsabilidad'];
                
                foreach ($areas as $area) {
                    $sql_hab = "INSERT INTO habilidades (area, nombre, puntos, avatar_id) 
                                VALUES (?, ?, 10, ?)";
                    
                    $nombre_hab = ucfirst(str_replace('_', ' ', $area));
                    
                    $stmt_hab = $conexion->prepare($sql_hab);
                    $stmt_hab->bind_param("ssi", $area, $nombre_hab, $avatar_id);
                    $stmt_hab->execute();
                    $stmt_hab->close();
                }
                
                // Confirmar transacción
                $conexion->commit();
                
                // Devolver el nuevo avatar
                echo json_encode([
                    'success' => true,
                    'avatar' => [
                        'id' => $avatar_id,
                        'inquilino_id' => $inquilino_id,
                        'nombre' => $nombre_avatar,
                        'descripcion' => $descripcion,
                        'clase' => $clase_avatar,
                        'nivel' => 1,
                        'puntos' => 10,
                        'creditos' => 0
                    ],
                    'habilidades' => [
                        ['area' => 'autocuidado', 'puntos' => 10],
                        ['area' => 'cuidado_de_tu_hogar', 'puntos' => 10],
                        ['area' => 'cuidado_del_otro', 'puntos' => 10],
                        ['area' => 'responsabilidad', 'puntos' => 10]
                    ]
                ]);
                
            } else {
                throw new Exception("Error al crear el avatar");
            }
            
            $stmt_crear->close();
            
        } catch (Exception $e) {
            // Revertir cambios si hay error
            $conexion->rollback();
            
            // Devolver error
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener o crear avatar: ' . $e->getMessage()
            ]);
        }
    }
    
    $stmt->close();
} else {
    // Si no se proporcionó un ID de inquilino
    echo json_encode([
        'success' => false,
        'message' => 'ID de inquilino no proporcionado'
    ]);
}

// Cerrar la conexión
$conexion->close();
?> 