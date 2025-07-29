class MusicPlayer {
    constructor(youtubeUrl, autoplay = true, volume = 0.5) {
        this.youtubeUrl = youtubeUrl;
        this.autoplay = autoplay;
        this.volume = volume;
        this.player = null;
        this.isPlaying = false;
        this.isReady = false;
        this.userInteracted = false;
        this.videoTitle = 'Cargando...';
        
        console.log('MusicPlayer iniciado con:', { youtubeUrl, autoplay, volume });
        
        if (youtubeUrl && this.isValidYouTubeUrl(youtubeUrl)) {
            this.setupUserInteraction();
            this.loadYouTubeAPI();
            this.fetchVideoTitle();
        } else {
            console.warn('URL de YouTube inválida o vacía');
        }
    }
    
    isValidYouTubeUrl(url) {
        const regex = /^(https?:\/\/)?(www\.)?(youtube\.com\/(watch\?v=|embed\/)|youtu\.be\/)[\w-]+/;
        return regex.test(url);
    }
    
    async fetchVideoTitle() {
        const videoId = this.extractVideoId(this.youtubeUrl);
        if (!videoId) return;
        
        try {
            // Usar oEmbed API de YouTube para obtener el título
            const response = await fetch(`https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=${videoId}&format=json`);
            const data = await response.json();
            this.videoTitle = data.title || 'Música de fondo';
            
            // Actualizar título si los controles ya están creados
            const titleElement = document.querySelector('.music-title');
            if (titleElement) {
                titleElement.textContent = this.videoTitle;
                titleElement.title = this.videoTitle; // Tooltip para títulos largos
            }
        } catch (error) {
            console.log('No se pudo obtener el título del video');
            this.videoTitle = 'Música de fondo';
        }
    }
    
    setupUserInteraction() {
        const handleFirstInteraction = () => {
            this.userInteracted = true;
            console.log('Primera interacción del usuario detectada');
            
            if (this.autoplay && this.isReady && !this.isPlaying) {
                setTimeout(() => this.play(), 100);
            }
            
            document.removeEventListener('click', handleFirstInteraction);
            document.removeEventListener('touchstart', handleFirstInteraction);
            document.removeEventListener('keydown', handleFirstInteraction);
            document.removeEventListener('scroll', handleFirstInteraction);
        };
        
        document.addEventListener('click', handleFirstInteraction, { passive: true });
        document.addEventListener('touchstart', handleFirstInteraction, { passive: true });
        document.addEventListener('keydown', handleFirstInteraction, { passive: true });
        document.addEventListener('scroll', handleFirstInteraction, { passive: true });
    }
    
    loadYouTubeAPI() {
        if (window.YT && window.YT.Player) {
            console.log('API de YouTube ya cargada');
            this.createPlayer();
            return;
        }
        
        console.log('Cargando API de YouTube...');
        
        window.onYouTubeIframeAPIReady = () => {
            console.log('API de YouTube lista');
            this.createPlayer();
        };
        
        const tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        tag.onerror = () => {
            console.error('Error al cargar la API de YouTube');
        };
        
        const firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    }
    
    extractVideoId(url) {
        const patterns = [
            /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/,
            /youtube\.com\/watch\?.*v=([^&\n?#]+)/
        ];
        
        for (let pattern of patterns) {
            const match = url.match(pattern);
            if (match && match[1]) {
                return match[1];
            }
        }
        return null;
    }
    
    createPlayer() {
        const videoId = this.extractVideoId(this.youtubeUrl);
        if (!videoId) {
            console.error('No se pudo extraer el ID del video:', this.youtubeUrl);
            return;
        }
        
        console.log('Creando reproductor para video ID:', videoId);
        
        let playerContainer = document.getElementById('youtube-music-player');
        if (!playerContainer) {
            playerContainer = document.createElement('div');
            playerContainer.id = 'youtube-music-player';
            playerContainer.style.cssText = `
                position: fixed;
                top: -1000px;
                left: -1000px;
                width: 1px;
                height: 1px;
                opacity: 0;
                pointer-events: none;
                z-index: -1;
            `;
            document.body.appendChild(playerContainer);
        }
        
        try {
            this.player = new YT.Player('youtube-music-player', {
                height: '1',
                width: '1',
                videoId: videoId,
                playerVars: {
                    autoplay: 0,
                    controls: 0,
                    disablekb: 1,
                    fs: 0,
                    modestbranding: 1,
                    rel: 0,
                    showinfo: 0,
                    loop: 1,
                    playlist: videoId,
                    mute: 0,
                    start: 0
                },
                events: {
                    onReady: (event) => this.onPlayerReady(event),
                    onStateChange: (event) => this.onPlayerStateChange(event),
                    onError: (event) => this.onPlayerError(event)
                }
            });
        } catch (error) {
            console.error('Error creando el reproductor:', error);
        }
    }
    
    onPlayerReady(event) {
        console.log('Reproductor listo');
        this.isReady = true;
        
        event.target.setVolume(Math.round(this.volume * 100));
        this.createMusicControls();
        
        // Auto-reproducir después de un pequeño delay
        if (this.autoplay) {
            setTimeout(() => {
                this.play();
            }, 1500);
        }
    }
    
    onPlayerStateChange(event) {
        const states = {
            [-1]: 'unstarted',
            [0]: 'ended',
            [1]: 'playing',
            [2]: 'paused',
            [3]: 'buffering',
            [5]: 'cued'
        };
        
        console.log('Estado del reproductor:', states[event.data] || event.data);
        
        this.isPlaying = (event.data === YT.PlayerState.PLAYING);
        this.updateControlsUI();
        
        if (event.data === YT.PlayerState.ENDED) {
            setTimeout(() => {
                if (this.player) {
                    this.player.seekTo(0);
                    this.play();
                }
            }, 1000);
        }
    }
    
    onPlayerError(event) {
        console.error('Error del reproductor YouTube:', event.data);
        const errors = {
            2: 'ID de video inválido',
            5: 'Error de HTML5',
            100: 'Video no encontrado o privado',
            101: 'Video no permite reproducción embebida',
            150: 'Video no permite reproducción embebida'
        };
        console.error('Descripción del error:', errors[event.data] || 'Error desconocido');
    }
    
    createMusicControls() {
        const existingControls = document.querySelector('.music-controls');
        if (existingControls) {
            existingControls.remove();
        }
        
        const controls = document.createElement('div');
        controls.className = 'music-controls';
        controls.innerHTML = `
            <div class="music-player-widget">
                <button class="music-toggle" id="musicToggle" title="Reproducir/Pausar música">
                    <span class="play-icon">▶</span>
                </button>
                <div class="music-info">
                    <span class="music-title" title="${this.videoTitle}">${this.videoTitle}</span>
                    <span class="music-status">Cargando...</span>
                </div>
                <div class="music-volume">
                    <span class="volume-icon">♪</span>
                    <input type="range" id="volumeSlider" min="0" max="100" value="${Math.round(this.volume * 100)}" class="volume-slider" title="Volumen">
                </div>
            </div>
        `;
        
        document.body.appendChild(controls);
        
        const toggleBtn = document.getElementById('musicToggle');
        const volumeSlider = document.getElementById('volumeSlider');
        
        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.toggle();
        });
        
        volumeSlider.addEventListener('input', (e) => {
            const volume = e.target.value / 100;
            this.setVolume(volume);
        });
        
        console.log('Controles de música creados');
    }
    
    play() {
        if (!this.player || !this.isReady) {
            console.warn('Reproductor no está listo');
            return;
        }
        
        console.log('Intentando reproducir...');
        
        try {
            this.player.playVideo();
        } catch (error) {
            console.error('Error al reproducir:', error);
        }
    }
    
    pause() {
        if (!this.player || !this.isReady) {
            console.warn('Reproductor no está listo');
            return;
        }
        
        console.log('Pausando...');
        
        try {
            this.player.pauseVideo();
        } catch (error) {
            console.error('Error al pausar:', error);
        }
    }
    
    toggle() {
        this.userInteracted = true;
        
        if (this.isPlaying) {
            this.pause();
        } else {
            this.play();
        }
    }
    
    setVolume(volume) {
        this.volume = volume;
        if (this.player && this.isReady) {
            try {
                this.player.setVolume(Math.round(volume * 100));
            } catch (error) {
                console.error('Error al cambiar volumen:', error);
            }
        }
    }
    
    updateControlsUI() {
        const toggleBtn = document.getElementById('musicToggle');
        const statusSpan = document.querySelector('.music-status');
        
        if (toggleBtn) {
            const icon = toggleBtn.querySelector('.play-icon');
            icon.textContent = this.isPlaying ? '⏸' : '▶';
        }
        
        if (statusSpan) {
            statusSpan.textContent = this.isPlaying ? 'Reproduciendo' : 'Pausado';
        }
    }
}

window.initMusicPlayer = function(youtubeUrl, autoplay, volume) {
    console.log('Inicializando reproductor de música...');
    
    if (!youtubeUrl) {
        console.warn('No hay URL de YouTube proporcionada');
        return;
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.musicPlayer = new MusicPlayer(youtubeUrl, autoplay, volume);
        });
    } else {
        window.musicPlayer = new MusicPlayer(youtubeUrl, autoplay, volume);
    }
};