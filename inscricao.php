<?php
$pageTitle = 'Inscrição nos Jogos';
require_once '../config/conexao.php';
require_once '../includes/functions.php';

if (!isAlunoLogado()) redirecionar(BASE_URL . '/auth/login.php');

$alunoId = $_SESSION['aluno_id'];

// Buscar dados do aluno
$stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = ?");
$stmt->execute([$alunoId]);
$aluno = $stmt->fetch();

// Buscar jogos disponíveis
$jogos = $pdo->query("
    SELECT j.*, 
           (SELECT COUNT(*) FROM inscricoes i WHERE i.jogo_id = j.id AND i.status != 'cancelada') as inscritos
    FROM jogos j 
    WHERE j.status = 'aberto'
    ORDER BY j.nome
")->fetchAll();

// Buscar inscrições já feitas pelo aluno
$stmtJaInscrito = $pdo->prepare("SELECT jogo_id FROM inscricoes WHERE aluno_id = ? AND status != 'cancelada'");
$stmtJaInscrito->execute([$alunoId]);
$jaInscritos = array_column($stmtJaInscrito->fetchAll(), 'jogo_id');

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $jogoId       = (int)($_POST['jogo_id'] ?? 0);
    $nomecompleto = sanitize($_POST['nome_completo'] ?? '');
    $serie        = sanitize($_POST['serie'] ?? '');
    $cpf          = sanitize($_POST['cpf'] ?? '');
    
    if (empty($jogoId) || empty($nomecompleto) || empty($serie)) {
        $erro = 'Preencha todos os campos obrigatórios!';
    } elseif (!validarCPF($cpf)) {
        $erro = 'CPF inválido!';
    } else {
        // Validar CPF contra o cadastro do aluno
        $cpfFormatado = formatarCPF($cpf);
        if ($cpfFormatado !== $aluno['cpf']) {
            $erro = 'O CPF informado não corresponde ao CPF cadastrado na sua conta!';
        } else {
            // Verificar se já está inscrito
            if (in_array($jogoId, $jaInscritos)) {
                $erro = 'Você já está inscrito nesta modalidade!';
            } else {
                // Verificar vagas com bloqueio transacional para evitar exceder a capacidade
                $pdo->beginTransaction();
                $stmtJogo = $pdo->prepare("SELECT * FROM jogos WHERE id = ? AND status = 'aberto' FOR UPDATE");
                $stmtJogo->execute([$jogoId]);
                $jogo = $stmtJogo->fetch();
                
                if (!$jogo) {
                    $pdo->rollBack();
                    $erro = 'Jogo não disponível para inscrição!';
                } else {
                    $inscritos = $pdo->prepare("SELECT COUNT(*) FROM inscricoes WHERE jogo_id = ? AND status != 'cancelada'");
                    $inscritos->execute([$jogoId]);
                    $qtd = $inscritos->fetchColumn();
                    
                    if ($qtd >= $jogo['max_participantes']) {
                        $pdo->rollBack();
                        $erro = 'Vagas esgotadas para esta modalidade!';
                    } else {
                        // Inserir inscrição
                        $stmtIns = $pdo->prepare("
                            INSERT INTO inscricoes (aluno_id, jogo_id, nome_completo, serie, cpf, status)
                            VALUES (?, ?, ?, ?, ?, 'confirmada')
                            ON DUPLICATE KEY UPDATE nome_completo=VALUES(nome_completo), serie=VALUES(serie), cpf=VALUES(cpf), status='confirmada'
                        ");
                        
                        try {
                            $stmtIns->execute([$alunoId, $jogoId, $nomecompleto, $serie, $cpfFormatado]);
                            $stmtId = $pdo->prepare("SELECT id FROM inscricoes WHERE aluno_id = ? AND jogo_id = ?");
                            $stmtId->execute([$alunoId, $jogoId]);
                            $inscId = (int)$stmtId->fetchColumn();
                            $pdo->commit();

                            // O envio não segura o bloqueio de vagas; o resultado real fica registrado.
                            $emailEnviado = enviarEmailConfirmacao($aluno['email'], $nomecompleto, $jogo['nome'], $serie);
                            $pdo->prepare("UPDATE inscricoes SET confirmacao_email = ? WHERE id = ?")
                                ->execute([$emailEnviado ? 1 : 0, $inscId]);

                            $sucesso = "Inscrição realizada com sucesso em {$jogo['nome']}.";
                            $jaInscritos[] = $jogoId;
                        } catch (Throwable $e) {
                            if ($pdo->inTransaction()) $pdo->rollBack();
                            error_log('Falha na inscrição: ' . $e->getMessage());
                            $erro = 'Erro ao realizar inscrição. Tente novamente!';
                        }
                    }
                }
            }
        }
    }
}

$jogoPreSelecionado = (int)($_GET['jogo'] ?? 0);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscrição - Planer SESI</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
    <link rel="icon" href="<?= BASE_URL ?>/favicon.png" type="image/png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modern.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-avatar">🎓</div>
            <div class="sidebar-name"><?= htmlspecialchars($aluno['nome_completo']) ?></div>
            <div class="sidebar-role">Aluno</div>
        </div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="sidebar-link">
                <span class="link-icon">🏠</span> Meu Painel
            </a>
            <a href="inscricao.php" class="sidebar-link active">
                <span class="link-icon">✍️</span> Inscrever-se
            </a>
            <a href="meus_jogos.php" class="sidebar-link">
                <span class="link-icon">🏆</span> Minhas Inscrições
            </a>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="sidebar-link">
                <span class="link-icon">🚪</span> Sair
            </a>
        </nav>
    </aside>
    
    <main class="dashboard-content">
        <div class="page-header">
            <div>
                <h1>✍️ Inscrição nos Jogos</h1>
                <p class="breadcrumb"><a href="dashboard.php">Painel</a> › Inscrição</p>
            </div>
        </div>
        
        <?php if ($erro): echo alerta('erro', $erro); endif; ?>
        <?php if ($sucesso): echo alerta('sucesso', $sucesso); endif; ?>
        
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">📋 Formulário de Inscrição</span>
            </div>
            <div class="panel-body">
                <?php if (empty($jogos)): ?>
                    <div style="text-align: center; padding: 30px;">
                        <div style="font-size: 3rem; margin-bottom: 15px;">😢</div>
                        <h3>Nenhum jogo disponível</h3>
                        <p style="color: #777;">Não há jogos abertos para inscrição no momento.</p>
                    </div>
                <?php else: ?>
                    <form method="POST" id="formInscricao">
                    <?= csrfField() ?>
                        <div class="form-group">
                            <label class="form-label">Modalidade Esportiva <span>*</span></label>
                            <select name="jogo_id" class="form-control" required id="jogoSelect">
                                <option value="">Selecione a modalidade...</option>
                                <?php foreach ($jogos as $jogo): ?>
                                    <?php $disponivel = !in_array($jogo['id'], $jaInscritos) && $jogo['inscritos'] < $jogo['max_participantes']; ?>
                                    <option value="<?= $jogo['id'] ?>" 
                                            <?= $jogoPreSelecionado == $jogo['id'] ? 'selected' : '' ?>
                                            <?= !$disponivel ? 'disabled' : '' ?>>
                                        <?= getIconeJogo($jogo['nome']) ?> <?= htmlspecialchars($jogo['nome']) ?>
                                        <?php if (in_array($jogo['id'], $jaInscritos)): ?>
                                            (Já inscrito)
                                        <?php elseif ($jogo['inscritos'] >= $jogo['max_participantes']): ?>
                                            (Vagas esgotadas)
                                        <?php else: ?>
                                            (<?= $jogo['max_participantes'] - $jogo['inscritos'] ?> vagas)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Nome Completo <span>*</span></label>
                            <input 
                                type="text" 
                                name="nome_completo" 
                                class="form-control" 
                                value="<?= htmlspecialchars($aluno['nome_completo']) ?>"
                                required
                                readonly
                            >
                            <div class="form-hint">Nome preenchido automaticamente do seu cadastro</div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Série / Ano Escolar <span>*</span></label>
                                <select name="serie" class="form-control" required>
                                    <option value="">Selecione sua série...</option>
                                    <optgroup label="Ensino Fundamental">
                                        <option value="6º Ano EF">6º Ano - Ensino Fundamental</option>
                                        <option value="7º Ano EF">7º Ano - Ensino Fundamental</option>
                                        <option value="8º Ano EF">8º Ano - Ensino Fundamental</option>
                                        <option value="9º Ano EF">9º Ano - Ensino Fundamental</option>
                                    </optgroup>
                                    <optgroup label="Ensino Médio">
                                        <option value="1º Ano EM">1º Ano - Ensino Médio</option>
                                        <option value="2º Ano EM">2º Ano - Ensino Médio</option>
                                        <option value="3º Ano EM">3º Ano - Ensino Médio</option>
                                    </optgroup>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">CPF de Confirmação <span>*</span></label>
                                <input 
                                    type="text" 
                                    name="cpf" 
                                    id="cpfInput"
                                    class="form-control" 
                                    placeholder="000.000.000-00"
                                    maxlength="14"
                                    required
                                >
                                <div class="form-hint">Digite seu CPF para confirmar a inscrição</div>
                            </div>
                        </div>
                        
                        <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                            <p style="margin: 0; font-size: 0.9rem; color: #856404;">
                                ⚠️ <strong>Atenção:</strong> O CPF informado será validado contra o CPF cadastrado na sua conta. 
                                Certifique-se de que é o mesmo CPF.
                            </p>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            ✅ Confirmar Inscrição
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary btn-lg" style="margin-left: 10px;">
                            Cancelar
                        </a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Minhas inscrições atuais -->
        <?php
        $stmtMinhas = $pdo->prepare("
            SELECT i.*, j.nome as jogo_nome 
            FROM inscricoes i 
            JOIN jogos j ON i.jogo_id = j.id 
            WHERE i.aluno_id = ? AND i.status != 'cancelada'
        ");
        $stmtMinhas->execute([$alunoId]);
        $minhasInscricoes = $stmtMinhas->fetchAll();
        ?>
        
        <?php if (!empty($minhasInscricoes)): ?>
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">✅ Suas Inscrições Atuais</span>
            </div>
            <div class="panel-body" style="padding: 0;">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Modalidade</th>
                                <th>Série</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($minhasInscricoes as $mi): ?>
                            <tr>
                                <td><?= getIconeJogo($mi['jogo_nome']) ?> <?= htmlspecialchars($mi['jogo_nome']) ?></td>
                                <td><?= htmlspecialchars($mi['serie']) ?></td>
                                <td><span class="badge badge-<?= $mi['status'] ?>"><?= ucfirst($mi['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>
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