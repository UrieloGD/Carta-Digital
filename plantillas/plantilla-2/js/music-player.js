class MusicPlayer {
    constructor() {
        this.audio = null;
        this.isPlaying = false;
        this.isMinimized = false;
        this.currentTime = 0;
        this.duration = 0;
        this.volume = 0.7;
        
        this.init();
    }
    
    init() {
        if (invitacionData.musicaArchivo) {
            this.createPlayer();
            this.setupAudio();
            this.setupEventListeners();
            
            // Auto-play si está configurado
            if (invitacionData.musicaAutoplay) {
                setTimeout(() => {
                    this.play();
                }, 1000);
            }
        }
    }
    
    createPlayer() {
        const playerHTML = `
            <div class="music-player" id="musicPlayer">
                <div class="player-content">
                    <div class="player-header">
                        <h4 class="player-title">🎵 Música de fondo</h4>
                        <button class="minimize-btn" onclick="musicPlayer.toggleMinimize()">−</button>
                    </div>
                    
                    <div class="song-info">
                        <p class="song-title">${invitacionData.musicaNombre || 'Canción de boda'}</p>
                    </div>
                    
                    <div class="player-controls">
                        <button class="play-pause-btn" onclick="musicPlayer.togglePlay()">
                            <span class="play-icon">▶</span>
                            <span class="pause-icon" style="display: none;">⏸</span>
                        </button>
                        
                        <div class="volume-control">
                            <button class="volume-btn" onclick="musicPlayer.toggleMute()">🔊</button>
                            <input type="range" class="volume-slider" min="0" max="1" step="0.1" value="0.7">
                        </div>
                    </div>
                    
                    <div class="progress-container">
                        <input type="range" class="progress-bar" min="0" max="100" value="0">
                    </div>
                    
                    <div class="time-display">
                        <span class="current-time">0:00</span>
                        <span class="total-time">0:00</span>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', playerHTML);
    }
    
    setupAudio() {
        this.audio = new Audio(invitacionData.musicaArchivo);
        this.audio.loop = true;
        this.audio.volume = this.volume;
        
        this.audio.addEventListener('loadedmetadata', () => {
            this.duration = this.audio.duration;
            this.updateTimeDisplay();
        });
        
        this.audio.addEventListener('timeupdate', () => {
            this.currentTime = this.audio.currentTime;
            this.updateProgress();
            this.updateTimeDisplay();
        });
        
        this.audio.addEventListener('ended', () => {
            this.isPlaying = false;
            this.updatePlayButton();
        });
        
        this.audio.addEventListener('error', (e) => {
            console.error('Error loading audio:', e);
            this.showError();
        });
    }
    
    setupEventListeners() {
        const player = document.getElementById('musicPlayer');
        const volumeSlider = player.querySelector('.volume-slider');
        const progressBar = player.querySelector('.progress-bar');
        
        volumeSlider.addEventListener('input', (e) => {
            this.setVolume(e.target.value);
        });
        
        progressBar.addEventListener('input', (e) => {
            this.seek(e.target.value);
        });
        
        // Detectar interacción del usuario para auto-play
        document.addEventListener('click', () => {
            if (invitacionData.musicaAutoplay && !this.isPlaying && !this.userInteracted) {
                this.userInteracted = true;
                this.play();
            }
        }, { once: true });
    }
    
    togglePlay() {
        if (this.isPlaying) {
            this.pause();
        } else {
            this.play();
        }
    }
    
    play() {
        if (this.audio) {
            const playPromise = this.audio.play();
            
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    this.isPlaying = true;
                    this.updatePlayButton();
                    this.addPlayingClass();
                }).catch((error) => {
                    console.error('Error playing audio:', error);
                });
            }
        }
    }
    
    pause() {
        if (this.audio) {
            this.audio.pause();
            this.isPlaying = false;
            this.updatePlayButton();
            this.removePlayingClass();
        }
    }
    
    setVolume(volume) {
        this.volume = volume;
        if (this.audio) {
            this.audio.volume = volume;
        }
        this.updateVolumeButton();
    }
    
    toggleMute() {
        if (this.audio) {
            if (this.audio.volume > 0) {
                this.previousVolume = this.audio.volume;
                this.setVolume(0);
                document.querySelector('.volume-slider').value = 0;
            } else {
                const volumeToRestore = this.previousVolume || 0.7;
                this.setVolume(volumeToRestore);
                document.querySelector('.volume-slider').value = volumeToRestore;
            }
        }
    }
    
    seek(percentage) {
        if (this.audio && this.duration) {
            const newTime = (percentage / 100) * this.duration;
            this.audio.currentTime = newTime;
        }
    }
    
    toggleMinimize() {
        const player = document.getElementById('musicPlayer');
        this.isMinimized = !this.isMinimized;
        
        if (this.isMinimized) {
            player.classList.add('minimized');
        } else {
            player.classList.remove('minimized');
        }
    }
    
    updatePlayButton() {
        const playIcon = document.querySelector('.play-icon');
        const pauseIcon = document.querySelector('.pause-icon');
        
        if (this.isPlaying) {
            playIcon.style.display = 'none';
            pauseIcon.style.display = 'inline';
        } else {
            playIcon.style.display = 'inline';
            pauseIcon.style.display = 'none';
        }
    }
    
    updateProgress() {
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar && this.duration) {
            const percentage = (this.currentTime / this.duration) * 100;
            progressBar.value = percentage;
        }
    }
    
    updateTimeDisplay() {
        const currentTimeEl = document.querySelector('.current-time');
        const totalTimeEl = document.querySelector('.total-time');
        
        if (currentTimeEl) currentTimeEl.textContent = this.formatTime(this.currentTime);
        if (totalTimeEl) totalTimeEl.textContent = this.formatTime(this.duration);
    }
    
    updateVolumeButton() {
        const volumeBtn = document.querySelector('.volume-btn');
        if (volumeBtn) {
            if (this.volume === 0) {
                volumeBtn.textContent = '🔇';
            } else if (this.volume < 0.5) {
                volumeBtn.textContent = '🔉';
            } else {
                volumeBtn.textContent = '🔊';
            }
        }
    }
    
    addPlayingClass() {
        const player = document.getElementById('musicPlayer');
        if (player) player.classList.add('playing');
    }
    
    removePlayingClass() {
        const player = document.getElementById('musicPlayer');
        if (player) player.classList.remove('playing');
    }
    
    formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    }
    
    showError() {
        const player = document.getElementById('musicPlayer');
        if (player) {
            player.innerHTML = `
                <div class="player-content">
                    <div class="player-header">
                        <h4 class="player-title">🎵 Error de audio</h4>
                        <button class="minimize-btn" onclick="musicPlayer.toggleMinimize()">×</button>
                    </div>
                    <p style="font-size: 12px; color: #666; text-align: center;">
                        No se pudo cargar la música
                    </p>
                </div>
            `;
        }
    }
}

// Inicializar el reproductor cuando el documento esté listo
let musicPlayer;
document.addEventListener('DOMContentLoaded', () => {
    musicPlayer = new MusicPlayer();
});

function toggleMusicFromFooter() {
    if (musicPlayer) {
        musicPlayer.togglePlay();
        const status = document.getElementById('musicStatus');
        if (status) {
            status.textContent = musicPlayer.isPlaying ? 'Pausar música' : 'Reproducir música';
        }
    }
}