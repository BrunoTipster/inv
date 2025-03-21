<?php
/**
 * Funções de Autenticação e Autorização
 * 
 * @package InvestSystem
 * @version 1.0.0
 * @author Bruno Tipster
 * @copyright 2025 InvestSystem
 * @last_modified 2025-03-20 23:54:44 UTC
 */

class Auth {
    private static $instance = null;
    private $db;
    private $user = null;

    /**
     * Construtor privado - Padrão Singleton
     */
    private function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->checkSession();
    }

    /**
     * Obtém instância única da classe
     * 
     * @return Auth
     */
    public static function getInstance(): Auth {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Verifica e atualiza a sessão do usuário
     */
    private function checkSession(): void {
        if (isset($_SESSION['user_id'])) {
            $stmt = $this->db->prepare("
                SELECT * FROM users 
                WHERE id = ? AND status = 'active'
                LIMIT 1
            ");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $this->user = $stmt->get_result()->fetch_assoc();

            if (!$this->user) {
                $this->logout();
            }
        } elseif (isset($_COOKIE['remember_token'])) {
            $this->loginWithToken($_COOKIE['remember_token']);
        }
    }

    /**
     * Tenta autenticar usuário com email e senha
     * 
     * @param string $email
     * @param string $password
     * @param bool $remember
     * @return bool
     */
    public function login(string $email, string $password, bool $remember = false): bool {
        try {
            // Verificar tentativas de login
            if ($this->isLoginBlocked($email)) {
                throw new Exception('Muitas tentativas de login. Tente novamente mais tarde.');
            }

            $stmt = $this->db->prepare("
                SELECT * FROM users 
                WHERE email = ? 
                LIMIT 1
            ");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user || !password_verify($password, $user['password'])) {
                $this->incrementLoginAttempts($email);
                throw new Exception('Email ou senha incorretos');
            }

            if ($user['status'] !== 'active') {
                throw new Exception('Conta inativa ou pendente de verificação');
            }

            // Limpar tentativas de login
            $this->clearLoginAttempts($email);

            // Atualizar último login
            $stmt = $this->db->prepare("
                UPDATE users 
                SET last_login = NOW() 
                WHERE id = ?
            ");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();

            // Criar sessão
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['type'];
            $this->user = $user;

            // Criar token de "lembrar-me"
            if ($remember) {
                $this->createRememberToken($user['id']);
            }

            // Registrar log de acesso
            $this->logAccess($user['id'], true);

            return true;

        } catch (Exception $e) {
            $this->logAccess($user['id'] ?? null, false, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Autentica usuário com token "lembrar-me"
     * 
     * @param string $token
     * @return bool
     */
    private function loginWithToken(string $token): bool {
        $stmt = $this->db->prepare("
            SELECT u.* FROM users u
            JOIN remember_tokens rt ON u.id = rt.user_id
            WHERE rt.token = ? AND rt.expires_at > NOW()
            AND u.status = 'active'
            LIMIT 1
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['type'];
            $this->user = $user;

            // Renovar token
            $this->createRememberToken($user['id']);
            return true;
        }

        return false;
    }

    /**
     * Cria token "lembrar-me"
     * 
     * @param int $userId
     */
    private function createRememberToken(int $userId): void {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

        $stmt = $this->db->prepare("
            INSERT INTO remember_tokens (user_id, token, expires_at)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iss", $userId, $token, $expires);
        $stmt->execute();

        setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
    }

    /**
     * Encerra a sessão do usuário
     */
    public function logout(): void {
        if (isset($_COOKIE['remember_token'])) {
            $stmt = $this->db->prepare("
                DELETE FROM remember_tokens 
                WHERE token = ?
            ");
            $stmt->bind_param("s", $_COOKIE['remember_token']);
            $stmt->execute();
            
            setcookie('remember_token', '', time() - 3600, '/');
        }

        session_destroy();
        $this->user = null;
    }

    /**
     * Verifica se usuário está autenticado
     * 
     * @return bool
     */
    public function isLoggedIn(): bool {
        return $this->user !== null;
    }

    /**
     * Verifica se usuário tem determinada permissão
     * 
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool {
        if (!$this->isLoggedIn()) return false;

        $stmt = $this->db->prepare("
            SELECT 1 FROM user_permissions up
            JOIN permissions p ON up.permission_id = p.id
            WHERE up.user_id = ? AND p.name = ?
            LIMIT 1
        ");
        $stmt->bind_param("is", $this->user['id'], $permission);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    /**
     * Verifica se usuário tem determinado papel
     * 
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool {
        return $this->isLoggedIn() && $this->user['type'] === $role;
    }

    /**
     * Retorna dados do usuário autenticado
     * 
     * @return array|null
     */
    public function getUser(): ?array {
        return $this->user;
    }

    /**
     * Verifica se IP está bloqueado para login
     * 
     * @param string $email
     * @return bool
     */
    private function isLoginBlocked(string $email): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result['attempts'] >= MAX_LOGIN_ATTEMPTS;
    }

    /**
     * Incrementa contagem de tentativas de login
     * 
     * @param string $email
     */
    private function incrementLoginAttempts(string $email): void {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (email, ip_address) 
            VALUES (?, ?)
        ");
        $stmt->bind_param("ss", $email, $_SERVER['REMOTE_ADDR']);
        $stmt->execute();
    }

    /**
     * Limpa tentativas de login
     * 
     * @param string $email
     */
    private function clearLoginAttempts(string $email): void {
        $stmt = $this->db->prepare("
            DELETE FROM login_attempts 
            WHERE email = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
    }

    /**
     * Registra log de acesso
     * 
     * @param int|null $userId
     * @param bool $success
     * @param string|null $message
     */
    private function logAccess(?int $userId, bool $success, ?string $message = null): void {
        $stmt = $this->db->prepare("
            INSERT INTO access_logs (
                user_id, ip_address, user_agent, 
                success, message, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        
        $stmt->bind_param("issss", 
            $userId, 
            $ip, 
            $userAgent, 
            $success, 
            $message
        );
        $stmt->execute();
    }
}