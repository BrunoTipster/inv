<?php
/**
 * Configurações Globais do Sistema
 * 
 * @package InvestSystem
 * @version 1.0.0
 * @author Bruno Tipster
 * @copyright 2025 InvestSystem
 * @last_modified 2025-03-21 00:29:08 UTC
 */

// Previne acesso direto ao arquivo
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Configurações de Ambiente
define('ENVIRONMENT', 'development'); // development, testing, production
define('DEBUG', ENVIRONMENT === 'development');

// Configurações de Timezone
date_default_timezone_set('UTC');
setlocale(LC_ALL, 'pt_BR.UTF-8');

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'investment_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurações de URL
define('SITE_URL', 'http://localhost/investment');
define('API_URL', SITE_URL . '/api');

// Configurações de Sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', ENVIRONMENT === 'production');
session_name('INVESTSESSION');

// Configurações de Email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'seu-email@gmail.com');
define('SMTP_PASS', 'sua-senha-app');
define('MAIL_FROM', 'noreply@investsystem.com');
define('MAIL_NAME', 'InvestSystem');

// Diretórios do Sistema
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('TEMPLATES_PATH', BASE_PATH . '/templates');
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('LOG_PATH', BASE_PATH . '/logs');

// Configurações de Upload
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);

// Configurações de Investimento
define('MIN_INVESTMENT', 100.00);
define('MAX_INVESTMENT', 1000000.00);
define('DEFAULT_CURRENCY', 'BRL');

// Configurações de Segurança
define('HASH_COST', 12); // Custo do bcrypt
define('TOKEN_EXPIRY', 7200); // 2 horas em segundos
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutos em segundos

// Configurações de API
define('API_TOKEN_EXPIRY', 3600); // 1 hora em segundos
define('API_RATE_LIMIT', 100); // requisições por hora

// Configurações de Cache
define('CACHE_ENABLED', true);
define('CACHE_EXPIRY', 3600); // 1 hora em segundos

// Configurações de Logs
define('LOG_ERRORS', true);
define('LOG_QUERIES', ENVIRONMENT === 'development');
define('LOG_ACTIONS', true);

// Headers de Segurança
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
if (ENVIRONMENT === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// Configuração de Erro
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Funções de Autoload
spl_autoload_register(function ($class) {
    $file = INCLUDES_PATH . '/' . strtolower($class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Inicialização de Sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificação de HTTPS em produção
if (ENVIRONMENT === 'production' && !isset($_SERVER['HTTPS'])) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
