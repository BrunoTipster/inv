<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Verificação de autenticação
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Buscar dados do usuário
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Buscar investimentos ativos
$stmt = $conn->prepare("
    SELECT i.*, p.name as package_name, p.return_rate 
    FROM investments i 
    JOIN investment_packages p ON i.package_id = p.id 
    WHERE i.user_id = ? AND i.status = 'active'
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$investments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calcular totais
$total_invested = 0;
$total_returns = 0;
foreach ($investments as $inv) {
    $total_invested += $inv['amount'];
    $total_returns += $inv['return_amount'];
}

// Últimas transações
$stmt = $conn->prepare("
    SELECT * FROM transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/investment/css/client.css">
</head>
<body>
    <?php require_once '../includes/client_header.php'; ?>

    <div class="container py-4">
        <!-- Boas-vindas -->
        <div class="row mb-4">
            <div class="col-12">
                <h2>Bem-vindo(a), <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
                <p class="text-muted">
                    Último acesso: <?php echo date('d/m/Y H:i', strtotime($user['last_login'])); ?>
                </p>
            </div>
        </div>

        <!-- Cards de Resumo -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5>Saldo Disponível</h5>
                        <h3>R$ <?php echo number_format($user['balance'], 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5>Total Investido</h5>
                        <h3>R$ <?php echo number_format($total_invested, 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5>Rendimentos</h5>
                        <h3>R$ <?php echo number_format($total_returns, 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5>Investimentos Ativos</h5>
                        <h3><?php echo count($investments); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5>Ações Rápidas</h5>
                        <div class="btn-group">
                            <a href="deposit.php" class="btn btn-primary">
                                <i class="bi bi-cash"></i> Depositar
                            </a>
                            <a href="withdraw.php" class="btn btn-secondary">
                                <i class="bi bi-wallet2"></i> Sacar
                            </a>
                            <a href="invest.php" class="btn btn-success">
                                <i class="bi bi-graph-up"></i> Novo Investimento
                            </a>
                            <a href="support.php" class="btn btn-info">
                                <i class="bi bi-headset"></i> Suporte
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Investimentos Ativos -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Investimentos Ativos</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Pacote</th>
                                        <th>Valor</th>
                                        <th>Retorno</th>
                                        <th>Próximo Pagamento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($investments as $inv): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($inv['package_name']); ?></td>
                                        <td>R$ <?php echo number_format($inv['amount'], 2, ',', '.'); ?></td>
                                        <td>R$ <?php echo number_format($inv['return_amount'], 2, ',', '.'); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($inv['next_return_date'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Últimas Transações -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Últimas Transações</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php foreach ($transactions as $trans): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?php echo ucfirst($trans['type']); ?></strong><br>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y H:i', strtotime($trans['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div>
                                        <span class="text-<?php 
                                            echo $trans['type'] == 'deposit' || $trans['type'] == 'return' ? 'success' : 'danger'; 
                                        ?>">
                                            R$ <?php echo number_format($trans['amount'], 2, ',', '.'); ?>
                                        </span><br>
                                        <span class="badge bg-<?php 
                                            echo $trans['status'] == 'completed' ? 'success' : 
                                                ($trans['status'] == 'pending' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($trans['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="transactions.php" class="btn btn-link mt-3">Ver todas as transações</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>
</body>
</html>