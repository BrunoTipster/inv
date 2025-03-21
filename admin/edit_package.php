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

$package_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$package_id) {
    header('Location: dashboard.php');
    exit;
}

// Buscar dados do pacote
$stmt = $conn->prepare("SELECT * FROM investment_packages WHERE id = ?");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$package = $stmt->get_result()->fetch_assoc();

if (!$package) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $min_amount = filter_input(INPUT_POST, 'min_amount', FILTER_VALIDATE_FLOAT);
    $max_amount = filter_input(INPUT_POST, 'max_amount', FILTER_VALIDATE_FLOAT);
    $return_rate = filter_input(INPUT_POST, 'return_rate', FILTER_VALIDATE_FLOAT);
    $period_days = filter_input(INPUT_POST, 'period_days', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    if ($name && $min_amount && $max_amount && $return_rate && $period_days) {
        $stmt = $conn->prepare("UPDATE investment_packages SET name = ?, description = ?, min_amount = ?, max_amount = ?, return_rate = ?, period_days = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssdddisd", $name, $description, $min_amount, $max_amount, $return_rate, $period_days, $status, $package_id);
        
        if ($stmt->execute()) {
            $success = "Pacote atualizado com sucesso!";
            // Atualizar dados do pacote
            $package = array_merge($package, $_POST);
        } else {
            $error = "Erro ao atualizar pacote.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pacote - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Editar Pacote de Investimento</h4>
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
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($package['name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Descrição</label>
                                <textarea class="form-control" id="description" name="description" rows="3"
                                ><?php echo htmlspecialchars($package['description']); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="min_amount" class="form-label">Valor Mínimo (R$)</label>
                                    <input type="number" class="form-control" id="min_amount" name="min_amount" 
                                           value="<?php echo $package['min_amount']; ?>" step="0.01" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="max_amount" class="form-label">Valor Máximo (R$)</label>
                                    <input type="number" class="form-control" id="max_amount" name="max_amount" 
                                           value="<?php echo $package['max_amount']; ?>" step="0.01" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="return_rate" class="form-label">Taxa de Retorno (%)</label>
                                    <input type="number" class="form-control" id="return_rate" name="return_rate" 
                                           value="<?php echo $package['return_rate']; ?>" step="0.01" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="period_days" class="form-label">Período em Dias</label>
                                    <input type="number" class="form-control" id="period_days" name="period_days" 
                                           value="<?php echo $package['period_days']; ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" <?php echo $package['status'] == 'active' ? 'selected' : ''; ?>>Ativo</option>
                                    <option value="inactive" <?php echo $package['status'] == 'inactive' ? 'selected' : ''; ?>>Inativo</option>
                                </select>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
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