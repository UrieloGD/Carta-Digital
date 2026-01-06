// ============================================
// CONTADOR REGRESIVO - TEMPLATE 5
// Versión Completa y Corregida
// ============================================

// Variables globales
let countdownInterval = null;
let previousValues = {
    days: null,
    hours: null,
    minutes: null,
    seconds: null
};

// ============================================
// FUNCIONES AUXILIARES
// ============================================

// Detectar dispositivo móvil
function isMobileDevice() {
    return window.innerWidth <= 768;
}

// Formatear valores de tiempo con ceros a la izquierda
function formatTimeValue(value, unit) {
    return String(value).padStart(2, '0');
}

// Animar cambio de número con transición suave
function animateNumberChange(element, newValue) {
    element.classList.add('updating');
    
    setTimeout(() => {
        element.textContent = newValue;
        element.setAttribute('data-number', newValue);
    }, 400);
    
    setTimeout(() => {
        element.classList.remove('updating');
    }, 800);
}

// Crear efecto de celebración cuando llega el día
function createCelebrationEffect() {
    const contador = document.querySelector('.contador');
    if (!contador) return;
    
    contador.classList.add('celebration');
    
    // Crear partículas de celebración
    for (let i = 0; i < 50; i++) {
        createParticle(contador);
    }
}

// Crear partícula individual para efecto de celebración
function createParticle(container) {
    const particle = document.createElement('div');
    particle.className = 'celebration-particle';
    particle.style.left = Math.random() * 100 + '%';
    particle.style.top = Math.random() * 100 + '%';
    particle.style.animationDelay = Math.random() * 2 + 's';
    particle.style.animationDuration = (Math.random() * 3 + 2) + 's';
    container.appendChild(particle);
    
    setTimeout(() => {
        particle.remove();
    }, 5000);
}

// ============================================
// CONTADOR COMPLETO (Días, Horas, Minutos, Segundos)
// ============================================

function initCompleteCountdown(fechaEvento) {
    const contadorElement = document.querySelector('.contador');
    const countdownElement = document.getElementById('countdown');
    
    if (!countdownElement) {
        console.error('Elemento countdown no encontrado en el DOM');
        return;
    }
    
    // Marcar que la página ha cargado
    document.body.classList.add('loaded');
    
    function updateCountdown() {
        try {
            const ahora = new Date().getTime();
            const fechaEventoTime = fechaEvento.getTime();
            const distancia = fechaEventoTime - ahora;
            
            // Si el evento ya pasó o es hoy
            if (distancia < 0) {
                // Reemplazar todo el contenido del countdown con el mensaje de celebración
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
                
                // Agregar clases al elemento countdown
                countdownElement.classList.add('final-day');
                
                // Activar efecto de celebración
                createCelebrationEffect();
                
                // Ocultar el mensaje del contador si existe
                const messageElement = document.querySelector('.countdown-message');
                if (messageElement) {
                    messageElement.style.display = 'none';
                }
                
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
                countdownElement.classList.toggle('final-countdown', dias <= 1);
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
            
            // Formatear valores con ceros a la izquierda
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
                        // Animar cambio (excepto segundos en móvil para mejor rendimiento)
                        if (!isMobileDevice() || key !== 'seconds') {
                            animateNumberChange(elements[key], newValues[key]);
                        } else {
                            elements[key].textContent = newValues[key];
                            elements[key].setAttribute('data-number', newValues[key]);
                        }
                    } else {
                        // Primera carga, sin animación
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
    
    // Actualizar mensaje dinámico según tiempo restante
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
        
        // Actualizar con transición suave
        if (messageElement.textContent !== mensaje) {
            messageElement.style.opacity = '0';
            messageElement.style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
                messageElement.textContent = mensaje;
                messageElement.style.opacity = '1';
            }, 300);
        }
    }
    
    // Ejecutar primera actualización inmediatamente
    updateCountdown();
    
    // Actualizar cada segundo
    return setInterval(updateCountdown, 1000);
}

// ============================================
// CONTADOR SIMPLE (Solo Días)
// ============================================

function initSimpleCountdown(fechaEvento) {
    const contadorElement = document.querySelector('.contador-simple');
    const countdownElement = document.getElementById('countdown');
    
    if (!countdownElement) {
        console.error('Elemento countdown no encontrado');
        return;
    }
    
    function updateSimpleCountdown() {
        try {
            const ahora = new Date().getTime();
            const fechaEventoTime = fechaEvento.getTime();
            const distancia = fechaEventoTime - ahora;
            
            // Si el evento ya pasó
            if (distancia < 0) {
                // Reemplazar todo el contenido con el mensaje de celebración
                countdownElement.innerHTML = `
                    <div class="countdown-expired">
                        <div class="time-unit-large celebration">
                            <div class="celebration-content">
                                <span class="celebration-emoji">🎉</span>
                                <span class="celebration-text">¡Es hoy!</span>
                                <span class="celebration-subtitle">¡Nuestro día especial ha llegado!</span>
                            </div>
                        </div>
                    </div>
                `;
                
                // Agregar clase de celebración
                if (contadorElement) {
                    contadorElement.classList.add('celebration');
                }
                
                // Activar efecto de celebración
                createCelebrationEffect();
                
                // Ocultar el mensaje si existe
                const messageElement = document.querySelector('.countdown-message');
                if (messageElement) {
                    messageElement.style.display = 'none';
                }
                
                // Limpiar intervalo
                if (countdownInterval) {
                    clearInterval(countdownInterval);
                    countdownInterval = null;
                }
                return;
            }
            
            // Calcular días restantes
            const dias = Math.floor(distancia / (1000 * 60 * 60 * 24));
            const daysElement = document.getElementById('days');
            
            if (daysElement) {
                const newValue = String(dias);
                if (previousValues.days !== newValue) {
                    animateNumberChange(daysElement, newValue);
                    previousValues.days = newValue;
                }
            }
        } catch (error) {
            console.error('Error en contador simple:', error);
        }
    }
    
    // Ejecutar primera actualización
    updateSimpleCountdown();
    
    // Actualizar cada minuto (suficiente para contador de días)
    return setInterval(updateSimpleCountdown, 60000);
}

// ============================================
// INICIALIZACIÓN PRINCIPAL
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando contador...');
    
    // Verificar que los datos de la invitación existen
    if (typeof invitacionData === 'undefined') {
        console.error('invitacionData no está definido');
        return;
    }
    
    // Verificar si el contador está habilitado
    if (!invitacionData.mostrarContador) {
        console.log('Contador deshabilitado en la configuración');
        return;
    }
    
    try {
        // Crear fecha del evento (combinar fecha y hora)
        const fechaEvento = new Date(invitacionData.fecha + 'T' + invitacionData.hora);
        
        // Validar que la fecha es válida
        if (isNaN(fechaEvento.getTime())) {
            console.error('Fecha de evento inválida:', invitacionData.fecha, invitacionData.hora);
            return;
        }
        
        console.log('Fecha del evento:', fechaEvento);
        console.log('Tipo de contador:', invitacionData.tipoContador);
        
        // Inicializar el tipo de contador correcto
        if (invitacionData.tipoContador === 'simple') {
            countdownInterval = initSimpleCountdown(fechaEvento);
        } else {
            countdownInterval = initCompleteCountdown(fechaEvento);
        }
        
        console.log('Contador inicializado correctamente');
        
    } catch (error) {
        console.error('Error al inicializar contador:', error);
    }
});

// Limpiar intervalo cuando se cierra la página
window.addEventListener('beforeunload', function() {
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
});

// ============================================
// FIN DEL CÓDIGO
// ============================================
