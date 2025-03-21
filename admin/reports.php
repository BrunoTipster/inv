<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Estatísticas gerais
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE type = 'client'")->fetch_assoc()['count'],
    'active_investments' => $conn->query("SELECT COUNT(*) as count FROM investments WHERE status = 'active'")->fetch_assoc()['count'],
    'total_invested' => $conn->query("SELECT SUM(amount) as total FROM investments WHERE status = 'active'")->fetch_assoc()['total'] ?? 0,
    'pending_withdrawals' => $conn->query("SELECT COUNT(*) as count FROM transactions WHERE type = 'withdrawal' AND status = 'pending'")->fetch_assoc()['count']
];

// Dados para gráficos
$monthly_data = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        SUM(CASE WHEN type = 'deposit' AND status = 'completed' THEN amount ELSE 0 END) as deposits,
        SUM(CASE WHEN type = 'withdrawal' AND status = 'completed' THEN amount ELSE 0 END) as withdrawals,
        SUM(CASE WHEN type = 'investment' AND status = 'completed' THEN amount ELSE 0 END) as investments,
        COUNT(DISTINCT CASE WHEN type = 'deposit' THEN user_id END) as active_users
    FROM transactions
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
")->fetch_all(MYSQLI_ASSOC);

// Top investidores
$top_investors = $conn->query("
    SELECT 
        u.username,
        COUNT(i.id) as total_investments,
        SUM(i.amount) as total_amount
    FROM users u
    JOIN investments i ON u.id = i.user_id
    WHERE i.status = 'active'
    GROUP BY u.id
    ORDER BY total_amount DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php require_once '../includes/admin_header.php'; ?>

    <div class="container-fluid py-4">
        <!-- Cards de Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5>Total de Usuários</h5>
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5>Investimentos Ativos</h5>
                        <h3><?php echo number_format($stats['active_investments']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5>Total Investido</h5>
                        <h3>R$ <?php echo number_format($stats['total_invested'], 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5>Saques Pendentes</h5>
                        <h3><?php echo number_format($stats['pending_withdrawals']); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <!-- Gráfico de Movimentações -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Movimentações dos Últimos 6 Meses</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="transactionsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Investidores -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Top 5 Investidores</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Usuário</th>
                                        <th>Investimentos</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_investors as $investor): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($investor['username']); ?></td>
                                        <td><?php echo $investor['total_investments']; ?></td>
                                        <td>R$ <?php echo number_format($investor['total_amount'], 2, ',', '.'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões de Exportação -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5>Exportar Relatórios</h5>
                        <button class="btn btn-primary" onclick="exportTransactions()">
                            Transações
                        </button>
                        <button class="btn btn-success" onclick="exportInvestments()">
                            Investimentos
                        </button>
                        <button class="btn btn-info" onclick="exportUsers()">
                            Usuários
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>

    <script>
    // Dados para o gráfico
    const monthlyData = <?php echo json_encode($monthly_data); ?>;
    
    // Configuração do gráfico
    const ctx = document.getElementById('transactionsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyData.map(d => {
                const [year, month] = d.month.split('-');
                return `${month}/${year}`;
            }),
            datasets: [{
                label: 'Depósitos',
                data: monthlyData.map(d => d.deposits),
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }, {
                label: 'Saques',
                data: monthlyData.map(d => d.withdrawals),
                borderColor: 'rgb(255, 99, 132)',
                tension: 0.1
            }, {
                label: 'Investimentos',
                data: monthlyData.map(d => d.investments),
                borderColor: 'rgb(54, 162, 235)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Movimentações Financeiras'
                }
            }
        }
    });

    // Funções de exportação
    function exportTransactions() {
        window.location.href = '../api/transactions.php?action=export';
    }

    function exportInvestments() {
        window.location.href = '../api/investments.php?action=export';
    }

    function exportUsers() {
        window.location.href = '../api/users.php?action=export';
    }
    </script>
</body>
</html>