<?php
/**
 * Funções Utilitárias Globais
 * 
 * @package InvestSystem
 * @version 1.0.0
 * @author Bruno Tipster
 * @copyright 2025 InvestSystem
 * @last_modified 2025-03-20 23:57:58 UTC
 */

/**
 * Gera código único para transações
 * 
 * @param string $prefix Prefixo do código (INV, DEP, WIT, etc)
 * @return string
 */
function generateTransactionCode(string $prefix): string {
    return $prefix . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

/**
 * Formata valor monetário
 * 
 * @param float $value
 * @param bool $withSymbol
 * @return string
 */
function formatCurrency(float $value, bool $withSymbol = true): string {
    return ($withSymbol ? 'R$ ' : '') . number_format($value, 2, ',', '.');
}

/**
 * Formata data e hora
 * 
 * @param string $datetime
 * @param bool $showTime
 * @return string
 */
function formatDateTime(string $datetime, bool $showTime = true): string {
    $date = new DateTime($datetime);
    return $date->format($showTime ? 'd/m/Y H:i' : 'd/m/Y');
}

/**
 * Gera username único baseado no nome
 * 
 * @param string $name
 * @return string
 */
function generateUsername(string $name): string {
    $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
    $username = substr($username, 0, 15);
    $username .= rand(100, 999);
    return $username;
}

/**
 * Valida força da senha
 * 
 * @param string $password
 * @return array
 */
function checkPasswordStrength(string $password): array {
    $strength = 0;
    $messages = [];

    if (strlen($password) >= 8) {
        $strength += 25;
    } else {
        $messages[] = "Senha deve ter no mínimo 8 caracteres";
    }

    if (preg_match('/[A-Z]/', $password)) {
        $strength += 25;
    } else {
        $messages[] = "Senha deve conter letra maiúscula";
    }

    if (preg_match('/[a-z]/', $password)) {
        $strength += 25;
    } else {
        $messages[] = "Senha deve conter letra minúscula";
    }

    if (preg_match('/[0-9]/', $password)) {
        $strength += 25;
    } else {
        $messages[] = "Senha deve conter número";
    }

    return [
        'strength' => $strength,
        'messages' => $messages
    ];
}

/**
 * Gera token seguro
 * 
 * @param int $length
 * @return string
 */
function generateToken(int $length = 32): string {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Envia email usando template
 * 
 * @param string $to
 * @param string $subject
 * @param string $template
 * @param array $data
 * @return bool
 */
function sendEmail(string $to, string $subject, string $template, array $data): bool {
    $template_file = BASE_PATH . '/templates/emails/' . $template . '.html';
    
    if (!file_exists($template_file)) {
        return false;
    }

    $content = file_get_contents($template_file);
    foreach ($data as $key => $value) {
        $content = str_replace('{{' . $key . '}}', $value, $content);
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: InvestSystem <noreply@investsystem.com>',
        'Reply-To: suporte@investsystem.com',
        'X-Mailer: PHP/' . phpversion()
    ];

    return mail($to, $subject, $content, implode("\r\n", $headers));
}

/**
 * Filtra e sanitiza input
 * 
 * @param mixed $data
 * @return mixed
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Calcula retorno de investimento
 * 
 * @param float $amount
 * @param float $rate
 * @param int $days
 * @return float
 */
function calculateReturn(float $amount, float $rate, int $days): float {
    return $amount * ($rate / 100) * ($days / 30);
}

/**
 * Verifica se é dispositivo móvel
 * 
 * @return bool
 */
function isMobile(): bool {
    return preg_match('/Mobile|Android|iPhone/i', $_SERVER['HTTP_USER_AGENT']);
}

/**
 * Limita string com reticências
 * 
 * @param string $text
 * @param int $length
 * @return string
 */
function truncateText(string $text, int $length = 100): string {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

/**
 * Registra log personalizado
 * 
 * @param string $message
 * @param string $type
 * @param array $context
 */
function logActivity(string $message, string $type = 'info', array $context = []): void {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => $type,
        'message' => $message,
        'user_id' => $_SESSION['user_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'context' => $context
    ];

    $log_file = LOG_PATH . '/' . date('Y-m-d') . '.log';
    file_put_contents($log_file, json_encode($log) . "\n", FILE_APPEND);
}

/**
 * Verifica limite de requisições
 * 
 * @param string|int $identifier
 * @param int $limit
 * @param int $interval
 * @return array
 */
function checkRateLimit($identifier, int $limit = 60, int $interval = 60): array {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);

    $key = "rate_limit:{$identifier}";
    $current = $redis->get($key) ?: 0;

    if ($current >= $limit) {
        return [
            'allowed' => false,
            'remaining' => 0,
            'reset' => $redis->ttl($key)
        ];
    }

    $redis->incr($key);
    if ($current == 0) {
        $redis->expire($key, $interval);
    }

    return [
        'allowed' => true,
        'remaining' => $limit - $current - 1,
        'reset' => $redis->ttl($key)
    ];
}

/**
 * Converte tamanho de arquivo para formato legível
 * 
 * @param int $bytes
 * @return string
 */
function formatFileSize(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Gera slug amigável para URL
 * 
 * @param string $text
 * @return string
 */
function generateSlug(string $text): string {
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = preg_replace('/[^a-zA-Z0-9\s]/', '', $text);
    $text = strtolower(trim($text));
    $text = preg_replace('/[\s]+/', '-', $text);
    return $text;
}