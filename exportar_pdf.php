<?php
require_once '../config/conexao.php';
require_once '../includes/functions.php';

if (!isProfessorLogado()) redirecionar(BASE_URL . '/auth/login.php?tipo=professor');

$filtroJogo   = (int)($_GET['jogo'] ?? 0);
$filtroSerie  = sanitize($_GET['serie'] ?? '');

$sql = "
    SELECT i.*, j.nome as jogo_nome, a.email as aluno_email
    FROM inscricoes i
    JOIN jogos j ON i.jogo_id = j.id
    JOIN alunos a ON i.aluno_id = a.id
    WHERE i.status != 'cancelada'
";
$params = [];

if ($filtroJogo) {
    $sql .= " AND i.jogo_id = ?";
    $params[] = $filtroJogo;
}
if ($filtroSerie) {
    $sql .= " AND i.serie = ?";
    $params[] = $filtroSerie;
}

$sql .= " ORDER BY j.nome, i.serie, i.nome_completo";

$stmtInsc = $pdo->prepare($sql);
$stmtInsc->execute($params);
$inscricoes = $stmtInsc->fetchAll();

$jogos = $pdo->query("SELECT id, nome FROM jogos ORDER BY nome")->fetchAll();
$series = $pdo->query("SELECT DISTINCT serie FROM inscricoes ORDER BY serie")->fetchAll(PDO::FETCH_COLUMN);

// Se solicitou download
if (isset($_GET['download'])) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="relatorio-planer-sesi-' . date('Y-m-d') . '.html"');
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Relatório Planer SESI</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: Arial, sans-serif; padding: 20px; color: #16171a; }
            .header { background: #16171a; color: white; padding: 30px; text-align: center; border-radius: 8px; margin-bottom: 25px; }
            h1 { font-size: 1.8rem; }
            h2 { color: #c8102e; margin: 20px 0 10px; border-bottom: 2px solid #c8102e; padding-bottom: 5px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th { background: #16171a; color: white; padding: 10px; text-align: left; font-size: 0.85rem; }
            td { padding: 8px 10px; border-bottom: 1px solid #eee; font-size: 0.85rem; }
            tr:nth-child(even) { background: #f9f9f9; }
            .footer { text-align: center; margin-top: 30px; color: #5b616b; font-size: 0.8rem; }
        </style>
</head>
    <body>
    <div class="header">
        <h1>🏆 PLANER SESI</h1>
        <p>Relatório de Inscrições - Jogos Escolares 2025</p>
        <p>' . date('d/m/Y H:i') . '</p>
    </div>';
    
    // Agrupar por jogo
    $porJogo = [];
    foreach ($inscricoes as $insc) {
        $porJogo[$insc['jogo_nome']][] = $insc;
    }
    
    foreach ($porJogo as $jogoNome => $inscs) {
        echo "<h2>" . htmlspecialchars($jogoNome) . " (" . count($inscs) . " inscritos)</h2>";
        echo "<table>
            <tr>
                <th>#</th>
                <th>Nome do Aluno</th>
                <th>CPF</th>
                <th>Série</th>
                <th>Status</th>
                <th>E-mail</th>
            </tr>";
        
        foreach ($inscs as $i => $insc) {
            echo "<tr>
                <td>" . ($i + 1) . "</td>
                <td>" . htmlspecialchars($insc['nome_completo']) . "</td>
                <td>" . htmlspecialchars($insc['cpf']) . "</td>
                <td>" . htmlspecialchars($insc['serie']) . "</td>
                <td>" . ucfirst($insc['status']) . "</td>
                <td>" . htmlspecialchars($insc['aluno_email']) . "</td>
            </tr>";
        }
        
        echo "</table>";
    }
    
    echo '<div class="footer">
        <p>Relatório gerado em ' . date('d/m/Y H:i:s') . ' | Planer SESI - Sistema de Gestão de Jogos Escolares</p>
    </div>
    </body>
    </html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportar PDF - Planer SESI</title>
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
            <div class="sidebar-name"><?= htmlspecialchars($_SESSION['professor_nome'] ?? 'Professor') ?></div>
            <div class="sidebar-role">Professor</div>
        </div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="sidebar-link"><span class="link-icon">📊</span> Dashboard</a>
            <a href="gerenciar_inscricoes.php" class="sidebar-link"><span class="link-icon">📋</span> Inscrições</a>
            <a href="placar.php" class="sidebar-link"><span class="link-icon">🏆</span> Placar</a>
            <a href="relatorio.php" class="sidebar-link"><span class="link-icon">📈</span> Relatórios</a>
            <a href="exportar_pdf.php" class="sidebar-link active"><span class="link-icon">📄</span> Exportar PDF</a>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="sidebar-link"><span class="link-icon">🚪</span> Sair</a>
        </nav>
    </aside>
    
    <main class="dashboard-content">
        <div class="page-header">
            <div>
                <h1>📄 Exportar Relatório</h1>
                <p class="breadcrumb"><a href="dashboard.php">Dashboard</a> › Exportar PDF</p>
            </div>
        </div>
        
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">⚙️ Configurar Exportação</span>
            </div>
            <div class="panel-body">
                <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end; margin-bottom: 20px;">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Modalidade</label>
                        <select name="jogo" class="form-control">
                            <option value="">Todas as Modalidades</option>
                            <?php foreach ($jogos as $j): ?>
                                <option value="<?= $j['id'] ?>" <?= $filtroJogo == $j['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($j['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Série</label>
                        <select name="serie" class="form-control">
                            <option value="">Todas as Séries</option>
                            <?php foreach ($series as $s): ?>
                                <option value="<?= htmlspecialchars($s) ?>" <?= $filtroSerie === $s ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary">🔍 Filtrar Visualização</button>
                    <a href="?<?= http_build_query(['jogo' => $filtroJogo, 'serie' => $filtroSerie, 'download' => 1]) ?>" 
                       class="btn btn-danger">📥 Download HTML/PDF</a>
                </form>
                
                <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 12px 15px; margin-bottom: 20px;">
                    <p style="margin: 0; font-size: 0.9rem; color: #856404;">
                        💡 <strong>Dica:</strong> Após o download, abra o arquivo no navegador e use <strong>Ctrl+P</strong> (ou ⌘+P) para imprimir ou salvar como PDF.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Preview do Relatório -->
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">👁️ Preview do Relatório (<?= count($inscricoes) ?> registros)</span>
            </div>
            <div class="panel-body" style="padding: 0;">
                <?php if (empty($inscricoes)): ?>
                    <div style="padding: 30px; text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 15px;">📭</div>
                        <h3>Nenhum registro encontrado</h3>
                    </div>
                <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nome do Aluno</th>
                                <th>CPF</th>
                                <th>Série</th>
                                <th>Modalidade</th>
                                <th>Status</th>
                                <th>E-mail</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inscricoes as $i => $insc): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><strong><?= htmlspecialchars($insc['nome_completo']) ?></strong></td>
                                <td><?= htmlspecialchars($insc['cpf']) ?></td>
                                <td><?= htmlspecialchars($insc['serie']) ?></td>
                                <td><?= getIconeJogo($insc['jogo_nome']) ?> <?= htmlspecialchars($insc['jogo_nome']) ?></td>
                                <td><span class="badge badge-<?= $insc['status'] ?>"><?= ucfirst($insc['status']) ?></span></td>
                                <td><?= htmlspecialchars($insc['aluno_email']) ?></td>
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