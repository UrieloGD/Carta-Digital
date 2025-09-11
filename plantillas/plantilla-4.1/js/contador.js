// Variables globales para el contador
let previousValues = { days: null, hours: null, minutes: null, seconds: null };
let countdownInterval;
let isAnimating = false;

// Mejorar la animación de cambio de números
function animateNumberChange(element, newValue) {
    if (!element || isAnimating) return;
    
    isAnimating = true;
    element.classList.add('updating');
    
    // Crear efecto de parpadeo suave para segundos
    if (element.id === 'seconds') {
        element.style.transform = 'scale(1.1)';
    }
    
    setTimeout(() => {
        element.textContent = newValue;
        element.setAttribute('data-number', newValue);
        
        if (element.id === 'seconds') {
            element.style.transform = 'scale(1)';
        }
    }, 200);
    
    setTimeout(() => {
        element.classList.remove('updating');
        isAnimating = false;
    }, 400);
}

// Función para crear efecto de celebración
function createCelebrationEffect() {
    const contador = document.querySelector('.contador');
    if (!contador) return;
    
    // Agregar clase de celebración
    contador.classList.add('celebration-mode');
    
    // Crear partículas de celebración (opcional)
    const particles = [];
    for (let i = 0; i < 20; i++) {
        const particle = document.createElement('div');
        particle.className = 'celebration-particle';
        particle.style.cssText = `
            position: absolute;
            width: 6px;
            height: 6px;
            background: var(--gold-accent);
            border-radius: 50%;
            left: ${Math.random() * 100}%;
            top: ${Math.random() * 100}%;
            opacity: 0;
            animation: celebrate-particle 3s ease-out infinite;
            animation-delay: ${Math.random() * 2}s;
        `;
        contador.appendChild(particle);
        particles.push(particle);
    }
    
    // Limpiar partículas después de 10 segundos
    setTimeout(() => {
        particles.forEach(particle => {
            if (particle.parentNode) {
                particle.parentNode.removeChild(particle);
            }
        });
    }, 10000);
}

// Función mejorada para manejar diferentes formatos de fecha
function parseEventDate(fecha, hora) {
    // Lista de formatos posibles
    const formats = [
        // Con hora específica
        () => new Date(`${fecha} ${hora}`),
        () => new Date(`${fecha}T${hora}`),
        () => new Date(`${fecha}T${hora}:00`),
        // Solo fecha (mediodía por defecto)
        () => new Date(`${fecha}T12:00:00`),
        () => new Date(`${fecha} 12:00:00`),
        // Formato manual
        () => {
            const [year, month, day] = fecha.split('-').map(Number);
            const [hours, minutes, seconds = 0] = (hora || '12:00:00').split(':').map(Number);
            return new Date(year, month - 1, day, hours, minutes, seconds);
        }
    ];
    
    for (const formatFn of formats) {
        try {
            const date = formatFn();
            if (!isNaN(date.getTime())) {
                return date;
            }
        } catch (error) {
            continue;
        }
    }
    
    throw new Error(`No se pudo parsear la fecha: ${fecha} ${hora || ''}`);
}

// Función para formatear tiempo con lógica mejorada
function formatTimeValue(value, unit) {
    switch (unit) {
        case 'days':
            return value.toString();
        case 'hours':
        case 'minutes':
        case 'seconds':
            return value.toString().padStart(2, '0');
        default:
            return value.toString();
    }
}

