// Variables globales para el contador
let previousValues = { days: null, hours: null, minutes: null, seconds: null };
let countdownInterval;

function animateNumberChange(element, newValue) {
    if (!element) return;
    
    element.classList.add('updating');
    
    setTimeout(() => {
        element.textContent = newValue;
        element.setAttribute('data-number', newValue);
    }, 200);
    
    setTimeout(() => {
        element.classList.remove('updating');
    }, 400);
}

function initCountdown() {
    // Verificar si se debe mostrar el contador
    if (!invitacionData.mostrarContador) {
        const contadorElement = document.querySelector('.contador');
        if (contadorElement) {
            contadorElement.style.display = 'none';
        }
        return;
    }
    
    // Obtener elementos del DOM
    const contadorElement = document.querySelector('.contador');
    const countdownElement = document.getElementById('countdown');
    
    if (!countdownElement) {
        console.error('Elemento countdown no encontrado');
        return;
    }
    
    // Crear la fecha del evento
    let fechaEvento;
    try {
        // Intentar diferentes formatos de fecha
        if (invitacionData.hora && invitacionData.hora !== '') {
            // Formato: YYYY-MM-DD HH:MM:SS o YYYY-MM-DD HH:MM
            fechaEvento = new Date(`${invitacionData.fecha} ${invitacionData.hora}`);
            
            // Si no funciona, intentar con formato T
            if (isNaN(fechaEvento.getTime())) {
                fechaEvento = new Date(`${invitacionData.fecha}T${invitacionData.hora}`);
            }
        } else {
            // Solo fecha, asumir mediodía
            fechaEvento = new Date(`${invitacionData.fecha}T12:00:00`);
        }
        
        // Verificar si la fecha es válida
        if (isNaN(fechaEvento.getTime())) {
            throw new Error('Fecha inválida');
        }
        
    } catch (error) {
        console.error('Error al parsear la fecha:', error);
        console.log('Fecha recibida:', invitacionData.fecha, 'Hora recibida:', invitacionData.hora);
        
        // Fallback: crear fecha manualmente
        const fechaParts = invitacionData.fecha.split('-');
        const horaParts = invitacionData.hora ? invitacionData.hora.split(':') : ['12', '00'];
        
        fechaEvento = new Date(
            parseInt(fechaParts[0]), // año
            parseInt(fechaParts[1]) - 1, // mes (0-indexado)
            parseInt(fechaParts[2]), // día
            parseInt(horaParts[0]), // hora
            parseInt(horaParts[1]) || 0, // minutos
            parseInt(horaParts[2]) || 0 // segundos
        );
    }
    
    function updateCountdown() {
        const ahora = new Date().getTime();
        const fechaEventoTime = fechaEvento.getTime();
        const distancia = fechaEventoTime - ahora;
                
        // Si el evento ya pasó
        if (distancia < 0) {
            countdownElement.innerHTML = `
                <div class="time-unit celebration">
                    <span class="number celebration-text">¡Hoy es el día!</span>
                    <span class="label">¡Celebremos!</span>
                </div>
            `;
            countdownElement.classList.add('final-day');
            
            // Limpiar el intervalo
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
            return;
        }
        
        // Calcular tiempo restante
        const dias = Math.floor(distancia / (1000 * 60 * 60 * 24));
        const horas = Math.floor((distancia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutos = Math.floor((distancia % (1000 * 60 * 60)) / (1000 * 60));
        const segundos = Math.floor((distancia % (1000 * 60)) / 1000);
        
        // Efecto especial cuando faltan menos de 14 días
        if (dias <= 14 && contadorElement) {
            contadorElement.classList.add('close-date');
        }
        
        // Obtener elementos
        const elements = {
            days: document.getElementById('days'),
            hours: document.getElementById('hours'),
            minutes: document.getElementById('minutes'),
            seconds: document.getElementById('seconds')
        };
        
        // Formatear valores
        const newValues = {
            days: dias.toString(),
            hours: horas.toString().padStart(2, '0'),
            minutes: minutos.toString().padStart(2, '0'),
            seconds: segundos.toString().padStart(2, '0')
        };
        
        // Actualizar con animación solo si hay cambios
        Object.keys(newValues).forEach(key => {
            if (elements[key] && previousValues[key] !== newValues[key]) {
                if (previousValues[key] !== null) {
                    animateNumberChange(elements[key], newValues[key]);
                } else {
                    // Primera carga sin animación
                    elements[key].textContent = newValues[key];
                    elements[key].setAttribute('data-number', newValues[key]);
                }
                previousValues[key] = newValues[key];
            }
        });
    }
    
    // Ejecutar inmediatamente y luego cada segundo
    updateCountdown();
    countdownInterval = setInterval(updateCountdown, 1000);
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Pequeña pausa para asegurar que todas las variables estén cargadas
    setTimeout(initCountdown, 100);
});

// También inicializar si el script se carga después del DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCountdown);
} else {
    setTimeout(initCountdown, 100);
}