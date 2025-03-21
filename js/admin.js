/**
 * Scripts do Painel Administrativo
 * 
 * @package InvestSystem
 * @version 1.0.0
 * @author Bruno Tipster
 * @copyright 2025 InvestSystem
 * @last_modified 2025-03-20 23:52:10 UTC
 */

// Namespace global para funções admin
const AdminPanel = {
    // Inicialização
    init: function() {
        this.initCharts();
        this.initDataTables();
        this.setupEventListeners();
        this.initRealTimeUpdates();
    },

    // Configuração dos gráficos
    initCharts: function() {
        // Gráfico de Investimentos
        if (document.getElementById('investmentsChart')) {
            new Chart(document.getElementById('investmentsChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: this.last30Days(),
                    datasets: [{
                        label: 'Investimentos',
                        data: [],
                        borderColor: '#1a237e',
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

        // Gráfico de Transações
        if (document.getElementById('transactionsChart')) {
            new Chart(document.getElementById('transactionsChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Depósitos', 'Saques', 'Investimentos', 'Retornos'],
                    datasets: [{
                        data: [],
                        backgroundColor: ['#28a745', '#dc3545', '#1a237e', '#ffc107']
                    }]
                }
            });
        }
    },

    // Inicialização das tabelas de dados
    initDataTables: function() {
        $('.admin-table').DataTable({
            language: {
                url: '/js/datatables/pt-BR.json'
            },
            pageLength: 25,
            responsive: true,
            order: [[0, 'desc']],
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });
    },

    // Setup de event listeners
    setupEventListeners: function() {
        // Toggle da sidebar
        document.querySelector('.sidebar-toggle')?.addEventListener('click', () => {
            document.querySelector('.admin-sidebar').classList.toggle('collapsed');
        });

        // Formulários
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', this.handleFormSubmit.bind(this));
        });

        // Botões de ação
        document.querySelectorAll('[data-action]').forEach(button => {
            button.addEventListener('click', this.handleAction.bind(this));
        });
    },

    // Atualizações em tempo real
    initRealTimeUpdates: function() {
        const ws = new WebSocket('wss://investsystem.com/ws/admin');
        
        ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleRealTimeUpdate(data);
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

    // Manipulação de ações
    handleAction: async function(e) {
        const button = e.target;
        const action = button.dataset.action;
        const id = button.dataset.id;

        if (!confirm('Confirma esta ação?')) return;

        try {
            button.disabled = true;
            this.showLoading();

            const response = await fetch(`/admin/actions.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action, id })
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('success', result.message);
                if (result.reload) {
                    location.reload();
                }
            } else {
                this.showNotification('error', result.error);
            }
        } catch (error) {
            this.showNotification('error', 'Erro ao executar ação');
            console.error(error);
        } finally {
            button.disabled = false;
            this.hideLoading();
        }
    },

    // Manipulação de atualizações em tempo real
    handleRealTimeUpdate: function(data) {
        switch (data.type) {
            case 'new_transaction':
                this.updateTransactionsList(data.transaction);
                break;
            case 'new_user':
                this.updateUserCount(data.count);
                break;
            case 'investment_status':
                this.updateInvestmentStatus(data.investment);
                break;
        }

        // Atualizar badges de notificação
        this.updateNotificationBadges(data.notifications);
    },

    // Utilitários
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
        notification.className = `admin-notification ${type}`;
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
    },

    formatCurrency: function(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    },

    // Exportação de dados
    exportData: async function(type) {
        try {
            this.showLoading();
            const response = await fetch(`/admin/export.php?type=${type}`);
            const blob = await response.blob();
            
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${type}_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();
        } catch (error) {
            this.showNotification('error', 'Erro ao exportar dados');
            console.error(error);
        } finally {
            this.hideLoading();
        }
    }
};

// Inicialização quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    AdminPanel.init();
});