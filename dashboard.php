<?php
$pageTitle = 'Painel do Aluno';
require_once '../config/conexao.php';
require_once '../includes/functions.php';

if (!isAlunoLogado()) redirecionar(BASE_URL . '/auth/login.php');

$alunoId = $_SESSION['aluno_id'];

$mensagemExclusao = '';
$tipoExclusao = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'excluir') {
    validarCsrf();
    $inscId = (int)($_POST['inscricao_id'] ?? 0);
    // O aluno só pode apagar as próprias inscrições
    $stmtDel = $pdo->prepare("DELETE FROM inscricoes WHERE id = ? AND aluno_id = ?");
    $stmtDel->execute([$inscId, $alunoId]);
    if ($stmtDel->rowCount() === 1) {
        $mensagemExclusao = 'Inscrição apagada com sucesso!';
        $tipoExclusao = 'sucesso';
    } else {
        $mensagemExclusao = 'Você só pode apagar as suas próprias inscrições.';
        $tipoExclusao = 'erro';
    }
}

// Buscar dados do aluno
$stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = ?");
$stmt->execute([$alunoId]);
$aluno = $stmt->fetch();

// Buscar inscrições
$stmtInsc = $pdo->prepare("
    SELECT i.*, j.nome as jogo_nome, j.data_inicio, j.data_fim, j.local, j.status as jogo_status
    FROM inscricoes i 
    JOIN jogos j ON i.jogo_id = j.id 
    WHERE i.aluno_id = ? 
    ORDER BY i.criado_em DESC
");
$stmtInsc->execute([$alunoId]);
$inscricoes = $stmtInsc->fetchAll();

$totalInscritos = count($inscricoes);
$confirmadas = array_filter($inscricoes, fn($i) => $i['status'] === 'confirmada');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Aluno - Planer SESI</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
    <link rel="icon" href="<?= BASE_URL ?>/favicon.png" type="image/png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modern.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-avatar">🎓</div>
            <div class="sidebar-name"><?= htmlspecialchars($aluno['nome_completo']) ?></div>
            <div class="sidebar-role">Aluno</div>
        </div>
        
        <nav class="sidebar-menu">
            <div class="sidebar-section">Menu</div>
            <a href="dashboard.php" class="sidebar-link active">
                <span class="link-icon">🏠</span> Meu Painel
            </a>
            <a href="inscricao.php" class="sidebar-link">
                <span class="link-icon">✍️</span> Inscrever-se
            </a>
            <a href="meus_jogos.php" class="sidebar-link">
                <span class="link-icon">🏆</span> Minhas Inscrições
                <?php if ($totalInscritos > 0): ?>
                    <span class="badge-count"><?= $totalInscritos ?></span>
                <?php endif; ?>
            </a>
            <div class="sidebar-section">Sistema</div>
            <a href="<?= BASE_URL ?>/index.php" class="sidebar-link">
                <span class="link-icon">🌐</span> Página Inicial
            </a>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="sidebar-link">
                <span class="link-icon">🚪</span> Sair
            </a>
        </nav>
    </aside>
    
    <!-- Conteúdo -->
    <main class="dashboard-content">
        <?php if ($mensagemExclusao): ?>
            <div style="margin-bottom:20px;"><?= alerta($tipoExclusao, $mensagemExclusao) ?></div>
        <?php endif; ?>
        
        <div class="page-header">
            <div>
                <h1>Olá, <?= htmlspecialchars(explode(' ', $aluno['nome_completo'])[0]) ?>! 👋</h1>
                <p class="breadcrumb">Jogos Escolares SESI 2025</p>
            </div>
            <a href="inscricao.php" class="btn btn-primary">
                ✍️ Nova Inscrição
            </a>
        </div>
        
        <!-- Cards de Resumo -->
        <div class="dashboard-cards">
            <div class="dash-card">
                <div class="dash-card-icon">📋</div>
                <div class="dash-card-value"><?= $totalInscritos ?></div>
                <div class="dash-card-label">Total de Inscrições</div>
            </div>
            <div class="dash-card">
                <div class="dash-card-icon">✅</div>
                <div class="dash-card-value"><?= count($confirmadas) ?></div>
                <div class="dash-card-label">Confirmadas</div>
            </div>
            <div class="dash-card">
                <div class="dash-card-icon">🏐</div>
                <div class="dash-card-value"><?= 7 - $totalInscritos ?></div>
                <div class="dash-card-label">Jogos Disponíveis</div>
            </div>
        </div>
        
        <!-- Dados do Aluno -->
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">👤 Meus Dados</span>
            </div>
            <div class="panel-body">
                <div class="form-row">
                    <div>
                        <p style="font-size: 0.85rem; color: #777; margin-bottom: 3px;">Nome Completo</p>
                        <p style="font-weight: 600;"><?= htmlspecialchars($aluno['nome_completo']) ?></p>
                    </div>
                    <div>
                        <p style="font-size: 0.85rem; color: #777; margin-bottom: 3px;">CPF</p>
                        <p style="font-weight: 600;"><?= htmlspecialchars($aluno['cpf']) ?></p>
                    </div>
                    <div>
                        <p style="font-size: 0.85rem; color: #777; margin-bottom: 3px;">E-mail</p>
                        <p style="font-weight: 600;"><?= htmlspecialchars($aluno['email']) ?></p>
                    </div>
                    <div>
                        <p style="font-size: 0.85rem; color: #777; margin-bottom: 3px;">Data de Nascimento</p>
                        <p style="font-weight: 600;"><?= date('d/m/Y', strtotime($aluno['data_nascimento'])) ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Inscrições Recentes -->
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">🏆 Minhas Inscrições</span>
                <a href="meus_jogos.php" class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: white;">Ver Todas</a>
            </div>
            <div class="panel-body" style="padding: 0;">
                <?php if (empty($inscricoes)): ?>
                    <div style="padding: 30px; text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 15px;">🏆</div>
                        <h3>Nenhuma inscrição ainda</h3>
                        <p style="color: #777; margin: 10px 0 20px;">Participe dos Jogos Escolares SESI!</p>
                        <a href="inscricao.php" class="btn btn-primary">✍️ Fazer Inscrição</a>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Modalidade</th>
                                    <th>Série</th>
                                    <th>Status</th>
                                    <th>Data Inscrição</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inscricoes as $insc): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($insc['jogo_nome']) ?></strong></td>
                                    <td><?= htmlspecialchars($insc['serie']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $insc['status'] ?>">
                                            <?= ucfirst($insc['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($insc['criado_em'])) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Apagar esta inscrição definitivamente? Esta ação não pode ser desfeita.')">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="excluir">
                                            <input type="hidden" name="inscricao_id" value="<?= (int)$insc['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Apagar inscrição" aria-label="Apagar inscrição">🗑️ Apagar</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>