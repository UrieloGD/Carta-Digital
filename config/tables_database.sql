-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS carta_digital CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE carta_digital;

-- Tabla de plantillas base
CREATE TABLE IF NOT EXISTS plantillas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    carpeta VARCHAR(100) NOT NULL,
    archivo_principal VARCHAR(100) NOT NULL,
    invitacion_ejemplo_id INT NULL,
    imagen_preview VARCHAR(255),
    activa TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invitacion_ejemplo_id) REFERENCES invitaciones(id) ON DELETE SET NULL;
);

-- Tabla de invitaciones (estructura completa actualizada)
CREATE TABLE IF NOT EXISTS invitaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plantilla_id INT NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    nombres_novios VARCHAR(150) NOT NULL,
    fecha_evento DATE NOT NULL,
    fecha_limite_rsvp DATE NULL,
    hora_evento TIME NOT NULL,
    
    -- Campos legacy para compatibilidad
    ubicacion VARCHAR(255),
    direccion_completa TEXT,
    
    -- Contenido principal
    historia TEXT,
    frase_historia TEXT,
    dresscode TEXT,
    texto_rsvp TEXT,
    tipo_rsvp ENUM('digital', 'whatsapp') DEFAULT 'whatsapp',
    mensaje_footer TEXT,
    firma_footer VARCHAR(100),
    
    -- Imágenes principales
    imagen_hero VARCHAR(255),
    imagen_dedicatoria VARCHAR(255),
    imagen_destacada VARCHAR(255),
    
    -- Información familiar
    padres_novia VARCHAR(255),
    padres_novio VARCHAR(255),
    padrinos_novia VARCHAR(255),
    padrinos_novio VARCHAR(255),
    
    -- Configuraciones
    mostrar_contador TINYINT(1) DEFAULT 1,
    tipo_contador ENUM('completo', 'simple') DEFAULT 'completo', 
    mostrar_cronograma TINYINT(1) DEFAULT 1,
    mostrar_fecha_limite_rsvp TINYINT(1) DEFAULT 1,
    mostrar_solo_adultos TINYINT(1) DEFAULT 1,
    mostrar_solo_adultos TINYINT(1) DEFAULT 1,
    mostrar_compartir TINYINT(1) DEFAULT 1,

    -- Musica
    musica_youtube_url VARCHAR(255),
    musica_autoplay TINYINT(1),
    musica_volumen decimal(3.2),

    -- Whatsapp
    whatsapp_confirmacion VARCHAR(20) DEFAULT NULL,
    
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activa BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (plantilla_id) REFERENCES plantillas(id) ON DELETE CASCADE
);

-- Tabla de ubicaciones (ceremonia y evento)
CREATE TABLE IF NOT EXISTS invitacion_ubicaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invitacion_id INT NOT NULL,
    tipo ENUM('ceremonia', 'evento') NOT NULL,
    nombre_lugar VARCHAR(255) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    hora_inicio TIME,
    hora_fin TIME,
    google_maps_url TEXT,
    imagen VARCHAR(500),
    descripcion TEXT,
    orden INT DEFAULT 0,
    FOREIGN KEY (invitacion_id) REFERENCES invitaciones(id) ON DELETE CASCADE
);

-- Tabla de imágenes para dresscode
CREATE TABLE IF NOT EXISTS invitacion_dresscode (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invitacion_id INT NOT NULL,
    hombres VARCHAR(255),
    mujeres VARCHAR(255),
    descripcion_hombres TEXT,
    descripcion_mujeres TEXT,
    FOREIGN KEY (invitacion_id) REFERENCES invitaciones(id) ON DELETE CASCADE
);

-- Tabla de galería de imágenes
CREATE TABLE IF NOT EXISTS invitacion_galeria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invitacion_id INT NOT NULL,
    ruta VARCHAR(255) NOT NULL,
    descripcion VARCHAR(255),
    orden INT DEFAULT 0,
    activa TINYINT(1) DEFAULT 1,
    FOREIGN KEY (invitacion_id) REFERENCES invitaciones(id) ON DELETE CASCADE
);

