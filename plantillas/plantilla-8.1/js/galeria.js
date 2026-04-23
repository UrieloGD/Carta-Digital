/* Carrusel Interactivo Galería */

document.addEventListener('DOMContentLoaded', function() {
    const carouselTrack = document.getElementById('carouselTrack');
    const prevBtn = document.getElementById('carouselPrev');
    const nextBtn = document.getElementById('carouselNext');
    const dotsContainer = document.getElementById('carouselDots');
    
    if (!carouselTrack) return;

    const slides = carouselTrack.querySelectorAll('.carousel-slide');
    const dots = dotsContainer?.querySelectorAll('.dot');
    
    let currentIndex = 0;

    // Mostrar la primera slide
    showSlide(0);

    // Navegación con botones
    if (prevBtn) {
        prevBtn.addEventListener('click', previousSlide);
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', nextSlide);
    }

    // Navegación con puntos
    if (dots) {
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                goToSlide(index);
            });
        });
    }

    // Hacer imagen clickeable para abrir modal
    carouselTrack.addEventListener('click', function(e) {
        if (e.target.classList.contains('carousel-image')) {
            openImageModal(e.target.src);
        }
    });

    // Navegación con teclado
    document.addEventListener('keydown', function(e) {
        if (carouselTrack.closest('.galeria')) {
            if (e.key === 'ArrowLeft') previousSlide();
            if (e.key === 'ArrowRight') nextSlide();
            if (e.key === 'Escape') closeModal();
        }
    });

    function showSlide(index) {
        // Remover display de todas las slides
        slides.forEach(slide => {
            slide.style.display = 'none';
            slide.classList.remove('hidden');
        });

        // Mostrar slide actual
        slides[index].style.display = 'flex';

        // Actualizar contador de slides
        const slideNumber = document.getElementById('slideNumber');
        if (slideNumber) {
            slideNumber.textContent = index + 1;
        }

        // Actualizar puntos
        if (dots) {
            dots.forEach(dot => dot.classList.remove('active'));
            dots[index].classList.add('active');
        }

        currentIndex = index;
    }

    function nextSlide() {
        let next = (currentIndex + 1) % slides.length;
        showSlide(next);
    }

    function previousSlide() {
        let prev = (currentIndex - 1 + slides.length) % slides.length;
        showSlide(prev);
    }

    function goToSlide(index) {
        showSlide(index);
    }

    // Modal para ver imagen a pantalla completa
    function openImageModal(src) {
        const modal = document.createElement('div');
        modal.className = 'image-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 20, 33, 0.98);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease-out;
        `;

        const imgContainer = document.createElement('div');
        imgContainer.style.cssText = `
            position: relative;
            width: 90%;
            height: 90%;
            display: flex;
            align-items: center;
            justify-content: center;
        `;

        const img = document.createElement('img');
        img.src = src;
        img.style.cssText = `
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 16px;
            border: 4px solid #00d4ff;
            box-shadow: 0 0 80px rgba(0, 212, 255, 0.6),
                        0 0 150px rgba(157, 78, 221, 0.4),
                        inset 0 0 50px rgba(0, 212, 255, 0.1);
            animation: zoomIn 0.4s ease-out;
        `;

        // Botón cerrar
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '✕';
        closeBtn.style.cssText = `
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, rgba(157, 78, 221, 0.8), rgba(0, 212, 255, 0.6));
            color: white;
            border: 2px solid #00d4ff;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.8rem;
            font-weight: bold;
            transition: all 0.3s ease;
            z-index: 2001;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            box-shadow: 0 0 30px rgba(0, 212, 255, 0.5);
        `;

        closeBtn.addEventListener('mouseenter', function() {
            this.style.background = 'linear-gradient(135deg, rgba(0, 212, 255, 0.9), rgba(0, 212, 255, 0.8))';
            this.style.boxShadow = '0 0 50px rgba(0, 212, 255, 0.8)';
            this.style.transform = 'scale(1.1)';
        });

        closeBtn.addEventListener('mouseleave', function() {
            this.style.background = 'linear-gradient(135deg, rgba(157, 78, 221, 0.8), rgba(0, 212, 255, 0.6))';
            this.style.boxShadow = '0 0 30px rgba(0, 212, 255, 0.5)';
            this.style.transform = 'scale(1)';
        });

        closeBtn.addEventListener('click', closeModal);

        // Cerrar al clickear en el fondo
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        function closeModal() {
            modal.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => modal.remove(), 300);
        }

        // Teclado para cerrar
        const keyListener = function(e) {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', keyListener);
            }
        };
        document.addEventListener('keydown', keyListener);

        imgContainer.appendChild(img);
        imgContainer.appendChild(closeBtn);
        modal.appendChild(imgContainer);
        document.body.appendChild(modal);
    }

    // Agregar estilos de animación
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }

        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .image-modal {
            animation: fadeIn 0.3s ease-out;
        }
    `;
    document.head.appendChild(style);
});
