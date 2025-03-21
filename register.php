<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/validation.php';

// Se já está logado, redireciona
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['user_type'] == 'admin' ? 'admin/dashboard.php' : 'client/dashboard.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $terms = isset($_POST['terms']);

    // Validações
    $errors = [];

    if (!$full_name || !$email || !$phone || !$password || !$confirm_password) {
        $errors[] = "Todos os campos são obrigatórios.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email inválido.";
    }

    if (strlen($password) < 6) {
        $errors[] = "A senha deve ter no mínimo 6 caracteres.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "As senhas não conferem.";
    }

    if (!$terms) {
        $errors[] = "Você deve aceitar os termos de uso.";
    }

    // Verificar se email já existe
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Este email já está cadastrado.";
    }

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));
        $username = generateUsername($full_name);

        $stmt = $conn->prepare("
            INSERT INTO users (
                username, full_name, email, phone, 
                password, type, status, created_at, 
                verification_token
            ) VALUES (
                ?, ?, ?, ?, 
                ?, 'client', 'pending', NOW(), 
                ?
            )
        ");
        $stmt->bind_param("ssssss", 
            $username, 
            $full_name, 
            $email, 
            $phone, 
            $password_hash, 
            $token
        );

        if ($stmt->execute()) {
            // Enviar email de verificação
            $verification_link = "https://investsystem.com/verify.php?token=" . $token;
            $to = $email;
            $subject = "Confirme seu cadastro - InvestSystem";
            $message = "Olá $full_name,\n\n";
            $message .= "Bem-vindo à InvestSystem! Por favor, confirme seu email clicando no link abaixo:\n\n";
            $message .= $verification_link . "\n\n";
            $message .= "Atenciosamente,\nEquipe InvestSystem";
            $headers = "From: noreply@investsystem.com";

            mail($to, $subject, $message, $headers);

            $success = "Cadastro realizado com sucesso! Verifique seu email para ativar sua conta.";
        } else {
            $errors[] = "Erro ao criar conta. Tente novamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - InvestSystem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .register-container {
            min-height: 100vh;
            padding: 40px 0;
        }
        .register-card {
            max-width: 500px;
            margin: 0 auto;
        }
        .password-strength {
            height: 5px;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-light">
    <div class="register-container">
        <div class="container">
            <div class="register-card card shadow">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h2>Criar Nova Conta</h2>
                        <p class="text-muted">Comece sua jornada de investimentos</p>
                    </div>

                    <?php if(!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($success)): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                            <hr>
                            <a href="login.php" class="btn btn-success">Ir para Login</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" name="full_name" 
                                       value="<?php echo $_POST['full_name'] ?? ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo $_POST['email'] ?? ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="tel" class="form-control" name="phone" 
                                       value="<?php echo $_POST['phone'] ?? ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Senha</label>
                                <input type="password" class="form-control" name="password" 
                                       id="password" minlength="6" required>
                                <div class="password-strength mt-2"></div>
                                <small class="text-muted">Mínimo 6 caracteres</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirmar Senha</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="terms" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    Li e aceito os <a href="terms.php">Termos de Uso</a> e 
                                    <a href="privacy.php">Política de Privacidade</a>
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">Criar Conta</button>
                            
                            <div class="text-center">
                                <p class="mb-0">
                                    Já tem uma conta? <a href="login.php">Fazer Login</a>
                                </p>
                            </div>
                        </form>
                    <?php endif; ?>
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

    // Verificador de força da senha
    document.getElementById('password').addEventListener('input', function() {
        var password = this.value;
        var strength = 0;
        
        if(password.length >= 6) strength += 20;
        if(password.match(/[a-z]/)) strength += 20;
        if(password.match(/[A-Z]/)) strength += 20;
        if(password.match(/[0-9]/)) strength += 20;
        if(password.match(/[^a-zA-Z0-9]/)) strength += 20;

        var strengthBar = document.querySelector('.password-strength');
        strengthBar.style.width = strength + '%';
        
        if(strength < 40) {
            strengthBar.style.backgroundColor = '#dc3545';
        } else if(strength < 80) {
            strengthBar.style.backgroundColor = '#ffc107';
        } else {
            strengthBar.style.backgroundColor = '#198754';
        }
    });
    </script>
</body>
</html>