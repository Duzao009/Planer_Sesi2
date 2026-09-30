<?php
// ============================================
// PAINEL DO PROFESSOR - professor/dashboard.php
// ============================================
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/conexao.php';
require_once ROOT_PATH . '/includes/functions.php';

if (!isProfessorLogado()) {
    redirecionar(BASE_URL . '/auth/login.php?tipo=professor');
}

$profId = $_SESSION['professor_id'];

// Buscar dados do professor
$stmt = $pdo->prepare("SELECT * FROM professores WHERE id = ?");
$stmt->execute([$profId]);
$professor = $stmt->fetch();

// ============================================
// VERIFICAR QUAIS COLUNAS EXISTEM NA TABELA
// ============================================
$colunasJogos = $pdo->query("SHOW COLUMNS FROM jogos")->fetchAll(PDO::FETCH_COLUMN);
$temIcone     = in_array('icone', $colunasJogos);
$temCriadoPor = in_array('criado_por', $colunasJogos);

// Monta o SELECT dinamicamente conforme colunas existentes
$selectIcone     = $temIcone     ? "COALESCE(j.icone, '🏆') AS icone" : "'🏆' AS icone";
$selectIconeAlias = $temIcone ? "COALESCE(j.icone, '🏆')" : "'🏆'";
$selectCriadoPor = $temCriadoPor ? "j.criado_por" : "NULL as criado_por";

// ============================================
// ESTATÍSTICAS GERAIS
// ============================================
$totalInscricoes  = $pdo->query("SELECT COUNT(*) FROM inscricoes WHERE status != 'cancelada'")->fetchColumn();
$totalAlunos      = $pdo->query("SELECT COUNT(*) FROM alunos")->fetchColumn();
$confirmadas      = $pdo->query("SELECT COUNT(*) FROM inscricoes WHERE status = 'confirmada'")->fetchColumn();
$canceladas       = $pdo->query("SELECT COUNT(*) FROM inscricoes WHERE status = 'cancelada'")->fetchColumn();
$pendentes        = $pdo->query("SELECT COUNT(*) FROM inscricoes WHERE status = 'pendente'")->fetchColumn();
$totalJogos       = $pdo->query("SELECT COUNT(*) FROM jogos")->fetchColumn();
$jogosAbertos     = $pdo->query("SELECT COUNT(*) FROM jogos WHERE status = 'aberto'")->fetchColumn();
$totalProfessores = $pdo->query("SELECT COUNT(*) FROM professores")->fetchColumn();

