<?php
$pageTitle = 'Cadastro de Professor';
require_once '../config/conexao.php';
require_once '../includes/functions.php';

if (isProfessorLogado()) redirecionar(BASE_URL . '/professor/dashboard.php');

$erro = '';
$dados = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $dados = [
        'nome_completo'   => sanitize($_POST['nome_completo'] ?? ''),
        'cpf'             => sanitize($_POST['cpf'] ?? ''),
        'data_nascimento' => sanitize($_POST['data_nascimento'] ?? ''),
        'email'           => sanitize($_POST['email'] ?? ''),
        'senha'           => $_POST['senha'] ?? '',
        'confirmar_senha' => $_POST['confirmar_senha'] ?? '',
    ];
    
    if (empty($dados['nome_completo']) || empty($dados['cpf']) || 
        empty($dados['data_nascimento']) || empty($dados['email']) || 
        empty($dados['senha'])) {
        $erro = 'Todos os campos são obrigatórios!';
    } elseif (!validarCPF($dados['cpf'])) {
        $erro = 'CPF inválido!';
    } elseif (strlen($dados['senha']) < 6) {
        $erro = 'Senha com no mínimo 6 caracteres!';
    } elseif ($dados['senha'] !== $dados['confirmar_senha']) {
        $erro = 'As senhas não coincidem!';
    } elseif (cpfExiste($pdo, $dados['cpf'], 'professores')) {
        $erro = 'Este CPF já está cadastrado como professor!';
    } elseif (cpfExiste($pdo, $dados['cpf'], 'alunos')) {
        $erro = 'Este CPF já está cadastrado como aluno!';
    } else {
        $stmtEmail = $pdo->prepare("SELECT id FROM professores WHERE email = ?");
        $stmtEmail->execute([$dados['email']]);
        if ($stmtEmail->fetch()) {
            $erro = 'Este e-mail já está em uso!';
        } else {
            $cpfFormatado = formatarCPF($dados['cpf']);
            $senhaHash = password_hash($dados['senha'], PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO professores (nome_completo, cpf, data_nascimento, email, senha) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$dados['nome_completo'], $cpfFormatado, $dados['data_nascimento'], $dados['email'], $senhaHash])) {
                redirecionar(BASE_URL . '/auth/login.php?cadastro=1&tipo=professor');
            } else {
                $erro = 'Erro ao cadastrar. Tente novamente!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Professor - Planer SESI</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
    <link rel="icon" href="<?= BASE_URL ?>/favicon.png" type="image/png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modern.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 580px;">
        <div class="auth-header">
            <span class="logo-icon">👨‍🏫</span>
            <h1>Cadastro de Professor</h1>
            <p>Jogos Escolares SESI 2025</p>
        </div>
        
        <div class="auth-tabs">
            <a href="cadastro_aluno.php" class="auth-tab">🎓 Sou Aluno</a>
            <a href="cadastro_professor.php" class="auth-tab active">👨‍🏫 Sou Professor</a>
        </div>
        
        <div class="auth-body">
            <h2>Criar Conta de Professor</h2>
            <p class="subtitle">Preencha seus dados para se cadastrar</p>
            
            <?php if ($erro): echo alerta('erro', $erro); endif; ?>
            
            <form method="POST">
                    <?= csrfField() ?>
                <div class="form-group">
                    <label class="form-label">Nome Completo <span>*</span></label>
                    <input type="text" name="nome_completo" class="form-control" 
                           placeholder="Seu nome completo" 
                           value="<?= htmlspecialchars($dados['nome_completo'] ?? '') ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">CPF <span>*</span></label>
                        <input type="text" name="cpf" id="cpfInput" class="form-control" 
                               placeholder="000.000.000-00" 
                               value="<?= htmlspecialchars($dados['cpf'] ?? '') ?>" 
                               maxlength="14" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data de Nascimento <span>*</span></label>
                        <input type="date" name="data_nascimento" class="form-control" 
                               value="<?= htmlspecialchars($dados['data_nascimento'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">E-mail <span>*</span></label>
                    <input type="email" name="email" class="form-control" 
                           placeholder="seuemail@email.com" 
                           value="<?= htmlspecialchars($dados['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Senha <span>*</span></label>
                        <input type="password" name="senha" class="form-control" 
                               placeholder="Mín. 6 caracteres" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirmar Senha <span>*</span></label>
                        <input type="password" name="confirmar_senha" class="form-control" 
                               placeholder="Repita a senha" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    👨‍🏫 Criar Conta de Professor
                </button>
            </form>
            
            <div class="auth-footer-link">
                Já tem conta? <a href="login.php?tipo=professor">Fazer Login</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<script>
document.getElementById('cpfInput').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '');
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    e.target.value = v;
});
</script>
</body>
</html>