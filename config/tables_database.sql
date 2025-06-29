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
    imagen_preview VARCHAR(255),
    activa TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de invitaciones (estructura completa actualizada)
CREATE TABLE IF NOT EXISTS invitaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plantilla_id INT NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    nombres_novios VARCHAR(150) NOT NULL,
    fecha_evento DATE NOT NULL,
    hora_evento TIME NOT NULL,
    
    -- Campos legacy para compatibilidad
    ubicacion VARCHAR(255),
    direccion_completa TEXT,
    
    -- Contenido principal
    historia TEXT,
    frase_historia TEXT,
    dresscode TEXT,
    texto_rsvp TEXT,
    mensaje_footer TEXT,
    firma_footer VARCHAR(100),
    
    -- Imágenes principales
    imagen_hero VARCHAR(255),
    imagen_dedicatoria VARCHAR(255),
    imagen_destacada VARCHAR(255),
    
    -- Música
    musica_url VARCHAR(255),
    musica_autoplay TINYINT(1) DEFAULT 1,
    
    -- Información familiar
    padres_novia VARCHAR(255),
    padres_novio VARCHAR(255),
    padrinos_novia VARCHAR(255),
    padrinos_novio VARCHAR(255),
    
    -- Configuraciones
    mostrar_contador TINYINT(1) DEFAULT 1,
    
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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
    imagen VARCHAR(255),
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

-- Tabla de preguntas frecuentes
CREATE TABLE IF NOT EXISTS invitacion_faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invitacion_id INT NOT NULL,
    pregunta TEXT NOT NULL,
    respuesta TEXT NOT NULL,
    orden INT DEFAULT 0,
    activa TINYINT(1) DEFAULT 1,
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

-- Tabla de respuestas RSVP (actualizada)
CREATE TABLE IF NOT EXISTS rsvps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invitacion_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(100),
    asistencia ENUM('si', 'no', 'tal_vez') NOT NULL,
    acompanantes INT DEFAULT 0,
    nombres_acompanantes TEXT,
    restricciones_alimentarias TEXT,
    mensaje TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_respuesta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invitacion_id) REFERENCES invitaciones(id) ON DELETE CASCADE
);

-- Tabla de configuraciones adicionales por invitación
CREATE TABLE IF NOT EXISTS invitacion_configuraciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invitacion_id INT NOT NULL,
    clave VARCHAR(100) NOT NULL,
    valor TEXT,
    tipo ENUM('texto', 'numero', 'booleano', 'json') DEFAULT 'texto',
    UNIQUE KEY unique_config (invitacion_id, clave),
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

-- Insertar plantilla base
INSERT INTO plantillas (id, nombre, descripcion, carpeta, archivo_principal, imagen_preview, activa) 
VALUES (1, 'Elegancia Clásica', 'Plantilla elegante con diseño clásico en tonos dorados y burgundy', 'plantilla-1', 'index.php', './plantillas/plantilla-1/preview.jpg', 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);