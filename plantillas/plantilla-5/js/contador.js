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
    
    // Detectar el tipo de contador
    const esContadorSimple = invitacionData.tipoContador === 'simple';
    
    // Crear la fecha del evento
    let fechaEvento;
    try {
        if (invitacionData.hora && invitacionData.hora !== '') {
            fechaEvento = new Date(`${invitacionData.fecha} ${invitacionData.hora}`);
            
            if (isNaN(fechaEvento.getTime())) {
                fechaEvento = new Date(`${invitacionData.fecha}T${invitacionData.hora}`);
            }
        } else {
            fechaEvento = new Date(`${invitacionData.fecha}T12:00:00`);
        }
        
        if (isNaN(fechaEvento.getTime())) {
            throw new Error('Fecha inválida');
        }
        
    } catch (error) {
        console.error('Error al parsear la fecha:', error);
        console.log('Fecha recibida:', invitacionData.fecha, 'Hora recibida:', invitacionData.hora);
        
        const fechaParts = invitacionData.fecha.split('-');
        const horaParts = invitacionData.hora ? invitacionData.hora.split(':') : ['12', '00'];
        
        fechaEvento = new Date(
            parseInt(fechaParts[0]),
            parseInt(fechaParts[1]) - 1,
            parseInt(fechaParts[2]),
            parseInt(horaParts[0]),
            parseInt(horaParts[1]) || 0,
            parseInt(horaParts[2]) || 0
        );
    }
    
    function updateCountdown() {
        const ahora = new Date().getTime();
        const fechaEventoTime = fechaEvento.getTime();
        const distancia = fechaEventoTime - ahora;
        
        // Si el evento ya pasó
        if (distancia < 0) {
            if (esContadorSimple) {
                countdownElement.innerHTML = `
                    <div class="time-unit time-unit-large celebration">
                        <span class="label">¡Hoy es el gran día!</span>
                        <span class="number celebration-text">0</span>
                    </div>
                `;
            } else {
                countdownElement.innerHTML = `
                    <div class="time-unit celebration">
                        <span class="number celebration-text">¡Hoy es el día!</span>
                        <span class="label">¡Celebremos!</span>
                    </div>
                `;
            }
            countdownElement.classList.add('final-day');
            
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
            return;
        }
        
        // Calcular tiempo restante
        const dias = Math.floor(distancia / (1000 * 60 * 60 * 24));
        
        // Efecto especial cuando faltan menos de 14 días
        if (dias <= 14 && contadorElement) {
            contadorElement.classList.add('close-date');
        }
        
        if (esContadorSimple) {
            // Versión simple: solo días
            const daysElement = document.getElementById('days');
            
            if (daysElement) {
                const newValue = dias.toString();
                
                if (previousValues.days !== newValue) {
                    if (previousValues.days !== null) {
                        animateNumberChange(daysElement, newValue);
                    } else {
                        daysElement.textContent = newValue;
                        daysElement.setAttribute('data-number', newValue);
                    }
                    previousValues.days = newValue;
                }
            }
        } else {
            // Versión completa: días, horas, minutos, segundos
            const horas = Math.floor((distancia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutos = Math.floor((distancia % (1000 * 60 * 60)) / (1000 * 60));
            const segundos = Math.floor((distancia % (1000 * 60)) / 1000);
            
            const elements = {
                days: document.getElementById('days'),
                hours: document.getElementById('hours'),
                minutes: document.getElementById('minutes'),
                seconds: document.getElementById('seconds')
            };
            
            const newValues = {
                days: dias.toString(),
                hours: horas.toString().padStart(2, '0'),
                minutes: minutos.toString().padStart(2, '0'),
                seconds: segundos.toString().padStart(2, '0')
            };
            
            Object.keys(newValues).forEach(key => {
                if (elements[key] && previousValues[key] !== newValues[key]) {
                    if (previousValues[key] !== null) {
                        animateNumberChange(elements[key], newValues[key]);
                    } else {
                        elements[key].textContent = newValues[key];
                        elements[key].setAttribute('data-number', newValues[key]);
                    }
                    previousValues[key] = newValues[key];
                }
            });
        }
    }
    
    // Ejecutar inmediatamente y luego cada segundo (o cada minuto para versión simple)
    updateCountdown();
    
    // Para la versión simple, actualizar cada minuto es suficiente ya que solo muestra días
    const intervaloActualizacion = esContadorSimple ? 60000 : 1000; // 60000ms = 1 minuto
    countdownInterval = setInterval(updateCountdown, intervaloActualizacion);
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(initCountdown, 100);
});

// También inicializar si el script se carga después del DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCountdown);
} else {
    setTimeout(initCountdown, 100);
}