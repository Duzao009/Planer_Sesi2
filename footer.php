<footer class="footer-main">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <div class="footer-logo" aria-label="Planer SESI"><span>Planer SESI</span></div>
                <p>Organização dos Jogos Escolares SESI.</p>
            </div>
        </div>
        <div class="footer-bottom"><p>&copy; <?= date('Y') ?> Planer SESI. Todos os direitos reservados.</p></div>
    </div>
</footer>
<script src="<?= BASE_URL ?>/assets/js/icons.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<?php if (isset($extraJS)): foreach ($extraJS as $js): ?>
<script src="<?= BASE_URL ?>/assets/js/<?= htmlspecialchars($js, ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endforeach; endif; ?>
