<?php
/**
 * Cabeçalho Global do Site
 * 
 * @package InvestSystem
 * @version 1.0.0
 * @author Bruno Tipster
 * @copyright 2025 InvestSystem
 * @last_modified 2025-03-20 23:57:58 UTC
 */

// Verificar se já iniciou a sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Instância da classe Auth
$auth = Auth::getInstance();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="InvestSystem - Plataforma líder em investimentos">
    <meta name="author" content="Bruno Tipster">
    
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>InvestSystem</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="/investment/images/favicon.ico" type="image/x-icon">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="/investment/css/main.css?v=<?php echo filemtime(BASE_PATH . '/css/main.css'); ?>" rel="stylesheet">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:title" content="<?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>InvestSystem">
    <meta property="og:description" content="Plataforma líder em investimentos com retornos garantidos">
    <meta property="og:image" content="<?php echo SITE_URL; ?>/images/og-image.jpg">
    <meta property="og:url" content="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">
    
    <!-- Preload de fontes críticas -->
    <link rel="preload" href="/investment/fonts/inter-v12-latin-regular.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/investment/fonts/inter-v12-latin-600.woff2" as="font" type="font/woff2" crossorigin>
</head>
<body>
    <!-- Header Principal -->
    <header class="main-header">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <!-- Logo -->
                <a class="navbar-brand" href="/">
                    <img src="/investment/images/logo.png" alt="InvestSystem" height="40">
                </a>

                <!-- Toggle Menu Mobile -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Menu Principal -->
                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link<?php echo $_SERVER['REQUEST_URI'] == '/' ? ' active' : ''; ?>" 
                               href="/">Início</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo strpos($_SERVER['REQUEST_URI'], '/packages') !== false ? ' active' : ''; ?>" 
                               href="/packages">Pacotes</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo strpos($_SERVER['REQUEST_URI'], '/about') !== false ? ' active' : ''; ?>" 
                               href="/about">Sobre</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link<?php echo strpos($_SERVER['REQUEST_URI'], '/contact') !== false ? ' active' : ''; ?>" 
                               href="/contact">Contato</a>
                        </li>
                    </ul>

                    <!-- Menu do Usuário -->
                    <div class="navbar-nav">
                        <?php if ($auth->isLoggedIn()): ?>
                            <div class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userMenu" data-bs-toggle="dropdown">
                                    <?php if ($user = $auth->getUser()): ?>
                                        <img src="<?php echo $user['profile_photo'] ? '/uploads/' . $user['profile_photo'] : '/images/default-avatar.png'; ?>" 
                                             class="rounded-circle me-1" width="24" height="24" alt="">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    <?php endif; ?>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <?php if ($auth->hasRole('admin')): ?>
                                        <a class="dropdown-item" href="/admin/dashboard">
                                            <i class="bi bi-speedometer2"></i> Painel Admin
                                        </a>
                                        <div class="dropdown-divider"></div>
                                    <?php else: ?>
                                        <a class="dropdown-item" href="/client/dashboard">
                                            <i class="bi bi-grid"></i> Meu Painel
                                        </a>
                                    <?php endif; ?>
                                    <a class="dropdown-item" href="/client/profile">
                                        <i class="bi bi-person"></i> Meu Perfil
                                    </a>
                                    <a class="dropdown-item" href="/client/investments">
                                        <i class="bi bi-graph-up"></i> Investimentos
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" href="/logout.php">
                                        <i class="bi bi-box-arrow-right"></i> Sair
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <a class="nav-link" href="/login">Entrar</a>
                            <a class="btn btn-light ms-2" href="/register">Criar Conta</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Mensagens de Sistema -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show m-0">
                <div class="container">
                    <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show m-0">
                <div class="container">
                    <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>
    </header>

    <!-- Container Principal -->
    <main class="main-content">