<?php
/**
 * Cabeçalho da Área do Cliente
 * 
 * @package InvestSystem
 * @version 1.0.0
 * @author Bruno Tipster
 * @copyright 2025 InvestSystem
 * @last_modified 2025-03-21 00:03:19 UTC
 */

// Verificar autenticação de cliente
$auth = Auth::getInstance();
if (!$auth->isLoggedIn() || !$auth->hasRole('client')) {
    header('Location: /client/login.php');
    exit;
}

$user = $auth->getUser();

// Buscar saldo e notificações
$db = Database::getInstance()->getConnection();

$balance = $db->query("
    SELECT 
        balance,
        (SELECT SUM(amount) FROM investments WHERE user_id = {$user['id']} AND status = 'active') as invested,
        (SELECT SUM(amount) FROM investment_returns WHERE user_id = {$user['id']} AND status = 'completed') as returns
    FROM users 
    WHERE id = {$user['id']}
")->fetch_assoc();

$notifications = $db->query("
    SELECT * FROM notifications 
    WHERE user_id = {$user['id']} 
    AND read_at IS NULL 
    ORDER BY created_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Minha Conta | InvestSystem</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="/investment/css/client.css?v=<?php echo filemtime(BASE_PATH . '/css/client.css'); ?>" rel="stylesheet">
    
    <!-- Chart.js -->
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css" rel="stylesheet">
</head>
<body>
    <div class="client-wrapper">
        <!-- Header -->
        <header class="client-header">
            <div class="container">
                <div class="header-nav">
                    <!-- Logo -->
                    <a href="/client/dashboard.php" class="header-brand">
                        <img src="/investment/images/logo.png" alt="InvestSystem" height="40">
                    </a>

                    <!-- Menu Principal -->
                    <nav class="header-menu d-none d-lg-flex">
                        <a href="/client/dashboard.php" class="menu-link<?php echo strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false ? ' active' : ''; ?>">
                            <i class="bi bi-grid"></i>
                            Dashboard
                        </a>
                        <a href="/client/investments.php" class="menu-link<?php echo strpos($_SERVER['PHP_SELF'], 'investments.php') !== false ? ' active' : ''; ?>">
                            <i class="bi bi-graph-up"></i>
                            Investimentos
                        </a>
                        <a href="/client/transactions.php" class="menu-link<?php echo strpos($_SERVER['PHP_SELF'], 'transactions.php') !== false ? ' active' : ''; ?>">
                            <i class="bi bi-clock-history"></i>
                            Histórico
                        </a>
                        <a href="/client/support.php" class="menu-link<?php echo strpos($_SERVER['PHP_SELF'], 'support.php') !== false ? ' active' : ''; ?>">
                            <i class="bi bi-headset"></i>
                            Suporte
                        </a>
                    </nav>

                    <!-- Menu do Usuário -->
                    <div class="header-user d-flex align-items-center">
                        <!-- Saldo -->
                        <div class="balance-display me-4">
                            <small class="d-block text-muted">Saldo Disponível</small>
                            <strong class="text-primary">R$ <?php echo number_format($balance['balance'], 2, ',', '.'); ?></strong>
                        </div>

                        <!-- Notificações -->
                        <div class="dropdown me-3">
                            <button class="btn position-relative" data-bs-toggle="dropdown">
                                <i class="bi bi-bell"></i>
                                <?php if (count($notifications) > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo count($notifications); ?>
                                    </span>
                                <?php endif; ?>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end notifications-menu">
                                <h6 class="dropdown-header">Notificações</h6>
                                <?php if (empty($notifications)): ?>
                                    <div class="dropdown-item text-muted">
                                        Nenhuma notificação nova
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notification): ?>
                                        <a href="<?php echo $notification['link']; ?>" class="dropdown-item">
                                            <small class="text-muted d-block">
                                                <?php echo formatDateTime($notification['created_at']); ?>
                                            </small>
                                            <?php echo $notification['message']; ?>
                                        </a>
                                    <?php endforeach; ?>
                                    <div class="dropdown-divider"></div>
                                    <a href="/client/notifications.php" class="dropdown-item text-center">
                                        Ver Todas
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Perfil -->
                        <div class="dropdown">
                            <button class="btn d-flex align-items-center" data-bs-toggle="dropdown">
                                <img src="<?php echo $user['profile_photo'] ? '/uploads/' . $user['profile_photo'] : '/images/default-avatar.png'; ?>" 
                                     class="rounded-circle me-2" width="32" height="32" alt="">
                                <span class="d-none d-md-inline"><?php echo htmlspecialchars($user['username']); ?></span>
                                <i class="bi bi-chevron-down ms-2"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <div class="dropdown-header">
                                    <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                    <small class="d-block text-muted"><?php echo $user['email']; ?></small>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a href="/client/profile.php" class="dropdown-item">
                                    <i class="bi bi-person"></i> Meu Perfil
                                </a>
                                <a href="/client/security.php" class="dropdown-item">
                                    <i class="bi bi-shield-check"></i> Segurança
                                </a>
                                <a href="/client/settings.php" class="dropdown-item">
                                    <i class="bi bi-gear"></i> Configurações
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="/logout.php" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right"></i> Sair
                                </a>
                            </div>
                        </div>

                        <!-- Menu Mobile -->
                        <button class="btn d-lg-none ms-2" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
                            <i class="bi bi-list"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Menu Mobile -->
        <div class="offcanvas offcanvas-start d-lg-none" id="mobileMenu">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title">Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body">
                <nav class="mobile-nav">
                    <a href="/client/dashboard.php" class="nav-link<?php echo strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false ? ' active' : ''; ?>">
                        <i class="bi bi-grid"></i>
                        Dashboard
                    </a>
                    <a href="/client/investments.php" class="nav-link<?php echo strpos($_SERVER['PHP_SELF'], 'investments.php') !== false ? ' active' : ''; ?>">
                        <i class="bi bi-graph-up"></i>
                        Investimentos
                    </a>
                    <a href="/client/transactions.php" class="nav-link<?php echo strpos($_SERVER['PHP_SELF'], 'transactions.php') !== false ? ' active' : ''; ?>">
                        <i class="bi bi-clock-history"></i>
                        Histórico
                    </a>
                    <a href="/client/support.php" class="nav-link<?php echo strpos($_SERVER['PHP_SELF'], 'support.php') !== false ? ' active' : ''; ?>">
                        <i class="bi bi-headset"></i>
                        Suporte
                    </a>
                </nav>
            </div>
        </div>

        <!-- Conteúdo Principal -->
        <main class="client-main">
            <div class="container py-4">
                <!-- Alertas -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php 
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Início do Conteúdo -->