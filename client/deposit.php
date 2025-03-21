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
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);

    if ($amount < 100) {
        $error = "O valor mínimo para depósito é R$ 100,00";
    } else {
        // Gerar código da transação
        $transaction_code = generateTransactionCode('DEP');
        
        // Registrar depósito pendente
        $stmt = $conn->prepare("
            INSERT INTO transactions (
                user_id, type, amount, status, 
                transaction_code, payment_method, description
            ) VALUES (
                ?, 'deposit', ?, 'pending', 
                ?, ?, ?
            )
        ");
        
        $description = "Depósito via " . ucfirst($payment_method);
        $stmt->bind_param("idsss", 
            $_SESSION['user_id'], 
            $amount, 
            $transaction_code, 
            $payment_method, 
            $description
        );
        
        if ($stmt->execute()) {
            $deposit_id = $conn->insert_id;
            $_SESSION['deposit_id'] = $deposit_id;
            header("Location: deposit_instructions.php?method=$payment_method&code=$transaction_code");
            exit;
        } else {
            $error = "Erro ao processar depósito. Tente novamente.";
        }
    }
}

// Buscar métodos de pagamento disponíveis
$payment_methods = [
    'pix' => [
        'name' => 'PIX',
        'icon' => 'bi-qr-code',
        'description' => 'Transferência instantânea',
        'min' => 100,
        'max' => 50000
    ],
    'bank_transfer' => [
        'name' => 'Transferência Bancária',
        'icon' => 'bi-bank',
        'description' => 'TED/DOC - 1 dia útil',
        'min' => 100,
        'max' => 100000
    ],
    'crypto' => [
        'name' => 'Criptomoedas',
        'icon' => 'bi-currency-bitcoin',
        'description' => 'BTC, ETH, USDT',
        'min' => 100,
        'max' => null
    ]
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realizar Depósito</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .payment-method-card {
            cursor: pointer;
            transition: transform 0.3s;
            border: 2px solid transparent;
        }
        .payment-method-card:hover {
            transform: translateY(-5px);
        }
        .payment-method-card.selected {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .payment-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php require_once '../includes/client_header.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Realizar Depósito</h4>
                    </div>
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <!-- Valor do Depósito -->
                            <div class="mb-4">
                                <label for="amount" class="form-label">Valor do Depósito</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" class="form-control form-control-lg" 
                                           id="amount" name="amount" step="0.01" min="100"
                                           required value="<?php echo $_POST['amount'] ?? ''; ?>">
                                </div>
                                <div class="form-text">Valor mínimo: R$ 100,00</div>
                            </div>

                            <!-- Métodos de Pagamento -->
                            <div class="mb-4">
                                <label class="form-label">Método de Pagamento</label>
                                <div class="row">
                                    <?php foreach ($payment_methods as $key => $method): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="payment-method-card card h-100 text-center p-3">
                                            <input type="radio" name="payment_method" 
                                                   value="<?php echo $key; ?>" class="d-none" required>
                                            <i class="bi <?php echo $method['icon']; ?> payment-icon"></i>
                                            <h5><?php echo $method['name']; ?></h5>
                                            <p class="text-muted small mb-0"><?php echo $method['description']; ?></p>
                                            <?php if ($method['max']): ?>
                                            <small class="text-muted">
                                                Limite: R$ <?php echo number_format($method['max'], 2, ',', '.'); ?>
                                            </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">Continuar</button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>

    <script>
    // Selecionar método de pagamento
    document.querySelectorAll('.payment-method-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.payment-method-card').forEach(c => {
                c.classList.remove('selected');
            });
            card.classList.add('selected');
            card.querySelector('input[type="radio"]').checked = true;
        });
    });

    // Validação do formulário
    (function() {
        'use strict';
        var form = document.querySelector('.needs-validation');
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    })();
    </script>
</body>
</html>