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

// Buscar todos os investimentos do usuário
$stmt = $conn->prepare("
    SELECT i.*, p.name as package_name, p.return_rate, p.period_days
    FROM investments i 
    JOIN investment_packages p ON i.package_id = p.id 
    WHERE i.user_id = ?
    ORDER BY i.status = 'active' DESC, i.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$investments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calcular totais
$totals = [
    'invested' => 0,
    'returns' => 0,
    'active' => 0,
    'completed' => 0
];

foreach ($investments as $inv) {
    if ($inv['status'] == 'active') {
        $totals['active'] += $inv['amount'];
        $totals['invested'] += $inv['amount'];
    } elseif ($inv['status'] == 'completed') {
        $totals['completed'] += $inv['amount'];
        $totals['invested'] += $inv['amount'];
    }
    $totals['returns'] += $inv['return_amount'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Investimentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/investment/css/client.css">
</head>
<body>
    <?php require_once '../includes/client_header.php'; ?>

    <div class="container py-4">
        <!-- Cards de Resumo -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5>Total Investido</h5>
                        <h3>R$ <?php echo number_format($totals['invested'], 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5>Investimentos Ativos</h5>
                        <h3>R$ <?php echo number_format($totals['active'], 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5>Total de Retornos</h5>
                        <h3>R$ <?php echo number_format($totals['returns'], 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5>Investimentos Finalizados</h5>
                        <h3>R$ <?php echo number_format($totals['completed'], 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Investimentos -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Meus Investimentos</h4>
                <a href="packages.php" class="btn btn-primary">Novo Investimento</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pacote</th>
                                <th>Valor</th>
                                <th>Taxa</th>
                                <th>Retorno</th>
                                <th>Próximo Pagamento</th>
                                <th>Status</th>
                                <th>Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($investments as $inv): ?>
                            <tr>
                                <td>#<?php echo $inv['id']; ?></td>
                                <td><?php echo htmlspecialchars($inv['package_name']); ?></td>
                                <td>R$ <?php echo number_format($inv['amount'], 2, ',', '.'); ?></td>
                                <td><?php echo $inv['return_rate']; ?>% / <?php echo $inv['period_days']; ?> dias</td>
                                <td>R$ <?php echo number_format($inv['return_amount'], 2, ',', '.'); ?></td>
                                <td>
                                    <?php if($inv['status'] == 'active'): ?>
                                        <?php echo date('d/m/Y', strtotime($inv['next_return_date'])); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $inv['status'] == 'active' ? 'success' : 
                                            ($inv['status'] == 'completed' ? 'info' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($inv['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="showInvestmentDetails(<?php echo $inv['id']; ?>)">
                                        Ver Detalhes
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes do Investimento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="investmentDetails">
                    Carregando...
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>

    <script>
    function showInvestmentDetails(id) {
        const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
        const detailsContainer = document.getElementById('investmentDetails');
        
        // Buscar detalhes via AJAX
        fetch(`/investment/api/investments.php?action=details&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    detailsContainer.innerHTML = `
                        <div class="mb-3">
                            <strong>Data de Início:</strong> ${data.investment.start_date}
                        </div>
                        <div class="mb-3">
                            <strong>Próximo Rendimento:</strong> ${data.investment.next_return_date}
                        </div>
                        <div class="mb-3">
                            <strong>Total de Rendimentos:</strong> R$ ${data.investment.total_returns}
                        </div>
                        <div class="mb-3">
                            <strong>Histórico de Pagamentos:</strong>
                            <ul class="list-unstyled">
                                ${data.investment.payments.map(p => `
                                    <li>
                                        ${p.date} - R$ ${p.amount}
                                        <span class="badge bg-success">Pago</span>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    `;
                } else {
                    detailsContainer.innerHTML = '<div class="alert alert-danger">Erro ao carregar detalhes</div>';
                }
            })
            .catch(error => {
                detailsContainer.innerHTML = '<div class="alert alert-danger">Erro ao carregar detalhes</div>';
            });
        
        modal.show();
    }
    </script>
</body>
</html>