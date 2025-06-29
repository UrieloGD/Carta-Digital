// Variables globales para la música
let backgroundMusic = null;
let musicPlayer = null;
let isPlaying = false;
let musicInitialized = false;

function initMusic() {
    // Solo inicializar si hay URL de música
    if (!invitacionData.musicaUrl || invitacionData.musicaUrl === '') {
        return;
    }
    
    // Crear elemento de audio si no existe
    if (!backgroundMusic) {
        backgroundMusic = document.createElement('audio');
        backgroundMusic.src = invitacionData.musicaUrl;
        backgroundMusic.loop = true;
        backgroundMusic.volume = 0.3; // Volumen inicial al 30%
        backgroundMusic.preload = 'auto';
        document.body.appendChild(backgroundMusic);
    }
    
    // Crear reproductor flotante si no existe
    if (!musicPlayer) {
        createMusicPlayer();
    }
    
    // Configurar eventos de audio
    setupAudioEvents();
    
    // Auto-reproducir si está configurado
    if (invitacionData.musicaAutoplay && !musicInitialized) {
        setTimeout(() => {
            playMusic();
        }, 1000); // Esperar 1 segundo antes de iniciar
        musicInitialized = true;
    }
}

function createMusicPlayer() {
    musicPlayer = document.createElement('div');
    musicPlayer.className = 'music-player';
    musicPlayer.setAttribute('data-tooltip', 'Música');
    musicPlayer.innerHTML = `
        <div class="music-icon">
            <svg id="musicIcon" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
            </svg>
        </div>
    `;
    
    // Agregar event listener
    musicPlayer.addEventListener('click', toggleMusic);
    
    // Agregar al DOM
    document.body.appendChild(musicPlayer);
    
    // Actualizar estado inicial
    updateMusicPlayerState();
}

function setupAudioEvents() {
    if (!backgroundMusic) return;
    
    backgroundMusic.addEventListener('play', () => {
        isPlaying = true;
        updateMusicPlayerState();
    });
    
    backgroundMusic.addEventListener('pause', () => {
        isPlaying = false;
        updateMusicPlayerState();
    });
    
    backgroundMusic.addEventListener('ended', () => {
        isPlaying = false;
        updateMusicPlayerState();
    });
    
    backgroundMusic.addEventListener('error', (e) => {
        console.error('Error al cargar la música:', e);
        if (musicPlayer) {
            musicPlayer.style.display = 'none';
        }
    });
    
    backgroundMusic.addEventListener('loadstart', () => {
        console.log('Cargando música...');
    });
    
    backgroundMusic.addEventListener('canplaythrough', () => {
        console.log('Música lista para reproducir');
    });
}

function updateMusicPlayerState() {
    if (!musicPlayer) return;
    
    const musicIcon = document.getElementById('musicIcon');
    
    if (isPlaying) {
        musicPlayer.classList.add('playing');
        musicPlayer.classList.remove('paused');
        musicPlayer.setAttribute('data-tooltip', 'Pausar música');
        if (musicIcon) {
            musicIcon.innerHTML = `<path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>`;
        }
    } else {
        musicPlayer.classList.remove('playing');
        musicPlayer.classList.add('paused');
        musicPlayer.setAttribute('data-tooltip', 'Reproducir música');
        if (musicIcon) {
            musicIcon.innerHTML = `<path d="M3.27 3L2 4.27l9 9v.28c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4v-1.73L19.73 21 21 19.73 3.27 3zM14 7h4V3h-6v5.18l2 2z"/>`;
        }
    }
}

function toggleMusic() {
    if (!backgroundMusic) {
        console.error('Audio no disponible');
        return;
    }
    
    try {
        if (isPlaying) {
            pauseMusic();
        } else {
            playMusic();
        }
    } catch (error) {
        console.error('Error al controlar la música:', error);
    }
}

function playMusic() {
    if (!backgroundMusic) return;
    
    const playPromise = backgroundMusic.play();
    
    if (playPromise !== undefined) {
        playPromise
            .then(() => {
                console.log('Música iniciada');
                isPlaying = true;
                updateMusicPlayerState();
            })
            .catch((error) => {
                console.log('No se pudo reproducir automáticamente:', error);
                isPlaying = false;
                updateMusicPlayerState();
                
                // Mostrar mensaje al usuario si es necesario
                if (error.name === 'NotAllowedError') {
                    showMusicPrompt();
                }
            });
    }
}

function pauseMusic() {
    if (!backgroundMusic) return;
    
    backgroundMusic.pause();
    isPlaying = false;
    updateMusicPlayerState();
}

function showMusicPrompt() {
    // Crear un prompt discreto para que el usuario active la música
    const prompt = document.createElement('div');
    prompt.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--color-white);
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: var(--shadow-medium);
        z-index: 1001;
        font-size: 14px;
        max-width: 300px;
        border-left: 4px solid var(--color-secondary);
    `;
    prompt.innerHTML = `
        <div style="margin-bottom: 10px;">
            <strong>🎵 Música disponible</strong>
        </div>
        <div style="margin-bottom: 10px; color: var(--color-text-light);">
            Haz clic en el botón de música para activar el audio
        </div>
        <button onclick="this.parentElement.remove()" style="
            background: var(--color-secondary);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        ">Entendido</button>
    `;
    
    document.body.appendChild(prompt);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (prompt.parentElement) {
            prompt.remove();
        }
    }, 5000);
}

// Funciones de compatibilidad con el código existente
function toggleMusicFromFooter() {
    toggleMusic();
}

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    // Esperar un poco para que todo esté cargado
    setTimeout(initMusic, 500);
});

// También inicializar si el script se carga después del DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMusic);
} else {
    setTimeout(initMusic, 500);
}

// Manejar la interacción del usuario para navegadores que requieren gesture
document.addEventListener('click', function() {
    if (!musicInitialized && invitacionData.musicaAutoplay && backgroundMusic) {
        playMusic();
        musicInitialized = true;
    }
}, { once: true });