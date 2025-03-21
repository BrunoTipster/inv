<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Verificação de autenticação
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Processar respostas aos tickets
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ticket_id = filter_input(INPUT_POST, 'ticket_id', FILTER_VALIDATE_INT);
    $response = filter_input(INPUT_POST, 'response', FILTER_SANITIZE_STRING);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    if ($ticket_id && $response && $status) {
        $stmt = $conn->prepare("
            UPDATE support_tickets 
            SET admin_response = ?, 
                status = ?, 
                updated_at = NOW(), 
                admin_id = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("ssii", $response, $status, $_SESSION['user_id'], $ticket_id);
        
        if ($stmt->execute()) {
            $success = "Resposta enviada com sucesso!";
        } else {
            $error = "Erro ao enviar resposta.";
        }
    }
}

// Buscar tickets
$status_filter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING) ?? 'pending';
$query = "
    SELECT st.*, u.username, u.email 
    FROM support_tickets st 
    JOIN users u ON st.user_id = u.id 
    WHERE 1=1 
";

if ($status_filter !== 'all') {
    $query .= " AND st.status = '$status_filter'";
}

$query .= " ORDER BY st.created_at DESC";
$tickets = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Tickets de Suporte - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .ticket-card {
            transition: transform 0.2s;
        }
        .ticket-card:hover {
            transform: translateY(-5px);
        }
        .priority-high { border-left: 4px solid #dc3545; }
        .priority-medium { border-left: 4px solid #ffc107; }
        .priority-low { border-left: 4px solid #28a745; }
    </style>
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

        <!-- Filtros -->
        <div class="mb-4">
            <div class="btn-group">
                <a href="?status=all" class="btn btn-outline-primary <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                    Todos
                </a>
                <a href="?status=pending" class="btn btn-outline-primary <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                    Pendentes
                </a>
                <a href="?status=in_progress" class="btn btn-outline-primary <?php echo $status_filter == 'in_progress' ? 'active' : ''; ?>">
                    Em Andamento
                </a>
                <a href="?status=resolved" class="btn btn-outline-primary <?php echo $status_filter == 'resolved' ? 'active' : ''; ?>">
                    Resolvidos
                </a>
            </div>
        </div>

        <!-- Lista de Tickets -->
        <div class="row">
            <?php foreach ($tickets as $ticket): ?>
            <div class="col-md-6 mb-4">
                <div class="card ticket-card priority-<?php echo $ticket['priority']; ?>">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">#<?php echo $ticket['id']; ?> - <?php echo htmlspecialchars($ticket['subject']); ?></h5>
                        <span class="badge bg-<?php 
                            echo $ticket['status'] == 'pending' ? 'warning' : 
                                ($ticket['status'] == 'resolved' ? 'success' : 'info'); 
                        ?>">
                            <?php echo ucfirst($ticket['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Usuário:</strong> 
                            <?php echo htmlspecialchars($ticket['username']); ?> 
                            (<?php echo $ticket['email']; ?>)
                        </div>
                        <div class="mb-3">
                            <strong>Mensagem:</strong><br>
                            <?php echo nl2br(htmlspecialchars($ticket['message'])); ?>
                        </div>
                        <div class="mb-3">
                            <strong>Data:</strong> 
                            <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?>
                        </div>
                        
                        <?php if ($ticket['admin_response']): ?>
                        <div class="alert alert-info">
                            <strong>Resposta:</strong><br>
                            <?php echo nl2br(htmlspecialchars($ticket['admin_response'])); ?>
                        </div>
                        <?php endif; ?>

                        <!-- Formulário de Resposta -->
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Resposta</label>
                                <textarea name="response" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="in_progress">Em Andamento</option>
                                    <option value="resolved">Resolvido</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Enviar Resposta</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>
</body>
</html>