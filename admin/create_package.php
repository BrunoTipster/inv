<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Verifica se é admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$pageTitle = "Criar Pacote de Investimento";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $min_amount = filter_input(INPUT_POST, 'min_amount', FILTER_VALIDATE_FLOAT);
    $max_amount = filter_input(INPUT_POST, 'max_amount', FILTER_VALIDATE_FLOAT);
    $return_rate = filter_input(INPUT_POST, 'return_rate', FILTER_VALIDATE_FLOAT);
    $period_days = filter_input(INPUT_POST, 'period_days', FILTER_VALIDATE_INT);

    if ($name && $min_amount && $max_amount && $return_rate && $period_days) {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("INSERT INTO investment_packages (name, description, min_amount, max_amount, return_rate, period_days) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdddi", $name, $description, $min_amount, $max_amount, $return_rate, $period_days);
        
        if ($stmt->execute()) {
            $success = "Pacote criado com sucesso!";
        } else {
            $error = "Erro ao criar pacote.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Criar Novo Pacote de Investimento</h4>
                    </div>
                    <div class="card-body">
                        <?php if(isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="name" class="form-label">Nome do Pacote</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Descrição</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="min_amount" class="form-label">Valor Mínimo (R$)</label>
                                    <input type="number" class="form-control" id="min_amount" name="min_amount" step="0.01" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="max_amount" class="form-label">Valor Máximo (R$)</label>
                                    <input type="number" class="form-control" id="max_amount" name="max_amount" step="0.01" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="return_rate" class="form-label">Taxa de Retorno (%)</label>
                                    <input type="number" class="form-control" id="return_rate" name="return_rate" step="0.01" value="3" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="period_days" class="form-label">Período em Dias</label>
                                    <input type="number" class="form-control" id="period_days" name="period_days" value="3" required>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Criar Pacote</button>
                                <a href="dashboard.php" class="btn btn-secondary">Voltar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>