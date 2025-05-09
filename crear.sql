-- CRM para Administración de Edificios (GAMA CRM)
-- =================================================================================
-- Uso de InnoDB para soportar transacciones y claves foráneas
-- Charset utf8mb4 para compatibilidad con emojis y múltiples idiomas
-- =================================================================================
-- Constructores
CREATE TABLE constructores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(100),
    email VARCHAR(100) UNIQUE NOT NULL,
    descripcion TEXT,
    password VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dueños del edificios
CREATE TABLE propietarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(100),
    email VARCHAR(100) UNIQUE NOT NULL,
    rol ENUM('copropietario', 'administrador', 'representante') NOT NULL DEFAULT 'copropietario',
    password VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Edificios
CREATE TABLE edificios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    constructor_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    direccion TEXT NOT NULL,
    ciudad VARCHAR(100) NOT NULL,
    pais VARCHAR(100) NOT NULL,
    descripcion TEXT,
    fecha_construccion DATE NOT NULL,
    numero_matricula_inmobiliaria VARCHAR(100),
    estatuto TEXT,
    manual_convivencia TEXT,
    reglamento_interno TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (constructor_id) REFERENCES constructores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Relación muchos a muchos entre dueños y edificios
CREATE TABLE dueños_edificios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dueño_id INT NOT NULL,
    edificio_id INT NOT NULL,
    porcentaje_propiedad DECIMAL(5,2) DEFAULT 100.00,
    fecha_adquisicion DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dueño_id) REFERENCES propietarios(id),
    FOREIGN KEY (edificio_id) REFERENCES edificios(id),
    UNIQUE KEY unique_dueño_edificio (dueño_id, edificio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Unidades/Viviendas dentro de cada edificio
CREATE TABLE unidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    edificio_id INT NOT NULL,
    numero_unidad VARCHAR(20) NOT NULL,
    piso INT,
    torre VARCHAR(50),
    tipo VARCHAR(50),  
    estado ENUM('vacante','ocupado','mantenimiento') NOT NULL DEFAULT 'vacante',
    dueño_id INT NULL,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (edificio_id) REFERENCES edificios(id),
    FOREIGN KEY (dueño_id) REFERENCES propietarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE zonas_comunes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    edificio_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (edificio_id) REFERENCES edificios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para almacenar imágenes y videos
CREATE TABLE multimedia_edificio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    edificio_id INT NOT NULL,
    tipo ENUM('imagen', 'video') NOT NULL,
    unidad_id INT NULL,
    zona_comun_id INT NULL,
    url VARCHAR(255) NOT NULL,
    descripcion TEXT,
    formato VARCHAR(50),
    tamaño INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (edificio_id) REFERENCES edificios(id),
    FOREIGN KEY (unidad_id) REFERENCES unidades(id),
    FOREIGN KEY (zona_comun_id) REFERENCES zonas_comunes(id),
    CHECK (
        (unidad_id IS NOT NULL AND zona_comun_id IS NULL) OR
        (unidad_id IS NULL AND zona_comun_id IS NOT NULL) OR
        (unidad_id IS NULL AND zona_comun_id IS NULL)
    ) -- Permite asociar a unidad, zona común, o solo al edificio
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Personal colaborador en nomina del edificio
CREATE TABLE personal_colaborador (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(100),
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(100) NOT NULL,
    cargo VARCHAR(100) NOT NULL,
    departamento VARCHAR(100),
    fecha_contratacion DATE NOT NULL,
    estado ENUM('activo','inactivo') DEFAULT 'activo',
    foto VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inquilinos asignados a unidades
CREATE TABLE inquilinos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(100),
    email VARCHAR(100) UNIQUE NOT NULL,
    telegram_id VARCHAR(100),
    foto VARCHAR(255),
    password VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dia_pago_renta INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Avatares (cuidador, sabio, explorador)
CREATE TABLE avatares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inquilino_id INT NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    clase VARCHAR(50) NOT NULL,
    nivel INT DEFAULT 1,
    puntos INT DEFAULT 10,
    creditos INT DEFAULT 0,
    foto VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inquilino_id) REFERENCES inquilinos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE facturas_inquilinos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    concepto VARCHAR(100) NOT NULL,
    inquilino_id INT NOT NULL,
    fecha_factura DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    valor_facturado DECIMAL(10,2) NOT NULL,
    valor_pagado DECIMAL(10,2) NOT NULL DEFAULT 0,
    estado ENUM('pendiente', 'pagado') NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inquilino_id) REFERENCES inquilinos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pagos_factura (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    metodo_pago VARCHAR(50) NOT NULL,
    referencia VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    img_comprobante VARCHAR(255),
    FOREIGN KEY (factura_id) REFERENCES facturas_inquilinos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Proveedores
CREATE TABLE proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    servicio VARCHAR(100) NOT NULL,
    descripcion_servicio TEXT,
    contacto VARCHAR(100),
    contrato_adjunto VARCHAR(100),
    calificacion DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE facturas_proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,
    concepto VARCHAR(100) NOT NULL,
    valor_facturado DECIMAL(10,2) NOT NULL,
    valor_pagado DECIMAL(10,2) NOT NULL DEFAULT 0,
    estado ENUM('pendiente', 'pagado') NOT NULL DEFAULT 'pendiente',
    fecha_factura DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pagos_proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_proveedor_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    metodo_pago VARCHAR(50) NOT NULL,
    referencia VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    img_comprobante VARCHAR(255),
    FOREIGN KEY (factura_proveedor_id) REFERENCES facturas_proveedores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Incidencias reportadas por usuarios
CREATE TABLE incidencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inquilino_id INT NOT NULL,
    categoria VARCHAR(100),
    descripcion TEXT NOT NULL,
    estado ENUM('pendiente','en_revision','derivado','resuelto') DEFAULT 'pendiente',
    img_reporte VARCHAR(255),
    fecha_reportada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_resuelta TIMESTAMP NULL,
    FOREIGN KEY (inquilino_id) REFERENCES inquilinos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Requerimientos de mantenimiento (generados por administrador)
CREATE TABLE requerimientos_mantenimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unidad_id INT NULL,
    zona_comun_id INT NULL,
    creado_por INT NOT NULL,
    incidencia_id INT NULL,
    categoria VARCHAR(100),
    descripcion TEXT NOT NULL,
    prioridad ENUM('baja','media','alta','urgente') NOT NULL DEFAULT 'media',
    estado ENUM('abierto','en_progreso','resuelto','cerrado') NOT NULL DEFAULT 'abierto',
    asignado_a_personal_colaborador INT NULL,
    asignado_a_proveedor INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion TIMESTAMP NULL,
    FOREIGN KEY (unidad_id) REFERENCES unidades(id),
    FOREIGN KEY (creado_por) REFERENCES personal_colaborador(id),
    FOREIGN KEY (incidencia_id) REFERENCES incidencias(id),
    FOREIGN KEY (asignado_a_personal_colaborador) REFERENCES personal_colaborador(id),
    FOREIGN KEY (asignado_a_proveedor) REFERENCES proveedores(id),
    FOREIGN KEY (zona_comun_id) REFERENCES zonas_comunes(id),
    CHECK (unidad_id IS NULL OR zona_comun_id IS NULL), -- Evita que ambos estén poblados simultáneamente
    CHECK (unidad_id IS NOT NULL OR zona_comun_id IS NOT NULL) -- Asegura que al menos uno esté poblado
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE facturas_requerimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requerimiento_id INT NOT NULL,
    fecha_factura DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    valor_facturado DECIMAL(10,2) NOT NULL,
    valor_pagado DECIMAL(10,2) NOT NULL DEFAULT 0,
    estado ENUM('pendiente', 'pagado') NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requerimiento_id) REFERENCES requerimientos_mantenimiento(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pagos_facturas_requerimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_requerimiento_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (factura_requerimiento_id) REFERENCES facturas_requerimientos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tareas internas / workflow para staff
CREATE TABLE tareas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    asignado_a INT NULL,
    requerimiento_id INT NULL,
    estado ENUM('pendiente','en_progreso','completada','cancelada') DEFAULT 'pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_completada TIMESTAMP NULL,
    FOREIGN KEY (asignado_a) REFERENCES personal_colaborador(id),
    FOREIGN KEY (requerimiento_id) REFERENCES requerimientos_mantenimiento(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reservas_zonas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zona_id INT NOT NULL,
    inquilino_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_inicio TIMESTAMP NULL,
    fecha_fin TIMESTAMP NULL,
    FOREIGN KEY (zona_id) REFERENCES zonas_comunes(id),
    FOREIGN KEY (inquilino_id) REFERENCES inquilinos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Skills de los avatares
CREATE TABLE habilidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area ENUM ('autocuidado', 'cuidado_de_tu_hogar', 'cuidado_del_otro', 'responsabilidad') NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    puntos INT NOT NULL DEFAULT 0,
    avatar_id INT NOT NULL,
    icono VARCHAR(255),
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (avatar_id) REFERENCES avatares(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Misiones
CREATE TABLE misiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    habilidad_a_mejorar TEXT,
    titulo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    puntos_a_ganar INT NOT NULL,
    nivel_requerido INT DEFAULT 1,
    icono VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mision_asignada (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mision_id INT NOT NULL,
    avatar_id INT NOT NULL,
    fecha_inicio TIMESTAMP NULL,
    fecha_completada TIMESTAMP NULL,
    estado ENUM('pendiente','en_progreso','completada','cancelada') DEFAULT 'pendiente',
    FOREIGN KEY (mision_id) REFERENCES misiones(id),
    FOREIGN KEY (avatar_id) REFERENCES avatares(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insignias
CREATE TABLE insignias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    clase ENUM('autocuidado', 'cuidado_de_tu_hogar', 'cuidado_del_otro', 'responsabilidad', 'especial') NOT NULL,
    descripcion TEXT,
    criterio TEXT,
    icono VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insignias obtenidas
CREATE TABLE insignias_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    avatar_id INT NOT NULL,
    insignia_id INT NOT NULL,
    fecha_obtenida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (avatar_id) REFERENCES avatares(id),
    FOREIGN KEY (insignia_id) REFERENCES insignias(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Recompensas
CREATE TABLE recompensas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    clase ENUM('autocuidado', 'cuidado_de_tu_hogar', 'cuidado_del_otro', 'responsabilidad', 'especial') NOT NULL,
    descripcion TEXT,
    puntos_requeridos INT NOT NULL,
    criterio TEXT,
    icono VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Recompensas obtenidas
CREATE TABLE recompensas_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    avatar_id INT NOT NULL,
    recompensa_id INT NOT NULL,
    fecha_obtenida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (avatar_id) REFERENCES avatares(id),
    FOREIGN KEY (recompensa_id) REFERENCES recompensas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notificaciones
CREATE TABLE notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    generada_por INT NOT NULL,
    fecha_para_enviar TIMESTAMP NULL,
    fecha_vencimiento TIMESTAMP NULL,
    horario_para_enviar TEXT NULL,
    se_repite BOOLEAN DEFAULT FALSE,
    intervalo_repeticion INT NULL,
    inquilino_id INT NOT NULL,
    mensaje TEXT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    estado ENUM('pendiente','en_progreso','completada','cancelada') DEFAULT 'pendiente',
    leido BOOLEAN DEFAULT FALSE,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    respondida BOOLEAN DEFAULT FALSE,
    respuesta TEXT NULL,
    fecha_respuesta TIMESTAMP NULL,
    img_respuesta VARCHAR(255) NULL,
    FOREIGN KEY (inquilino_id) REFERENCES inquilinos(id),
    FOREIGN KEY (generada_por) REFERENCES personal_colaborador(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Añadir relación entre personal colaborador y edificios
CREATE TABLE personal_edificio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personal_id INT NOT NULL,
    edificio_id INT NOT NULL,
    fecha_asignacion DATE NOT NULL,
    rol VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personal_id) REFERENCES personal_colaborador(id),
    FOREIGN KEY (edificio_id) REFERENCES edificios(id),
    UNIQUE KEY unique_personal_edificio (personal_id, edificio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Unificar tablas de pagos para mejor consistencia
CREATE TABLE pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_inquilino_id INT NULL,
    factura_proveedor_id INT NULL,
    factura_requerimiento_id INT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_pago DATE NOT NULL,
    metodo_pago VARCHAR(50) NOT NULL,
    referencia VARCHAR(100),
    img_comprobante VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (factura_inquilino_id) REFERENCES facturas_inquilinos(id),
    FOREIGN KEY (factura_proveedor_id) REFERENCES facturas_proveedores(id),
    FOREIGN KEY (factura_requerimiento_id) REFERENCES facturas_requerimientos(id),
    CHECK (
        (factura_inquilino_id IS NOT NULL AND factura_proveedor_id IS NULL AND factura_requerimiento_id IS NULL) OR
        (factura_inquilino_id IS NULL AND factura_proveedor_id IS NOT NULL AND factura_requerimiento_id IS NULL) OR
        (factura_inquilino_id IS NULL AND factura_proveedor_id IS NULL AND factura_requerimiento_id IS NOT NULL)
    ) -- Asegura que solo uno de los tres tipos de factura esté asociado
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS gamo_mensajes(
    id SERIAL PRIMARY KEY,
    inquilino_id INT NOT NULL,
    message_text TEXT,  -- contenido del mensaje
    direction ENUM('incoming', 'outgoing') NOT NULL default 'incoming',  -- 'incoming' o 'outgoing'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inquilino_id) REFERENCES inquilinos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- crear la tabla intermedia para la relación muchos a muchos entre inquilinos y unidades
CREATE TABLE inquilinos_unidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inquilino_id INT NOT NULL,
    unidad_id INT NOT NULL,
    fecha_ingreso DATE NOT NULL,
    fecha_salida DATE NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inquilino_id) REFERENCES inquilinos(id),
    FOREIGN KEY (unidad_id) REFERENCES unidades(id),
    UNIQUE KEY unique_inquilino_unidad_activo (inquilino_id, unidad_id, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Agregar índices para mejorar el rendimiento en consultas frecuentes
CREATE INDEX idx_incidencias_estado ON incidencias(estado);
CREATE INDEX idx_requerimientos_estado ON requerimientos_mantenimiento(estado);
CREATE INDEX idx_facturas_inquilinos_estado ON facturas_inquilinos(estado);
CREATE INDEX idx_facturas_proveedores_estado ON facturas_proveedores(estado);
CREATE INDEX idx_reservas_zonas_fechas ON reservas_zonas(fecha_inicio, fecha_fin);
CREATE INDEX idx_misiones_asignadas_estado ON mision_asignada(estado);
CREATE INDEX idx_notificaciones_leido ON notificaciones(leido);