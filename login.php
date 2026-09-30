<?php
// ============================================
// LOGIN - auth/login.php
// ============================================

// Caminhos corretos para subpasta auth/
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/conexao.php';
require_once ROOT_PATH . '/includes/functions.php';

if (isAlunoLogado()) redirecionar(BASE_URL . '/aluno/dashboard.php');
if (isProfessorLogado()) redirecionar(BASE_URL . '/professor/dashboard.php');

$erro   = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $email = sanitize($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $tipo  = $_POST['tipo'] ?? 'aluno';

    if (empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos!';
    } else {
        if ($tipo === 'professor') {
            $usuario = verificarLoginProfessor($pdo, $email, $senha);
            if ($usuario) {
                session_regenerate_id(true);
                $_SESSION['professor_id']    = $usuario['id'];
                $_SESSION['professor_nome']  = $usuario['nome_completo'];
                $_SESSION['professor_email'] = $usuario['email'];
                redirecionar(BASE_URL . '/professor/dashboard.php');
            } else {
                $erro = 'E-mail ou senha inválidos!';
            }
        } else {
            $usuario = verificarLoginAluno($pdo, $email, $senha);
            if ($usuario) {
                session_regenerate_id(true);
                $_SESSION['aluno_id']    = $usuario['id'];
                $_SESSION['aluno_nome']  = $usuario['nome_completo'];
                $_SESSION['aluno_email'] = $usuario['email'];
                redirecionar(BASE_URL . '/aluno/dashboard.php');
            } else {
                $erro = 'E-mail ou senha inválidos!';
            }
        }
    }
}

$tipo_ativo = $_GET['tipo'] ?? 'aluno';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Planer SESI</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
    <link rel="icon" href="<?= BASE_URL ?>/favicon.png" type="image/png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modern.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <span class="logo-icon">🏆</span>
            <h1>Planer SESI</h1>
            <p>Jogos Escolares 2025</p>
        </div>
        
        <div class="auth-tabs">
            <a href="?tipo=aluno" class="auth-tab <?= $tipo_ativo === 'aluno' ? 'active' : '' ?>">
                🎓 Aluno
            </a>
            <a href="?tipo=professor" class="auth-tab <?= $tipo_ativo === 'professor' ? 'active' : '' ?>">
                👨‍🏫 Professor
            </a>
        </div>
        
        <div class="auth-body">
            <h2><?= $tipo_ativo === 'professor' ? '👨‍🏫 Acesso Professor' : '🎓 Acesso Aluno' ?></h2>
            <p class="subtitle">Faça login para acessar o sistema</p>
            
            <?php if ($erro): echo alerta('erro', $erro); endif; ?>
            <?php if (isset($_GET['cadastro'])): echo alerta('sucesso', 'Cadastro realizado! Faça login.'); endif; ?>
            
            <form method="POST">
                    <?= csrfField() ?>
                <input type="hidden" name="tipo" value="<?= $tipo_ativo ?>">
                
                <div class="form-group">
                    <label class="form-label">E-mail <span>*</span></label>
                    <input type="email" name="email" class="form-control" 
                           placeholder="seuemail@email.com"
                           value="<?= sanitize($_POST['email'] ?? '') ?>"
                           required autofocus>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Senha <span>*</span></label>
                    <div class="password-toggle">
                        <input type="password" name="senha" id="senhaInput"
                               class="form-control" placeholder="••••••••" required>
                        <button type="button" class="toggle-btn" onclick="
                            const i = document.getElementById('senhaInput');
                            i.type = i.type === 'password' ? 'text' : 'password';
                        ">👁️</button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    🔑 Entrar
                </button>
            </form>
            
            <div class="auth-footer-link" style="margin-top: 20px;">
                Não tem conta?
                <a href="<?= BASE_URL ?>/auth/cadastro_<?= $tipo_ativo ?>.php">Cadastre-se aqui</a>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
</body>
</html>