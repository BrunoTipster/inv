<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Se já está logado, redireciona
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['user_type'] == 'admin' ? 'admin/dashboard.php' : 'client/dashboard.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    $db = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if ($user['status'] == 'pending') {
            $error = "Por favor, verifique seu email para ativar sua conta.";
        } elseif ($user['status'] == 'suspended') {
            $error = "Sua conta está suspensa. Entre em contato com o suporte.";
        } elseif (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['type'];
            $_SESSION['username'] = $user['username'];

            // Atualizar último login
            $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();

            // Criar cookie de "lembrar-me"
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $stmt = $conn->prepare("
                    INSERT INTO remember_tokens (user_id, token, expires_at) 
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("iss", $user['id'], $token, $expires);
                $stmt->execute();

                setcookie('remember_token', $token, time() + (86400 * 30), '/');
            }

            header('Location: ' . ($user['type'] == 'admin' ? 'admin/dashboard.php' : 'client/dashboard.php'));
            exit;
        } else {
            $error = "Senha incorreta";
        }
    } else {
        $error = "Email não encontrado";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - InvestSystem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%);
        }
        .login-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 30px;
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="login-card">
                        <div class="text-center mb-4">
                            <img src="/investment/images/logo.png" alt="InvestSystem" height="60">
                            <h2 class="mt-3">Bem-vindo de volta!</h2>
                            <p class="text-muted">Faça login para acessar sua conta</p>
                        </div>

                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required 
                                       value="<?php echo $_POST['email'] ?? ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Senha</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="remember" id="remember">
                                    <label class="form-check-label" for="remember">Lembrar-me</label>
                                </div>
                                <a href="forgot-password.php">Esqueceu a senha?</a>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">Entrar</button>

                            <div class="text-center">
                                <p class="mb-0">
                                    Não tem uma conta? <a href="register.php">Criar Conta</a>
                                </p>
                            </div>
                        </form>

                        <!-- Botões de login social -->
                        <div class="mt-4">
                            <p class="text-center text-muted">Ou entre com</p>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary">
                                    <i class="bi bi-google"></i> Google
                                </button>
                                <button class="btn btn-outline-primary">
                                    <i class="bi bi-facebook"></i> Facebook
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Validação do formulário
    (function() {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
    </script>
</body>
</html>