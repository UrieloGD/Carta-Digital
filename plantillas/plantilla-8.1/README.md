/**
 * PLANTILLA 8 - K-pop Demon Hunters x Raya
 * 
 * INSTRUCCIONES DE USO Y CONFIGURACIÓN
 * =====================================
 * 
 * Características principales:
 * - Diseño oscuro y vibrante con 2 tonos principales (Morado Neón y Aqua)
 * - Imágenes de personajes destacadas pero no invasivas
 * - Animaciones vibrantess y fluidas
 * - Totalmente responsivo (Mobile, Tablet, Desktop)
 * - Efectos neon con glow y brillo
 * 
 * =====================================
 * COLORES PRINCIPALES
 * =====================================
 * 
 * Color Primario (Fondo): #0f1421 (Azul oscuro profundo)
 * Color Secundario: #1a1f3a (Azul oscuro)
 * 
 * Morado Neón: #9d4edd (Principal K-pop)
 * Aqua/Cian: #00d4ff (Raya)
 * Verde Cian: #06ffa5 (Detalles vibrantes)
 * 
 * =====================================
 * ESTRUCTURA DE CARPETAS
 * =====================================
 * 
 * /plantilla-8
 * ├── invitacion-8.php       Main file - Renderiza la invitación
 * ├── css/
 * │   ├── global.css         Variables CSS y estilos globales
 * │   ├── hero.css           Sección hero con animaciones
 * │   ├── bienvenida.css     Sección de bienvenida
 * │   ├── historia.css       Sección de historia
 * │   ├── galeria.css        Galería de personajes
 * │   ├── contador.css       Contador regresivo neon
 * │   ├── cronograma.css     Timeline de eventos
 * │   ├── ubicaciones.css    Maps y ubicaciones
 * │   ├── dresscode.css      Sección dress code
 * │   ├── rsvp.css           Formulario RSVP
 * │   ├── mesa-regalos.css   Sección de regalos
 * │   ├── footer.css         Footer
 * │   ├── music-player.css   Reproductor de música
 * │   └── transition-image.css   Efectos de transición
 * ├── js/
 * │   ├── main.js            Funcionalidades generales
 * │   ├── contador.js        Lógica del contador
 * │   ├── galeria.js         Interactividad galería
 * │   └── music-player.js    Control de música
 * ├── img/
 * │   ├── hero.jpg           Imagen principal (recomendado 2000x1080)
 * │   ├── dedicatoria.jpg    Imagen de dedicatoria (recomendado 400x533)
 * │   ├── destacada.jpg      Imagen destacada (recomendado 400x600)
 * │   └── galeria/           Carpeta de galería
 * │       ├── foto1.jpg
 * │       ├── foto2.jpg
 * │       └── ... (máximo 6-8 imágenes)
 * └── api/                   (APIs específicas si es necesario)
 * 
 * =====================================
 * IMÁGENES RECOMENDADAS
 * =====================================
 * 
 * HERO (Portada):
 * - Tamaño: 2000x1080px (16:9)
 * - Peso máximo: 500KB
 * - Formato: JPG optimizado
 * - Contenido: Imagen general del evento con personajes
 * 
 * DEDICATORIA:
 * - Tamaño: 400x533px (3:4)
 * - Peso máximo: 150KB
 * - Formato: JPG optimizado
 * - Contenido: Retrato del personaje principal
 * 
 * GALERÍA:
 * - Tamaño: 1000x1333px (3:4) mínimo
 * - Peso máximo: 200KB por imagen
 * - Formato: JPG optimizado
 * - Cantidad: 4-8 imágenes
 * - Contenido: Diferentes personajes o momentos
 * 
 * =====================================
 * CÓMO USAR LA PLANTILLA
 * =====================================
 * 
 * 1. CREAR INVITACIÓN EN BASE DE DATOS:
 *    - Registrar en tabla 'invitaciones' con plantilla_id = 8
 *    - Asignar un 'slug' único
 *    - Llenar datos: nombres, fecha, ubicación, etc.
 * 
 * 2. SUBIR IMÁGENES:
 *    - Ir a admin panel y subir imágenes
 *    - Asignar a campos: imagen_hero, imagen_dedicatoria
 *    - Subir galería (máximo 8 imágenes)
 * 
 * 3. CONFIGURAR SECCIONES:
 *    - Historia: Texto personalizado
 *    - Ubicaciones: Este y coordenadas para mapa
 *    - Cronograma: Horarios y eventos
 * 
 * 4. PERSONALIZAR COLORS (OPCIONAL):
 *    - Editar :root en /css/global.css
 *    - Cambiar variables --accent-neon, --accent-aqua
 *    - Guardar cambios
 * 
 * =====================================
 * ANIMACIONES INCLUIDAS
 * =====================================
 * 
 * - vibrantZoom: Zoom sutil en hero
 * - neonPulse: Efecto de pulsación en color neón
 * - aquaGlow: Brillo aqua en textos
 * - floatingParticles: Partículas flotantes
 * - characterFloat: Flotación en tarjetas
 * - characterGlow: Glow en cards de personajes
 * - borderNeon: Animación de bordes neón
 * - shimmer: Efecto de destello
 * 
 * Todas las animaciones son responsivas y no afectan performance.
 * 
 * =====================================
 * FUNCIONALIDADES JAVASCRIPT
 * =====================================
 * 
 * 1. CONTADOR REGRESIVO:
 *    - Actualiza cada segundo
 *    - Muestra días, horas, minutos, segundos
 *    - Efecto de animación al cambiar números
 * 
 * 2. GALERÍA INTERACTIVA:
 *    - Hover con efectos visuales
 *    - Click para ampliar imagen
 *    - Modal responsivo
 * 
 * 3. REPRODUCTOR DE MÚSICA:
 *    - Botón flotante en esquina
 *    - Soporte para agregar música personalizada
 *    - Controles play/pause
 * 
 * 4. SMOOTH SCROLL:
 *    - Links con scroll suave
 *    - Animaciones en intersección
 * 
 * =====================================
 * OPTIMIZACIONES
 * =====================================
 * 
 * ✓ Lazy loading de imágenes
 * ✓ CSS Grid y Flexbox modernos
 * ✓ Variables CSS para fácil personalización
 * ✓ Animaciones GPU-aceleradas
 * ✓ Media queries para todos los dispositivos
 * ✓ Fonts de Google optimizadas
 * ✓ Imágenes WebP (si el navegador lo soporta)
 * 
 * =====================================
 * COMPATIBILIDAD
 * =====================================
 * 
 * Navegadores:
 * ✓ Chrome/Chromium (últimas versiones)
 * ✓ Firefox (últimas versiones)
 * ✓ Safari (iOS 13+, macOS 10.15+)
 * ✓ Edge (14+)
 * 
 * Dispositivos:
 * ✓ Desktop (1920x1080 y más)
 * ✓ Tablet (768px - 1024px)
 * ✓ Mobile (320px - 480px)
 * 
 * =====================================
 * PERSONALIZACIÓN AVANZADA
 * =====================================
 * 
 * Para cambiar colores globalmente:
 * 
 * Editar en /css/global.css:
 * ```
 * --accent-neon: #9d4edd;     // Morado
 * --accent-aqua: #00d4ff;     // Aqua
 * --bg-primary: #0f1421;      // Fondo
 * ```
 * 
 * Para cambiar fuentes:
 * Cambiar imports en <head> de invitacion-8.php
 * Actualizar --font-sans y --font-bold en global.css
 * 
 * =====================================
 * SOPORTE Y MANTENIMIENTO
 * =====================================
 * 
 * Archivos a revisar regularmente:
 * - /css/global.css (Actualizar variables si es necesario)
 * - /js/main.js (Agregar funcionalidades nuevas)
 * - invitacion-8.php (Cambios en estructura HTML)
 * 
 * =====================================
 */
