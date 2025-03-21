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

// Filtros
$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
$period = filter_input(INPUT_GET, 'period', FILTER_SANITIZE_STRING) ?? '30';

// Construir query base
$query = "
    SELECT * FROM transactions 
    WHERE user_id = ? 
";

// Aplicar filtros
if ($type) {
    $query .= " AND type = '$type'";
}

if ($period !== 'all') {
    $query .= " AND created_at >= DATE_SUB(NOW(), INTERVAL $period DAY)";
}

$query .= " ORDER BY created_at DESC";

// Executar query
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calcular totais
$totals = [
    'deposits' => 0,
    'withdrawals' => 0,
    'investments' => 0,
    'returns' => 0
];

foreach ($transactions as $t) {
    if ($t['status'] == 'completed') {
        $totals[$t['type'] . 's'] += $t['amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Transações</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php require_once '../includes/client_header.php'; ?>

    <div class="container py-4">
        <!-- Totais -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5>Total Depósitos</h5>
                        <h3>R$ <?php echo number_format($totals['deposits'], 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h5>Total Saques</h5>
                        <h3>R$ <?php echo number_format($totals['withdrawals'], 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5>Total Investido</h5>
                        <h3>R$ <?php echo number_format($totals['investments'], 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5>Total Retornos</h5>
                        <h3>R$ <?php echo number_format($totals['returns'], 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <select name="type" class="form-select">
                            <option value="">Todos os Tipos</option>
                            <option value="deposit" <?php echo $type == 'deposit' ? 'selected' : ''; ?>>Depósitos</option>
                            <option value="withdrawal" <?php echo $type == 'withdrawal' ? 'selected' : ''; ?>>Saques</option>
                            <option value="investment" <?php echo $type == 'investment' ? 'selected' : ''; ?>>Investimentos</option>
                            <option value="return" <?php echo $type == 'return' ? 'selected' : ''; ?>>Retornos</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="period" class="form-select">
                            <option value="7" <?php echo $period == '7' ? 'selected' : ''; ?>>Últimos 7 dias</option>
                            <option value="30" <?php echo $period == '30' ? 'selected' : ''; ?>>Últimos 30 dias</option>
                            <option value="90" <?php echo $period == '90' ? 'selected' : ''; ?>>Últimos 90 dias</option>
                            <option value="all" <?php echo $period == 'all' ? 'selected' : ''; ?>>Todo período</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="transactions.php" class="btn btn-secondary">Limpar</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Transações -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Código</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($t['created_at'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $t['type'] == 'deposit' ? 'success' : 
                                            ($t['type'] == 'withdrawal' ? 'danger' : 
                                            ($t['type'] == 'investment' ? 'primary' : 'info')); 
                                    ?>">
                                        <?php echo ucfirst($t['type']); ?>
                                    </span>
                                </td>
                                <td>R$ <?php echo number_format($t['amount'], 2, ',', '.'); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $t['status'] == 'completed' ? 'success' : 
                                            ($t['status'] == 'pending' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($t['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $t['transaction_code']; ?></td>
                                <td><?php echo htmlspecialchars($t['description']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>
</body>
</html>