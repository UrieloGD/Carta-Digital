/**
 * MÓDULO CHECKOUT - Gestión de pagos con Stripe y Mercado Pago
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
        this.setupEventListeners();
        this.initStripe();
    }
    
    /**
     * Inicializar Stripe y crear elemento de tarjeta
     */
    initStripe() {
        try {
            this.stripe = Stripe(this.config.stripeKey);
            const elements = this.stripe.elements();
            
            const cardElementDiv = document.getElementById('card-element');
            if (!cardElementDiv) {
                console.warn('Elemento card-element no encontrado');
                return;
            }
            
            this.cardElement = elements.create('card', {
                hidePostalCode: true,
                style: this.getCardStyle()
            });
            
            this.cardElement.mount('#card-element');
            this.setupCardErrorHandling();
        } catch (error) {
            console.error('Error inicializando Stripe:', error);
        }
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
        if (!this.cardElement) return;
        
        this.cardElement.on('change', (event) => {
            if (event.error) {
                this.showError(event.error.message);
            } else {
                this.clearError();
            }
        });
    }
    
    /**
     * Configurar listeners de eventos - MÉTODO ÚNICO
     */
    setupEventListeners() {
        // Listener para envío del formulario
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }
        
        // Listener para cambiar método de pago
        const radioButtons = document.querySelectorAll('input[name="payment_method"]');
        
        if (radioButtons.length > 0) {
            radioButtons.forEach(radio => {
                radio.addEventListener('change', (e) => {
                    this.handlePaymentMethodChange(e.target.value);
                });
            });
        }
    }
    
    /**
     * Manejar cambio de método de pago
     */
    handlePaymentMethodChange(method) {
        const stripeSection = document.getElementById('stripe-section');
        const mpSection = document.getElementById('mercadopago-section');
        const submitText = document.getElementById('submit-text');
        
        if (method === 'stripe') {
            if (stripeSection) stripeSection.style.display = 'block';
            if (mpSection) mpSection.style.display = 'none';
            if (submitText) submitText.textContent = `Pagar $${this.config.precio} MXN`;
            if (!this.cardElement) this.initStripe();
        } else if (method === 'mercadopago') {
            if (stripeSection) stripeSection.style.display = 'none';
            if (mpSection) mpSection.style.display = 'block';
            if (submitText) submitText.textContent = `Continuar a Mercado Pago`;
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
            const paymentMethodRadio = document.querySelector('input[name="payment_method"]:checked');
            const paymentMethod = paymentMethodRadio ? paymentMethodRadio.value : 'stripe';
            
            if (paymentMethod === 'mercadopago') {
                await this.processMercadoPagoPayment(formData);
            } else {
                await this.processPayment(formData);
            }
        } catch (error) {
            console.error('Error:', error);
            this.showError(error.message);
            this.setLoading(false);
            this.isProcessing = false;
        }
    }
    
    /**
     * Recopilar datos del formulario
     */
    collectFormData() {
        const nombreNovio = document.getElementById('nombre_novio');
        const nombreNovia = document.getElementById('nombre_novia');
        const nombre = document.getElementById('nombre');
        const apellido = document.getElementById('apellido');
        const fechaEvento = document.getElementById('fecha_evento');
        const horaEvento = document.getElementById('hora_evento');
        const email = document.getElementById('email');
        const telefono = document.getElementById('telefono');
        
        // Validar que existan los elementos
        if (!nombreNovio || !nombreNovia || !nombre || !apellido || !email || !telefono) {
            throw new Error('Faltan campos en el formulario');
        }
        
        const nombreNovioValue = nombreNovio.value.trim();
        const nombreNovaValue = nombreNovia.value.trim();
        
        return {
            nombre: nombre.value.trim(),
            apellido: apellido.value.trim(),
            nombre_novio: nombreNovioValue,
            nombre_novia: nombreNovaValue,
            nombres_novios: `${nombreNovaValue} & ${nombreNovioValue}`,
            fecha_evento: fechaEvento ? fechaEvento.value : '',
            hora_evento: horaEvento ? horaEvento.value : '',
            email: email.value.trim(),
            telefono: telefono.value.trim(),
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
     * Procesar pago con Mercado Pago
     */
    async processMercadoPagoPayment(formData) {
        const response = await fetch('./api/register_mercadopago.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (!response.ok || data.error) {
            throw new Error(data.error || 'Error al procesar el pago');
        }
        
        // Redirigir a Mercado Pago
        if (data.preference_url) {
            window.location.href = data.preference_url;
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
        if (!this.stripe || !this.cardElement) {
            throw new Error('Stripe no está inicializado correctamente');
        }
        
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
        if (this.submitBtn) this.submitBtn.disabled = isLoading;
        if (this.submitText) this.submitText.style.display = isLoading ? 'none' : 'inline';
        if (this.submitLoading) this.submitLoading.style.display = isLoading ? 'inline' : 'none';
    }
}

/**
 * INICIALIZACIÓN
 */
document.addEventListener('DOMContentLoaded', () => {
    if (typeof CHECKOUT_CONFIG !== 'undefined') {
        new CheckoutManager(CHECKOUT_CONFIG);
    } else {
        console.warn('CHECKOUT_CONFIG no está definido');
    }
});
