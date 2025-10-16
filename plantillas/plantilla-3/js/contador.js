// Variables globales para el contador
let previousValues = { days: null, hours: null, minutes: null, seconds: null };
let countdownInterval;
let isAnimating = false;

// Detectar si es contador simple
function isSimpleCountdown() {
    return document.querySelector('.countdown-simple') !== null;
}

// Animación mejorada para contador simple
function animateSimpleNumberChange(element, newValue) {
    if (!element || isAnimating) return;
    
    isAnimating = true;
    element.classList.add('updating');
    
    setTimeout(() => {
        element.textContent = newValue;
        element.setAttribute('data-number', newValue);
    }, 300);
    
    setTimeout(() => {
        element.classList.remove('updating');
        isAnimating = false;
    }, 600);
}

// Animación para contador completo
function animateNumberChange(element, newValue) {
    if (!element || isAnimating) return;
    
    isAnimating = true;
    element.classList.add('updating');
    
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
    
    contador.classList.add('celebration-mode');
    
    if (isSimpleCountdown()) {
        // Efecto especial para contador simple
        const numberElement = document.querySelector('.time-unit-large .number');
        if (numberElement) {
            numberElement.style.animation = 'celebrateSimple 2s infinite ease-in-out';
        }
    }
}

// Función mejorada para manejar diferentes formatos de fecha
function parseEventDate(fecha, hora) {
    const formats = [
        () => new Date(`${fecha} ${hora}`),
        () => new Date(`${fecha}T${hora}`),
        () => new Date(`${fecha}T${hora}:00`),
        () => new Date(`${fecha}T12:00:00`),
        () => new Date(`${fecha} 12:00:00`),
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

// Función para formatear tiempo
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

// Función principal del contador
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
    
    const isSimple = isSimpleCountdown();
    const contadorElement = document.querySelector('.contador');
    const countdownElement = document.getElementById('countdown');
    
    if (!countdownElement) {
        console.error('Elemento countdown no encontrado en el DOM');
        return;
    }
    
    // Agregar clase de cargado
    document.body.classList.add('loaded');
    
    // Crear la fecha del evento
    let fechaEvento;
    try {
        fechaEvento = parseEventDate(invitacionData.fecha, invitacionData.hora);
        console.log('Fecha del evento parseada:', fechaEvento);
    } catch (error) {
        console.error('Error al parsear la fecha del evento:', error);
        
        // Mostrar error al usuario
        if (isSimple) {
            countdownElement.innerHTML = `
                <div class="time-unit-large error">
                    <span class="number">⚠️</span>
                    <span class="label">Error en la fecha</span>
                </div>
            `;
        } else {
            countdownElement.innerHTML = `
                <div class="time-unit error">
                    <div class="error-message">
                        <span class="number">⚠️</span>
                        <span class="label">Error en la fecha</span>
                    </div>
                </div>
            `;
        }
        return;
    }
    
    function updateCountdown() {
        try {
            const ahora = new Date().getTime();
            const fechaEventoTime = fechaEvento.getTime();
            const distancia = fechaEventoTime - ahora;
            
            // Si el evento ya pasó
            if (distancia < 0) {
                if (isSimple) {
                    countdownElement.innerHTML = `
                        <div class="time-unit-large celebration">
                            <span class="particle"></span>
                            <span class="particle"></span>
                            <span class="particle"></span>
                            <span class="label">¡Hoy es nuestro gran día!</span>
                            <span class="number">0</span>
                        </div>
                    `;
                } else {
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
                }
                
                countdownElement.classList.add('final-day');
                createCelebrationEffect();
                
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
            
            if (isSimple) {
                // CONTADOR SIMPLE - Solo días
                const daysElement = document.getElementById('days');
                if (daysElement) {
                    const newValue = formatTimeValue(dias, 'days');
                    if (previousValues.days !== newValue) {
                        animateSimpleNumberChange(daysElement, newValue);
                        previousValues.days = newValue;
                    }
                }
                
                // Actualizar mensaje para contador simple
                updateSimpleCountdownMessage(dias);
                
            } else {
                // CONTADOR COMPLETO - Todos los elementos
                const elements = {
                    days: document.getElementById('days'),
                    hours: document.getElementById('hours'),
                    minutes: document.getElementById('minutes'),
                    seconds: document.getElementById('seconds')
                };
                
                const missingElements = Object.keys(elements).filter(key => !elements[key]);
                if (missingElements.length > 0) {
                    console.warn('Elementos faltantes en el contador:', missingElements);
                    return;
                }
                
                const newValues = {
                    days: formatTimeValue(dias, 'days'),
                    hours: formatTimeValue(horas, 'hours'),
                    minutes: formatTimeValue(minutos, 'minutes'),
                    seconds: formatTimeValue(segundos, 'seconds')
                };
                
                Object.keys(newValues).forEach(key => {
                    if (elements[key] && previousValues[key] !== newValues[key]) {
                        if (previousValues[key] !== null) {
                            if (!isMobileDevice() || key !== 'seconds') {
                                animateNumberChange(elements[key], newValues[key]);
                            } else {
                                elements[key].textContent = newValues[key];
                                elements[key].setAttribute('data-number', newValues[key]);
                            }
                        } else {
                            elements[key].textContent = newValues[key];
                            elements[key].setAttribute('data-number', newValues[key]);
                        }
                        previousValues[key] = newValues[key];
                    }
                });
                
                updateCountdownMessage(dias, horas, minutos);
            }
            
        } catch (error) {
            console.error('Error en updateCountdown:', error);
        }
    }
    
    // Función para mensaje dinámico del contador completo
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
    
    // Función para mensaje del contador simple
    function updateSimpleCountdownMessage(dias) {
        const labelElement = document.querySelector('.time-unit-large .label');
        if (!labelElement) return;
        
        let mensaje = '';
        
        if (dias === 0) {
            mensaje = '¡Hoy es nuestro gran día!';
        } else if (dias === 1) {
            mensaje = 'Mañana será nuestro día especial';
        } else if (dias <= 7) {
            mensaje = `¡Solo ${dias} días para nuestro gran día!`;
        } else if (dias <= 30) {
            mensaje = `Faltan ${dias} días para celebrar juntos`;
        } else {
            // Mantener la frase aleatoria original para más de 30 días
            mensaje = labelElement.getAttribute('data-original-text') || labelElement.textContent;
        }
        
        if (labelElement.textContent !== mensaje) {
            labelElement.style.opacity = '0.7';
            setTimeout(() => {
                labelElement.textContent = mensaje;
                labelElement.style.opacity = '1';
            }, 300);
        }
    }
    
    // Guardar texto original para contador simple
    if (isSimpleCountdown()) {
        const labelElement = document.querySelector('.time-unit-large .label');
        if (labelElement) {
            labelElement.setAttribute('data-original-text', labelElement.textContent);
        }
    }
    
    // Ejecutar inmediatamente y luego cada segundo
    updateCountdown();
    countdownInterval = setInterval(updateCountdown, 1000);
    
    console.log('Contador inicializado correctamente - Tipo:', isSimple ? 'Simple' : 'Completo');
}

// Función para limpiar recursos
function cleanupCountdown() {
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
    
    previousValues = { days: null, hours: null, minutes: null, seconds: null };
    isAnimating = false;
}

// Manejar visibilidad de la página
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
    } else {
        if (invitacionData?.mostrarContador && !countdownInterval) {
            initCountdown();
        }
    }
});

// Inicialización segura
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