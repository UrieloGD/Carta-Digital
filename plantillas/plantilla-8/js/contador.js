/* Contador Regresivo */

document.addEventListener('DOMContentLoaded', function() {
    const contadorElement = document.getElementById('contador-timer');
    
    if (!contadorElement || !FECHA_EVENTO) return;

    function actualizarContador() {
        const ahora = new Date().getTime();
        const fechaEvento = new Date(FECHA_EVENTO).getTime();
        const diferencia = fechaEvento - ahora;

        if (diferencia <= 0) {
            document.getElementById('dias').textContent = '0';
            document.getElementById('horas').textContent = '0';
            document.getElementById('minutos').textContent = '0';
            document.getElementById('segundos').textContent = '0';
            return;
        }

        const dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
        const horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
        const segundos = Math.floor((diferencia % (1000 * 60)) / 1000);

        // Actualizar con animación
        actualizarNumero('dias', dias);
        actualizarNumero('horas', horas);
        actualizarNumero('minutos', minutos);
        actualizarNumero('segundos', segundos);
    }

    function actualizarNumero(id, valor) {
        const elemento = document.getElementById(id);
        if (elemento && elemento.textContent !== String(valor)) {
            elemento.style.animation = 'none';
            setTimeout(() => {
                elemento.style.animation = 'imagePulse 0.4s ease-out';
                elemento.textContent = String(valor).padStart(2, '0');
            }, 10);
        }
    }

    // Actualizar cada segundo
    actualizarContador();
    setInterval(actualizarContador, 1000);
});
