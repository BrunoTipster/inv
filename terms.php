<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Buscar última versão dos termos
$db = Database::getInstance();
$conn = $db->getConnection();

$terms = $conn->query("
    SELECT * FROM terms_of_service 
    WHERE status = 'active' 
    ORDER BY version DESC 
    LIMIT 1
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termos de Serviço - InvestSystem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/investment/css/main.css">
    <style>
        .terms-content {
            font-size: 1.1rem;
            line-height: 1.8;
        }
        .terms-content h2 {
            color: #1a237e;
            margin-top: 2rem;
        }
        .terms-content p {
            margin-bottom: 1.5rem;
        }
        .terms-nav {
            position: sticky;
            top: 20px;
        }
        .terms-nav .nav-link {
            color: #495057;
        }
        .terms-nav .nav-link.active {
            color: #1a237e;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <!-- Navegação Lateral -->
            <div class="col-lg-3">
                <div class="terms-nav">
                    <h5>Sumário</h5>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="#introducao">Introdução</a>
                        <a class="nav-link" href="#servicos">Serviços</a>
                        <a class="nav-link" href="#conta">Sua Conta</a>
                        <a class="nav-link" href="#responsabilidades">Responsabilidades</a>
                        <a class="nav-link" href="#pagamentos">Pagamentos</a>
                        <a class="nav-link" href="#privacidade">Privacidade</a>
                        <a class="nav-link" href="#modificacoes">Modificações</a>
                        <a class="nav-link" href="#encerramento">Encerramento</a>
                        <a class="nav-link" href="#legislacao">Legislação Aplicável</a>
                    </nav>
                    <div class="mt-4">
                        <small class="text-muted">
                            Última atualização: <?php echo date('d/m/Y', strtotime($terms['updated_at'])); ?><br>
                            Versão: <?php echo $terms['version']; ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Conteúdo dos Termos -->
            <div class="col-lg-9">
                <div class="terms-content">
                    <h1 class="mb-4">Termos de Serviço</h1>

                    <div class="alert alert-info">
                        <strong>Nota Importante:</strong> Ao utilizar nossos serviços, você concorda com estes termos. 
                        Por favor, leia-os atentamente.
                    </div>

                    <section id="introducao">
                        <h2>1. Introdução</h2>
                        <p>Ao acessar e utilizar a plataforma InvestSystem, você concorda em cumprir e estar vinculado aos 
                        seguintes termos e condições de uso. Se você não concordar com qualquer parte destes termos, 
                        não deverá utilizar nossos serviços.</p>
                    </section>

                    <section id="servicos">
                        <h2>2. Serviços</h2>
                        <p>A InvestSystem oferece uma plataforma de investimentos online que permite aos usuários:</p>
                        <ul>
                            <li>Realizar investimentos em diferentes pacotes</li>
                            <li>Acompanhar rendimentos</li>
                            <li>Realizar depósitos e saques</li>
                            <li>Gerenciar sua carteira de investimentos</li>
                        </ul>
                    </section>

                    <section id="conta">
                        <h2>3. Sua Conta</h2>
                        <p>Para utilizar nossos serviços, você deve:</p>
                        <ul>
                            <li>Ter pelo menos 18 anos de idade</li>
                            <li>Fornecer informações verdadeiras e precisas</li>
                            <li>Manter suas informações atualizadas</li>
                            <li>Proteger suas credenciais de acesso</li>
                        </ul>
                    </section>

                    <section id="responsabilidades">
                        <h2>4. Responsabilidades</h2>
                        <p>Você é responsável por:</p>
                        <ul>
                            <li>Todas as atividades realizadas em sua conta</li>
                            <li>Manter a confidencialidade de suas credenciais</li>
                            <li>Cumprir todas as leis e regulamentos aplicáveis</li>
                            <li>Reportar imediatamente qualquer uso não autorizado</li>
                        </ul>
                    </section>

                    <section id="pagamentos">
                        <h2>5. Pagamentos</h2>
                        <p>Aspectos importantes sobre pagamentos:</p>
                        <ul>
                            <li>Todos os valores são processados em Reais (BRL)</li>
                            <li>Taxas e comissões serão claramente informadas</li>
                            <li>Prazos de processamento dependem do método escolhido</li>
                            <li>Reembolsos seguem política específica</li>
                        </ul>
                    </section>

                    <section id="privacidade">
                        <h2>6. Privacidade</h2>
                        <p>Nossa Política de Privacidade descreve como coletamos, usamos e protegemos suas informações. 
                        Ao usar nossos serviços, você concorda com nossas práticas de privacidade.</p>
                    </section>

                    <section id="modificacoes">
                        <h2>7. Modificações</h2>
                        <p>Reservamo-nos o direito de modificar estes termos a qualquer momento. Alterações significativas 
                        serão notificadas aos usuários.</p>
                    </section>

                    <section id="encerramento">
                        <h2>8. Encerramento</h2>
                        <p>Podemos encerrar ou suspender sua conta por violação destes termos. Você pode encerrar sua 
                        conta a qualquer momento, seguindo nossas políticas de encerramento.</p>
                    </section>

                    <section id="legislacao">
                        <h2>9. Legislação Aplicável</h2>
                        <p>Estes termos são regidos pelas leis do Brasil. Qualquer disputa será resolvida nos tribunais 
                        brasileiros competentes.</p>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>

    <script>
    // Ativar links do menu lateral conforme scroll
    document.addEventListener('DOMContentLoaded', function() {
        const sections = document.querySelectorAll('.terms-content section');
        const navLinks = document.querySelectorAll('.terms-nav .nav-link');

        function onScroll() {
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                if (pageYOffset >= sectionTop - 100) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href').substring(1) === current) {
                    link.classList.add('active');
                }
            });
        }

        window.addEventListener('scroll', onScroll);
    });

    // Scroll suave para as seções
    document.querySelectorAll('.terms-nav .nav-link').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const section = document.querySelector(this.getAttribute('href'));
            window.scrollTo({
                top: section.offsetTop - 80,
                behavior: 'smooth'
            });
        });
    });
    </script>
</body>
</html>