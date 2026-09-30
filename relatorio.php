<?php
$pageTitle = 'Relatórios';
require_once '../config/conexao.php';
require_once '../includes/functions.php';

if (!isProfessorLogado()) redirecionar(BASE_URL . '/auth/login.php?tipo=professor');

$profId = $_SESSION['professor_id'];
$stmt = $pdo->prepare("SELECT * FROM professores WHERE id = ?");
$stmt->execute([$profId]);
$professor = $stmt->fetch();

// Relatório por série
$porSerie = $pdo->query("
    SELECT i.serie, j.nome as jogo_nome, COUNT(*) as total
    FROM inscricoes i
    JOIN jogos j ON i.jogo_id = j.id
    WHERE i.status != 'cancelada'
    GROUP BY i.serie, i.jogo_id, j.nome
    ORDER BY i.serie, j.nome
")->fetchAll();

// Total por modalidade
$porModalidade = $pdo->query("
    SELECT j.nome,
           COALESCE(i.total, 0) as total,
           COALESCE(i.confirmadas, 0) as confirmadas,
           COALESCE(i.canceladas, 0) as canceladas
    FROM jogos j
    LEFT JOIN (
        SELECT jogo_id,
               COUNT(*) as total,
               SUM(status = 'confirmada') as confirmadas,
               SUM(status = 'cancelada') as canceladas
        FROM inscricoes
        GROUP BY jogo_id
    ) i ON j.id = i.jogo_id
    ORDER BY total DESC
")->fetchAll();

// Total geral
$totais = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
        SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
        SUM(CASE WHEN status = 'cancelada' THEN 1 ELSE 0 END) as canceladas
    FROM inscricoes
")->fetch();

// Agrupa por série
$seriesAgrupadas = [];
foreach ($porSerie as $ps) {
    $seriesAgrupadas[$ps['serie']][] = $ps;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Planer SESI</title>
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
            <div class="sidebar-avatar">👨‍🏫</div>
            <div class="sidebar-name"><?= htmlspecialchars($professor['nome_completo']) ?></div>
            <div class="sidebar-role">Professor</div>
        </div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="sidebar-link"><span class="link-icon">📊</span> Dashboard</a>
            <a href="gerenciar_inscricoes.php" class="sidebar-link"><span class="link-icon">📋</span> Inscrições</a>
            <a href="placar.php" class="sidebar-link"><span class="link-icon">🏆</span> Placar</a>
            <a href="relatorio.php" class="sidebar-link active"><span class="link-icon">📈</span> Relatórios</a>
            <a href="exportar_pdf.php" class="sidebar-link"><span class="link-icon">📄</span> Exportar PDF</a>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="sidebar-link"><span class="link-icon">🚪</span> Sair</a>
        </nav>
    </aside>
    
    <main class="dashboard-content">
        <div class="page-header">
            <div>
                <h1>📈 Relatórios</h1>
                <p class="breadcrumb"><a href="dashboard.php">Dashboard</a> › Relatórios</p>
            </div>
            <a href="exportar_pdf.php" class="btn btn-danger">📄 Exportar PDF</a>
        </div>
        
        <!-- Resumo Geral -->
        <div class="dashboard-cards">
            <div class="dash-card">
                <div class="dash-card-icon">📋</div>
                <div class="dash-card-value"><?= $totais['total'] ?></div>
                <div class="dash-card-label">Total de Inscrições</div>
            </div>
            <div class="dash-card">
                <div class="dash-card-icon">✅</div>
                <div class="dash-card-value"><?= $totais['confirmadas'] ?></div>
                <div class="dash-card-label">Confirmadas</div>
            </div>
            <div class="dash-card">
                <div class="dash-card-icon">⏳</div>
                <div class="dash-card-value"><?= $totais['pendentes'] ?></div>
                <div class="dash-card-label">Pendentes</div>
            </div>
            <div class="dash-card">
                <div class="dash-card-icon">❌</div>
                <div class="dash-card-value"><?= $totais['canceladas'] ?></div>
                <div class="dash-card-label">Canceladas</div>
            </div>
        </div>
        
        <!-- Por Modalidade -->
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">🏅 Participantes por Modalidade</span>
            </div>
            <div class="panel-body" style="padding: 0;">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Modalidade</th>
                                <th>Total</th>
                                <th>Confirmadas</th>
                                <th>Canceladas</th>
                                <th>Ocupação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($porModalidade as $pm): ?>
                            <?php 
                            $stmtMax = $pdo->prepare("SELECT max_participantes FROM jogos WHERE nome = ?");
                            $stmtMax->execute([$pm['nome']]);
                            $max = $stmtMax->fetchColumn();
                            $pct = $max > 0 ? round(($pm['confirmadas'] / $max) * 100) : 0;
                            ?>
                            <tr>
                                <td><?= getIconeJogo($pm['nome']) ?> <strong><?= htmlspecialchars($pm['nome']) ?></strong></td>
                                <td><?= $pm['total'] ?></td>
                                <td><span class="badge badge-confirmada"><?= $pm['confirmadas'] ?></span></td>
                                <td><span class="badge badge-cancelada"><?= $pm['canceladas'] ?></span></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="flex: 1; background: #f0f0f0; border-radius: 10px; height: 10px; min-width: 100px;">
                                            <div style="width: <?= min(100, $pct) ?>%; height: 100%; background: <?= $pct > 80 ? '#e53935' : '#CC0000' ?>; border-radius: 10px;"></div>
                                        </div>
                                        <span style="font-size: 0.85rem; font-weight: 700; min-width: 40px;"><?= $pct ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Por Série -->
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">🎓 Participantes por Série</span>
            </div>
            <div class="panel-body">
                <?php foreach ($seriesAgrupadas as $serie => $jogos): ?>
                <div style="margin-bottom: 25px; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden;">
                    <div style="background: linear-gradient(135deg, #1a1a1a, #333); color: white; padding: 12px 20px; font-weight: 700; font-size: 1rem;">
                        🎓 <?= htmlspecialchars($serie) ?>
                        <span style="float: right; background: rgba(255,255,255,0.2); padding: 2px 10px; border-radius: 10px; font-size: 0.85rem;">
                            <?= array_sum(array_column($jogos, 'total')) ?> inscrições
                        </span>
                    </div>
                    <div style="padding: 0;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f5f5f5;">
                                    <th style="padding: 10px 15px; text-align: left; font-size: 0.85rem;">Modalidade</th>
                                    <th style="padding: 10px 15px; text-align: center; font-size: 0.85rem;">Participantes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jogos as $jogo): ?>
                                <tr style="border-bottom: 1px solid #f0f0f0;">
                                    <td style="padding: 10px 15px;">
                                        <?= getIconeJogo($jogo['jogo_nome']) ?> <?= htmlspecialchars($jogo['jogo_nome']) ?>
                                    </td>
                                    <td style="padding: 10px 15px; text-align: center;">
                                        <strong style="color: #CC0000;"><?= $jogo['total'] ?></strong>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($seriesAgrupadas)): ?>
                    <div style="text-align: center; padding: 30px;">
                        <div style="font-size: 3rem; margin-bottom: 10px;">📊</div>
                        <p style="color: #777;">Nenhum dado disponível ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>