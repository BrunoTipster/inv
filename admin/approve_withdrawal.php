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

// Processar aprovação/rejeição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $withdrawal_id = filter_input(INPUT_POST, 'withdrawal_id', FILTER_VALIDATE_INT);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);

    if ($withdrawal_id && $action) {
        $stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ? AND type = 'withdrawal' AND status = 'pending'");
        $stmt->bind_param("i", $withdrawal_id);
        $stmt->execute();
        $withdrawal = $stmt->get_result()->fetch_assoc();

        if ($withdrawal) {
            if ($action === 'approve') {
                // Iniciar transação
                $conn->begin_transaction();
                try {
                    // Atualizar status do saque
                    $stmt = $conn->prepare("UPDATE transactions SET status = 'completed', description = CONCAT(description, ' | Notas: ', ?) WHERE id = ?");
                    $stmt->bind_param("si", $notes, $withdrawal_id);
                    $stmt->execute();

                    // Atualizar saldo do usuário
                    $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                    $stmt->bind_param("di", $withdrawal['amount'], $withdrawal['user_id']);
                    $stmt->execute();

                    $conn->commit();
                    $success = "Saque aprovado com sucesso!";
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Erro ao aprovar saque: " . $e->getMessage();
                }
            } elseif ($action === 'reject') {
                $stmt = $conn->prepare("UPDATE transactions SET status = 'cancelled', description = CONCAT(description, ' | Motivo rejeição: ', ?) WHERE id = ?");
                $stmt->bind_param("si", $notes, $withdrawal_id);
                if ($stmt->execute()) {
                    $success = "Saque rejeitado com sucesso!";
                } else {
                    $error = "Erro ao rejeitar saque.";
                }
            }
        }
    }
}

// Buscar saques pendentes
$withdrawals = $conn->query("
    SELECT t.*, u.username, u.email, u.balance 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.type = 'withdrawal' AND t.status = 'pending' 
    ORDER BY t.created_at ASC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprovar Saques - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php require_once '../includes/admin_header.php'; ?>

    <div class="container-fluid py-4">
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Saques Pendentes</h4>
            </div>
            <div class="card-body">
                <?php if (empty($withdrawals)): ?>
                    <div class="alert alert-info">Não há saques pendentes de aprovação.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuário</th>
                                    <th>Valor</th>
                                    <th>Saldo Atual</th>
                                    <th>Data Solicitação</th>
                                    <th>Código</th>
                                    <th>Detalhes</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($withdrawals as $w): ?>
                                <tr>
                                    <td><?php echo $w['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($w['username']); ?><br>
                                        <small class="text-muted"><?php echo $w['email']; ?></small>
                                    </td>
                                    <td>R$ <?php echo number_format($w['amount'], 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($w['balance'], 2, ',', '.'); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($w['created_at'])); ?></td>
                                    <td><?php echo $w['transaction_code']; ?></td>
                                    <td><?php echo htmlspecialchars($w['description']); ?></td>
                                    <td>
                                        <button class="btn btn-success btn-sm" onclick="showApproveModal(<?php echo $w['id']; ?>)">
                                            Aprovar
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="showRejectModal(<?php echo $w['id']; ?>)">
                                            Rejeitar
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de Aprovação -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Aprovar Saque</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="withdrawal_id" id="approve_withdrawal_id">
                        <input type="hidden" name="action" value="approve">
                        <div class="mb-3">
                            <label class="form-label">Notas/Comentários</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar Aprovação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Rejeição -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rejeitar Saque</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="withdrawal_id" id="reject_withdrawal_id">
                        <input type="hidden" name="action" value="reject">
                        <div class="mb-3">
                            <label class="form-label">Motivo da Rejeição</label>
                            <textarea name="notes" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Confirmar Rejeição</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>

    <script>
    function showApproveModal(id) {
        document.getElementById('approve_withdrawal_id').value = id;
        new bootstrap.Modal(document.getElementById('approveModal')).show();
    }

    function showRejectModal(id) {
        document.getElementById('reject_withdrawal_id').value = id;
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    }
    </script>
</body>
</html>