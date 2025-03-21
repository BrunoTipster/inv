<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Buscar todos os pacotes ativos
$packages = $conn->query("
    SELECT * FROM investment_packages 
    WHERE status = 'active' 
    ORDER BY min_amount ASC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pacotes de Investimento - InvestSystem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/investment/css/main.css">
    <style>
        .package-card {
            transition: transform 0.3s;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .package-card:hover {
            transform: translateY(-10px);
        }
        .package-features {
            list-style: none;
            padding: 0;
        }
        .package-features li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .package-features li:last-child {
            border-bottom: none;
        }
        .popular-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>

    <!-- Cabeçalho da Página -->
    <section class="bg-primary text-white py-5">
        <div class="container text-center">
            <h1>Nossos Pacotes de Investimento</h1>
            <p class="lead">Escolha o melhor plano para começar sua jornada de investimentos</p>
        </div>
    </section>

    <!-- Calculadora de Retorno -->
    <section class="py-4 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="text-center mb-4">Calculadora de Retorno</h4>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Valor do Investimento</label>
                                    <input type="number" class="form-control" id="investment-amount" min="100">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Taxa de Retorno (%)</label>
                                    <select class="form-select" id="return-rate">
                                        <?php foreach ($packages as $package): ?>
                                            <option value="<?php echo $package['return_rate']; ?>">
                                                <?php echo $package['return_rate']; ?>% / <?php echo $package['period_days']; ?> dias
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="text-center mt-4">
                                <h5>Retorno Previsto:</h5>
                                <h3 class="text-success" id="expected-return">R$ 0,00</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Lista de Pacotes -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <?php foreach ($packages as $index => $package): ?>
                <div class="col-md-4 mb-4">
                    <div class="card package-card h-100">
                        <?php if($index == 1): ?>
                            <span class="popular-badge">Mais Popular</span>
                        <?php endif; ?>
                        <div class="card-body text-center">
                            <h3><?php echo htmlspecialchars($package['name']); ?></h3>
                            <div class="package-rate my-4">
                                <span class="display-4 text-primary"><?php echo $package['return_rate']; ?>%</span>
                                <span class="text-muted">/ <?php echo $package['period_days']; ?> dias</span>
                            </div>
                            <ul class="package-features">
                                <li>
                                    <strong>Investimento Mínimo:</strong><br>
                                    R$ <?php echo number_format($package['min_amount'], 2, ',', '.'); ?>
                                </li>
                                <li>
                                    <strong>Investimento Máximo:</strong><br>
                                    R$ <?php echo number_format($package['max_amount'], 2, ',', '.'); ?>
                                </li>
                                <li>
                                    <strong>Retorno Total:</strong><br>
                                    <?php echo $package['return_rate']; ?>% a cada <?php echo $package['period_days']; ?> dias
                                </li>
                                <li>
                                    <?php echo htmlspecialchars($package['description']); ?>
                                </li>
                            </ul>
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <a href="client/invest.php?package=<?php echo $package['id']; ?>" 
                                   class="btn btn-primary btn-lg mt-4 w-100">Investir Agora</a>
                            <?php else: ?>
                                <a href="register.php" class="btn btn-primary btn-lg mt-4 w-100">Criar Conta</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Perguntas Frequentes</h2>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Como começar a investir?
                                </button>
                            </h3>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Para começar, basta criar sua conta, fazer um depósito e escolher um dos nossos pacotes de investimento.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Qual o valor mínimo para investir?
                                </button>
                            </h3>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Nosso pacote inicial começa com apenas R$ 100,00, permitindo que todos possam começar a investir.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Como funciona o retorno dos investimentos?
                                </button>
                            </h3>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Os retornos são creditados automaticamente em sua conta de acordo com o período do pacote escolhido.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php require_once 'includes/footer.php'; ?>

    <script>
    // Calculadora de retorno
    document.getElementById('investment-amount').addEventListener('input', calculateReturn);
    document.getElementById('return-rate').addEventListener('change', calculateReturn);

    function calculateReturn() {
        const amount = parseFloat(document.getElementById('investment-amount').value) || 0;
        const rate = parseFloat(document.getElementById('return-rate').value) || 0;
        const return_amount = amount * (rate / 100);
        
        document.getElementById('expected-return').textContent = 
            'R$ ' + return_amount.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    </script>
</body>
</html>