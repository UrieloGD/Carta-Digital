/* Music Player */

document.addEventListener('DOMContentLoaded', function() {
    const musicPlayer = document.getElementById('musicPlayer');
    const playerAudio = document.getElementById('playerAudio');

    if (!musicPlayer || !playerAudio || !MUSICA_URL) return;

    // Convertir URL de YouTube a URL embebida si es necesario
    let audioUrl = MUSICA_URL;
    
    // Si es URL de YouTube, convertir a URL de audio
    if (MUSICA_URL.includes('youtube.com') || MUSICA_URL.includes('youtu.be')) {
        // Para YouTube usaremos un reproductor alternativo
        console.log('URL de YouTube detectada. Implementar reproductor personalizado.');
        // Por ahora, solo log
    }

    playerAudio.src = audioUrl;

    let isPlaying = false;

    musicPlayer.addEventListener('click', function(e) {
        e.stopPropagation();
        
        isPlaying = !isPlaying;

        if (isPlaying) {
            playerAudio.play();
            musicPlayer.style.animation = 'neonPulse 1s ease-in-out infinite';
            musicPlayer.style.boxShadow = '0 0 50px rgba(0, 212, 255, 0.8), 0 0 100px rgba(157, 78, 221, 0.5)';
        } else {
            playerAudio.pause();
            musicPlayer.style.animation = 'none';
            musicPlayer.style.boxShadow = '0 0 35px rgba(0, 212, 255, 0.5), 0 0 70px rgba(157, 78, 221, 0.3)';
        }
    });

    // Si la música termina, actualizar estado
    playerAudio.addEventListener('ended', function() {
        isPlaying = false;
        musicPlayer.style.animation = 'none';
    });

    // Cambiar el ícono cuando se pausee
    playerAudio.addEventListener('pause', function() {
        musicPlayer.innerHTML = '<span class="music-player-icon">🎵</span>';
    });

    playerAudio.addEventListener('play', function() {
        musicPlayer.innerHTML = '<span class="music-player-icon">⏸️</span>';
    });
});
