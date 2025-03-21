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

// Buscar saldo do usuário
$stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Buscar saques pendentes
$stmt = $conn->prepare("
    SELECT COUNT(*) as pending_count 
    FROM transactions 
    WHERE user_id = ? AND type = 'withdrawal' 
    AND status = 'pending'
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$pending = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $withdrawal_method = filter_input(INPUT_POST, 'withdrawal_method', FILTER_SANITIZE_STRING);
    $wallet_address = filter_input(INPUT_POST, 'wallet_address', FILTER_SANITIZE_STRING);

    // Validações
    if ($pending['pending_count'] > 0) {
        $error = "Você já possui um saque pendente. Aguarde a aprovação.";
    } elseif ($amount < 100) {
        $error = "O valor mínimo para saque é R$ 100,00";
    } elseif ($amount > $user['balance']) {
        $error = "Saldo insuficiente para este saque";
    } else {
        // Registrar solicitação de saque
        $transaction_code = generateTransactionCode('WIT');
        
        $stmt = $conn->prepare("
            INSERT INTO transactions (
                user_id, type, amount, status, 
                transaction_code, withdrawal_method, 
                wallet_address, description
            ) VALUES (
                ?, 'withdrawal', ?, 'pending', 
                ?, ?, ?, ?
            )
        ");
        
        $description = "Solicitação de saque via " . ucfirst($withdrawal_method);
        $stmt->bind_param("idssss", 
            $_SESSION['user_id'], 
            $amount, 
            $transaction_code, 
            $withdrawal_method, 
            $wallet_address, 
            $description
        );
        
        if ($stmt->execute()) {
            $success = "Solicitação de saque enviada com sucesso! Aguarde a aprovação.";
        } else {
            $error = "Erro ao processar saque. Tente novamente.";
        }
    }
}

// Métodos de saque disponíveis
$withdrawal_methods = [
    'pix' => [
        'name' => 'PIX',
        'icon' => 'bi-qr-code',
        'description' => 'Chave PIX',
        'placeholder' => 'Digite sua chave PIX'
    ],
    'bank_transfer' => [
        'name' => 'Transferência Bancária',
        'icon' => 'bi-bank',
        'description' => 'Dados Bancários',
        'placeholder' => 'Banco, Agência, Conta'
    ],
    'crypto' => [
        'name' => 'Criptomoedas',
        'icon' => 'bi-currency-bitcoin',
        'description' => 'BTC, ETH, USDT',
        'placeholder' => 'Endereço da carteira'
    ]
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Saque</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .balance-card {
            background: linear-gradient(45deg, #1a237e, #0d47a1);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .withdrawal-method-card {
            cursor: pointer;
            transition: transform 0.3s;
            border: 2px solid transparent;
        }
        .withdrawal-method-card:hover {
            transform: translateY(-5px);
        }
        .withdrawal-method-card.selected {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php require_once '../includes/client_header.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Saldo Disponível -->
                <div class="balance-card text-center">
                    <h5>Saldo Disponível para Saque</h5>
                    <h2>R$ <?php echo number_format($user['balance'], 2, ',', '.'); ?></h2>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Solicitar Saque</h4>
                    </div>
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if(isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <?php if($pending['pending_count'] == 0): ?>
                        <form method="POST" class="needs-validation" novalidate>
                            <!-- Valor do Saque -->
                            <div class="mb-4">
                                <label for="amount" class="form-label">Valor do Saque</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" class="form-control form-control-lg" 
                                           id="amount" name="amount" step="0.01" min="100"
                                           max="<?php echo $user['balance']; ?>" required>
                                </div>
                                <div class="form-text">
                                    Valor mínimo: R$ 100,00 | Taxa: 0%
                                </div>
                            </div>

                            <!-- Métodos de Saque -->
                            <div class="mb-4">
                                <label class="form-label">Método de Saque</label>
                                <div class="row">
                                    <?php foreach ($withdrawal_methods as $key => $method): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="withdrawal-method-card card h-100 text-center p-3">
                                            <input type="radio" name="withdrawal_method" 
                                                   value="<?php echo $key; ?>" class="d-none" required>
                                            <i class="bi <?php echo $method['icon']; ?> fs-1 mb-2"></i>
                                            <h5><?php echo $method['name']; ?></h5>
                                            <p class="text-muted small mb-0"><?php echo $method['description']; ?></p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Dados do Saque -->
                            <div class="mb-4" id="withdrawalDetails" style="display: none;">
                                <label for="wallet_address" class="form-label">Dados para Recebimento</label>
                                <textarea class="form-control" id="wallet_address" name="wallet_address" 
                                          rows="3" required></textarea>
                                <div class="form-text" id="walletAddressHelp"></div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">Solicitar Saque</button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">Cancelar</a>
                            </div>
                        </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                Você já possui um saque pendente de aprovação.
                                Por favor, aguarde a confirmação antes de solicitar um novo saque.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>

    <script>
    const withdrawalMethods = <?php echo json_encode($withdrawal_methods); ?>;

    // Selecionar método de saque
    document.querySelectorAll('.withdrawal-method-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.withdrawal-method-card').forEach(c => {
                c.classList.remove('selected');
            });
            card.classList.add('selected');
            const method = card.querySelector('input[type="radio"]').value;
            card.querySelector('input[type="radio"]').checked = true;
            
            // Mostrar campo de detalhes
            const detailsDiv = document.getElementById('withdrawalDetails');
            const addressField = document.getElementById('wallet_address');
            const addressHelp = document.getElementById('walletAddressHelp');
            
            detailsDiv.style.display = 'block';
            addressField.placeholder = withdrawalMethods[method].placeholder;
            addressHelp.textContent = withdrawalMethods[method].description;
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