-- Tabla de cronograma
CREATE TABLE IF NOT EXISTS invitacion_cronograma (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invitacion_id INT NOT NULL,
    hora TIME NOT NULL,
    evento VARCHAR(150) NOT NULL,
    descripcion TEXT,
    icono VARCHAR(50),
    ubicacion VARCHAR(255),
    orden INT DEFAULT 0,
    FOREIGN KEY (invitacion_id) REFERENCES invitaciones(id) ON DELETE CASCADE
);

-- Tabla de mesa de regalos
CREATE TABLE IF NOT EXISTS invitacion_mesa_regalos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invitacion_id INT NOT NULL,
    tienda ENUM('liverpool', 'amazon', 'sears', 'palacio_hierro', 'walmart', 'costco', 'coppel', 'elektra', 'otro') NOT NULL,
    nombre_tienda VARCHAR(100),
    url TEXT,
    numero_evento VARCHAR(100),
    codigo_evento VARCHAR(100),
    descripcion TEXT,
    icono VARCHAR(255),
    orden INT DEFAULT 0,
    activa TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invitacion_id) REFERENCES invitaciones(id) ON DELETE CASCADE
);

-- Tabla de estadísticas y tracking
CREATE TABLE IF NOT EXISTS invitacion_estadisticas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invitacion_id INT NOT NULL,
    tipo_evento ENUM('visita', 'rsvp', 'compartir', 'galeria_click', 'ubicacion_click') NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    datos_adicionales JSON,
    fecha_evento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invitacion_id) REFERENCES invitaciones(id) ON DELETE CASCADE
);

-- Tabla de clientes login
CREATE TABLE IF NOT EXISTS clientes_login (
  id int NOT NULL AUTO_INCREMENT,
  slug varchar(255) NOT NULL,
  usuario varchar(255) NOT NULL,
  email varchar(255) NULL,
  telefono varchar(20) NULL,
  recibe_notificaciones TINYINT DEFAULT 1,
  notificar_email TINYINT DEFAULT 1,
  notificar_whatsapp TINYINT DEFAULT 0,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  password varchar(255) NOT NULL,
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY slug (slug),
  UNIQUE KEY slug_unique (slug)
);

CREATE TABLE IF NOT EXISTS invitados_grupos (
    id_grupo INT AUTO_INCREMENT PRIMARY KEY,
    slug_invitacion VARCHAR(100) NOT NULL,
    nombre_grupo VARCHAR(255) NOT NULL,
    boletos_asignados INT NOT NULL DEFAULT 1,
    token_unico VARCHAR(64) NOT NULL UNIQUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug_invitacion (slug_invitacion),
    INDEX idx_token_unico (token_unico)
);

CREATE TABLE IF NOT EXISTS rsvp_respuestas (
    id_respuesta INT AUTO_INCREMENT PRIMARY KEY,
    id_grupo INT NOT NULL,
    nombre_invitado_principal VARCHAR(255),
    estado ENUM('pendiente','aceptado','rechazado') DEFAULT 'pendiente',
    boletos_confirmados INT DEFAULT 0,
    nombres_acompanantes TEXT,
    comentarios TEXT,
    fecha_respuesta TIMESTAMP NULL,
    FOREIGN KEY (id_grupo) REFERENCES invitados_grupos(id_grupo) ON DELETE CASCADE
);

-- Insertar plantilla base
INSERT INTO plantillas (id, nombre, descripcion, carpeta, archivo_principal, imagen_preview, activa) 
VALUES (1, 'Tinta', 'Plantilla elegante con diseño clásico en tonos tintos y arena', 'plantilla-1', 'plantilla-1.php', 'img/preview.png', 1)

ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);