<?php
$pageTitle = 'Gerenciar Inscrições';
require_once '../config/conexao.php';
require_once '../includes/functions.php';

if (!isProfessorLogado()) redirecionar(BASE_URL . '/auth/login.php?tipo=professor');

$profId = $_SESSION['professor_id'];
$stmt = $pdo->prepare("SELECT * FROM professores WHERE id = ?");
$stmt->execute([$profId]);
$professor = $stmt->fetch();

$mensagem = '';
$tipoMensagem = '';

// Ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $action = $_POST['action'] ?? '';
    $inscId = (int)($_POST['inscricao_id'] ?? 0);
    
    if ($action === 'cancelar' && $inscId) {
        $stmt = $pdo->prepare("UPDATE inscricoes SET status = 'cancelada' WHERE id = ?");
        if ($stmt->execute([$inscId])) {
            $mensagem = 'Inscrição cancelada com sucesso!';
            $tipoMensagem = 'sucesso';
        }
    } elseif ($action === 'confirmar' && $inscId) {
        $stmt = $pdo->prepare("UPDATE inscricoes SET status = 'confirmada' WHERE id = ?");
        if ($stmt->execute([$inscId])) {
            $mensagem = 'Inscrição confirmada com sucesso!';
            $tipoMensagem = 'sucesso';
        }
    } elseif ($action === 'editar' && $inscId) {
        $novaSerie = sanitize($_POST['serie'] ?? '');
        if (!empty($novaSerie)) {
            $stmt = $pdo->prepare("UPDATE inscricoes SET serie = ? WHERE id = ?");
            if ($stmt->execute([$novaSerie, $inscId])) {
                $mensagem = 'Inscrição atualizada com sucesso!';
                $tipoMensagem = 'sucesso';
            }
        }
    } elseif ($action === 'excluir' && $inscId) {
        $stmt = $pdo->prepare("DELETE FROM inscricoes WHERE id = ?");
        $stmt->execute([$inscId]);

        if ($stmt->rowCount() === 1) {
            $mensagem = 'Inscrição apagada definitivamente!';
            $tipoMensagem = 'sucesso';
        } else {
            $mensagem = 'Inscrição não encontrada ou já apagada.';
            $tipoMensagem = 'erro';
        }
    }
}

// Filtros
$filtroJogo   = (int)($_GET['jogo'] ?? 0);
$filtroSerie  = sanitize($_GET['serie'] ?? '');
$filtroStatus = sanitize($_GET['status'] ?? '');
$busca        = sanitize($_GET['busca'] ?? '');

