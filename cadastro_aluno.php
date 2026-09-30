<?php
$pageTitle = 'Cadastro de Aluno';
require_once '../config/conexao.php';
require_once '../includes/functions.php';

if (isAlunoLogado()) redirecionar(BASE_URL . '/aluno/dashboard.php');

$erro = '';
$sucesso = '';
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
    
    // Validações
    if (empty($dados['nome_completo']) || empty($dados['cpf']) || 
        empty($dados['data_nascimento']) || empty($dados['email']) || 
        empty($dados['senha'])) {
        $erro = 'Todos os campos são obrigatórios!';
    } elseif (!validarCPF($dados['cpf'])) {
        $erro = 'CPF inválido! Verifique o número digitado.';
    } elseif (strlen($dados['senha']) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres!';
    } elseif ($dados['senha'] !== $dados['confirmar_senha']) {
        $erro = 'As senhas não coincidem!';
    } elseif (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido!';
    } elseif (cpfExiste($pdo, $dados['cpf'], 'alunos')) {
        $erro = 'Este CPF já está cadastrado no sistema!';
    } elseif (cpfExiste($pdo, $dados['cpf'], 'professores')) {
        $erro = 'Este CPF já está cadastrado como professor!';
    } else {
        // Verificar e-mail
        $stmtEmail = $pdo->prepare("SELECT id FROM alunos WHERE email = ?");
        $stmtEmail->execute([$dados['email']]);
        if ($stmtEmail->fetch()) {
            $erro = 'Este e-mail já está cadastrado!';
        } else {
            // Inserir
            $cpfFormatado = formatarCPF($dados['cpf']);
            $senhaHash = password_hash($dados['senha'], PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO alunos (nome_completo, cpf, data_nascimento, email, senha) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$dados['nome_completo'], $cpfFormatado, $dados['data_nascimento'], $dados['email'], $senhaHash])) {
                redirecionar(BASE_URL . '/auth/login.php?cadastro=1&tipo=aluno');
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
    <title>Cadastro Aluno - Planer SESI</title>
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
            <span class="logo-icon">🎓</span>
            <h1>Cadastro de Aluno</h1>
            <p>Jogos Escolares SESI 2025</p>
        </div>
        
        <div class="auth-tabs">
            <a href="cadastro_aluno.php" class="auth-tab active">🎓 Sou Aluno</a>
            <a href="cadastro_professor.php" class="auth-tab">👨‍🏫 Sou Professor</a>
        </div>
        
        <div class="auth-body">
            <h2>Criar Conta de Aluno</h2>
            <p class="subtitle">Preencha seus dados para se cadastrar</p>
            
            <?php if ($erro): echo alerta('erro', $erro); endif; ?>
            
            <form method="POST" id="formCadastroAluno">
                    <?= csrfField() ?>
                <div class="form-group">
                    <label class="form-label">Nome Completo <span>*</span></label>
                    <input 
                        type="text" 
                        name="nome_completo" 
                        class="form-control" 
                        placeholder="Seu nome completo"
                        value="<?= htmlspecialchars($dados['nome_completo'] ?? '') ?>"
                        required
                    >
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">CPF <span>*</span></label>
                        <input 
                            type="text" 
                            name="cpf" 
                            id="cpfInput"
                            class="form-control" 
                            placeholder="000.000.000-00"
                            value="<?= htmlspecialchars($dados['cpf'] ?? '') ?>"
                            maxlength="14"
                            required
                        >
                        <div class="form-error" id="cpfError">CPF inválido!</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Data de Nascimento <span>*</span></label>
                        <input 
                            type="date" 
                            name="data_nascimento" 
                            class="form-control"
                            value="<?= htmlspecialchars($dados['data_nascimento'] ?? '') ?>"
                            max="<?= date('Y-m-d', strtotime('-5 years')) ?>"
                            required
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">E-mail <span>*</span></label>
                    <input 
                        type="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="seuemail@email.com"
                        value="<?= htmlspecialchars($dados['email'] ?? '') ?>"
                        required
                    >
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Senha <span>*</span></label>
                        <div class="password-toggle">
                            <input 
                                type="password" 
                                name="senha" 
                                id="senhaInput"
                                class="form-control" 
                                placeholder="Mín. 6 caracteres"
                                required
                                minlength="6"
                            >
                            <button type="button" class="toggle-btn" onclick="toggleSenha('senhaInput')">👁️</button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Confirmar Senha <span>*</span></label>
                        <div class="password-toggle">
                            <input 
                                type="password" 
                                name="confirmar_senha" 
                                id="confirmarSenhaInput"
                                class="form-control" 
                                placeholder="Repita a senha"
                                required
                            >
                            <button type="button" class="toggle-btn" onclick="toggleSenha('confirmarSenhaInput')">👁️</button>
                        </div>
                        <div class="form-error" id="senhaError">As senhas não coincidem!</div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    🎓 Criar Conta de Aluno
                </button>
            </form>
            
            <div class="auth-footer-link">
                Já tem conta? <a href="login.php?tipo=aluno">Fazer Login</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<script>
function toggleSenha(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

// Máscara CPF
document.getElementById('cpfInput').addEventListener('input', function(e) {
    let v = e.target.value.replace(/\D/g, '');
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    e.target.value = v;
});

// Validação em tempo real
document.getElementById('confirmarSenhaInput').addEventListener('input', function() {
    const senha = document.getElementById('senhaInput').value;
    const erro = document.getElementById('senhaError');
    if (this.value && this.value !== senha) {
        erro.classList.add('show');
        this.classList.add('error');
    } else {
        erro.classList.remove('show');
        this.classList.remove('error');
    }
});
</script>
</body>
</html>