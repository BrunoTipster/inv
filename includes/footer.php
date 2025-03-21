<?php
/**
 * Rodapé Global do Site
 * 
 * @package InvestSystem
 * @version 1.0.0
 * @author Bruno Tipster
 * @copyright 2025 InvestSystem
 * @last_modified 2025-03-20 23:57:58 UTC
 */
?>
    </main>

    <!-- Footer -->
    <footer class="main-footer bg-dark text-light py-5 mt-5">
        <div class="container">
            <div class="row">
                <!-- Logo e Informações -->
                <div class="col-md-4 mb-4">
                    <img src="/investment/images/logo-white.png" alt="InvestSystem" height="40" class="mb-3">
                    <p class="mb-3">
                        Plataforma líder em investimentos com retornos garantidos e gerenciamento transparente.
                    </p>
                    <div class="social-links">
                        <a href="https://facebook.com/investsystem" class="text-light me-3" target="_blank">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="https://instagram.com/investsystem" class="text-light me-3" target="_blank">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="https://linkedin.com/company/investsystem" class="text-light me-3" target="_blank">
                            <i class="bi bi-linkedin"></i>
                        </a>
                        <a href="https://twitter.com/investsystem" class="text-light" target="_blank">
                            <i class="bi bi-twitter"></i>
                        </a>
                    </div>
                </div>

                <!-- Links Rápidos -->
                <div class="col-md-2 mb-4">
                    <h5 class="mb-3">Links Rápidos</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="/packages" class="text-light text-decoration-none">Pacotes</a>
                        </li>
                        <li class="mb-2">
                            <a href="/about" class="text-light text-decoration-none">Sobre Nós</a>
                        </li>
                        <li class="mb-2">
                            <a href="/contact" class="text-light text-decoration-none">Contato</a>
                        </li>
                        <li class="mb-2">
                            <a href="/faq" class="text-light text-decoration-none">FAQ</a>
                        </li>
                    </ul>
                </div>

                <!-- Informações Legais -->
                <div class="col-md-2 mb-4">
                    <h5 class="mb-3">Legal</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="/terms" class="text-light text-decoration-none">Termos de Uso</a>
                        </li>
                        <li class="mb-2">
                            <a href="/privacy" class="text-light text-decoration-none">Privacidade</a>
                        </li>
                        <li class="mb-2">
                            <a href="/security" class="text-light text-decoration-none">Segurança</a>
                        </li>
                        <li class="mb-2">
                            <a href="/compliance" class="text-light text-decoration-none">Compliance</a>
                        </li>
                    </ul>
                </div>

                <!-- Contato -->
                <div class="col-md-4 mb-4">
                    <h5 class="mb-3">Contato</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-geo-alt me-2"></i>
                            Av. Paulista, 1000 - São Paulo/SP
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-telephone me-2"></i>
                            (11) 98765-4321
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-envelope me-2"></i>
                            contato@investsystem.com
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-clock me-2"></i>
                            Segunda a Sexta: 9h às 18h
                        </li>
                    </ul>
                </div>
            </div>

            <hr class="my-4">

            <!-- Copyright e Links do Rodapé -->
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    &copy; <?php echo date('Y'); ?> InvestSystem. Todos os direitos reservados.
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <img src="/investment/images/payment-methods.png" alt="Métodos de Pagamento" height="30">
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/investment/js/main.js?v=<?php echo filemtime(BASE_PATH . '/js/main.js'); ?>"></script>

    <!-- Scripts específicos da página -->
    <?php if (isset($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Google Analytics -->
    <?php if (!DEBUG_MODE): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-XXXXXXXXXX');
    </script>
    <?php endif; ?>

    <!-- Suporte Online -->
    <?php if (!DEBUG_MODE): ?>
    <script>
        window.intercomSettings = {
            app_id: "XXXXXXXXXX"
        };
        (function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',w.intercomSettings);}else{var d=document;var i=function(){i.c(arguments);};i.q=[];i.c=function(args){i.q.push(args);};w.Intercom=i;var l=function(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/XXXXXXXXXX';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);};if(document.readyState==='complete'){l();}else if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})();
    </script>
    <?php endif; ?>
</body>
</html>