<?php
if (!function_exists('isAlunoLogado')) {
    $rootPath = dirname(__DIR__);
    if (!defined('BASE_URL')) require_once $rootPath . '/config/conexao.php';
    require_once $rootPath . '/includes/functions.php';
}
?>
<header class="header-main">
    <div class="container">
        <a class="header-logo" href="<?= BASE_URL ?>/index.php" aria-label="Planer SESI — início"><span>Planer SESI</span></a>
        <nav class="header-nav" aria-label="Navegação principal">
            <a href="<?= BASE_URL ?>/index.php" class="nav-link">Início</a>
            <a href="<?= BASE_URL ?>/index.php#jogos" class="nav-link">Modalidades</a>
            <a href="<?= BASE_URL ?>/index.php#placar" class="nav-link">Placares</a>
            <?php if (isAlunoLogado()): ?>
                <a href="<?= BASE_URL ?>/aluno/dashboard.php" class="nav-btn">Meu painel</a>
            <?php elseif (isProfessorLogado()): ?>
                <a href="<?= BASE_URL ?>/professor/dashboard.php" class="nav-btn">Painel</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/auth/login.php" class="nav-btn">Entrar</a>
            <?php endif; ?>
        </nav>
        <button class="menu-mobile" type="button" onclick="toggleMenu()" aria-controls="navMobile" aria-expanded="false">Menu</button>
    </div>
</header>
<nav class="nav-mobile" id="navMobile" aria-label="Navegação móvel">
    <a href="<?= BASE_URL ?>/index.php">Início</a>
    <a href="<?= BASE_URL ?>/index.php#jogos">Modalidades</a>
    <a href="<?= BASE_URL ?>/index.php#placar">Placares</a>
    <?php if (isAlunoLogado()): ?>
        <a href="<?= BASE_URL ?>/aluno/dashboard.php">Meu painel</a><a href="<?= BASE_URL ?>/auth/logout.php">Sair</a>
    <?php elseif (isProfessorLogado()): ?>
        <a href="<?= BASE_URL ?>/professor/dashboard.php">Painel</a><a href="<?= BASE_URL ?>/auth/logout.php">Sair</a>
    <?php else: ?>
        <a href="<?= BASE_URL ?>/auth/login.php">Entrar</a><a href="<?= BASE_URL ?>/auth/cadastro_aluno.php">Criar conta</a>
    <?php endif; ?>
</nav>
