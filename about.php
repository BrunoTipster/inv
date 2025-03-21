<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre Nós - InvestSystem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/investment/css/main.css">
    <style>
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        .timeline::before {
            content: '';
            position: absolute;
            width: 2px;
            background: #1a237e;
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -1px;
        }
        .timeline-item {
            margin-bottom: 40px;
            position: relative;
        }
        .timeline-content {
            margin-left: 50%;
            padding-left: 30px;
            position: relative;
        }
        .timeline-item:nth-child(even) .timeline-content {
            margin-left: 0;
            margin-right: 50%;
            padding-left: 0;
            padding-right: 30px;
            text-align: right;
        }
        .timeline-dot {
            width: 20px;
            height: 20px;
            background: #1a237e;
            border-radius: 50%;
            position: absolute;
            left: 50%;
            top: 0;
            margin-left: -10px;
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="bg-primary text-white py-5">
        <div class="container text-center">
            <h1>Nossa História</h1>
            <p class="lead">Construindo o futuro dos investimentos desde 2020</p>
        </div>
    </section>

    <!-- Sobre Nós -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <img src="/investment/images/about-us.jpg" alt="Sobre Nós" class="img-fluid rounded">
                </div>
                <div class="col-md-6">
                    <h2>Quem Somos</h2>
                    <p class="lead">Uma plataforma inovadora focada em democratizar o acesso a investimentos de qualidade.</p>
                    <p>Fundada em 2020, a InvestSystem nasceu com o objetivo de tornar os investimentos mais acessíveis e transparentes para todos. Nossa equipe é formada por profissionais experientes do mercado financeiro e tecnologia.</p>
                    <div class="row mt-4">
                        <div class="col-6">
                            <h4>Missão</h4>
                            <p>Democratizar o acesso a investimentos de qualidade de forma segura e transparente.</p>
                        </div>
                        <div class="col-6">
                            <h4>Visão</h4>
                            <p>Ser a principal plataforma de investimentos do Brasil até 2025.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Nossa Trajetória</h2>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h4>2020</h4>
                        <p>Fundação da empresa e lançamento da plataforma beta</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h4>2021</h4>
                        <p>Expansão da base de usuários e novos pacotes de investimento</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h4>2022</h4>
                        <p>Alcance de 10.000 investidores ativos</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h4>2023</h4>
                        <p>Lançamento de novos produtos e serviços financeiros</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h4>2024</h4>
                        <p>Expansão internacional e novas parcerias estratégicas</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Equipe -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Nossa Equipe</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card text-center">
                        <img src="/investment/images/team/ceo.jpg" class="card-img-top" alt="CEO">
                        <div class="card-body">
                            <h5 class="card-title">João Silva</h5>
                            <p class="card-text text-muted">CEO & Fundador</p>
                            <p>15 anos de experiência em mercado financeiro</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card text-center">
                        <img src="/investment/images/team/cto.jpg" class="card-img-top" alt="CTO">
                        <div class="card-body">
                            <h5 class="card-title">Maria Santos</h5>
                            <p class="card-text text-muted">CTO</p>
                            <p>Especialista em tecnologia financeira</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card text-center">
                        <img src="/investment/images/team/cfo.jpg" class="card-img-top" alt="CFO">
                        <div class="card-body">
                            <h5 class="card-title">Pedro Oliveira</h5>
                            <p class="card-text text-muted">CFO</p>
                            <p>20 anos de experiência em gestão financeira</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contato -->
    <section class="py-5 bg-light">
        <div class="container text-center">
            <h2 class="mb-4">Quer Saber Mais?</h2>
            <p class="lead mb-4">Entre em contato conosco e descubra como podemos ajudar você a alcançar seus objetivos financeiros</p>
            <a href="contact.php" class="btn btn-primary btn-lg">Fale Conosco</a>
        </div>
    </section>

    <?php require_once 'includes/footer.php'; ?>
</body>
</html>