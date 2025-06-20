// Dashboard específico
document.addEventListener('DOMContentLoaded', function() {
    initDashboard();
    loadDashboardData();
});

function initDashboard() {
    // Animaciones de entrada para las tarjetas
    const cards = document.querySelectorAll('.stat-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

function loadDashboardData() {
    // Simular carga de datos en tiempo real
    // En producción, esto haría llamadas AJAX a endpoints PHP
    
    // Actualizar estadísticas cada 30 segundos
    setInterval(updateStats, 30000);
}

function updateStats() {
    // Simular actualización de estadísticas
    const statsNumbers = document.querySelectorAll('.stat-number');
    
    statsNumbers.forEach(stat => {
        const currentValue = parseInt(stat.textContent);
        // Simular cambio aleatorio pequeño
        const change = Math.floor(Math.random() * 3) - 1; // -1, 0, o 1
        const newValue = Math.max(0, currentValue + change);
        
        if (newValue !== currentValue) {
            animateNumber(stat, currentValue, newValue);
        }
    });
}

function animateNumber(element, start, end) {
    const duration = 1000;
    const startTime = performance.now();
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const current = Math.floor(start + (end - start) * progress);
        element.textContent = current;
        
        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }
    
    requestAnimationFrame(update);
}

// Función para navegar rápidamente
function quickNavigate(page) {
    window.location.href = page + '.php';
}