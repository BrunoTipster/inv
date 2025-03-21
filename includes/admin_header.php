<?php
/**
 * Cabeçalho da Área Administrativa
 * 
 * @package InvestSystem
 * @version 1.0.0
 * @author Bruno Tipster
 * @copyright 2025 InvestSystem
 * @last_modified 2025-03-21 00:03:19 UTC
 */

// Verificar autenticação de administrador
$auth = Auth::getInstance();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: /admin/login.php');
    exit;
}

$user = $auth->getUser();

// Buscar notificações pendentes
$db = Database::getInstance()->getConnection();
$notifications = $db->query("
    SELECT COUNT(*) as count, type FROM (
        SELECT 'withdrawal' as type FROM withdrawal_requests WHERE status = 'pending'
        UNION ALL
        SELECT 'ticket' as type FROM support_tickets WHERE status = 'pending'
        UNION ALL
        SELECT 'kyc' as type FROM user_documents WHERE status = 'pending'
    ) as notifications
    GROUP BY type
")->fetch_all(MYSQLI_ASSOC);

$notification_count = array_sum(array_column($notifications, 'count'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Admin | InvestSystem</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="/investment/css/admin.css?v=<?php echo filemtime(BASE_PATH . '/css/admin.css'); ?>" rel="stylesheet">
    
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <a href="/admin/dashboard.php" class="sidebar-brand">
                    <img src="/investment/images/logo-white.png" alt="InvestSystem" height="30">
                </a>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-item<?php echo strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false ? ' active' : ''; ?>">
                    <a href="/admin/dashboard.php" class="nav-link">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <div class="nav-item<?php echo strpos($_SERVER['PHP_SELF'], 'users.php') !== false ? ' active' : ''; ?>">
                    <a href="/admin/users.php" class="nav-link">
                        <i class="bi bi-people"></i>
                        <span>Usuários</span>
                    </a>
                </div>

                <div class="nav-item<?php echo strpos($_SERVER['PHP_SELF'], 'package') !== false ? ' active' : ''; ?>">
                    <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#packageSubmenu">
                        <i class="bi bi-box"></i>
                        <span>Pacotes</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse<?php echo strpos($_SERVER['PHP_SELF'], 'package') !== false ? ' show' : ''; ?>" id="packageSubmenu">
                        <a href="/admin/create_package.php" class="nav-link">
                            <i class="bi bi-plus-circle"></i>
                            <span>Novo Pacote</span>
                        </a>
                        <a href="/admin/packages.php" class="nav-link">
                            <i class="bi bi-grid"></i>
                            <span>Gerenciar Pacotes</span>
                        </a>
                    </div>
                </div>

                <div class="nav-item<?php echo strpos($_SERVER['PHP_SELF'], 'transactions.php') !== false ? ' active' : ''; ?>">
                    <a href="/admin/transactions.php" class="nav-link">
                        <i class="bi bi-currency-exchange"></i>
                        <span>Transações</span>
                    </a>
                </div>

                <div class="nav-item<?php echo strpos($_SERVER['PHP_SELF'], 'approve_withdrawal.php') !== false ? ' active' : ''; ?>">
                    <a href="/admin/approve_withdrawal.php" class="nav-link">
                        <i class="bi bi-cash-stack"></i>
                        <span>Saques</span>
                        <?php if ($notification_count > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-auto"><?php echo $notification_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>

                <div class="nav-item<?php echo strpos($_SERVER['PHP_SELF'], 'reports.php') !== false ? ' active' : ''; ?>">
                    <a href="/admin/reports.php" class="nav-link">
                        <i class="bi bi-graph-up"></i>
                        <span>Relatórios</span>
                    </a>
                </div>

                <div class="nav-item<?php echo strpos($_SERVER['PHP_SELF'], 'support_tickets.php') !== false ? ' active' : ''; ?>">
                    <a href="/admin/support_tickets.php" class="nav-link">
                        <i class="bi bi-ticket"></i>
                        <span>Suporte</span>
                        <?php 
                        $ticket_count = array_filter($notifications, function($n) { 
                            return $n['type'] == 'ticket'; 
                        })[0]['count'] ?? 0;
                        if ($ticket_count > 0):
                        ?>
                            <span class="badge bg-danger rounded-pill ms-auto"><?php echo $ticket_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Conteúdo Principal -->
        <div class="admin-content">
            <!-- Header -->
            <header class="content-header bg-white p-3 mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <button class="btn sidebar-toggle">
                        <i class="bi bi-list"></i>
                    </button>

                    <div class="d-flex align-items-center">
                        <!-- Notificações -->
                        <div class="dropdown me-3">
                            <button class="btn position-relative" data-bs-toggle="dropdown">
                                <i class="bi bi-bell"></i>
                                <?php if ($notification_count > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $notification_count; ?>
                                    </span>
                                <?php endif; ?>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <h6 class="dropdown-header">Notificações</h6>
                                <?php foreach ($notifications as $notification): ?>
                                    <a href="#" class="dropdown-item">
                                        <?php echo $notification['count']; ?> 
                                        <?php echo $notification['type'] == 'withdrawal' ? 'saques' : 
                                            ($notification['type'] == 'ticket' ? 'tickets' : 'documentos'); ?> 
                                        pendentes
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Perfil -->
                        <div class="dropdown">
                            <button class="btn d-flex align-items-center" data-bs-toggle="dropdown">
                                <img src="<?php echo $user['profile_photo'] ? '/uploads/' . $user['profile_photo'] : '/images/default-avatar.png'; ?>" 
                                     class="rounded-circle me-2" width="32" height="32" alt="">
                                <span><?php echo htmlspecialchars($user['username']); ?></span>
                                <i class="bi bi-chevron-down ms-2"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a href="/admin/profile.php" class="dropdown-item">
                                    <i class="bi bi-person"></i> Perfil
                                </a>
                                <a href="/admin/settings.php" class="dropdown-item">
                                    <i class="bi bi-gear"></i> Configurações
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="/logout.php" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right"></i> Sair
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

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
            <div class="content-body">