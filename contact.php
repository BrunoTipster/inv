<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/validation.php';

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

    if (!$name || !$email || !$subject || !$message) {
        $error = "Por favor, preencha todos os campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor, insira um email válido.";
    } else {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO contact_messages (name, email, subject, message, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            $success = "Mensagem enviada com sucesso! Em breve entraremos em contato.";
            
            // Enviar email de confirmação
            $to = $email;
            $subject = "Recebemos sua mensagem - InvestSystem";
            $message = "Olá $name,\n\nRecebemos sua mensagem e responderemos em breve.\n\nAtenciosamente,\nEquipe InvestSystem";
            $headers = "From: contato@investsystem.com";
            
            mail($to, $subject, $message, $headers);
        } else {
            $error = "Erro ao enviar mensagem. Tente novamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contato - InvestSystem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/investment/css/main.css">
</head>
<body>
    <?php require_once 'includes/header.php'; ?>

    <section class="bg-primary text-white py-5">
        <div class="container text-center">
            <h1>Entre em Contato</h1>
            <p class="lead">Estamos aqui para ajudar você em sua jornada de investimentos</p>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Informações de Contato -->
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="mb-4">Informações de Contato</h4>
                            <ul class="list-unstyled">
                                <li class="mb-3">
                                    <i class="bi bi-geo-alt text-primary me-2"></i>
                                    Av. Paulista, 1000<br>
                                    São Paulo - SP
                                </li>
                                <li class="mb-3">
                                    <i class="bi bi-telephone text-primary me-2"></i>
                                    (11) 98765-4321
                                </li>
                                <li class="mb-3">
                                    <i class="bi bi-envelope text-primary me-2"></i>
                                    contato@investsystem.com
                                </li>
                                <li class="mb-3">
                                    <i class="bi bi-clock text-primary me-2"></i>
                                    Segunda a Sexta: 9h às 18h
                                </li>
                            </ul>

                            <h5 class="mt-4 mb-3">Redes Sociais</h5>
                            <div class="social-links">
                                <a href="#" class="btn btn-outline-primary me-2">
                                    <i class="bi bi-facebook"></i>
                                </a>
                                <a href="#" class="btn btn-outline-primary me-2">
                                    <i class="bi bi-instagram"></i>
                                </a>
                                <a href="#" class="btn btn-outline-primary me-2">
                                    <i class="bi bi-linkedin"></i>
                                </a>
                                <a href="#" class="btn btn-outline-primary">
                                    <i class="bi bi-twitter"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulário de Contato -->
                <div class="col-md-8 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="mb-4">Envie sua Mensagem</h4>

                            <?php if($success): ?>
                                <div class="alert alert-success"><?php echo $success; ?></div>
                            <?php endif; ?>

                            <?php if($error): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>

                            <form method="POST" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nome</label>
                                        <input type="text" class="form-control" name="name" required>
                                        <div class="invalid-feedback">
                                            Por favor, insira seu nome.
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" required>
                                        <div class="invalid-feedback">
                                            Por favor, insira um email válido.
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Assunto</label>
                                    <select name="subject" class="form-select" required>
                                        <option value="">Selecione um assunto</option>
                                        <option value="Dúvidas sobre investimentos">Dúvidas sobre investimentos</option>
                                        <option value="Suporte técnico">Suporte técnico</option>
                                        <option value="Parceria comercial">Parceria comercial</option>
                                        <option value="Outros assuntos">Outros assuntos</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Por favor, selecione um assunto.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Mensagem</label>
                                    <textarea name="message" class="form-control" rows="5" required></textarea>
                                    <div class="invalid-feedback">
                                        Por favor, escreva sua mensagem.
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">Enviar Mensagem</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mapa -->
    <section class="py-5 bg-light">
        <div class="container">
            <h4 class="text-center mb-4">Nossa Localização</h4>
            <div class="ratio ratio-21x9">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3657.098337788599!2d-46.65268208451437!3d-23.564616167596592!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94ce59c8da0aa315%3A0xd59f9431f2c9776a!2sAv.%20Paulista%2C%20S%C3%A3o%20Paulo%20-%20SP!5e0!3m2!1spt-BR!2sbr!4v1635901234567!5m2!1spt-BR!2sbr" 
                        allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </section>

    <?php require_once 'includes/footer.php'; ?>

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