// Función mejorada para detectar dispositivos móviles
function isMobileDevice() {
    return window.innerWidth <= 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

function initCountdown() {
    // Verificar si se debe mostrar el contador
    if (!invitacionData?.mostrarContador) {
        const contadorElement = document.querySelector('.contador');
        if (contadorElement) {
            contadorElement.style.display = 'none';
        }
        console.log('Contador deshabilitado por configuración');
        return;
    }
    
    // Obtener elementos del DOM
    const contadorElement = document.querySelector('.contador');
    const countdownElement = document.getElementById('countdown');
    
    if (!countdownElement) {
        console.error('Elemento countdown no encontrado en el DOM');
        return;
    }
    
    // Agregar clase de cargado
    document.body.classList.add('loaded');
    
    // Crear la fecha del evento con manejo de errores mejorado
    let fechaEvento;
    try {
        fechaEvento = parseEventDate(invitacionData.fecha, invitacionData.hora);
        console.log('Fecha del evento parseada:', fechaEvento);
    } catch (error) {
        console.error('Error al parsear la fecha del evento:', error);
        
        // Mostrar error al usuario
        countdownElement.innerHTML = `
            <div class="time-unit error">
                <div class="error-message">
                    <span class="number">⚠️</span>
                    <span class="label">Error en la fecha</span>
                </div>
            </div>
        `;
        return;
    }
    
    function updateCountdown() {
        try {
            const ahora = new Date().getTime();
            const fechaEventoTime = fechaEvento.getTime();
            const distancia = fechaEventoTime - ahora;
            
            // Si el evento ya pasó
            if (distancia < 0) {
                countdownElement.innerHTML = `
                    <div class="countdown-expired">
                        <div class="time-unit celebration">
                            <div class="celebration-content">
                                <span class="celebration-emoji">🎉</span>
                                <span class="celebration-text">¡Es hoy!</span>
                                <span class="celebration-subtitle">¡Nuestro día especial ha llegado!</span>
                            </div>
                        </div>
                    </div>
                `;
                
                countdownElement.classList.add('final-day');
                createCelebrationEffect();
                
                // Limpiar el intervalo
                if (countdownInterval) {
                    clearInterval(countdownInterval);
                    countdownInterval = null;
                }
                return;
            }
            
            // Calcular tiempo restante
            const dias = Math.floor(distancia / (1000 * 60 * 60 * 24));
            const horas = Math.floor((distancia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutos = Math.floor((distancia % (1000 * 60 * 60)) / (1000 * 60));
            const segundos = Math.floor((distancia % (1000 * 60)) / 1000);
            
            // Efectos especiales basados en tiempo restante
            if (contadorElement) {
                contadorElement.classList.toggle('close-date', dias <= 14);
                contadorElement.classList.toggle('very-close', dias <= 7);
                contadorElement.classList.toggle('final-countdown', dias <= 1);
            }
            
            // Obtener elementos del DOM
            const elements = {
                days: document.getElementById('days'),
                hours: document.getElementById('hours'),
                minutes: document.getElementById('minutes'),
                seconds: document.getElementById('seconds')
            };
            
            // Verificar que todos los elementos existen
            const missingElements = Object.keys(elements).filter(key => !elements[key]);
            if (missingElements.length > 0) {
                console.warn('Elementos faltantes en el contador:', missingElements);
                return;
            }
            
            // Formatear valores
            const newValues = {
                days: formatTimeValue(dias, 'days'),
                hours: formatTimeValue(horas, 'hours'),
                minutes: formatTimeValue(minutos, 'minutes'),
                seconds: formatTimeValue(segundos, 'seconds')
            };
            
            // Actualizar con animación solo si hay cambios
            Object.keys(newValues).forEach(key => {
                if (elements[key] && previousValues[key] !== newValues[key]) {
                    if (previousValues[key] !== null) {
                        // Solo animar en desktop o si no es segundos
                        if (!isMobileDevice() || key !== 'seconds') {
                            animateNumberChange(elements[key], newValues[key]);
                        } else {
                            // En móvil, cambio directo para segundos
                            elements[key].textContent = newValues[key];
                            elements[key].setAttribute('data-number', newValues[key]);
                        }
                    } else {
                        // Primera carga sin animación
                        elements[key].textContent = newValues[key];
                        elements[key].setAttribute('data-number', newValues[key]);
                    }
                    previousValues[key] = newValues[key];
                }
            });
            
            // Actualizar mensaje dinámico
            updateCountdownMessage(dias, horas, minutos);
            
        } catch (error) {
            console.error('Error en updateCountdown:', error);
        }
    }
    
    // Función para mensaje dinámico
    function updateCountdownMessage(dias, horas, minutos) {
        const messageElement = document.querySelector('.countdown-message .script-text');
        if (!messageElement) return;
        
        let mensaje = '';
        
        if (dias === 0) {
            if (horas === 0) {
                mensaje = minutos <= 30 ? 
                    '¡Solo unos minutos más para nuestro momento especial!' : 
                    '¡El día ha llegado! Solo algunas horas más...';
            } else {
                mensaje = '¡Hoy es nuestro día especial!';
            }
        } else if (dias === 1) {
            mensaje = '¡Mañana será nuestro día especial!';
        } else if (dias <= 7) {
            mensaje = `¡Solo ${dias} días para nuestro gran día!`;
        } else if (dias <= 30) {
            mensaje = `Faltan ${dias} días para celebrar nuestro amor`;
        } else {
            mensaje = `${dias} días hasta nuestro momento especial`;
        }
        
        if (messageElement.textContent !== mensaje) {
            messageElement.style.opacity = '0';
            setTimeout(() => {
                messageElement.textContent = mensaje;
                messageElement.style.opacity = '1';
            }, 300);
        }
    }
    
    // Ejecutar inmediatamente y luego cada segundo
    updateCountdown();
    countdownInterval = setInterval(updateCountdown, 1000);
    
    console.log('Contador inicializado correctamente');
}

// Función para limpiar recursos
function cleanupCountdown() {
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
    
    // Limpiar variables
    previousValues = { days: null, hours: null, minutes: null, seconds: null };
    isAnimating = false;
}

// Manejar visibilidad de la página para optimizar rendimiento
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Pausar animaciones cuando la página no es visible
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
    } else {
        // Reanudar cuando la página es visible
        if (invitacionData?.mostrarContador && !countdownInterval) {
            const updateCountdown = document.querySelector('.contador')?.updateCountdown;
            if (updateCountdown) {
                countdownInterval = setInterval(updateCountdown, 1000);
            }
        }
    }
});

// Manejar redimensionado de ventana
let resizeTimeout;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
        // Reajustar elementos si es necesario
        const contador = document.querySelector('.contador');
        if (contador) {
            contador.classList.toggle('mobile-layout', isMobileDevice());
        }
    }, 250);
});

// Inicialización con múltiples puntos de entrada
function safeInit() {
    try {
        if (typeof invitacionData === 'undefined') {
            console.warn('invitacionData no está disponible, reintentando...');
            setTimeout(safeInit, 500);
            return;
        }
        initCountdown();
    } catch (error) {
        console.error('Error en la inicialización del contador:', error);
    }
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', safeInit);
} else {
    setTimeout(safeInit, 100);
}

// Limpiar recursos al salir
window.addEventListener('beforeunload', cleanupCountdown);