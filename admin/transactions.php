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

// Filtros
$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING);
$date_from = filter_input(INPUT_GET, 'date_from', FILTER_SANITIZE_STRING);
$date_to = filter_input(INPUT_GET, 'date_to', FILTER_SANITIZE_STRING);

// Construir query
$query = "SELECT t.*, u.username, u.email 
          FROM transactions t 
          JOIN users u ON t.user_id = u.id 
          WHERE 1=1";

$params = [];
$types = "";

if ($type) {
    $query .= " AND t.type = ?";
    $params[] = $type;
    $types .= "s";
}

if ($status) {
    $query .= " AND t.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($date_from) {
    $query .= " AND DATE(t.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to) {
    $query .= " AND DATE(t.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$query .= " ORDER BY t.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
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
    <title>Gerenciar Transações - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/investment/css/admin.css">
</head>
<body>
    <?php require_once '../includes/admin_header.php'; ?>

    <div class="container-fluid py-4">
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
                        <h5>Total Investimentos</h5>
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
                    <div class="col-md-3">
                        <label class="form-label">Tipo</label>
                        <select name="type" class="form-select">
                            <option value="">Todos</option>
                            <option value="deposit" <?php echo $type == 'deposit' ? 'selected' : ''; ?>>Depósito</option>
                            <option value="withdrawal" <?php echo $type == 'withdrawal' ? 'selected' : ''; ?>>Saque</option>
                            <option value="investment" <?php echo $type == 'investment' ? 'selected' : ''; ?>>Investimento</option>
                            <option value="return" <?php echo $type == 'return' ? 'selected' : ''; ?>>Retorno</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Todos</option>
                            <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pendente</option>
                            <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Concluído</option>
                            <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data Inicial</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data Final</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="col-12">
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
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuário</th>
                                <th>Tipo</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Código</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?php echo $t['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($t['username']); ?><br>
                                    <small class="text-muted"><?php echo $t['email']; ?></small>
                                </td>
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
                                <td><?php echo date('d/m/Y H:i', strtotime($t['created_at'])); ?></td>
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