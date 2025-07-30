<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nueva Invitación</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./../css/crear.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-envelope-plus me-2"></i>
                Crear Nueva Invitación
            </a>
            <div class="navbar-nav ms-auto">
                <a href="./../index.php" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left me-1"></i>
                    Volver al Panel
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <form method="POST" enctype="multipart/form-data">
            <!-- Plantilla Base -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-layout-text-window-reverse me-2"></i>
                    Plantilla Base
                </h3>
                <div class="row">
                    <div class="col-md-6">
                        <label for="plantilla_id" class="form-label">Selecciona una plantilla</label>
                        <select name="plantilla_id" id="plantilla_id" class="form-select" required>
                            <option value="">-- Elegir plantilla --</option>
                            <?php foreach ($plantillas as $plantilla): ?>
                                <option value="<?= $plantilla['id'] ?>">
                                    <?= htmlspecialchars($plantilla['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Música de Fondo -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-music-note me-2"></i>
                    Música de Fondo
                </h3>
                <div class="row">
                    <div class="col-md-8">
                        <label for="musica_youtube_url" class="form-label">URL de YouTube</label>
                        <input type="url" id="musica_youtube_url" name="musica_youtube_url" 
                            class="form-control" placeholder="https://www.youtube.com/watch?v=dQw4w9WgXcQ">
                        <div class="form-text">Pega el enlace completo del video de YouTube que quieres usar como música de fondo</div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="musica_autoplay" name="musica_autoplay" value="1">
                            <label class="form-check-label" for="musica_autoplay">
                                Reproducir automáticamente
                            </label>
                            <div class="form-text">Nota: Muchos navegadores bloquean la reproducción automática</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="musica_volumen" class="form-label">Volumen inicial</label>
                        <input type="range" class="form-range" id="musica_volumen" name="musica_volumen" 
                            min="0" max="1" step="0.1" value="0.5">
                        <div class="form-text">0 = silencio, 1 = volumen máximo</div>
                    </div>
                </div>
            </div>

            <!-- Información Básica -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-info-circle me-2"></i>
                    Información Básica
                </h3>
                <div class="row">
                    <div class="col-md-6">
                        <label for="nombres_novios" class="form-label">Nombres de los Novios</label>
                        <input type="text" id="nombres_novios" name="nombres_novios" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="slug" class="form-label">URL (slug)</label>
                        <input type="text" id="slug" name="slug" class="form-control" required 
                            placeholder="ej: victoria-matthew-2025">
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="fecha_evento" class="form-label">Fecha del Evento</label>
                        <input type="date" id="fecha_evento" name="fecha_evento" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="hora_evento" class="form-label">Hora del Evento</label>
                        <input type="time" id="hora_evento" name="hora_evento" class="form-control" required>
                    </div>
                </div>
            </div>

            <!-- Ubicaciones del Evento -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-geo-alt me-2"></i>
                    Ubicaciones del Evento
                </h3>
                
                <!-- Ceremonia -->
                <div class="row">
                    <div class="col-12">
                        <h5 class="text-primary mb-3">Ceremonia</h5>
                    </div>
                    <div class="col-md-6">
                        <label for="ceremonia_lugar" class="form-label">Lugar de la Ceremonia</label>
                        <input type="text" id="ceremonia_lugar" name="ceremonia_lugar" 
                            class="form-control" placeholder="Iglesia San José">
                    </div>
                    <div class="col-md-6">
                        <label for="ceremonia_hora" class="form-label">Hora de la Ceremonia</label>
                        <input type="time" id="ceremonia_hora" name="ceremonia_hora" class="form-control">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="ceremonia_direccion" class="form-label">Dirección de la Ceremonia</label>
                        <input type="text" id="ceremonia_direccion" name="ceremonia_direccion" 
                            class="form-control" placeholder="Calle Principal 123">
                    </div>
                    <div class="col-md-6">
                        <label for="ceremonia_maps" class="form-label">URL de Google Maps (Ceremonia)</label>
                        <input type="url" id="ceremonia_maps" name="ceremonia_maps" 
                            class="form-control" placeholder="https://maps.google.com/?q=...">
                    </div>
                </div>
                
                <hr class="my-4">
                
                <!-- Evento/Recepción -->
                <div class="row">
                    <div class="col-12">
                        <h5 class="text-primary mb-3">Evento/Recepción</h5>
                    </div>
                    <div class="col-md-6">
                        <label for="evento_lugar" class="form-label">Lugar del Evento</label>
                        <input type="text" id="evento_lugar" name="evento_lugar" 
                            class="form-control" placeholder="Salón de Eventos Villa Jardín">
                    </div>
                    <div class="col-md-6">
                        <label for="evento_hora" class="form-label">Hora del Evento</label>
                        <input type="time" id="evento_hora" name="evento_hora" class="form-control">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="evento_direccion" class="form-label">Dirección del Evento</label>
                        <input type="text" id="evento_direccion" name="evento_direccion" 
                            class="form-control" placeholder="Avenida Central 456">
                    </div>
                    <div class="col-md-6">
                        <label for="evento_maps" class="form-label">URL de Google Maps (Evento)</label>
                        <input type="url" id="evento_maps" name="evento_maps" 
                            class="form-control" placeholder="https://maps.google.com/?q=...">
                    </div>
                </div>
            </div>

            <!-- Contenido Personalizado -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-card-text me-2"></i>
                    Contenido Personalizado
                </h3>
                
                <div class="mb-3">
                    <label for="historia" class="form-label">Historia de Amor</label>
                    <textarea id="historia" name="historia" rows="4" class="form-control" 
                        placeholder="Cuenta vuestra historia de amor..."></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="dresscode" class="form-label">Descripción del Código de Vestimenta</label>
                    <textarea id="dresscode" name="dresscode" rows="2" class="form-control" 
                        placeholder="Por favor, viste atuendo elegante..."></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="texto_rsvp" class="form-label">Texto para RSVP</label>
                    <input type="text" id="texto_rsvp" name="texto_rsvp" class="form-control" 
                        placeholder="Confirma tu asistencia antes del...">
                </div>
            </div>

            <!-- Información Familiar -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-people me-2"></i>
                    Información Familiar
                </h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <label for="padres_novia" class="form-label">Padres de la Novia</label>
                        <input type="text" id="padres_novia" name="padres_novia" class="form-control" 
                            placeholder="Nombres de los padres de la novia">
                    </div>
                    <div class="col-md-6">
                        <label for="padres_novio" class="form-label">Padres del Novio</label>
                        <input type="text" id="padres_novio" name="padres_novio" class="form-control" 
                            placeholder="Nombres de los padres del novio">
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="padrinos_novia" class="form-label">Padrinos de la Novia</label>
                        <input type="text" id="padrinos_novia" name="padrinos_novia" class="form-control" 
                            placeholder="Nombres de los padrinos de la novia">
                    </div>
                    <div class="col-md-6">
                        <label for="padrinos_novio" class="form-label">Padrinos del Novio</label>
                        <input type="text" id="padrinos_novio" name="padrinos_novio" class="form-control" placeholder="Nombres de los padrinos del novio">
                   </div>
               </div>
           </div>

           <!-- Mensajes Personalizados -->
           <div class="form-section">
               <h3 class="section-title">
                   <i class="bi bi-chat-heart me-2"></i>
                   Mensajes Personalizados
               </h3>
               
               <div class="mb-3">
                   <label for="mensaje_footer" class="form-label">Mensaje del Footer</label>
                   <textarea id="mensaje_footer" name="mensaje_footer" rows="2" class="form-control" 
                       placeholder="El amor es la fuerza más poderosa del mundo..."></textarea>
               </div>
               
               <div class="mb-3">
                   <label for="firma_footer" class="form-label">Firma del Footer</label>
                   <input type="text" id="firma_footer" name="firma_footer" class="form-control" 
                       placeholder="Con amor, Victoria & Matthew">
               </div>
           </div>

           <!-- Imágenes -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-images me-2"></i>
                    Imágenes
                </h3>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="imagen_hero" class="form-label">Imagen Hero</label>
                            <div class="input-group">
                                <input type="file" name="imagen_hero" id="imagen_hero" accept="image/*" 
                                    class="form-control" onchange="previewImage(this, 'hero-preview')">
                                <label class="input-group-text" for="imagen_hero">
                                    <i class="bi bi-upload"></i>
                                </label>
                            </div>
                            <div id="hero-preview" class="mt-2">
                                <img id="hero-preview-img" src="#" alt="Preview" class="img-thumbnail d-none" style="max-width: 200px;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="imagen_dedicatoria" class="form-label">Imagen Dedicatoria</label>
                            <div class="input-group">
                                <input type="file" name="imagen_dedicatoria" id="imagen_dedicatoria" accept="image/*" 
                                    class="form-control" onchange="previewImage(this, 'dedicatoria-preview')">
                                <label class="input-group-text" for="imagen_dedicatoria">
                                    <i class="bi bi-upload"></i>
                                </label>
                            </div>
                            <div id="dedicatoria-preview" class="mt-2">
                                <img id="dedicatoria-preview-img" src="#" alt="Preview" class="img-thumbnail d-none" style="max-width: 200px;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="imagen_destacada" class="form-label">Imagen Destacada</label>
                            <div class="input-group">
                                <input type="file" name="imagen_destacada" id="imagen_destacada" accept="image/*" 
                                    class="form-control" onchange="previewImage(this, 'destacada-preview')">
                                <label class="input-group-text" for="imagen_destacada">
                                    <i class="bi bi-upload"></i>
                                </label>
                            </div>
                            <div id="destacada-preview" class="mt-2">
                                <img id="destacada-preview-img" src="#" alt="Preview" class="img-thumbnail d-none" style="max-width: 200px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Galería -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-collection me-2"></i>
                    Galería de Imágenes
                </h3>
                
                <div class="mb-3">
                    <label for="imagenes_galeria" class="form-label">Imágenes de Galería (puedes seleccionar varias)</label>
                    <div class="input-group">
                        <input type="file" name="imagenes_galeria[]" id="imagenes_galeria" accept="image/*" 
                            multiple class="form-control" onchange="previewGallery(this)">
                        <label class="input-group-text" for="imagenes_galeria">
                            <i class="bi bi-images"></i> Seleccionar
                        </label>
                    </div>
                    <div class="form-text">Puedes seleccionar múltiples imágenes manteniendo presionado Ctrl (Windows) o Cmd (Mac)</div>
                </div>
                <div id="gallery-preview" class="row"></div>
            </div>

           <!-- Cronograma -->
           <div class="form-section">
               <h3 class="section-title">
                   <i class="bi bi-clock me-2"></i>
                   Cronograma del Evento
               </h3>
               
               <div id="cronograma-container">
                   <div class="cronograma-item">
                       <div class="row">
                           <div class="col-md-3">
                               <label class="form-label">Hora</label>
                               <input type="time" name="cronograma_hora[]" class="form-control">
                           </div>
                           <div class="col-md-3">
                               <label class="form-label">Evento</label>
                               <input type="text" name="cronograma_evento[]" class="form-control" 
                                   placeholder="Ceremonia">
                           </div>
                           <div class="col-md-4">
                               <label class="form-label">Descripción</label>
                               <input type="text" name="cronograma_descripcion[]" class="form-control" 
                                   placeholder="Descripción del evento">
                           </div>
                           <div class="col-md-2">
                               <label class="form-label">Icono</label>
                               <select name="cronograma_icono[]" class="form-select">
                                   <option value="anillos">Anillos</option>
                                   <option value="cena">Cena</option>
                                   <option value="fiesta">Fiesta</option>
                                   <option value="luna">Luna</option>
                               </select>
                           </div>
                       </div>
                   </div>
               </div>
               <button type="button" onclick="agregarCronograma()" class="btn btn-outline-primary mt-2">
                   <i class="bi bi-plus-circle me-1"></i>
                   Agregar Evento
               </button>
           </div>

           <!-- Dresscode -->
           <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-person-check me-2"></i>
                    Código de Vestimenta
                </h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="imagen_dresscode_hombres" class="form-label">Imagen Dresscode Hombres</label>
                            <div class="input-group">
                                <input type="file" name="imagen_dresscode_hombres" id="imagen_dresscode_hombres" 
                                    accept="image/*" class="form-control" onchange="previewImage(this, 'dresscode-hombres-preview')">
                                <label class="input-group-text" for="imagen_dresscode_hombres">
                                    <i class="bi bi-person-fill"></i>
                                </label>
                            </div>
                            <div id="dresscode-hombres-preview" class="mt-2">
                                <img id="dresscode-hombres-preview-img" src="#" alt="Preview" class="img-thumbnail d-none" style="max-width: 150px;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="imagen_dresscode_mujeres" class="form-label">Imagen Dresscode Mujeres</label>
                            <div class="input-group">
                                <input type="file" name="imagen_dresscode_mujeres" id="imagen_dresscode_mujeres" 
                                    accept="image/*" class="form-control" onchange="previewImage(this, 'dresscode-mujeres-preview')">
                                <label class="input-group-text" for="imagen_dresscode_mujeres">
                                    <i class="bi bi-person-dress"></i>
                                </label>
                            </div>
                            <div id="dresscode-mujeres-preview" class="mt-2">
                                <img id="dresscode-mujeres-preview-img" src="#" alt="Preview" class="img-thumbnail d-none" style="max-width: 150px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

           <!-- Preguntas Frecuentes -->
           <div class="form-section">
               <h3 class="section-title">
                   <i class="bi bi-question-circle me-2"></i>
                   Preguntas Frecuentes
               </h3>
               
               <div id="faq-container">
                   <div class="faq-item">
                       <div class="row">
                           <div class="col-md-6">
                               <label class="form-label">Pregunta</label>
                               <input type="text" name="faq_pregunta[]" class="form-control" 
                                   placeholder="¿Habrá servicio de transporte?">
                           </div>
                           <div class="col-md-6">
                               <label class="form-label">Respuesta</label>
                               <textarea name="faq_respuesta[]" rows="2" class="form-control" 
                                   placeholder="Sí, habrá servicio de transporte desde..."></textarea>
                           </div>
                       </div>
                   </div>
               </div>
               <button type="button" onclick="agregarFAQ()" class="btn btn-outline-primary mt-2">
                   <i class="bi bi-plus-circle me-1"></i>
                   Agregar FAQ
               </button>
           </div>

           <!-- Botones de acción -->
           <div class="form-section">
               <div class="d-flex gap-2 justify-content-end">
                   <a href="./../index.php" class="btn btn-outline-secondary btn-lg">
                       <i class="bi bi-x-circle me-1"></i>
                       Cancelar
                   </a>
                   <button type="submit" class="btn btn-primary btn-lg">
                       <i class="bi bi-check-circle me-1"></i>
                       Crear Invitación
                   </button>
               </div>
           </div>
       </form>
   </div>

   <!-- Bootstrap 5 JS -->
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
   
   <script>
       // Función para previsualizar imágenes individuales
       function previewImage(input, previewId) {
           const preview = document.getElementById(previewId + '-img');
           if (input.files && input.files[0]) {
               const reader = new FileReader();
               reader.onload = function(e) {
                   preview.src = e.target.result;
                   preview.classList.remove('d-none');
               }
               reader.readAsDataURL(input.files[0]);
           }
       }

       // Función para previsualizar galería de imágenes
       function previewGallery(input) {
           const preview = document.getElementById('gallery-preview');
           preview.innerHTML = '';
           
           if (input.files) {
               Array.from(input.files).forEach((file, index) => {
                   if (file.type.startsWith('image/')) {
                       const reader = new FileReader();
                       reader.onload = function(e) {
                           const col = document.createElement('div');
                           col.className = 'col-md-3 mb-3';
                           col.innerHTML = `
                               <div class="card">
                                   <img src="${e.target.result}" class="card-img-top" style="height: 150px; object-fit: cover;">
                                   <div class="card-body p-2">
                                       <small class="text-muted">${file.name}</small>
                                   </div>
                               </div>
                           `;
                           preview.appendChild(col);
                       }
                       reader.readAsDataURL(file);
                   }
               });
           }
       }

       // Función para agregar elementos al cronograma
       function agregarCronograma() {
           const container = document.getElementById('cronograma-container');
           const newItem = document.createElement('div');
           newItem.className = 'cronograma-item';
           newItem.innerHTML = `
               <div class="row">
                   <div class="col-md-3">
                       <label class="form-label">Hora</label>
                       <input type="time" name="cronograma_hora[]" class="form-control">
                   </div>
                   <div class="col-md-3">
                       <label class="form-label">Evento</label>
                       <input type="text" name="cronograma_evento[]" class="form-control" placeholder="Evento">
                   </div>
                   <div class="col-md-4">
                       <label class="form-label">Descripción</label>
                       <input type="text" name="cronograma_descripcion[]" class="form-control" placeholder="Descripción">
                   </div>
                   <div class="col-md-1">
                       <label class="form-label">Icono</label>
                       <select name="cronograma_icono[]" class="form-select">
                           <option value="anillos">Anillos</option>
                           <option value="cena">Cena</option>
                           <option value="fiesta">Fiesta</option>
                           <option value="luna">Luna</option>
                       </select>
                   </div>
                   <div class="col-md-1">
                       <label class="form-label">&nbsp;</label>
                       <button type="button" onclick="eliminarCronograma(this)" class="btn btn-outline-danger btn-sm d-block">
                           <i class="bi bi-trash"></i>
                       </button>
                   </div>
               </div>
           `;
           container.appendChild(newItem);
       }

       // Función para eliminar elementos del cronograma
       function eliminarCronograma(button) {
           button.closest('.cronograma-item').remove();
       }

       // Función para agregar FAQs
       function agregarFAQ() {
           const container = document.getElementById('faq-container');
           const newItem = document.createElement('div');
           newItem.className = 'faq-item';
           newItem.innerHTML = `
               <div class="row">
                   <div class="col-md-5">
                       <label class="form-label">Pregunta</label>
                       <input type="text" name="faq_pregunta[]" class="form-control" placeholder="Pregunta">
                   </div>
                   <div class="col-md-6">
                       <label class="form-label">Respuesta</label>
                       <textarea name="faq_respuesta[]" rows="2" class="form-control" placeholder="Respuesta"></textarea>
                   </div>
                   <div class="col-md-1">
                       <label class="form-label">&nbsp;</label>
                       <button type="button" onclick="eliminarFAQ(this)" class="btn btn-outline-danger btn-sm d-block">
                           <i class="bi bi-trash"></i>
                       </button>
                   </div>
               </div>
           `;
           container.appendChild(newItem);
       }

       // Función para eliminar FAQs
       function eliminarFAQ(button) {
           button.closest('.faq-item').remove();
       }

       // Generar slug automáticamente basado en nombres de novios
       document.getElementById('nombres_novios').addEventListener('input', function(e) {
           const nombres = e.target.value;
           const slug = nombres.toLowerCase()
               .replace(/[^a-z0-9\s-]/g, '') // Remover caracteres especiales
               .replace(/\s+/g, '-') // Reemplazar espacios con guiones
               .replace(/-+/g, '-'); // Evitar múltiples guiones seguidos
           document.getElementById('slug').value = slug;
       });
   </script>
</body>
</html>