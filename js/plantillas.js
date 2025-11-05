document.addEventListener('DOMContentLoaded', function() {
    const navButtons = document.querySelectorAll('.nav-btn');
    const templateCards = document.querySelectorAll('.template-card');

    navButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            navButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            const category = this.getAttribute('data-category');
            
            templateCards.forEach(card => {
                if (category === 'todas' || card.getAttribute('data-category') === category) {
                    card.classList.remove('hidden');
                    card.classList.add('visible');
                } else {
                    card.classList.add('hidden');
                    card.classList.remove('visible');
                }
            });
        });
    });
});

document.querySelectorAll('.template-card').forEach(card => {
    const selectPlan = card.querySelector('.select-plan');
    const btnComprar = card.querySelector('.btn-primary.template-btn');

    if (selectPlan && btnComprar) {
        // Actualiza URL inicial
        btnComprar.href = `./checkout.php?plan=${selectPlan.value}&plantilla=${card.querySelector('.open-modal, [data-plantilla-id]')?.getAttribute('data-plantilla-id') || ''}`;

        // Actualiza URL cuando cambie el select
        selectPlan.addEventListener('change', () => {
            btnComprar.href = `./checkout.php?plan=${selectPlan.value}&plantilla=${card.querySelector('.open-modal, [data-plantilla-id]')?.getAttribute('data-plantilla-id') || ''}`;
        });
    }
});