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

// Processar novo ticket
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    $priority = filter_input(INPUT_POST, 'priority', FILTER_SANITIZE_STRING);

    if ($subject && $message) {
        $stmt = $conn->prepare("
            INSERT INTO support_tickets (
                user_id, subject, message, priority, 
                status, created_at
            ) VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->bind_param("isss", 
            $_SESSION['user_id'], 
            $subject, 
            $message, 
            $priority
        );
        
        if ($stmt->execute()) {
            $success = "Ticket criado com sucesso! Em breve retornaremos seu contato.";
        } else {
            $error = "Erro ao criar ticket. Tente novamente.";
        }
    }
}

// Buscar tickets do usuário
$stmt = $conn->prepare("
    SELECT * FROM support_tickets 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suporte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
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
    <?php require_once '../includes/client_header.php'; ?>

    <div class="container py-4">
        <div class="row">
            <!-- Novo Ticket -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Novo Ticket</h4>
                    </div>
                    <div class="card-body">
                        <?php if(isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Assunto</label>
                                <input type="text" class="form-control" name="subject" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Prioridade</label>
                                <select name="priority" class="form-select" required>
                                    <option value="low">Baixa</option>
                                    <option value="medium">Média</option>
                                    <option value="high">Alta</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Mensagem</label>
                                <textarea name="message" class="form-control" rows="5" required></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Enviar Ticket</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lista de Tickets -->
            <div class="col-md-8">
                <h4 class="mb-4">Meus Tickets</h4>
                
                <?php if(empty($tickets)): ?>
                    <div class="alert alert-info">
                        Você ainda não possui tickets de suporte.
                    </div>
                <?php else: ?>
                    <?php foreach($tickets as $ticket): ?>
                        <div class="card ticket-card mb-3 priority-<?php echo $ticket['priority']; ?>">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?php echo htmlspecialchars($ticket['subject']); ?></h5>
                                <span class="badge bg-<?php 
                                    echo $ticket['status'] == 'pending' ? 'warning' : 
                                        ($ticket['status'] == 'resolved' ? 'success' : 'info'); 
                                ?>">
                                    <?php echo ucfirst($ticket['status']); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <p class="mb-3">
                                    <?php echo nl2br(htmlspecialchars($ticket['message'])); ?>
                                </p>
                                <small class="text-muted">
                                    Criado em: <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?>
                                </small>
                                
                                <?php if($ticket['admin_response']): ?>
                                    <hr>
                                    <div class="admin-response">
                                        <strong>Resposta da Administração:</strong>
                                        <p class="mb-0">
                                            <?php echo nl2br(htmlspecialchars($ticket['admin_response'])); ?>
                                        </p>
                                        <small class="text-muted">
                                            Respondido em: <?php echo date('d/m/Y H:i', strtotime($ticket['updated_at'])); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>
</body>
</html>