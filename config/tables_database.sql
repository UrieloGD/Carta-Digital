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

-- Tabla de invitaciones
CREATE TABLE IF NOT EXISTS invitaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plantilla_id INT NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    nombres_novios VARCHAR(150) NOT NULL,
    fecha_evento DATE NOT NULL,
    hora_evento TIME NOT NULL,
    ubicacion VARCHAR(255),
    direccion_completa TEXT,
    coordenadas VARCHAR(100),
    historia TEXT,
    dresscode TEXT,
    texto_rsvp TEXT,
    mensaje_footer TEXT,
    firma_footer VARCHAR(100),
    imagen_hero VARCHAR(255),
    imagen_dedicatoria VARCHAR(255),
    imagen_destacada VARCHAR(255),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plantilla_id) REFERENCES plantillas(id) ON DELETE CASCADE
);

-- Tabla de imágenes para dresscode
CREATE TABLE IF NOT EXISTS invitacion_dresscode (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invitacion_id INT NOT NULL,
    hombres VARCHAR(255),
    mujeres VARCHAR(255),
    FOREIGN KEY (invitacion_id) REFERENCES invitaciones(id) ON DELETE CASCADE
);

-- Tabla de galería de imágenes
CREATE TABLE IF NOT EXISTS invitacion_galeria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invitacion_id INT NOT NULL,
    ruta VARCHAR(255) NOT NULL,
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
    FOREIGN KEY (invitacion_id) REFERENCES invitaciones(id) ON DELETE CASCADE
);

-- Tabla de preguntas frecuentes
CREATE TABLE IF NOT EXISTS invitacion_faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invitacion_id INT NOT NULL,
    pregunta TEXT NOT NULL,
    respuesta TEXT NOT NULL,
    FOREIGN KEY (invitacion_id) REFERENCES invitaciones(id) ON DELETE CASCADE
);

-- Tabla de respuestas RSVP
CREATE TABLE IF NOT EXISTS rsvps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invitacion_id INT NOT NULL,
    nombre VARCHAR(100),
    asistencia ENUM('sí', 'no', 'tal vez'),
    mensaje TEXT,
    fecha_respuesta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invitacion_id) REFERENCES invitaciones(id) ON DELETE CASCADE
);
