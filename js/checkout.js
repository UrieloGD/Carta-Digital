/**
 * MÓDULO CHECKOUT - Gestión de pagos con Stripe
 * Maneja el flujo completo de compra y pago
 */

class CheckoutManager {
    constructor(config) {
        this.config = config;
        this.stripe = null;
        this.cardElement = null;
        this.isProcessing = false;
        
        // Elementos del DOM
        this.form = document.getElementById('checkout-form');
        this.submitBtn = document.getElementById('submit-button');
        this.submitText = document.getElementById('submit-text');
        this.submitLoading = document.getElementById('submit-loading');
        this.cardErrors = document.getElementById('card-errors');
        
        this.init();
    }
    
    /**
     * Inicializar el módulo
     */
    init() {
        this.initStripe();
        this.setupEventListeners();
    }
    
    /**
     * Inicializar Stripe y crear elemento de tarjeta
     */
    initStripe() {
        this.stripe = Stripe(this.config.stripeKey);
        const elements = this.stripe.elements();
        
        this.cardElement = elements.create('card', {
            hidePostalCode: true,
            style: this.getCardStyle()
        });
        
        this.cardElement.mount('#card-element');
        this.setupCardErrorHandling();
    }
    
    /**
     * Obtener estilos para el elemento de tarjeta
     */
    getCardStyle() {
        return {
            base: {
                fontSize: '16px',
                color: '#424770',
                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                '::placeholder': {
                    color: '#aab7c4',
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        };
    }
    
    /**
     * Configurar manejo de errores en tiempo real del elemento de tarjeta
     */
    setupCardErrorHandling() {
        this.cardElement.on('change', (event) => {
            if (event.error) {
                this.showError(event.error.message);
            } else {
                this.clearError();
            }
        });
    }
    
    /**
     * Configurar listeners de eventos
     */
    setupEventListeners() {
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }
    }
    
    /**
     * Manejar envío del formulario
     */
    async handleFormSubmit(event) {
        event.preventDefault();
        
        if (this.isProcessing) return;
        
        this.isProcessing = true;
        this.setLoading(true);
        this.clearError();
        
        try {
            const formData = this.collectFormData();
            await this.processPayment(formData);
        } catch (error) {
            this.showError(error.message);
            this.setLoading(false);
            this.isProcessing = false;
        }
    }
    
    /**
     * Recopilar datos del formulario
     */
    collectFormData() {
        const nombreNovio = document.getElementById('nombre_novio').value.trim();
        const nombreNovia = document.getElementById('nombre_novia').value.trim();
        
        return {
            nombre: document.getElementById('nombre').value.trim(),
            apellido: document.getElementById('apellido').value.trim(),
            nombre_novio: nombreNovio,
            nombre_novia: nombreNovia,
            nombres_novios: `${nombreNovia} & ${nombreNovio}`,
            fecha_evento: document.getElementById('fecha_evento').value,
            hora_evento: document.getElementById('hora_evento').value,
            email: document.getElementById('email').value.trim(),
            telefono: document.getElementById('telefono').value.trim(),
            plan: this.config.plan,
            plantilla_id: this.config.plantillaId
        };
    }
    
    /**
     * Procesar pago con Stripe
     */
    async processPayment(formData) {
        // Paso 1: Registrar cliente y crear payment intent
        const registerData = await this.registerAndCreatePaymentIntent(formData);
        
        if (!registerData.success) {
            throw new Error(registerData.error || 'Error al procesar el registro');
        }
        
        // Paso 2: Confirmar pago con Stripe
        const paymentResult = await this.confirmCardPayment(
            registerData.clientSecret,
            formData,
            registerData.pedido_id
        );
        
        if (paymentResult.success) {
            window.location.href = paymentResult.redirectUrl;
        }
    }
    
    /**
     * Registrar cliente y crear payment intent
     */
    async registerAndCreatePaymentIntent(formData) {
        const response = await fetch('./api/register_and_pay.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (!response.ok || data.error) {
            throw new Error(data.error || 'Error al procesar el registro');
        }
        
        return {
            success: true,
            clientSecret: data.clientSecret,
            pedido_id: data.pedido_id
        };
    }
    
    /**
     * Confirmar pago con Stripe
     */
    async confirmCardPayment(clientSecret, formData, pedidoId) {
        const { error, paymentIntent } = await this.stripe.confirmCardPayment(clientSecret, {
            payment_method: {
                card: this.cardElement,
                billing_details: {
                    name: `${formData.nombre} ${formData.apellido}`,
                    email: formData.email
                }
            }
        });
        
        if (error) {
            throw new Error(error.message);
        }
        
        if (paymentIntent.status === 'succeeded') {
            return {
                success: true,
                redirectUrl: `./payment_success.php?pedido_id=${pedidoId}&payment_intent=${paymentIntent.id}`
            };
        }
        
        throw new Error('El pago no fue procesado correctamente');
    }
    
    /**
     * Mostrar mensaje de error
     */
    showError(message) {
        if (this.cardErrors) {
            this.cardErrors.textContent = message;
            this.cardErrors.style.display = 'block';
        }
    }
    
    /**
     * Limpiar mensaje de error
     */
    clearError() {
        if (this.cardErrors) {
            this.cardErrors.textContent = '';
            this.cardErrors.style.display = 'none';
        }
    }
    
    /**
     * Mostrar/ocultar estado de carga
     */
    setLoading(isLoading) {
        this.submitBtn.disabled = isLoading;
        this.submitText.style.display = isLoading ? 'none' : 'inline';
        this.submitLoading.style.display = isLoading ? 'inline' : 'none';
    }
}

/**
 * INICIALIZACIÓN
 */
document.addEventListener('DOMContentLoaded', () => {
    if (typeof CHECKOUT_CONFIG !== 'undefined') {
        new CheckoutManager(CHECKOUT_CONFIG);
    }
});
