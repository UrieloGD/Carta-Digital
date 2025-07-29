// musica.js - Funcionalidad de música para formularios de invitaciones

let youtubePlayer = null;
let isPlayerReady = false;

// Cargar API de YouTube
function loadYouTubeAPI() {
    if (window.YT && window.YT.Player) {
        return Promise.resolve();
    }
    
    return new Promise((resolve) => {
        const tag = document.createElement('script');
        tag.src = "https://www.youtube.com/iframe_api";
        const firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        
        window.onYouTubeIframeAPIReady = resolve;
    });
}

// Extraer ID de video de YouTube
function extractYouTubeVideoId(url) {
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
    const match = url.match(regExp);
    return (match && match[2].length === 11) ? match[2] : null;
}

// Inicializar reproductor (oculto, solo audio)
function initializeYouTubePlayer(videoId) {
    return loadYouTubeAPI().then(() => {
        return new Promise((resolve) => {
            if (youtubePlayer) {
                youtubePlayer.destroy();
            }
            
            youtubePlayer = new YT.Player('youtubePlayer', {
                height: '1',  // Altura mínima
                width: '1',   // Ancho mínimo
                videoId: videoId,
                playerVars: {
                    'playsinline': 1,
                    'controls': 0,        // Sin controles del video
                    'modestbranding': 1,
                    'rel': 0,
                    'showinfo': 0,        // Sin información del video
                    'fs': 0,              // Sin pantalla completa
                    'cc_load_policy': 0,  // Sin subtítulos
                    'iv_load_policy': 3,  // Sin anotaciones
                    'autohide': 1
                },
                events: {
                    'onReady': function(event) {
                        isPlayerReady = true;
                        const volumen = document.getElementById('musica_volumen').value;
                        event.target.setVolume(volumen * 100);
                        
                        // Ocultar completamente el iframe del video
                        const iframe = document.querySelector('#youtubePlayer iframe');
                        if (iframe) {
                            iframe.style.display = 'none';
                        }
                        
                        resolve(event.target);
                    },
                    'onError': function(event) {
                        console.error('Error del reproductor YouTube:', event.data);
                        document.getElementById('musicPreview').innerHTML = '<p style="color: red;">Error al cargar el video. Verifica que la URL sea correcta.</p>';
                    }
                }
            });
        });
    });
}

// Inicializar funcionalidad de música
function initializeMusicControls() {
    const musicUrlInput = document.getElementById('musica_youtube_url');
    const musicPreview = document.getElementById('musicPreview');
    const previewPlay = document.getElementById('previewPlay');
    const previewPause = document.getElementById('previewPause');
    const previewStop = document.getElementById('previewStop');
    const volumeControl = document.getElementById('musica_volumen');
    
    if (!musicUrlInput) return; // Si no existe el campo, no hacer nada
    
    // Mostrar/ocultar preview y cargar reproductor
    musicUrlInput.addEventListener('input', function() {
        const url = this.value.trim();
        if (url) {
            const videoId = extractYouTubeVideoId(url);
            if (videoId) {
                // Mostrar solo los controles, no el video
                musicPreview.innerHTML = `
                    <h4>Vista previa de audio:</h4>
                    <div class="audio-player-container">
                        <div id="youtubePlayer" style="display: none;"></div>
                        <div class="audio-controls">
                            <div class="audio-info">
                                <span class="audio-icon">🎵</span>
                                <span class="audio-status" id="audioStatus">Listo para reproducir</span>
                            </div>
                            <div class="player-controls" style="margin-top: 10px;">
                                <button type="button" id="previewPlay" class="btn btn-secondary">▶️ Reproducir</button>
                                <button type="button" id="previewPause" class="btn btn-secondary">⏸️ Pausar</button>
                                <button type="button" id="previewStop" class="btn btn-secondary">⏹️ Detener</button>
                            </div>
                        </div>
                    </div>
                `;
                musicPreview.style.display = 'block';
                
                // Re-asignar event listeners después de recrear el HTML
                setupPlayerControls();
                
                initializeYouTubePlayer(videoId).catch((error) => {
                    console.error('Error al inicializar el reproductor:', error);
                });
            } else {
                musicPreview.style.display = 'block';
                musicPreview.innerHTML = '<p style="color: orange;">URL de YouTube no válida</p>';
            }
        } else {
            musicPreview.style.display = 'none';
        }
    });
    
    // Cargar reproductor si ya hay URL (para el formulario de editar)
    if (musicUrlInput.value.trim()) {
        const videoId = extractYouTubeVideoId(musicUrlInput.value.trim());
        if (videoId) {
            musicPreview.innerHTML = `
                <h4>Vista previa de audio:</h4>
                <div class="audio-player-container">
                    <div id="youtubePlayer" style="display: none;"></div>
                    <div class="audio-controls">
                        <div class="audio-info">
                            <span class="audio-icon">🎵</span>
                            <span class="audio-status" id="audioStatus">Cargando...</span>
                        </div>
                        <div class="player-controls" style="margin-top: 10px;">
                            <button type="button" id="previewPlay" class="btn btn-secondary">▶️ Reproducir</button>
                            <button type="button" id="previewPause" class="btn btn-secondary">⏸️ Pausar</button>
                            <button type="button" id="previewStop" class="btn btn-secondary">⏹️ Detener</button>
                        </div>
                    </div>
                </div>
            `;
            musicPreview.style.display = 'block';
            setupPlayerControls();
            initializeYouTubePlayer(videoId);
        }
    }
    
    // Configurar controles iniciales
    setupPlayerControls();
}

// Función separada para configurar los controles del reproductor
function setupPlayerControls() {
    const previewPlay = document.getElementById('previewPlay');
    const previewPause = document.getElementById('previewPause');
    const previewStop = document.getElementById('previewStop');
    const volumeControl = document.getElementById('musica_volumen');
    const audioStatus = document.getElementById('audioStatus');
    
    // Controles del reproductor
    if (previewPlay) {
        previewPlay.addEventListener('click', function() {
            if (youtubePlayer && isPlayerReady) {
                youtubePlayer.playVideo();
                if (audioStatus) audioStatus.textContent = 'Reproduciendo...';
            }
        });
    }
    
    if (previewPause) {
        previewPause.addEventListener('click', function() {
            if (youtubePlayer && isPlayerReady) {
                youtubePlayer.pauseVideo();
                if (audioStatus) audioStatus.textContent = 'Pausado';
            }
        });
    }
    
    if (previewStop) {
        previewStop.addEventListener('click', function() {
            if (youtubePlayer && isPlayerReady) {
                youtubePlayer.stopVideo();
                if (audioStatus) audioStatus.textContent = 'Detenido';
            }
        });
    }
    
    // Control de volumen
    if (volumeControl) {
        volumeControl.addEventListener('input', function() {
            if (youtubePlayer && isPlayerReady) {
                youtubePlayer.setVolume(this.value * 100);
            }
        });
    }
}

// Auto-inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initializeMusicControls();
});