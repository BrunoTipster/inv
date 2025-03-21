<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Buscar última versão da política de privacidade
$db = Database::getInstance();
$conn = $db->getConnection();

$privacy = $conn->query("
    SELECT * FROM privacy_policy 
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
    <title>Política de Privacidade - InvestSystem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/investment/css/main.css">
    <style>
        .privacy-content {
            font-size: 1.1rem;
            line-height: 1.8;
        }
        .privacy-content h2 {
            color: #1a237e;
            margin-top: 2rem;
        }
        .privacy-content p {
            margin-bottom: 1.5rem;
        }
        .privacy-nav {
            position: sticky;
            top: 20px;
        }
        .privacy-nav .nav-link {
            color: #495057;
        }
        .privacy-nav .nav-link.active {
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
                <div class="privacy-nav">
                    <h5>Índice</h5>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="#introducao">Introdução</a>
                        <a class="nav-link" href="#coleta">Coleta de Dados</a>
                        <a class="nav-link" href="#uso">Uso dos Dados</a>
                        <a class="nav-link" href="#compartilhamento">Compartilhamento</a>
                        <a class="nav-link" href="#seguranca">Segurança</a>
                        <a class="nav-link" href="#cookies">Cookies</a>
                        <a class="nav-link" href="#direitos">Seus Direitos</a>
                        <a class="nav-link" href="#contato">Contato</a>
                    </nav>
                    <div class="mt-4">
                        <small class="text-muted">
                            Última atualização: <?php echo date('d/m/Y', strtotime($privacy['updated_at'])); ?><br>
                            Versão: <?php echo $privacy['version']; ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Conteúdo da Política -->
            <div class="col-lg-9">
                <div class="privacy-content">
                    <h1 class="mb-4">Política de Privacidade</h1>

                    <div class="alert alert-info">
                        <strong>Compromisso com sua Privacidade:</strong> Levamos a proteção dos seus dados muito a sério. 
                        Esta política explica como tratamos suas informações.
                    </div>

                    <section id="introducao">
                        <h2>1. Introdução</h2>
                        <p>A InvestSystem está comprometida em proteger sua privacidade. Esta política descreve como 
                        coletamos, usamos, armazenamos e protegemos suas informações pessoais.</p>
                    </section>

                    <section id="coleta">
                        <h2>2. Coleta de Dados</h2>
                        <p>Coletamos os seguintes tipos de informações:</p>
                        <ul>
                            <li>Informações de identificação pessoal</li>
                            <li>Dados de contato</li>
                            <li>Informações financeiras</li>
                            <li>Dados de uso da plataforma</li>
                            <li>Informações do dispositivo</li>
                        </ul>
                    </section>

                    <section id="uso">
                        <h2>3. Uso dos Dados</h2>
                        <p>Utilizamos suas informações para:</p>
                        <ul>
                            <li>Processar suas transações</li>
                            <li>Manter sua conta segura</li>
                            <li>Melhorar nossos serviços</li>
                            <li>Enviar comunicações importantes</li>
                            <li>Cumprir obrigações legais</li>
                        </ul>
                    </section>

                    <section id="compartilhamento">
                        <h2>4. Compartilhamento de Dados</h2>
                        <p>Podemos compartilhar suas informações com:</p>
                        <ul>
                            <li>Parceiros de processamento de pagamento</li>
                            <li>Prestadores de serviços autorizados</li>
                            <li>Autoridades reguladoras</li>
                            <li>Quando exigido por lei</li>
                        </ul>
                    </section>

                    <section id="seguranca">
                        <h2>5. Segurança dos Dados</h2>
                        <p>Implementamos medidas de segurança para proteger suas informações:</p>
                        <ul>
                            <li>Criptografia de dados sensíveis</li>
                            <li>Controles de acesso rigorosos</li>
                            <li>Monitoramento contínuo</li>
                            <li>Backups regulares</li>
                        </ul>
                    </section>

                    <section id="cookies">
                        <h2>6. Cookies e Tecnologias Similares</h2>
                        <p>Utilizamos cookies para:</p>
                        <ul>
                            <li>Manter sua sessão ativa</li>
                            <li>Lembrar suas preferências</li>
                            <li>Analisar o uso da plataforma</li>
                            <li>Melhorar a experiência do usuário</li>
                        </ul>
                    </section>

                    <section id="direitos">
                        <h2>7. Seus Direitos</h2>
                        <p>Você tem direito a:</p>
                        <ul>
                            <li>Acessar seus dados</li>
                            <li>Corrigir informações incorretas</li>
                            <li>Solicitar exclusão de dados</li>
                            <li>Portabilidade dos dados</li>
                            <li>Revogar consentimento</li>
                        </ul>
                    </section>

                    <section id="contato">
                        <h2>8. Contato</h2>
                        <p>Para questões sobre privacidade:</p>
                        <ul>
                            <li>Email: privacidade@investsystem.com</li>
                            <li>Telefone: (11) 98765-4321</li>
                            <li>Endereço: Av. Paulista, 1000 - São Paulo/SP</li>
                        </ul>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>

    <script>
    // Ativar links do menu lateral conforme scroll
    document.addEventListener('DOMContentLoaded', function() {
        const sections = document.querySelectorAll('.privacy-content section');
        const navLinks = document.querySelectorAll('.privacy-nav .nav-link');

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
    document.querySelectorAll('.privacy-nav .nav-link').forEach(anchor => {
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