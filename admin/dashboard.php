<?php
/**
 * Dashboard do Administrador
 * 
 * @package InvestSystem
 * @version 1.0.0
 * @author Bruno Tipster
 * @copyright 2025 InvestSystem
 * @last_modified 2025-03-21 00:00:27 UTC
 */

require_once '../includes/config.php';
$page_title = 'Dashboard';

// Estatísticas Gerais
$db = Database::getInstance()->getConnection();

$stats = $db->query("
    SELECT
        (SELECT COUNT(*) FROM users WHERE type = 'client') as total_users,
        (SELECT COUNT(*) FROM users WHERE type = 'client' AND status = 'active') as active_users,
        (SELECT COUNT(*) FROM investments WHERE status = 'active') as active_investments,
        (SELECT SUM(amount) FROM investments WHERE status = 'active') as total_invested,
        (SELECT SUM(amount) FROM transactions WHERE type = 'withdrawal' AND status = 'pending') as pending_withdrawals,
        (SELECT COUNT(*) FROM support_tickets WHERE status = 'open') as open_tickets
")->fetch_assoc();

// Gráfico de Investimentos (últimos 30 dias)
$investment_data = $db->query("
    SELECT 
        DATE(created_at) as date,
        SUM(amount) as total
    FROM investments
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
")->fetch_all(MYSQLI_ASSOC);

// Últimas Transações
$recent_transactions = $db->query("
    SELECT t.*, u.username 
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Novos Usuários
$new_users = $db->query("
    SELECT *
    FROM users
    WHERE type = 'client'
    ORDER BY created_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

require_once '../includes/admin_header.php';
?>

<!-- Cards de Estatísticas -->
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="card-icon text-primary">
                    <i class="bi bi-people"></i>
                </div>
                <div class="card-title">Total de Usuários</div>
                <div class="card-value"><?php echo number_format($stats['total_users']); ?></div>
                <small class="text-muted">
                    <?php echo number_format($stats['active_users']); ?> ativos
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="card-icon text-success">
                    <i class="bi bi-graph-up"></i>
                </div>
                <div class="card-title">Total Investido</div>
                <div class="card-value"><?php echo formatCurrency($stats['total_invested']); ?></div>
                <small class="text-muted">
                    <?php echo number_format($stats['active_investments']); ?> investimentos ativos
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="card-icon text-warning">
                    <i class="bi bi-cash"></i>
                </div>
                <div class="card-title">Saques Pendentes</div>
                <div class="card-value"><?php echo formatCurrency($stats['pending_withdrawals']); ?></div>
                <small class="text-muted">
                    <a href="/admin/approve_withdrawal.php">Ver solicitações</a>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="card-icon text-info">
                    <i class="bi bi-headset"></i>
                </div>
                <div class="card-title">Tickets Abertos</div>
                <div class="card-value"><?php echo number_format($stats['open_tickets']); ?></div>
                <small class="text-muted">
                    <a href="/admin/support_tickets.php">Ver tickets</a>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos -->
<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Investimentos nos Últimos 30 Dias</h5>
                <canvas id="investmentsChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Distribuição por Status</h5>
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Últimas Atividades -->
<div class="row">
    <!-- Últimas Transações -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Últimas Transações</h5>
            </div>
            <div class="table-responsive">
                <table class="table">