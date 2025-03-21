<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Buscar pacotes em destaque
$packages = $conn->query("
    SELECT * FROM investment_packages 
    WHERE status = 'active' 
    ORDER BY min_amount ASC 
    LIMIT 3
")->fetch_all(MYSQLI_ASSOC);

// Estatísticas do sistema
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE type = 'client'")->fetch_assoc()['count'],
    'total_invested' => $conn->query("SELECT SUM(amount) as total FROM investments WHERE status = 'active'")->fetch_assoc()['total'] ?? 0,
    'total_returns' => $conn->query("SELECT SUM(return_amount) as total FROM investments WHERE status = 'completed'")->fetch_assoc()['total'] ?? 0
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InvestSystem - Plataforma de Investimentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/investment/css/main.css">
</head>
<body>
    <?php require_once 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4 fw-bold">Invista com Segurança e Rentabilidade</h1>
                    <p class="lead">Plataforma líder em investimentos com retornos garantidos e gerenciamento transparente.</p>
                    <div class="mt-4">
                        <?php if(!isset($_SESSION['user_id'])): ?>
                            <a href="register.php" class="btn btn-light btn-lg me-3">Começar Agora</a>
                            <a href="about.php" class="btn btn-outline-light btn-lg">Saiba Mais</a>
                        <?php else: ?>
                            <a href="client/dashboard.php" class="btn btn-light btn-lg">Acessar Dashboard</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <img src="/investment/images/hero-image.png" alt="Investimentos" class="img-fluid">
                </div>
            </div>
        </div>
    </section>

    <!-- Estatísticas -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="stats-card">
                        <i class="bi bi-people display-4 text-primary"></i>
                        <h3 class="mt-3"><?php echo number_format($stats['total_users']); ?>+</h3>
                        <p class="text-muted">Investidores Ativos</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="stats-card">
                        <i class="bi bi-graph-up display-4 text-success"></i>
                        <h3 class="mt-3">R$ <?php echo number_format($stats['total_invested'], 2, ',', '.'); ?></h3>
                        <p class="text-muted">Total Investido</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="stats-card">
                        <i class="bi bi-currency-dollar display-4 text-info"></i>
                        <h3 class="mt-3">R$ <?php echo number_format($stats['total_returns'], 2, ',', '.'); ?></h3>
                        <p class="text-muted">Retornos Pagos</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pacotes em Destaque -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Pacotes em Destaque</h2>
            <div class="row">
                <?php foreach ($packages as $package): ?>
                <div class="col-md-4 mb-4">
                    <div class="card package-card h-100">
                        <div class="card-body text-center">
                            <h3><?php echo htmlspecialchars($package['name']); ?></h3>
                            <div class="package-rate mt-3">
                                <span class="display-4 text-primary"><?php echo $package['return_rate']; ?>%</span>
                                <span class="text-muted">/ <?php echo $package['period_days']; ?> dias</span>
                            </div>
                            <hr>
                            <ul class="list-unstyled">
                                <li>Mínimo: R$ <?php echo number_format($package['min_amount'], 2, ',', '.'); ?></li>
                                <li>Máximo: R$ <?php echo number_format($package['max_amount'], 2, ',', '.'); ?></li>
                                <li><?php echo htmlspecialchars($package['description']); ?></li>
                            </ul>
                            <a href="<?php echo isset($_SESSION['user_id']) ? 'client/invest.php' : 'register.php'; ?>" 
                               class="btn btn-primary mt-3">Investir Agora</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <a href="packages.php" class="btn btn-outline-primary btn-lg">Ver Todos os Pacotes</a>
            </div>
        </div>
    </section>

    <!-- Recursos -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Por que Escolher Nossa Plataforma?</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="feature-card text-center">
                        <i class="bi bi-shield-check display-4 text-primary"></i>
                        <h4 class="mt-3">Segurança Garantida</h4>
                        <p>Seus investimentos protegidos por tecnologia de ponta e equipe especializada.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card text-center">
                        <i class="bi bi-graph-up-arrow display-4 text-primary"></i>
                        <h4 class="mt-3">Retornos Consistentes</h4>
                        <p>Rentabilidade acima do mercado com pagamentos pontuais.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card text-center">
                        <i class="bi bi-headset display-4 text-primary"></i>
                        <h4 class="mt-3">Suporte 24/7</h4>
                        <p>Equipe dedicada para auxiliar em todas as suas necessidades.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-5 bg-primary text-white text-center">
        <div class="container">
            <h2 class="mb-4">Comece a Investir Hoje Mesmo</h2>
            <p class="lead mb-4">Junte-se a milhares de investidores que já confiam em nossa plataforma</p>
            <?php if(!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="btn btn-light btn-lg">Criar Conta Grátis</a>
            <?php else: ?>
                <a href="client/dashboard.php" class="btn btn-light btn-lg">Acessar Minha Conta</a>
            <?php endif; ?>
        </div>
    </section>

    <?php require_once 'includes/footer.php'; ?>
</body>
</html>