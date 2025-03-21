/**
 * Scripts da Área do Cliente
 * 
 * @package InvestSystem
 * @version 1.0.0
 * @author Bruno Tipster
 * @copyright 2025 InvestSystem
 * @last_modified 2025-03-20 23:52:10 UTC
 */

// Namespace global para funções do cliente
const ClientArea = {
    // Inicialização
    init: function() {
        this.initCharts();
        this.setupEventListeners();
        this.initInvestmentCalculator();
        this.startBalanceUpdates();
    },

    // Configuração dos gráficos
    initCharts: function() {
        // Gráfico de rendimentos
        if (document.getElementById('returnsChart')) {
            new Chart(document.getElementById('returnsChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: this.last30Days(),
                    datasets: [{
                        label: 'Rendimentos',
                        data: [],
                        borderColor: '#28a745',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
    },

    // Setup de event listeners
    setupEventListeners: function() {
        // Formulários
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', this.handleFormSubmit.bind(this));
        });

        // Seleção de pacotes
        document.querySelectorAll('.package-card').forEach(card => {
            card.addEventListener('click', this.handlePackageSelection.bind(this));
        });

        // Upload de documentos
        document.querySelectorAll('.document-upload').forEach(input => {
            input.addEventListener('change', this.handleDocumentUpload.bind(this));
        });
    },

    // Calculadora de investimentos
    initInvestmentCalculator: function() {
        const calculator = document.getElementById('investmentCalculator');
        if (!calculator) return;

        const amount = calculator.querySelector('#amount');
        const rate = calculator.querySelector('#rate');
        const period = calculator.querySelector('#period');
        const result = calculator.querySelector('#result');

        [amount, rate, period].forEach(input => {
            input.addEventListener('input', () => {
                const value = parseFloat(amount.value) || 0;
                const rateValue = parseFloat(rate.value) || 0;
                const periodValue = parseInt(period.value) || 1;

                const returns = value * (rateValue / 100) * periodValue;
                result.textContent = this.formatCurrency(returns);
            });
        });
    },

    // Atualização do saldo em tempo real
    startBalanceUpdates: function() {
        const ws = new WebSocket('wss://investsystem.com/ws/client');
        
        ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.updateBalance(data.balance);
            this.updateInvestments(data.investments);
        };

        ws.onerror = (error) => {
            console.error('WebSocket Error:', error);
        };
    },

    // Manipulação de formulários
    handleFormSubmit: async function(e) {
        e.preventDefault();
        const form = e.target;
        const submitButton = form.querySelector('[type="submit"]');
        
        try {
            submitButton.disabled = true;
            this.showLoading();

            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: form.method,
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('success', result.message);
                if (result.redirect) {
                    window.location.href = result.redirect;
                }
            } else {
                this.showNotification('error', result.error);
            }
        } catch (error) {
            this.showNotification('error', 'Erro ao processar requisição');
            console.error(error);
        } finally {
            submitButton.disabled = false;
            this.hideLoading();
        }
    },

    // Seleção de pacote de investimento
    handlePackageSelection: function(e) {
        const card = e.currentTarget;
        document.querySelectorAll('.package-card').forEach(c => {
            c.classList.remove('selected');
        });
        card.classList.add('selected');

        const packageId = card.dataset.id;
        document.getElementById('selectedPackage').value = packageId;

        this.updateInvestmentSimulation(packageId);
    },

    // Upload de documentos
    handleDocumentUpload: async function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            this.showNotification('error', 'Arquivo muito grande. Máximo 5MB.');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('document', file);

            const response = await fetch('/client/upload_document.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('success', 'Documento enviado com sucesso');
                this.updateDocumentsList();
            } else {
                this.showNotification('error', result.error);
            }
        } catch (error) {
            this.showNotification('error', 'Erro ao enviar documento');
            console.error(error);
        }
    },

    // Utilitários
    updateBalance: function(balance) {
        const balanceElement = document.getElementById('userBalance');
        if (balanceElement) {
            balanceElement.textContent = this.formatCurrency(balance);
        }
    },

    updateInvestments: function(investments) {
        const container = document.getElementById('investmentsList');
        if (!container) return;

        investments.forEach(inv => {
            const element = container.querySelector(`[data-investment="${inv.id}"]`);
            if (element) {
                element.querySelector('.return-amount').textContent = 
                    this.formatCurrency(inv.return_amount);
                element.querySelector('.next-return').textContent = 
                    new Date(inv.next_return_date).toLocaleDateString('pt-BR');
            }
        });
    },

    formatCurrency: function(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    },

    showLoading: function() {
        const loader = document.createElement('div');
        loader.className = 'loading-overlay';
        loader.innerHTML = '<div class="loading-spinner"></div>';
        document.body.appendChild(loader);
    },

    hideLoading: function() {
        document.querySelector('.loading-overlay')?.remove();
    },

    showNotification: function(type, message) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    },

    last30Days: function() {
        const dates = [];
        for (let i = 29; i >= 0; i--) {
            const d = new Date();
            d.setDate(d.getDate() - i);
            dates.push(d.toLocaleDateString('pt-BR'));
        }
        return dates;
    }
};

// Inicialização quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    ClientArea.init();
});