// Inscrições por jogo
$porJogo = $pdo->query("
    SELECT
        j.nome,
        j.max_participantes,
        j.status,
        {$selectIcone},
        COALESCE(i.total, 0) as total
    FROM jogos j
    LEFT JOIN (
        SELECT jogo_id, COUNT(*) as total
        FROM inscricoes
        WHERE status != 'cancelada'
        GROUP BY jogo_id
    ) i ON j.id = i.jogo_id
    ORDER BY total DESC
")->fetchAll();

// Últimas 10 inscrições
$ultimas = $pdo->query("
    SELECT
        i.id,
        i.nome_completo,
        i.serie,
        i.status,
        i.criado_em,
        j.nome         AS jogo_nome,
        {$selectIconeAlias} AS jogo_icone,
        a.email        AS aluno_email
    FROM inscricoes i
    JOIN jogos   j ON i.jogo_id  = j.id
    JOIN alunos  a ON i.aluno_id = a.id
    ORDER BY i.criado_em DESC
    LIMIT 10
")->fetchAll();

// Top 5 séries
$porSerie = $pdo->query("
    SELECT serie, COUNT(*) as total
    FROM inscricoes
    WHERE status != 'cancelada'
    GROUP BY serie
    ORDER BY total DESC
    LIMIT 5
")->fetchAll();

// Jogos em andamento
$jogosAoVivo = $pdo->query("
    SELECT
        j.id,
        j.nome,
        j.status,
        {$selectIcone},
        p.placar_a,
        p.placar_b,
        p.time_a,
        p.time_b
    FROM jogos j
    LEFT JOIN placar p ON j.id = p.jogo_id
    WHERE j.status = 'em_andamento'
    LIMIT 3
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Professor - Planer SESI</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
    <style>
        /* ============================================
           ESTILOS DO DASHBOARD
           ============================================ */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 22px 20px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.07);
            border-left: 5px solid #CC0000;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            cursor: default;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: -15px; right: -15px;
            width: 70px; height: 70px;
            background: rgba(204,0,0,0.05);
            border-radius: 50%;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(204,0,0,0.15);
        }

        .stat-card.verde   { border-left-color: #2e7d32; }
        .stat-card.verde:hover { box-shadow: 0 10px 30px rgba(46,125,50,0.15); }
        .stat-card.verde .stat-valor { color: #2e7d32; }

        .stat-card.azul    { border-left-color: #1565c0; }
        .stat-card.azul:hover { box-shadow: 0 10px 30px rgba(21,101,192,0.15); }
        .stat-card.azul .stat-valor { color: #1565c0; }

        .stat-card.laranja { border-left-color: #e65100; }
        .stat-card.laranja:hover { box-shadow: 0 10px 30px rgba(230,81,0,0.15); }
        .stat-card.laranja .stat-valor { color: #e65100; }

        .stat-card.roxo    { border-left-color: #6a1b9a; }
        .stat-card.roxo:hover { box-shadow: 0 10px 30px rgba(106,27,154,0.15); }
        .stat-card.roxo .stat-valor { color: #6a1b9a; }

        .stat-icone { font-size: 1.9rem; margin-bottom: 10px; display: block; }

        .stat-valor {
            font-size: 2.3rem;
            font-weight: 900;
            color: #CC0000;
            line-height: 1;
            display: block;
        }

        .stat-label {
            font-size: 0.78rem;
            color: #888;
            margin-top: 5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Painel duplo */
        .painel-duplo {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
            margin-bottom: 22px;
        }

        /* Tabela */
        .tabela-ultimas { width: 100%; border-collapse: collapse; }

        .tabela-ultimas thead th {
            background: linear-gradient(135deg, #990000, #CC0000);
            color: white;
            padding: 11px 14px;
            text-align: left;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }

        .tabela-ultimas tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }

        .tabela-ultimas tbody tr:hover { background: #fff5f5; }

        .tabela-ultimas tbody td {
            padding: 11px 14px;
            font-size: 0.87rem;
            vertical-align: middle;
        }

        /* Barras de progresso */
        .modalidade-row { margin-bottom: 14px; }

        .modalidade-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .modalidade-nome {
            font-size: 0.88rem;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .modalidade-contagem {
            font-size: 0.82rem;
            font-weight: 700;
            color: #CC0000;
        }

        .barra-prog {
            background: #f0f0f0;
            border-radius: 10px;
            height: 9px;
            overflow: hidden;
        }

        .barra-fill {
            height: 100%;
            border-radius: 10px;
            background: linear-gradient(90deg, #CC0000, #FF3333);
            transition: width 1s ease;
        }

        /* Ações rápidas */
        .acoes-rapidas {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 22px;
        }

        .acao-btn {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 18px 14px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.25s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-family: inherit;
        }

        .acao-btn:hover {
            border-color: #CC0000;
            background: #fff5f5;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(204,0,0,0.15);
            color: #CC0000;
        }

        .acao-btn .acao-icon { font-size: 1.8rem; }
        .acao-btn .acao-text { font-size: 0.8rem; font-weight: 700; }

        /* Jogos ao vivo */
        .ao-vivo-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 15px;
            margin-bottom: 22px;
        }

        .ao-vivo-card {
            background: linear-gradient(135deg, #1a1a1a, #2d0000);
            border-radius: 12px;
            padding: 18px 20px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .ao-vivo-card::before {
            content: '';
            position: absolute;
            top: -30px; right: -30px;
            width: 100px; height: 100px;
            background: rgba(204,0,0,0.15);
            border-radius: 50%;
        }

        .ao-vivo-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #CC0000;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .ao-vivo-dot {
            width: 6px; height: 6px;
            background: white;
            border-radius: 50%;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0; }
        }

        .ao-vivo-nome {
            font-size: 1rem;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .ao-vivo-placar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .ao-vivo-time      { text-align: center; flex: 1; }
        .ao-vivo-time-nome { font-size: 0.72rem; opacity: 0.7; margin-bottom: 4px; }

        .ao-vivo-score {
            font-size: 2.5rem;
            font-weight: 900;
            color: #FF3333;
            line-height: 1;
        }

        .ao-vivo-vs { font-size: 1rem; font-weight: 700; opacity: 0.5; }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 0.73rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .badge-confirmada { background: #e8f5e9; color: #1b5e20; }
        .badge-pendente   { background: #fff3e0; color: #e65100; }
        .badge-cancelada  { background: #ffebee; color: #b71c1c; }

        .saudacao-hora { font-size: 0.85rem; color: #888; font-weight: 400; }

        /* Responsivo */
        @media (max-width: 960px) {
            .painel-duplo { grid-template-columns: 1fr; }
        }

        @media (max-width: 600px) {
            .stats-grid    { grid-template-columns: repeat(2, 1fr); }
            .acoes-rapidas { grid-template-columns: repeat(3, 1fr); }
        }
    </style>
    <link rel="icon" href="<?= BASE_URL ?>/favicon.png" type="image/png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modern.css">
</head>
<body>

<!-- ============================================
     HEADER
     ============================================ -->
<header class="header-main">
    <div class="container">
        <div class="header-logo">
            <div class="logo-icon">🏆</div>
            <div class="logo-text">
                <span class="logo-planer">Planer</span>
                <span class="logo-sesi">SESI</span>
            </div>
        </div>
        <nav class="header-nav">
            <a href="<?= BASE_URL ?>/index.php" class="nav-link">🌐 Site</a>
            <a href="<?= BASE_URL ?>/professor/gerenciar_inscricoes.php" class="nav-link">📋 Inscrições</a>
            <a href="<?= BASE_URL ?>/professor/placar.php" class="nav-link">🏆 Placar</a>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-link nav-logout">🚪 Sair</a>
        </nav>
        <button class="menu-mobile"
                onclick="document.getElementById('navMobile').classList.toggle('active')">☰</button>
    </div>
</header>

<div class="nav-mobile" id="navMobile">
    <a href="<?= BASE_URL ?>/index.php">🌐 Site</a>
    <a href="<?= BASE_URL ?>/professor/gerenciar_inscricoes.php">📋 Inscrições</a>
    <a href="<?= BASE_URL ?>/professor/placar.php">🏆 Placar</a>
    <a href="<?= BASE_URL ?>/professor/gerenciar_jogos.php">🏅 Modalidades</a>
    <a href="<?= BASE_URL ?>/professor/relatorio.php">📈 Relatórios</a>
    <a href="<?= BASE_URL ?>/professor/exportar_pdf.php">📄 Exportar PDF</a>
    <a href="<?= BASE_URL ?>/auth/logout.php">🚪 Sair</a>
</div>

<!-- ============================================
     LAYOUT
     ============================================ -->
<div class="dashboard-layout">

    <!-- ==========================================
         SIDEBAR
         ========================================== -->
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-avatar">👨‍🏫</div>
            <div class="sidebar-name">
                <?= htmlspecialchars($professor['nome_completo']) ?>
            </div>
            <div class="sidebar-role">Professor</div>
        </div>

        <nav class="sidebar-menu">

            <div class="sidebar-section">Gestão</div>

            <a href="<?= BASE_URL ?>/professor/dashboard.php"
               class="sidebar-link active">
                <span class="link-icon">📊</span>
                Dashboard
            </a>

            <a href="<?= BASE_URL ?>/professor/gerenciar_inscricoes.php"
               class="sidebar-link">
                <span class="link-icon">📋</span>
                Inscrições
                <?php if ($totalInscricoes > 0): ?>
                    <span class="badge-count"><?= $totalInscricoes ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= BASE_URL ?>/professor/gerenciar_jogos.php"
               class="sidebar-link">
                <span class="link-icon">🏅</span>
                Modalidades
                <?php if ($totalJogos > 0): ?>
                    <span class="badge-count"><?= $totalJogos ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= BASE_URL ?>/professor/placar.php"
               class="sidebar-link">
                <span class="link-icon">🏆</span>
                Placar
                <?php if (count($jogosAoVivo) > 0): ?>
                    <span class="badge-count"
                          style="background:#43a047;color:white;font-size:0.6rem;">
                        AO VIVO
                    </span>
                <?php endif; ?>
            </a>

            <a href="<?= BASE_URL ?>/professor/relatorio.php"
               class="sidebar-link">
                <span class="link-icon">📈</span>
                Relatórios
            </a>

            <a href="<?= BASE_URL ?>/professor/exportar_pdf.php"
               class="sidebar-link">
                <span class="link-icon">📄</span>
                Exportar PDF
            </a>

            <div class="sidebar-section">Sistema</div>

            <a href="<?= BASE_URL ?>/index.php"
               class="sidebar-link">
                <span class="link-icon">🌐</span>
                Ver Site
            </a>

            <a href="<?= BASE_URL ?>/auth/logout.php"
               class="sidebar-link">
                <span class="link-icon">🚪</span>
                Sair
            </a>

        </nav>
    </aside>

    <!-- ==========================================
         CONTEÚDO PRINCIPAL
         ========================================== -->
    <main class="dashboard-content">

        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>
                    📊 Dashboard
                    <span class="saudacao-hora" id="saudacao"></span>
                </h1>
                <p class="breadcrumb">
                    Jogos Escolares SESI 2025 —
                    <span id="dataHora" style="color:#CC0000;font-weight:600;"></span>
                </p>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="<?= BASE_URL ?>/professor/gerenciar_jogos.php"
                   class="btn btn-secondary">
                    🏅 Modalidades
                </a>
                <a href="<?= BASE_URL ?>/professor/gerenciar_inscricoes.php"
                   class="btn btn-primary">
                    📋 Ver Inscrições
                </a>
            </div>
        </div>

        <!-- ==========================================
             JOGOS AO VIVO
             ========================================== -->
        <?php if (!empty($jogosAoVivo)): ?>
        <div style="margin-bottom:22px;">
            <h3 style="font-size:1rem;font-weight:700;color:#CC0000;
                       margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                <span style="display:inline-flex;align-items:center;gap:5px;
                             background:#CC0000;color:white;padding:4px 12px;
                             border-radius:12px;font-size:0.78rem;">
                    <span style="width:7px;height:7px;background:white;
                                 border-radius:50%;animation:blink 1s infinite;
                                 display:block;"></span>
                    AO VIVO
                </span>
                Jogos em Andamento
            </h3>
            <div class="ao-vivo-container">
                <?php foreach ($jogosAoVivo as $jv): ?>
                <div class="ao-vivo-card">
                    <div class="ao-vivo-badge">
                        <span class="ao-vivo-dot"></span> AO VIVO
                    </div>
                    <div class="ao-vivo-nome">
                        <?= htmlspecialchars($jv['icone']) ?>
                        <?= htmlspecialchars($jv['nome']) ?>
                    </div>
                    <div class="ao-vivo-placar">
                        <div class="ao-vivo-time">
                            <div class="ao-vivo-time-nome">
                                <?= htmlspecialchars($jv['time_a'] ?? 'Time A') ?>
                            </div>
                            <div class="ao-vivo-score">
                                <?= (int)($jv['placar_a'] ?? 0) ?>
                            </div>
                        </div>
                        <div class="ao-vivo-vs">×</div>
                        <div class="ao-vivo-time">
                            <div class="ao-vivo-time-nome">
                                <?= htmlspecialchars($jv['time_b'] ?? 'Time B') ?>
                            </div>
                            <div class="ao-vivo-score">
                                <?= (int)($jv['placar_b'] ?? 0) ?>
                            </div>
                        </div>
                        <a href="<?= BASE_URL ?>/professor/placar.php"
                           style="background:rgba(255,255,255,0.12);color:white;
                                  text-decoration:none;padding:6px 12px;
                                  border-radius:8px;font-size:0.75rem;font-weight:700;
                                  border:1px solid rgba(255,255,255,0.2);
                                  white-space:nowrap;transition:all 0.2s;"
                           onmouseover="this.style.background='rgba(255,255,255,0.25)'"
                           onmouseout="this.style.background='rgba(255,255,255,0.12)'">
                            ✏️ Editar
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ==========================================
             CARDS DE ESTATÍSTICAS
             ========================================== -->
        <div class="stats-grid">

            <div class="stat-card">
                <span class="stat-icone">👥</span>
                <span class="stat-valor"><?= $totalAlunos ?></span>
                <div class="stat-label">Alunos Cadastrados</div>
            </div>

            <div class="stat-card verde">
                <span class="stat-icone">✅</span>
                <span class="stat-valor"><?= $confirmadas ?></span>
                <div class="stat-label">Confirmadas</div>
            </div>

            <div class="stat-card azul">
                <span class="stat-icone">📋</span>
                <span class="stat-valor"><?= $totalInscricoes ?></span>
                <div class="stat-label">Inscrições Ativas</div>
            </div>

            <div class="stat-card laranja">
                <span class="stat-icone">⏳</span>
                <span class="stat-valor"><?= $pendentes ?></span>
                <div class="stat-label">Pendentes</div>
            </div>

            <div class="stat-card roxo">
                <span class="stat-icone">🏅</span>
                <span class="stat-valor"><?= $totalJogos ?></span>
                <div class="stat-label">Modalidades</div>
            </div>

            <div class="stat-card">
                <span class="stat-icone">🟢</span>
                <span class="stat-valor"><?= $jogosAbertos ?></span>
                <div class="stat-label">Jogos Abertos</div>
            </div>

            <div class="stat-card verde">
                <span class="stat-icone">👨‍🏫</span>
                <span class="stat-valor"><?= $totalProfessores ?></span>
                <div class="stat-label">Professores</div>
            </div>

            <div class="stat-card"
                 style="border-left-color:#b71c1c;">
                <span class="stat-icone">❌</span>
                <span class="stat-valor"
                      style="color:#b71c1c;"><?= $canceladas ?></span>
                <div class="stat-label">Canceladas</div>
            </div>

        </div>

        <!-- ==========================================
             AÇÕES RÁPIDAS
             ========================================== -->
        <div style="margin-bottom:22px;">
            <h3 style="font-size:0.95rem;font-weight:700;color:#555;
                       text-transform:uppercase;letter-spacing:1px;
                       margin-bottom:14px;">
                ⚡ Ações Rápidas
            </h3>
            <div class="acoes-rapidas">

                <a href="<?= BASE_URL ?>/professor/gerenciar_jogos.php"
                   class="acao-btn">
                    <span class="acao-icon">🏅</span>
                    <span class="acao-text">Gerenciar Modalidades</span>
                </a>

                <a href="<?= BASE_URL ?>/professor/gerenciar_inscricoes.php"
                   class="acao-btn">
                    <span class="acao-icon">📋</span>
                    <span class="acao-text">Ver Inscrições</span>
                </a>

                <a href="<?= BASE_URL ?>/professor/placar.php"
                   class="acao-btn">
                    <span class="acao-icon">🏆</span>
                    <span class="acao-text">Editar Placar</span>
                </a>

                <a href="<?= BASE_URL ?>/professor/relatorio.php"
                   class="acao-btn">
                    <span class="acao-icon">📈</span>
                    <span class="acao-text">Ver Relatórios</span>
                </a>

                <a href="<?= BASE_URL ?>/professor/exportar_pdf.php"
                   class="acao-btn">
                    <span class="acao-icon">📄</span>
                    <span class="acao-text">Exportar PDF</span>
                </a>

                <a href="<?= BASE_URL ?>/professor/gerenciar_jogos.php"
                   class="acao-btn"
                   style="border-color:#CC0000;color:#CC0000;">
                    <span class="acao-icon">➕</span>
                    <span class="acao-text">Nova Modalidade</span>
                </a>

            </div>
        </div>

        <!-- ==========================================
             PAINEL DUPLO
             ========================================== -->
        <div class="painel-duplo">

            <!-- Inscrições por Modalidade -->
            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title">🏅 Inscrições por Modalidade</span>
                    <a href="<?= BASE_URL ?>/professor/relatorio.php"
                       style="background:rgba(255,255,255,0.2);color:white;
                              text-decoration:none;padding:5px 12px;
                              border-radius:8px;font-size:0.8rem;font-weight:600;">
                        Relatório
                    </a>
                </div>
                <div class="panel-body">
                    <?php if (empty($porJogo)): ?>
                        <p style="text-align:center;color:#aaa;padding:20px 0;">
                            Nenhuma modalidade cadastrada.
                        </p>
                    <?php else: ?>
                        <?php foreach ($porJogo as $pj):
                            $pct = $pj['max_participantes'] > 0
                                ? round(($pj['total'] / $pj['max_participantes']) * 100)
                                : 0;
                            $cor = $pct >= 90 ? '#e53935'
                                 : ($pct >= 70 ? '#fb8c00' : '#CC0000');

                            $statusCores = [
                                'aberto'       => '#2e7d32',
                                'fechado'      => '#b71c1c',
                                'em_andamento' => '#e65100',
                                'finalizado'   => '#555',
                            ];
                            $corStatus = $statusCores[$pj['status']] ?? '#555';
                        ?>
                        <div class="modalidade-row">
                            <div class="modalidade-header">
                                <span class="modalidade-nome">
                                    <?= htmlspecialchars($pj['icone']) ?>
                                    <?= htmlspecialchars($pj['nome']) ?>
                                    <span style="font-size:0.68rem;
                                                 color:<?= $corStatus ?>;
                                                 background:<?= $corStatus ?>22;
                                                 padding:2px 7px;
                                                 border-radius:8px;
                                                 font-weight:700;
                                                 margin-left:4px;">
                                        <?= ucfirst(str_replace('_', ' ', $pj['status'])) ?>
                                    </span>
                                </span>
                                <span class="modalidade-contagem">
                                    <?= $pj['total'] ?>/<?= $pj['max_participantes'] ?>
                                    <span style="font-size:0.72rem;color:#aaa;font-weight:400;">
                                        (<?= $pct ?>%)
                                    </span>
                                </span>
                            </div>
                            <div class="barra-prog">
                                <div class="barra-fill"
                                     style="width:<?= min(100,$pct) ?>%;
                                            background:<?= $cor ?>;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Séries -->
            <div class="panel">
                <div class="panel-header">
                    <span class="panel-title">🎓 Top Séries com Mais Inscrições</span>
                    <a href="<?= BASE_URL ?>/professor/gerenciar_inscricoes.php"
                       style="background:rgba(255,255,255,0.2);color:white;
                              text-decoration:none;padding:5px 12px;
                              border-radius:8px;font-size:0.8rem;font-weight:600;">
                        Ver Todas
                    </a>
                </div>
                <div class="panel-body">
                    <?php if (empty($porSerie)): ?>
                        <p style="text-align:center;color:#aaa;padding:20px 0;">
                            Nenhuma inscrição ainda.
                        </p>
                    <?php else:
                        $maxSerie = max(array_column($porSerie, 'total'));
                        foreach ($porSerie as $ps):
                            $pctSerie = $maxSerie > 0
                                ? round(($ps['total'] / $maxSerie) * 100)
                                : 0;
                    ?>
                        <div class="modalidade-row">
                            <div class="modalidade-header">
                                <span class="modalidade-nome">
                                    🎓 <?= htmlspecialchars($ps['serie']) ?>
                                </span>
                                <span class="modalidade-contagem">
                                    <?= $ps['total'] ?>
                                    <span style="font-size:0.72rem;
                                                 color:#aaa;font-weight:400;">
                                        inscrição(ões)
                                    </span>
                                </span>
                            </div>
                            <div class="barra-prog">
                                <div class="barra-fill"
                                     style="width:<?= $pctSerie ?>%;
                                            background:linear-gradient(90deg,#1565c0,#42a5f5);">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

        </div><!-- /painel-duplo -->

        <!-- ==========================================
             ÚLTIMAS INSCRIÇÕES
             ========================================== -->
        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">🕐 Últimas Inscrições Realizadas</span>
                <a href="<?= BASE_URL ?>/professor/gerenciar_inscricoes.php"
                   style="background:rgba(255,255,255,0.2);color:white;
                          text-decoration:none;padding:5px 12px;
                          border-radius:8px;font-size:0.8rem;font-weight:600;">
                    Ver Todas
                </a>
            </div>
            <div style="overflow-x:auto;">
                <?php if (empty($ultimas)): ?>
                    <div style="padding:40px;text-align:center;">
                        <div style="font-size:3rem;margin-bottom:12px;">📭</div>
                        <h3 style="color:#555;margin-bottom:8px;">
                            Nenhuma inscrição ainda
                        </h3>
                        <p style="color:#aaa;font-size:0.9rem;">
                            As inscrições dos alunos aparecerão aqui.
                        </p>
                    </div>
                <?php else: ?>
                    <table class="tabela-ultimas">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Aluno</th>
                                <th>Série</th>
                                <th>Modalidade</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimas as $ul): ?>
                            <tr>
                                <td style="color:#aaa;font-size:0.8rem;">
                                    <?= $ul['id'] ?>
                                </td>
                                <td>
                                    <strong>
                                        <?= htmlspecialchars($ul['nome_completo']) ?>
                                    </strong>
                                    <div style="font-size:0.75rem;color:#aaa;">
                                        <?= htmlspecialchars($ul['aluno_email']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="background:#f0f0f0;
                                                 padding:3px 8px;border-radius:6px;
                                                 font-size:0.8rem;font-weight:600;">
                                        <?= htmlspecialchars($ul['serie']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($ul['jogo_icone']) ?>
                                    <?= htmlspecialchars($ul['jogo_nome']) ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $ul['status'] ?>">
                                        <?= ucfirst($ul['status']) ?>
                                    </span>
                                </td>
                                <td style="font-size:0.8rem;color:#888;
                                           white-space:nowrap;">
                                    <?= date('d/m/Y', strtotime($ul['criado_em'])) ?>
                                    <div style="font-size:0.72rem;">
                                        <?= date('H:i', strtotime($ul['criado_em'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>/professor/gerenciar_inscricoes.php?busca=<?= urlencode($ul['nome_completo']) ?>"
                                       style="background:#fff5f5;color:#CC0000;
                                              border:1px solid #ffcdd2;
                                              padding:5px 10px;border-radius:7px;
                                              text-decoration:none;font-size:0.78rem;
                                              font-weight:700;transition:all 0.2s;"
                                       onmouseover="this.style.background='#CC0000';
                                                    this.style.color='white'"
                                       onmouseout="this.style.background='#fff5f5';
                                                   this.style.color='#CC0000'">
                                        🔍 Ver
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </main><!-- /dashboard-content -->
</div><!-- /dashboard-layout -->

<?php include '../includes/footer.php'; ?>

<!-- ============================================
     JAVASCRIPT
     ============================================ -->
<script>
// Saudação e relógio em tempo real
function atualizarTempo() {
    const agora = new Date();
    const hora  = agora.getHours();
    const min   = String(agora.getMinutes()).padStart(2, '0');
    const seg   = String(agora.getSeconds()).padStart(2, '0');
    const dia   = agora.toLocaleDateString('pt-BR', {
        weekday: 'long',
        day:     'numeric',
        month:   'long',
        year:    'numeric'
    });

    let saudacao = hora >= 5 && hora < 12 ? 'Bom dia'
                 : hora >= 12 && hora < 18 ? 'Boa tarde'
                 : 'Boa noite';

    const elSaud = document.getElementById('saudacao');
    const elData = document.getElementById('dataHora');
    if (elSaud) elSaud.textContent = saudacao;
    if (elData) elData.textContent = dia + ' — ' + hora + ':' + min + ':' + seg;
}
atualizarTempo();
setInterval(atualizarTempo, 1000);

// Animar barras ao entrar na tela
document.addEventListener('DOMContentLoaded', function () {

    const barras = document.querySelectorAll('.barra-fill');
    barras.forEach(b => {
        const larguraFinal = b.style.width;
        b.style.width = '0%';

        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    setTimeout(() => { b.style.width = larguraFinal; }, 200);
                    observer.unobserve(b);
                }
            });
        }, { threshold: 0.3 });

        observer.observe(b);
    });

    // Animar stat cards em sequência
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, i) => {
        card.style.opacity   = '0';
        card.style.transform = 'translateY(18px)';
        card.style.transition =
            `opacity 0.4s ease ${i * 0.07}s, transform 0.4s ease ${i * 0.07}s`;
        setTimeout(() => {
            card.style.opacity   = '1';
            card.style.transform = 'translateY(0)';
        }, 60);
    });
});

// Menu mobile — fechar ao clicar fora
document.addEventListener('click', function (e) {
    const nav = document.getElementById('navMobile');
    const btn = document.querySelector('.menu-mobile');
    if (nav && btn &&
        !nav.contains(e.target) &&
        !btn.contains(e.target)) {
        nav.classList.remove('active');
    }
});

</script>

</body>
</html>