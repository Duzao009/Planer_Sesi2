<?php
$pageTitle = 'Minhas Inscrições';
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
$stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = ?");
$stmt->execute([$alunoId]);
$aluno = $stmt->fetch();

$stmtInsc = $pdo->prepare("
    SELECT i.*, j.nome as jogo_nome, j.data_inicio, j.data_fim, j.local, j.status as jogo_status,
           p.placar_a, p.placar_b, p.time_a, p.time_b, p.status as placar_status
    FROM inscricoes i 
    JOIN jogos j ON i.jogo_id = j.id
    LEFT JOIN placar p ON j.id = p.jogo_id
    WHERE i.aluno_id = ? 
    ORDER BY i.criado_em DESC
");
$stmtInsc->execute([$alunoId]);
$inscricoes = $stmtInsc->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Inscrições - Planer SESI</title>
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
            <a href="dashboard.php" class="sidebar-link"><span class="link-icon">🏠</span> Meu Painel</a>
            <a href="inscricao.php" class="sidebar-link"><span class="link-icon">✍️</span> Inscrever-se</a>
            <a href="meus_jogos.php" class="sidebar-link active"><span class="link-icon">🏆</span> Minhas Inscrições</a>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="sidebar-link"><span class="link-icon">🚪</span> Sair</a>
        </nav>
    </aside>
    
    <main class="dashboard-content">
        <?php if ($mensagemExclusao): ?>
            <div style="margin-bottom:20px;"><?= alerta($tipoExclusao, $mensagemExclusao) ?></div>
        <?php endif; ?>
        
        <div class="page-header">
            <div>
                <h1>🏆 Minhas Inscrições</h1>
                <p class="breadcrumb"><a href="dashboard.php">Painel</a> › Inscrições</p>
            </div>
            <a href="inscricao.php" class="btn btn-primary">+ Nova Inscrição</a>
        </div>
        
        <?php if (empty($inscricoes)): ?>
            <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                <div style="font-size: 4rem; margin-bottom: 20px;">🏆</div>
                <h2 style="margin-bottom: 10px;">Você ainda não se inscreveu em nenhum jogo</h2>
                <p style="color: #777; margin-bottom: 25px;">Participe dos Jogos Escolares SESI 2025!</p>
                <a href="inscricao.php" class="btn btn-primary btn-lg">✍️ Fazer Primeira Inscrição</a>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
                <?php foreach ($inscricoes as $insc): ?>
                <div style="background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden; border: 1px solid #e0e0e0; transition: all 0.3s;" 
                     onmouseover="this.style.transform='translateY(-3px)'" 
                     onmouseout="this.style.transform='translateY(0)'">
                    <div style="background: linear-gradient(135deg, #990000, #CC0000); padding: 20px; text-align: center; color: white;">
                        <div style="font-size: 2.5rem; margin-bottom: 8px;"><?= getIconeJogo($insc['jogo_nome']) ?></div>
                        <div style="font-size: 1.2rem; font-weight: 800;"><?= htmlspecialchars($insc['jogo_nome']) ?></div>
                    </div>
                    <div style="padding: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="color: #777; font-size: 0.9rem;">Série</span>
                            <strong><?= htmlspecialchars($insc['serie']) ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="color: #777; font-size: 0.9rem;">Status</span>
                            <span class="badge badge-<?= $insc['status'] ?>"><?= ucfirst($insc['status']) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="color: #777; font-size: 0.9rem;">📍 Local</span>
                            <span style="font-size: 0.9rem; max-width: 180px; text-align: right;"><?= htmlspecialchars($insc['local']) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                            <span style="color: #777; font-size: 0.9rem;">📅 Período</span>
                            <span style="font-size: 0.85rem; font-weight: 600;">
                                <?= date('d/m', strtotime($insc['data_inicio'])) ?> a <?= date('d/m/Y', strtotime($insc['data_fim'])) ?>
                            </span>
                        </div>
                        
                        <!-- Mini Placar -->
                        <div style="background: #f9f9f9; border-radius: 8px; padding: 12px; text-align: center; border: 1px solid #e0e0e0;">
                            <p style="font-size: 0.75rem; color: #777; margin-bottom: 8px; font-weight: 600;">PLACAR ATUAL</p>
                            <div style="display: flex; justify-content: center; align-items: center; gap: 15px;">
                                <div style="text-align: center;">
                                    <div style="font-size: 0.8rem; color: #555;"><?= htmlspecialchars($insc['time_a'] ?? 'Time A') ?></div>
                                    <div style="font-size: 2rem; font-weight: 900; color: #CC0000;"><?= $insc['placar_a'] ?? 0 ?></div>
                                </div>
                                <div style="font-weight: 700; color: #aaa;">×</div>
                                <div style="text-align: center;">
                                    <div style="font-size: 0.8rem; color: #555;"><?= htmlspecialchars($insc['time_b'] ?? 'Time B') ?></div>
                                    <div style="font-size: 2rem; font-weight: 900; color: #CC0000;"><?= $insc['placar_b'] ?? 0 ?></div>
                                </div>
                            </div>
                        </div>
                        <div style="margin-top:15px;text-align:right;">
                                        <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Apagar esta inscrição definitivamente? Esta ação não pode ser desfeita.')">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="excluir">
                                            <input type="hidden" name="inscricao_id" value="<?= (int)$insc['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Apagar inscrição" aria-label="Apagar inscrição">🗑️ Apagar</button>
                                        </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>