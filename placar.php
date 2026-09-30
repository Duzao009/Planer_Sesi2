<?php
$pageTitle = 'Gerenciar Placar';
require_once '../config/conexao.php';
require_once '../includes/functions.php';

if (!isProfessorLogado()) redirecionar(BASE_URL . '/auth/login.php?tipo=professor');

$profId = $_SESSION['professor_id'];
$stmt = $pdo->prepare("SELECT * FROM professores WHERE id = ?");
$stmt->execute([$profId]);
$professor = $stmt->fetch();

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $jogoId  = (int)($_POST['jogo_id'] ?? 0);
    $placarA = (int)($_POST['placar_a'] ?? 0);
    $placarB = (int)($_POST['placar_b'] ?? 0);
    $timeA   = sanitize($_POST['time_a'] ?? 'Time A');
    $timeB   = sanitize($_POST['time_b'] ?? 'Time B');
    $status  = sanitize($_POST['status'] ?? 'aguardando');
    if (!in_array($status, ['aguardando','em_andamento','finalizado'], true)) $status = 'aguardando';
    $placarA = max(0, min(999, $placarA));
    $placarB = max(0, min(999, $placarB));
    
    if ($jogoId) {
        $stmtExiste = $pdo->prepare("SELECT id FROM placar WHERE jogo_id = ? LIMIT 1");
        $stmtExiste->execute([$jogoId]);
        $placarId = $stmtExiste->fetchColumn();

        if ($placarId) {
            $stmt = $pdo->prepare("
                UPDATE placar SET
                    placar_a = ?, placar_b = ?,
                    time_a = ?, time_b = ?,
                    status = ?, professor_id = ?
                WHERE id = ?
            ");
            $salvou = $stmt->execute([$placarA, $placarB, $timeA, $timeB, $status, $profId, $placarId]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO placar
                    (jogo_id, placar_a, placar_b, time_a, time_b, status, professor_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $salvou = $stmt->execute([$jogoId, $placarA, $placarB, $timeA, $timeB, $status, $profId]);
        }

        if ($salvou) {
            $mensagem = 'Placar atualizado com sucesso!';
        }
    }
}

$jogos = getJogos($pdo);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Placar - Planer SESI</title>
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
            <a href="placar.php" class="sidebar-link active"><span class="link-icon">🏆</span> Placar</a>
            <a href="relatorio.php" class="sidebar-link"><span class="link-icon">📈</span> Relatórios</a>
            <a href="exportar_pdf.php" class="sidebar-link"><span class="link-icon">📄</span> Exportar PDF</a>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="sidebar-link"><span class="link-icon">🚪</span> Sair</a>
        </nav>
    </aside>
    
    <main class="dashboard-content">
        <div class="page-header">
            <div>
                <h1>🏆 Gerenciar Placar</h1>
                <p class="breadcrumb"><a href="dashboard.php">Dashboard</a> › Placar</p>
            </div>
        </div>
        
        <?php if ($mensagem): echo alerta('sucesso', $mensagem); endif; ?>
        
        <div class="placar-grid">
            <?php foreach ($jogos as $jogo): ?>
            <div class="placar-editor">
                <div class="placar-editor-header">
                    <span class="placar-editor-title">
                        <?= getIconeJogo($jogo['nome']) ?> <?= htmlspecialchars($jogo['nome']) ?>
                    </span>
                    <select form="form-<?= $jogo['id'] ?>" name="status" class="placar-status">
                        <option value="aguardando" <?= ($jogo['placar_status'] ?? '') === 'aguardando' ? 'selected' : '' ?>>Aguardando</option>
                        <option value="em_andamento" <?= ($jogo['placar_status'] ?? '') === 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                        <option value="finalizado" <?= ($jogo['placar_status'] ?? '') === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                    </select>
                </div>
                
                <form method="POST" id="form-<?= $jogo['id'] ?>
                    <?= csrfField() ?>">
                    <input type="hidden" name="jogo_id" value="<?= $jogo['id'] ?>">
                    
                    <div class="placar-controls">
                        <!-- Time A -->
                        <div class="placar-control-group">
                            <input type="text" name="time_a" 
                                   value="<?= htmlspecialchars($jogo['time_a'] ?? 'Time A') ?>"
                                   placeholder="Time A"
                                   maxlength="100">
                            <div class="placar-score-controls">
                                <button type="button" class="btn-score btn-score-minus" 
                                        onclick="alterarPlacar('placar_a_<?= $jogo['id'] ?>', -1)">−</button>
                                <div class="score-display" id="score-a-<?= $jogo['id'] ?>">
                                    <?= $jogo['placar_a'] ?? 0 ?>
                                </div>
                                <input type="hidden" name="placar_a" id="placar_a_<?= $jogo['id'] ?>" 
                                       value="<?= $jogo['placar_a'] ?? 0 ?>">
                                <button type="button" class="btn-score btn-score-plus" 
                                        onclick="alterarPlacar('placar_a_<?= $jogo['id'] ?>', 1)">+</button>
                            </div>
                        </div>
                        
                        <div class="placar-versus">VS</div>
                        
                        <!-- Time B -->
                        <div class="placar-control-group">
                            <input type="text" name="time_b" 
                                   value="<?= htmlspecialchars($jogo['time_b'] ?? 'Time B') ?>"
                                   placeholder="Time B"
                                   maxlength="100">
                            <div class="placar-score-controls">
                                <button type="button" class="btn-score btn-score-minus" 
                                        onclick="alterarPlacar('placar_b_<?= $jogo['id'] ?>', -1)">−</button>
                                <div class="score-display" id="score-b-<?= $jogo['id'] ?>">
                                    <?= $jogo['placar_b'] ?? 0 ?>
                                </div>
                                <input type="hidden" name="placar_b" id="placar_b_<?= $jogo['id'] ?>" 
                                       value="<?= $jogo['placar_b'] ?? 0 ?>">
                                <button type="button" class="btn-score btn-score-plus" 
                                        onclick="alterarPlacar('placar_b_<?= $jogo['id'] ?>', 1)">+</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="placar-actions">
                        <button type="submit" class="btn btn-primary placar-save">
                            💾 Salvar Placar
                        </button>
                        <button type="button" class="btn btn-secondary" 
                                onclick="resetarPlacar(<?= $jogo['id'] ?>)">
                            🔄 Zerar Placar
                        </button>
                    </div>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
<script>
function alterarPlacar(inputId, delta) {
    const input = document.getElementById(inputId);
    const parts = inputId.split('_');
    const jogoId = parts[parts.length - 1];
    const timeLetra = parts[1]; // 'a' ou 'b'
    
    let valor = parseInt(input.value) + delta;
    if (valor < 0) valor = 0;
    input.value = valor;
    
    document.getElementById('score-' + timeLetra + '-' + jogoId).textContent = valor;
}

function resetarPlacar(jogoId) {
    if (confirm('Zerar o placar para 0 x 0?')) {
        document.getElementById('placar_a_' + jogoId).value = 0;
        document.getElementById('placar_b_' + jogoId).value = 0;
        document.getElementById('score-a-' + jogoId).textContent = 0;
        document.getElementById('score-b-' + jogoId).textContent = 0;
        document.getElementById('form-' + jogoId).requestSubmit();
    }
}
</script>
</body>
</html>