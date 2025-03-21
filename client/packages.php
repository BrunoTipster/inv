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

// Buscar pacotes disponíveis
$packages = $conn->query("
    SELECT * FROM investment_packages 
    WHERE status = 'active' 
    ORDER BY min_amount ASC
")->fetch_all(MYSQLI_ASSOC);

// Buscar saldo do usuário
$stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pacotes de Investimento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .package-card {
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        .package-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .package-card.selected {
            border: 2px solid #0d6efd;
        }
        .investment-simulator {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <?php require_once '../includes/client_header.php'; ?>

    <div class="container py-4">
        <!-- Saldo Disponível -->
        <div class="alert alert-info">
            <strong>Saldo Disponível:</strong> R$ <?php echo number_format($user['balance'], 2, ',', '.'); ?>
        </div>

        <!-- Pacotes -->
        <div class="row mb-4">
            <?php foreach ($packages as $package): ?>
            <div class="col-md-4 mb-4">
                <div class="card package-card" onclick="selectPackage(<?php echo $package['id']; ?>)">
                    <div class="card-body text-center">
                        <h4><?php echo htmlspecialchars($package['name']); ?></h4>
                        <hr>
                        <div class="mb-3">
                            <h5 class="text-primary"><?php echo $package['return_rate']; ?>%</h5>
                            <small class="text-muted">a cada <?php echo $package['period_days']; ?> dias</small>
                        </div>
                        <div class="mb-3">
                            <p class="mb-1">Investimento Mínimo:</p>
                            <h6>R$ <?php echo number_format($package['min_amount'], 2, ',', '.'); ?></h6>
                        </div>
                        <div class="mb-3">
                            <p class="mb-1">Investimento Máximo:</p>
                            <h6>R$ <?php echo number_format($package['max_amount'], 2, ',', '.'); ?></h6>
                        </div>
                        <p class="text-muted small">
                            <?php echo htmlspecialchars($package['description']); ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Simulador de Investimento -->
        <div class="investment-simulator" id="simulator" style="display: none;">
            <h4 class="mb-4">Simulador de Investimento</h4>
            <form action="invest.php" method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="package_id" id="package_id">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Valor do Investimento</label>
                            <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                            <div class="form-text">
                                Min: R$ <span id="min_amount">0,00</span> | 
                                Max: R$ <span id="max_amount">0,00</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Retorno Previsto</label>
                            <h4 class="text-success" id="expected_return">R$ 0,00</h4>
                            <div class="form-text">A cada <span id="period_days">0</span> dias</div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Realizar Investimento</button>
                    <button type="button" class="btn btn-outline-secondary btn-lg" onclick="resetSimulator()">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>

    <script>
    const packages = <?php echo json_encode($packages); ?>;
    let selectedPackage = null;

    function selectPackage(packageId) {
        // Remover seleção anterior
        document.querySelectorAll('.package-card').forEach(card => {
            card.classList.remove('selected');
        });

        // Selecionar novo pacote
        selectedPackage = packages.find(p => p.id == packageId);
        document.querySelector(`.package-card:nth-child(${packageId})`).classList.add('selected');

        // Atualizar simulador
        document.getElementById('package_id').value = packageId;
        document.getElementById('min_amount').textContent = formatMoney(selectedPackage.min_amount);
        document.getElementById('max_amount').textContent = formatMoney(selectedPackage.max_amount);
        document.getElementById('period_days').textContent = selectedPackage.period_days;

        // Mostrar simulador
        document.getElementById('simulator').style.display = 'block';
        document.getElementById('amount').focus();
    }

    function resetSimulator() {
        document.querySelectorAll('.package-card').forEach(card => {
            card.classList.remove('selected');
        });
        document.getElementById('simulator').style.display = 'none';
        selectedPackage = null;
    }

    // Calcular retorno ao digitar valor
    document.getElementById('amount').addEventListener('input', function() {
        if (selectedPackage) {
            const amount = parseFloat(this.value) || 0;
            const return_amount = amount * (selectedPackage.return_rate / 100);
            document.getElementById('expected_return').textContent = formatMoney(return_amount);
        }
    });

    function formatMoney(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }

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