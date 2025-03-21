<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Buscar FAQs do banco de dados
$faqs = $conn->query("
    SELECT * FROM faqs 
    WHERE status = 'active' 
    ORDER BY category, position
")->fetch_all(MYSQLI_ASSOC);

// Organizar FAQs por categoria
$faq_categories = [];
foreach ($faqs as $faq) {
    $faq_categories[$faq['category']][] = $faq;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perguntas Frequentes - InvestSystem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/investment/css/main.css">
    <style>
        .faq-search {
            background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%);
            padding: 60px 0;
        }
        .faq-category {
            border-left: 4px solid #1a237e;
            margin-bottom: 30px;
        }
        .accordion-button:not(.collapsed) {
            background-color: #e8eaf6;
            color: #1a237e;
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>

    <!-- Área de Busca -->
    <section class="faq-search text-white">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
                    <h1 class="mb-4">Como podemos ajudar?</h1>
                    <div class="search-box">
                        <input type="text" id="faqSearch" class="form-control form-control-lg" 
                               placeholder="Digite sua dúvida aqui...">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQs -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Menu de Categorias -->
                <div class="col-md-3 mb-4">
                    <div class="list-group">
                        <?php foreach($faq_categories as $category => $items): ?>
                        <a href="#category-<?php echo strtolower(str_replace(' ', '-', $category)); ?>" 
                           class="list-group-item list-group-item-action">
                            <?php echo $category; ?>
                            <span class="badge bg-primary float-end"><?php echo count($items); ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Accordion de FAQs -->
                <div class="col-md-9">
                    <?php foreach($faq_categories as $category => $items): ?>
                        <div class="faq-category mb-4" id="category-<?php echo strtolower(str_replace(' ', '-', $category)); ?>">
                            <h3 class="mb-4"><?php echo $category; ?></h3>
                            <div class="accordion" id="accordion-<?php echo strtolower(str_replace(' ', '-', $category)); ?>">
                                <?php foreach($items as $index => $faq): ?>
                                    <div class="accordion-item faq-item">
                                        <h4 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" 
                                                    data-bs-toggle="collapse" 
                                                    data-bs-target="#faq-<?php echo $faq['id']; ?>">
                                                <?php echo htmlspecialchars($faq['question']); ?>
                                            </button>
                                        </h4>
                                        <div id="faq-<?php echo $faq['id']; ?>" 
                                             class="accordion-collapse collapse" 
                                             data-bs-parent="#accordion-<?php echo strtolower(str_replace(' ', '-', $category)); ?>">
                                            <div class="accordion-body">
                                                <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Não Encontrou sua Dúvida? -->
    <section class="py-5 bg-light">
        <div class="container text-center">
            <h3>Não encontrou o que procurava?</h3>
            <p class="lead mb-4">Nossa equipe está pronta para ajudar você</p>
            <div class="row justify-content-center">
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="bi bi-headset display-4 text-primary"></i>
                            <h4 class="mt-3">Suporte 24/7</h4>
                            <p>Entre em contato com nossa equipe de suporte a qualquer momento</p>
                            <a href="contact.php" class="btn btn-primary">Contatar Suporte</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="bi bi-chat-dots display-4 text-primary"></i>
                            <h4 class="mt-3">Chat Online</h4>
                            <p>Converse em tempo real com nossos especialistas</p>
                            <button class="btn btn-primary" onclick="openChat()">Iniciar Chat</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php require_once 'includes/footer.php'; ?>

    <script>
    // Busca nas FAQs
    document.getElementById('faqSearch').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const faqItems = document.querySelectorAll('.faq-item');
        
        faqItems.forEach(item => {
            const question = item.querySelector('.accordion-button').textContent.toLowerCase();
            const answer = item.querySelector('.accordion-body').textContent.toLowerCase();
            
            if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });

    // Scroll suave para categorias
    document.querySelectorAll('.list-group-item').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const element = document.querySelector(this.getAttribute('href'));
            const offset = 100;
            const bodyRect = document.body.getBoundingClientRect().top;
            const elementRect = element.getBoundingClientRect().top;
            const elementPosition = elementRect - bodyRect;
            const offsetPosition = elementPosition - offset;

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        });
    });

    // Função para abrir chat (exemplo)
    function openChat() {
        // Implementar integração com sistema de chat
        alert('Sistema de chat em desenvolvimento');
    }
    </script>
</body>
</html>