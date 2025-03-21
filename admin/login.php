<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Se já está logado como admin, redireciona para o dashboard
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Processamento do formulário de login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT id, password, type FROM users WHERE email = ? AND type = 'admin'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['type'];
            
            // Log do login
            $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, 'admin_login')");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            
            header('Location: dashboard.php');
            exit;
        }
    }
    $error = "Email ou senha inválidos";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrativo - InvestSystem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(45deg, #1a237e, #0d47a1);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 90%;
        }
        .login-card h2 {
            color: #1a237e;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .btn-primary {
            background: #1a237e;
            border: none;
        }
        .btn-primary:hover {
            background: #0d47a1;
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Área Administrativa</h2>
        
        <?php if(isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required 
                       placeholder="Seu email administrativo">
                <div class="invalid-feedback">
                    Por favor, insira um email válido.
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Senha</label>
                <input type="password" class="form-control" id="password" name="password" required 
                       placeholder="Sua senha">
                <div class="invalid-feedback">
                    Por favor, insira sua senha.
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">Entrar</button>
                <a href="../index.php" class="btn btn-outline-secondary">Voltar para o Site</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validação do formulário
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>