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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $package_id = filter_input(INPUT_POST, 'package_id', FILTER_VALIDATE_INT);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    // Validar pacote e valor
    $stmt = $conn->prepare("
        SELECT * FROM investment_packages 
        WHERE id = ? AND status = 'active'
    ");
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $package = $stmt->get_result()->fetch_assoc();

    // Buscar saldo do usuário
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$package) {
        $error = "Pacote de investimento inválido.";
    } elseif ($amount < $package['min_amount'] || $amount > $package['max_amount']) {
        $error = "Valor fora dos limites permitidos para este pacote.";
    } elseif ($amount > $user['balance']) {
        $error = "Saldo insuficiente para realizar este investimento.";
    } else {
        // Iniciar transação
        $conn->begin_transaction();

        try {
            // Calcular retorno e próxima data
            $return_amount = $amount * ($package['return_rate'] / 100);
            $next_return_date = date('Y-m-d H:i:s', strtotime("+{$package['period_days']} days"));

            // Criar investimento
            $stmt = $conn->prepare("
                INSERT INTO investments (
                    user_id, package_id, amount, return_amount, 
                    next_return_date, status
                ) VALUES (?, ?, ?, ?, ?, 'active')
            ");
            $stmt->bind_param("iidds", 
                $_SESSION['user_id'], 
                $package_id, 
                $amount, 
                $return_amount, 
                $next_return_date
            );
            $stmt->execute();
            $investment_id = $conn->insert_id;

            // Deduzir do saldo
            $stmt = $conn->prepare("
                UPDATE users 
                SET balance = balance - ? 
                WHERE id = ?
            ");
            $stmt->bind_param("di", $amount, $_SESSION['user_id']);
            $stmt->execute();

            // Registrar transação
            $stmt = $conn->prepare("
                INSERT INTO transactions (
                    user_id, type, amount, status, 
                    transaction_code, description
                ) VALUES (
                    ?, 'investment', ?, 'completed', 
                    ?, ?
                )
            ");
            $transaction_code = generateTransactionCode('INV');
            $description = "Investimento no pacote {$package['name']}";
            $stmt->bind_param("idss", 
                $_SESSION['user_id'], 
                $amount, 
                $transaction_code, 
                $description
            );
            $stmt->execute();

            $conn->commit();
            $success = "Investimento realizado com sucesso!";
            
            // Redirecionar após sucesso
            header("Location: investments.php?success=1");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Erro ao processar investimento. Tente novamente.";
        }
    }
}

// Se não veio do formulário de pacotes, redireciona
if (!isset($_POST['package_id'])) {
    header('Location: packages.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Investimento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php require_once '../includes/client_header.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                        <br>
                        <a href="packages.php" class="btn btn-outline-danger mt-2">Voltar aos Pacotes</a>
                    </div>
                <?php endif; ?>

                <?php if(isset($success)): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                        <br>
                        <a href="investments.php" class="btn btn-success mt-2">Ver Meus Investimentos</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>
</body>
</html>