$sql = "
    SELECT i.*, j.nome as jogo_nome, a.email as aluno_email
    FROM inscricoes i
    JOIN jogos j ON i.jogo_id = j.id
    JOIN alunos a ON i.aluno_id = a.id
    WHERE 1=1
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
if ($filtroStatus) {
    $sql .= " AND i.status = ?";
    $params[] = $filtroStatus;
}
if ($busca) {
    $sql .= " AND (i.nome_completo LIKE ? OR i.cpf LIKE ? OR a.email LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

$sql .= " ORDER BY i.criado_em DESC";

$stmtInsc = $pdo->prepare($sql);
$stmtInsc->execute($params);
$inscricoes = $stmtInsc->fetchAll();

$jogos = $pdo->query("SELECT id, nome FROM jogos ORDER BY nome")->fetchAll();
$series = $pdo->query("SELECT DISTINCT serie FROM inscricoes ORDER BY serie")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Inscrições - Planer SESI</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
    <style>
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .modal h3 { margin-bottom: 20px; color: #CC0000; }
    </style>
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
            <a href="dashboard.php" class="sidebar-link">
                <span class="link-icon">📊</span> Dashboard
            </a>
            <a href="gerenciar_inscricoes.php" class="sidebar-link active">
                <span class="link-icon">📋</span> Inscrições
            </a>
            <a href="placar.php" class="sidebar-link">
                <span class="link-icon">🏆</span> Placar
            </a>
            <a href="relatorio.php" class="sidebar-link">
                <span class="link-icon">📈</span> Relatórios
            </a>
            <a href="exportar_pdf.php" class="sidebar-link">
                <span class="link-icon">📄</span> Exportar PDF
            </a>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="sidebar-link">
                <span class="link-icon">🚪</span> Sair
            </a>
        </nav>
    </aside>
    
    <main class="dashboard-content">
        <div class="page-header">
            <div>
                <h1>📋 Gerenciar Inscrições</h1>
                <p class="breadcrumb"><a href="dashboard.php">Dashboard</a> › Inscrições</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="exportar_pdf.php" class="btn btn-danger">📄 Exportar PDF</a>
                <a href="relatorio.php" class="btn btn-warning">📈 Relatório</a>
            </div>
        </div>
        
        <?php if ($mensagem): echo alerta($tipoMensagem, $mensagem); endif; ?>
        
        <!-- Filtros -->
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">🔍 Filtros de Busca</span>
            </div>
            <div class="panel-body">
                <form method="GET">
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; align-items: end;">
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label">Buscar</label>
                            <input type="text" name="busca" class="form-control" 
                                   placeholder="Nome, CPF ou e-mail..."
                                   value="<?= htmlspecialchars($busca) ?>">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label">Modalidade</label>
                            <select name="jogo" class="form-control">
                                <option value="">Todas</option>
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
                                <option value="">Todas</option>
                                <?php foreach ($series as $s): ?>
                                    <option value="<?= htmlspecialchars($s) ?>" <?= $filtroSerie === $s ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="">Todos</option>
                                <option value="confirmada" <?= $filtroStatus === 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
                                <option value="pendente" <?= $filtroStatus === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                                <option value="cancelada" <?= $filtroStatus === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                            </select>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
                            <a href="gerenciar_inscricoes.php" class="btn btn-secondary">Limpar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Tabela de Inscrições -->
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">
                    📋 Inscrições 
                    <span style="background: rgba(255,255,255,0.25); padding: 3px 10px; border-radius: 10px; margin-left: 10px; font-size: 0.85rem;">
                        <?= count($inscricoes) ?> resultado(s)
                    </span>
                </span>
            </div>
            <div class="panel-body" style="padding: 0;">
                <?php if (empty($inscricoes)): ?>
                    <div style="padding: 30px; text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 15px;">📭</div>
                        <h3>Nenhuma inscrição encontrada</h3>
                        <p style="color: #777;">Tente ajustar os filtros de busca.</p>
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
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inscricoes as $insc): ?>
                            <tr>
                                <td><?= $insc['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($insc['nome_completo']) ?></strong>
                                    <div style="font-size: 0.78rem; color: #777;"><?= htmlspecialchars($insc['aluno_email']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($insc['cpf']) ?></td>
                                <td><?= htmlspecialchars($insc['serie']) ?></td>
                                <td><?= getIconeJogo($insc['jogo_nome']) ?> <?= htmlspecialchars($insc['jogo_nome']) ?></td>
                                <td><span class="badge badge-<?= $insc['status'] ?>"><?= ucfirst($insc['status']) ?></span></td>
                                <td style="font-size: 0.82rem;"><?= date('d/m/Y', strtotime($insc['criado_em'])) ?></td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <?php if ($insc['status'] !== 'cancelada'): ?>
                                            <button onclick="abrirEditar(<?= $insc['id'] ?>, '<?= htmlspecialchars($insc['serie']) ?>')" 
                                                    class="btn btn-warning btn-sm">✏️</button>
                                        <?php endif; ?>
                                        <?php if ($insc['status'] === 'pendente'): ?>
                                            <form method="POST" style="display: inline;">
                    <?= csrfField() ?>
                                                <input type="hidden" name="action" value="confirmar">
                                                <input type="hidden" name="inscricao_id" value="<?= $insc['id'] ?>">
                                                <button type="submit" class="btn btn-success btn-sm">✅</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($insc['status'] !== 'cancelada'): ?>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Cancelar esta inscrição?')">
                    <?= csrfField() ?>
                                                <input type="hidden" name="action" value="cancelar">
                                                <input type="hidden" name="inscricao_id" value="<?= $insc['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" title="Cancelar inscrição" aria-label="Cancelar inscrição">❌</button>
                                            </form>
                                        <?php endif; ?>
                                            <form method="POST" style="display: inline;"
                                                  onsubmit="return confirm('Apagar esta inscrição definitivamente? Esta ação não pode ser desfeita.')">
                    <?= csrfField() ?>
                                                <input type="hidden" name="action" value="excluir">
                                                <input type="hidden" name="inscricao_id" value="<?= $insc['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" title="Apagar inscrição" aria-label="Apagar inscrição">🗑️</button>
                                            </form>
                                    </div>
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

<!-- Modal Editar -->
<div class="modal-overlay" id="modalEditar">
    <div class="modal">
        <h3>✏️ Editar Inscrição</h3>
        <form method="POST">
                    <?= csrfField() ?>
            <input type="hidden" name="action" value="editar">
            <input type="hidden" name="inscricao_id" id="editarId">
            <div class="form-group">
                <label class="form-label">Série <span>*</span></label>
                <select name="serie" id="editarSerie" class="form-control" required>
                    <optgroup label="Ensino Fundamental">
                        <option value="6º Ano EF">6º Ano EF</option>
                        <option value="7º Ano EF">7º Ano EF</option>
                        <option value="8º Ano EF">8º Ano EF</option>
                        <option value="9º Ano EF">9º Ano EF</option>
                    </optgroup>
                    <optgroup label="Ensino Médio">
                        <option value="1º Ano EM">1º Ano EM</option>
                        <option value="2º Ano EM">2º Ano EM</option>
                        <option value="3º Ano EM">3º Ano EM</option>
                    </optgroup>
                </select>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <button type="button" onclick="fecharModal()" class="btn btn-secondary">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<script>
function abrirEditar(id, serie) {
    document.getElementById('editarId').value = id;
    document.getElementById('editarSerie').value = serie;
    document.getElementById('modalEditar').classList.add('active');
}

function fecharModal() {
    document.getElementById('modalEditar').classList.remove('active');
}

document.getElementById('modalEditar').addEventListener('click', function(e) {
    if (e.target === this) fecharModal();
});
</script>
</body>
</html>