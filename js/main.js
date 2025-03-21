/**
 * Scripts Globais
 * 
 * @package InvestSystem
 * @version 1.0.0
 * @author Bruno Tipster
 * @copyright 2025 InvestSystem
 * @last_modified 2025-03-20 23:52:10 UTC
 */

// Namespace global
const InvestSystem = {
    // Configurações
    config: {
        apiUrl: 'https://investsystem.com/api',
        debug: false,
        currency: 'BRL',
        dateFormat: 'pt-BR'
    },

    // Inicialização
    init: function() {
        this.setupEventListeners();
        this.initializeComponents();
        this.setupFormValidation();
        this.initializeTooltips();
    },

    // Setup de event listeners globais
    setupEventListeners: function() {
        // Scroll suave para links de âncora
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        // Manipulação do menu mobile
        const mobileMenuButton = document.querySelector('.mobile-menu-toggle');
        if (mobileMenuButton) {
            mobileMenuButton.addEventListener('click', () => {
                document.querySelector('.mobile-menu').classList.toggle('active');
            });
        }

        // Fechar modais com Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    this.closeModal(modal);
                });
            }
        });
    },

    // Inicialização de componentes
    initializeComponents: function() {
        // Dropdowns
        document.querySelectorAll('.dropdown-toggle').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const dropdown = button.nextElementSibling;
                dropdown.classList.toggle('show');

                // Fechar outros dropdowns
                document.querySelectorAll('.dropdown-menu.show').forEach(d => {
                    if (d !== dropdown) {
                        d.classList.remove('show');
                    }
                });
            });
        });

        // Tabs
        document.querySelectorAll('.tab-control').forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                const target = tab.getAttribute('data-target');
                
                // Desativar outras tabs
                tab.parentElement.querySelectorAll('.tab-control').forEach(t => {
                    t.classList.remove('active');
                });
                tab.classList.add('active');

                // Mostrar conteúdo
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.querySelector(target).classList.add('active');
            });
        });
    },

    // Validação de formulários
    setupFormValidation: function() {
        document.querySelectorAll('form[data-validate]').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });

            // Validação em tempo real
            form.querySelectorAll('input, select, textarea').forEach(field => {
                field.addEventListener('blur', () => {
                    this.validateField(field);
                });
            });
        });
    },

    // Tooltips
    initializeTooltips: function() {
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = element.getAttribute('data-tooltip');
                
                const rect = element.getBoundingClientRect();
                tooltip.style.top = rect.top - 30 + 'px';
                tooltip.style.left = rect.left + (rect.width / 2) + 'px';
                
                document.body.appendChild(tooltip);
            });

            element.addEventListener('mouseleave', () => {
                document.querySelector('.tooltip')?.remove();
            });
        });
    },

    // Validação de formulário
    validateForm: function(form) {
        let valid = true;
        form.querySelectorAll('[required]').forEach(field => {
            if (!this.validateField(field)) {
                valid = false;
            }
        });
        return valid;
    },

    // Validação de campo
    validateField: function(field) {
        const value = field.value.trim();
        let valid = true;
        let message = '';

        switch (field.type) {
            case 'email':
                valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                message = 'Email inválido';
                break;

            case 'tel':
                valid = /^\d{10,11}$/.test(value.replace(/\D/g, ''));
                message = 'Telefone inválido';
                break;

            case 'password':
                valid = value.length >= 6;
                message = 'Senha deve ter no mínimo 6 caracteres';
                break;

            default:
                valid = value.length > 0;
                message = 'Campo obrigatório';
        }

        this.setFieldValidationState(field, valid, message);
        return valid;
    },

    // Estado de validação do campo
    setFieldValidationState: function(field, valid, message) {
        field.classList.toggle('is-invalid', !valid);
        field.classList.toggle('is-valid', valid);

        let feedback = field.nextElementSibling;
        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            field.parentNode.insertBefore(feedback, field.nextSibling);
        }
        feedback.textContent = valid ? '' : message;
    },

    // Manipulação de modais
    openModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';

        modal.querySelector('.modal-close')?.addEventListener('click', () => {
            this.closeModal(modal);
        });
    },

    closeModal: function(modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    },

    // Utilitários
    formatCurrency: function(value) {
        return new Intl.NumberFormat(this.config.dateFormat, {
            style: 'currency',
            currency: this.config.currency
        }).format(value);
    },

    formatDate: function(date) {
        return new Date(date).toLocaleDateString(this.config.dateFormat);
    },

    debug: function(...args) {
        if (this.config.debug) {
            console.log(...args);
        }
    }
};

// Inicialização quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    InvestSystem.init();
});