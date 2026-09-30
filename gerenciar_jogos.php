<?php
// ============================================
// GERENCIAR JOGOS - professor/gerenciar_jogos.php
// ============================================
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/conexao.php';
require_once ROOT_PATH . '/includes/functions.php';

if (!isProfessorLogado()) {
    redirecionar(BASE_URL . '/auth/login.php?tipo=professor');
}

$profId = $_SESSION['professor_id'];
$stmtProf = $pdo->prepare("SELECT * FROM professores WHERE id = ?");
$stmtProf->execute([$profId]);
$professor = $stmtProf->fetch();

$mensagem    = '';
$tipoMensagem = '';
$modalAbrir  = ''; // Qual modal abrir ao recarregar

// ============================================
// PROCESSAR AÇÕES POST
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();
    $action = $_POST['action'] ?? '';

    // ------------------------------------------
    // CRIAR NOVO JOGO
    // ------------------------------------------
    if ($action === 'criar') {
        $nome             = limpar($_POST['nome'] ?? '');
        $descricao        = limpar($_POST['descricao'] ?? '');
        $dataInicio       = limpar($_POST['data_inicio'] ?? '');
        $dataFim          = limpar($_POST['data_fim'] ?? '');
        $local            = limpar($_POST['local'] ?? '');
        $maxParticipantes = (int)($_POST['max_participantes'] ?? 30);
        $status           = limpar($_POST['status'] ?? 'aberto');
        $icone            = limpar($_POST['icone'] ?? '');

        // Valores padrao: o professor pode criar um evento "na hora",
        // informando apenas o nome da modalidade e o numero de participantes.
        if ($dataInicio === '') { $dataInicio = date('Y-m-d'); }
        if ($dataFim === '')    { $dataFim    = $dataInicio; }
        if ($local === '')      { $local      = 'A definir'; }
        if ($icone === '')      { $icone      = 'esporte'; }

        $statusValidos = ['aberto', 'fechado', 'em_andamento', 'finalizado'];
        if (!in_array($status, $statusValidos, true)) { $status = 'aberto'; }

        if ($nome === '') {
            $mensagem     = 'Informe o nome da modalidade!';
            $tipoMensagem = 'erro';
            $modalAbrir   = 'criar';
        } elseif (!validarData($dataInicio) || !validarData($dataFim)) {
            $mensagem     = 'Datas invalidas! Use o formato dia/mes/ano.';
            $tipoMensagem = 'erro';
            $modalAbrir   = 'criar';
        } elseif ($dataFim < $dataInicio) {
            $mensagem     = 'A data de termino nao pode ser anterior a data de inicio!';
            $tipoMensagem = 'erro';
            $modalAbrir   = 'criar';
        } elseif ($maxParticipantes < 2 || $maxParticipantes > 500) {
            $mensagem     = 'O numero de participantes deve ser entre 2 e 500!';
            $tipoMensagem = 'erro';
            $modalAbrir   = 'criar';
        } else {
            try {
                $pdo->beginTransaction();

                $stmtJogo = $pdo->prepare("
                    INSERT INTO jogos
                        (nome, descricao, data_inicio, data_fim, local, max_participantes, status, icone, criado_por)
                    VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtJogo->execute([
                    $nome, $descricao, $dataInicio, $dataFim,
                    $local, $maxParticipantes, $status, $icone, $profId
                ]);
                $novoJogoId = (int)$pdo->lastInsertId();

                // Cria o placar inicial do evento
                $stmtPlacar = $pdo->prepare("
                    INSERT INTO placar (jogo_id, time_a, time_b, placar_a, placar_b, status, professor_id)
                    VALUES (?, 'Time A', 'Time B', 0, 0, 'aguardando', ?)
                ");
                $stmtPlacar->execute([$novoJogoId, $profId]);

                $pdo->commit();

                $mensagem     = "Evento criado com sucesso!";
                $tipoMensagem = 'sucesso';

            } catch (Exception $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $mensagem     = 'Não foi possível criar o evento. Tente novamente.';
                $tipoMensagem = 'erro';
                $modalAbrir   = 'criar';
            }
        }
    }

    // ------------------------------------------
    // EDITAR JOGO EXISTENTE
    // ------------------------------------------
    elseif ($action === 'editar') {
        $jogoId          = (int)($_POST['jogo_id'] ?? 0);
        $nome            = limpar($_POST['nome'] ?? '');
        $descricao       = limpar($_POST['descricao'] ?? '');
        $dataInicio      = limpar($_POST['data_inicio'] ?? '');
        $dataFim         = limpar($_POST['data_fim'] ?? '');
        $local           = limpar($_POST['local'] ?? '');
        $maxParticipantes = (int)($_POST['max_participantes'] ?? 30);
        $status          = limpar($_POST['status'] ?? 'aberto');
        $icone           = limpar($_POST['icone'] ?? '');

        if (!in_array($status, ['aberto','fechado','em_andamento','finalizado'], true)) $status = 'aberto';
        $icone = mb_substr($icone, 0, 16);
        if (!$jogoId || empty($nome) || empty($dataInicio) || empty($dataFim) || empty($local)) {
            $mensagem     = 'Preencha todos os campos obrigatórios!';
            $tipoMensagem = 'erro';
        } else {
            {
                $stmtEdit = $pdo->prepare("
                    UPDATE jogos SET
                        nome = ?, descricao = ?, data_inicio = ?, data_fim = ?,
                        local = ?, max_participantes = ?, status = ?, icone = ?
                    WHERE id = ?
                ");
                if ($stmtEdit->execute([
                    $nome, $descricao, $dataInicio, $dataFim,
                    $local, $maxParticipantes, $status, $icone, $jogoId
                ])) {
                    $mensagem     = "Modalidade atualizada com sucesso!";
                    $tipoMensagem = 'sucesso';
                } else {
                    $mensagem     = 'Erro ao atualizar modalidade!';
                    $tipoMensagem = 'erro';
                }
            }
        }
    }

    // ------------------------------------------
    // EXCLUIR JOGO
    // ------------------------------------------
    elseif ($action === 'excluir') {
        $jogoId = (int)($_POST['jogo_id'] ?? 0);

        if ($jogoId) {
            // Verificar se tem inscrições ativas
            $stmtVerifica = $pdo->prepare(
                "SELECT COUNT(*) FROM inscricoes WHERE jogo_id = ? AND status != 'cancelada'"
            );
            $stmtVerifica->execute([$jogoId]);
            $qtdInscritos = (int)$stmtVerifica->fetchColumn();

            if ($qtdInscritos > 0) {
                $mensagem     = "Não é possível excluir: existem {$qtdInscritos} inscrição(ões) ativa(s) nesta modalidade!";
                $tipoMensagem = 'erro';
            } else {
                try {
                    $pdo->beginTransaction();
                    // Remove placar primeiro (FK)
                    $pdo->prepare("DELETE FROM placar WHERE jogo_id = ?")->execute([$jogoId]);
                    // Remove inscrições canceladas
                    $pdo->prepare("DELETE FROM inscricoes WHERE jogo_id = ?")->execute([$jogoId]);
                    // Remove o jogo
                    $pdo->prepare("DELETE FROM jogos WHERE id = ?")->execute([$jogoId]);
                    $pdo->commit();

                    $mensagem     = 'Modalidade excluída com sucesso!';
                    $tipoMensagem = 'sucesso';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $mensagem     = 'Erro ao excluir modalidade!';
                    $tipoMensagem = 'erro';
                }
            }
        }
    }

    // ------------------------------------------
    // ALTERNAR STATUS DO JOGO
    // ------------------------------------------
    elseif ($action === 'toggle_status') {
        $jogoId    = (int)($_POST['jogo_id'] ?? 0);
        $novoStatus = limpar($_POST['novo_status'] ?? 'aberto');

        $statusValidos = ['aberto', 'fechado', 'em_andamento', 'finalizado'];
        if ($jogoId && in_array($novoStatus, $statusValidos)) {
            $pdo->prepare("UPDATE jogos SET status = ? WHERE id = ?")->execute([$novoStatus, $jogoId]);
            $mensagem     = 'Status atualizado!';
            $tipoMensagem = 'sucesso';
        }
    }
}

// ============================================
// BUSCAR TODOS OS JOGOS COM ESTATÍSTICAS
// ============================================
$jogos = $pdo->query("
    SELECT 
        j.*,
        COALESCE(j.icone, 'esporte') as icone,
        COALESCE(i.inscritos_confirmados, 0) as inscritos_confirmados,
        COALESCE(i.inscritos_total, 0) as inscritos_total,
        COALESCE(i.cancelados, 0) as cancelados,
        p.placar_a,
        p.placar_b,
        p.time_a,
        p.time_b,
        p.status as placar_status
    FROM jogos j
    LEFT JOIN (
        SELECT
            jogo_id,
            SUM(status = 'confirmada') as inscritos_confirmados,
            SUM(status != 'cancelada') as inscritos_total,
            SUM(status = 'cancelada') as cancelados
        FROM inscricoes
        GROUP BY jogo_id
    ) i ON j.id = i.jogo_id
    LEFT JOIN placar p ON j.id = p.jogo_id
    ORDER BY j.id DESC
")->fetchAll();

// Ícones disponíveis para seleção
$iconesDisponiveis = [
    '⚽' => 'Bola de Futebol',
    '🏀' => 'Basquete',
    '🏐' => 'Vôlei',
    '🎾' => 'Tênis',
    '🏈' => 'Futebol Americano',
    '⚾' => 'Baseball',
    '🏉' => 'Rugby',
    '🎱' => 'Bilhar',
    '🏓' => 'Ping-Pong',
    '🏸' => 'Badminton',
    '🥊' => 'Boxe',
    '🤼' => 'Luta',
    '🤾' => 'Handebol',
    '🏊' => 'Natação',
    '🚴' => 'Ciclismo',
    '🏃' => 'Corrida',
    '🤸' => 'Ginástica',
    '⛹️' => 'Basketball Alt',
    '🏋️' => 'Levantamento',
    '🤺' => 'Esgrima',
    '🏇' => 'Hipismo',
    '🧗' => 'Escalada',
    '🏌️' => 'Golf',
    '🏖️' => 'Praia',
    '🥋' => 'Artes Marciais',
    '🎿' => 'Esqui',
    '🏒' => 'Hockey',
    '🏑' => 'Campo Hockey',
    '🥍' => 'Lacrosse',
    '🏆' => 'Troféu',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Evento / Modalidades - Planer SESI</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
    <style>
        /* ============================================
           ESTILOS ESPECÍFICOS DESTA PÁGINA
           ============================================ */

        /* CARDS DE JOGO */
        .jogos-manager-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 22px;
        }

        .jogo-manager-card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            position: relative;
        }

        .jogo-manager-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 35px rgba(204,0,0,0.15);
            border-color: #CC0000;
        }

        .jogo-manager-card.status-fechado {
            opacity: 0.8;
            border-color: #e0e0e0;
        }

        .jogo-manager-card.status-finalizado {
            opacity: 0.7;
        }

        .card-header-jogo {
            background: linear-gradient(135deg, #990000, #CC0000);
            padding: 22px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
        }

        .card-icone-grande {
            font-size: 2.8rem;
            line-height: 1;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        .card-header-info {
            flex: 1;
        }

        .card-header-nome {
            font-size: 1.2rem;
            font-weight: 800;
            color: white;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .card-header-status {
            display: inline-block;
        }

        .card-badge-status {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .badge-aberto       { background: rgba(76,175,80,0.25); color: #b9f6ca; border: 1px solid rgba(76,175,80,0.4); }
        .badge-fechado      { background: rgba(244,67,54,0.25); color: #ffcdd2; border: 1px solid rgba(244,67,54,0.4); }
        .badge-em_andamento { background: rgba(255,152,0,0.3);  color: #ffe0b2; border: 1px solid rgba(255,152,0,0.4); }
        .badge-finalizado   { background: rgba(158,158,158,0.3); color: #f5f5f5; border: 1px solid rgba(158,158,158,0.4); }

        .card-criado-por {
            position: absolute;
            top: 10px;
            right: 12px;
            font-size: 0.68rem;
            color: rgba(255,255,255,0.55);
            background: rgba(0,0,0,0.2);
            padding: 2px 8px;
            border-radius: 8px;
        }

        .card-body-jogo {
            padding: 18px 20px;
        }

        .card-stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }

        .card-stat {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            border: 1px solid #f0f0f0;
        }

        .card-stat-valor {
            font-size: 1.5rem;
            font-weight: 900;
            color: #CC0000;
            line-height: 1;
        }

        .card-stat-label {
            font-size: 0.68rem;
            color: #888;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 7px;
            font-size: 0.87rem;
        }

        .card-info-item .info-icon {
            width: 20px;
            text-align: center;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        .card-info-item .info-label {
            color: #888;
            min-width: 60px;
            font-size: 0.8rem;
        }

        .card-info-item .info-valor {
            color: #333;
            font-weight: 600;
            font-size: 0.85rem;
        }

        /* Barra de vagas */
        .vagas-container {
            margin: 12px 0;
        }

        .vagas-header {
            display: flex;
            justify-content: space-between;
            font-size: 0.78rem;
            margin-bottom: 5px;
        }

        .vagas-header .vagas-label { color: #888; }
        .vagas-header .vagas-count { font-weight: 700; color: #333; }

        .vagas-barra {
            background: #f0f0f0;
            border-radius: 10px;
            height: 7px;
            overflow: hidden;
        }

        .vagas-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 0.8s ease;
        }

        /* Ações do card */
        .card-acoes {
            padding: 14px 20px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
            background: #fafafa;
        }

        .btn-card-acao {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 7px 14px;
            border-radius: 7px;
            border: none;
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-editar {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #90caf9;
        }
        .btn-editar:hover { background: #1565c0; color: white; }

        .btn-excluir {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        .btn-excluir:hover { background: #c62828; color: white; }

        .btn-status {
            background: #f3e5f5;
            color: #6a1b9a;
            border: 1px solid #ce93d8;
        }
        .btn-status:hover { background: #6a1b9a; color: white; }

        .btn-inscritos {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
            text-decoration: none;
        }
        .btn-inscritos:hover { background: #2e7d32; color: white; }

        /* CARD "ADICIONAR NOVO" */
        .card-adicionar {
            background: white;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            overflow: hidden;
            border: 2px dashed #CC0000;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 280px;
            text-decoration: none;
            color: inherit;
        }

        .card-adicionar:hover {
            background: #fff5f5;
            transform: translateY(-4px);
            box-shadow: 0 10px 35px rgba(204,0,0,0.15);
            border-style: solid;
        }

        .card-adicionar-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #CC0000, #990000);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(204,0,0,0.3);
            transition: all 0.3s;
        }

        .card-adicionar:hover .card-adicionar-icon {
            transform: scale(1.1) rotate(10deg);
        }

        .card-adicionar h3 {
            font-size: 1.1rem;
            font-weight: 800;
            color: #CC0000;
            margin-bottom: 8px;
        }

        .card-adicionar p {
            font-size: 0.85rem;
            color: #888;
            text-align: center;
            max-width: 200px;
            line-height: 1.4;
        }

        /* ============================================
           MODAIS
           ============================================ */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.65);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
            backdrop-filter: blur(3px);
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 640px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalEntrar 0.3s ease;
        }

        @keyframes modalEntrar {
            from { opacity: 0; transform: scale(0.92) translateY(-20px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-header {
            background: linear-gradient(135deg, #990000, #CC0000);
            padding: 22px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .modal-header h3 {
            color: white;
            font-size: 1.15rem;
            font-weight: 800;
        }

        .modal-fechar {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 1.1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            line-height: 1;
        }

        .modal-fechar:hover {
            background: rgba(255,255,255,0.35);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 28px;
        }

        /* Seletor de ícones */
        .icone-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(52px, 1fr));
            gap: 8px;
            max-height: 200px;
            overflow-y: auto;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px;
            background: #fafafa;
        }

        .icone-opcao {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            border: 2px solid transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            transition: all 0.2s;
            background: white;
            position: relative;
        }

        .icone-opcao:hover {
            background: #fff5f5;
            border-color: #CC0000;
            transform: scale(1.12);
        }

        .icone-opcao.selecionado {
            background: #fff5f5;
            border-color: #CC0000;
            box-shadow: 0 0 0 3px rgba(204,0,0,0.15);
            transform: scale(1.08);
        }

        .icone-opcao.selecionado::after {
            content: '✓';
            position: absolute;
            bottom: -3px;
            right: -3px;
            background: #CC0000;
            color: white;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            font-size: 0.55rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icone-preview-container {
            display: flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, #990000, #CC0000);
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .icone-preview-emoji {
            font-size: 2.5rem;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        .icone-preview-texto {
            color: white;
        }

        .icone-preview-texto strong {
            display: block;
            font-size: 1rem;
            font-weight: 800;
        }

        .icone-preview-texto span {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        /* Modal de confirmação de exclusão */
        .modal-confirmar {
            max-width: 420px;
        }

        .confirmar-icone {
            font-size: 3.5rem;
            text-align: center;
            display: block;
            margin-bottom: 15px;
        }

        /* Status selector */
        .status-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .status-opcao {
            padding: 12px 15px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
            background: white;
        }

        .status-opcao:hover { border-color: #CC0000; }
        .status-opcao.ativo { border-color: #CC0000; background: #fff5f5; }

        .status-opcao input[type="radio"] { display: none; }

        .status-opcao .status-emoji { font-size: 1.5rem; display: block; margin-bottom: 4px; }
        .status-opcao .status-text  { font-size: 0.82rem; font-weight: 700; }

        /* Filtros */
        .filtros-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filtro-btn {
            padding: 8px 18px;
            border-radius: 20px;
            border: 2px solid #e0e0e0;
            background: white;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
            color: #555;
        }

        .filtro-btn:hover { border-color: #CC0000; color: #CC0000; }

        .filtro-btn.ativo {
            background: #CC0000;
            border-color: #CC0000;
            color: white;
        }

        /* Responsivo */
        @media (max-width: 768px) {
            .jogos-manager-grid { grid-template-columns: 1fr; }
            .modal-box { max-width: 100%; margin: 10px; }
            .card-stats-row { grid-template-columns: repeat(3, 1fr); }
            .status-grid { grid-template-columns: repeat(2, 1fr); }
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
            <a href="<?= BASE_URL ?>/index.php" class="nav-link">Site</a>
            <a href="<?= BASE_URL ?>/professor/dashboard.php" class="nav-link">Dashboard</a>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-link nav-logout">Sair</a>
        </nav>
        <button class="menu-mobile"
                onclick="document.getElementById('navMobile').classList.toggle('active')">☰</button>
    </div>
</header>
<div class="nav-mobile" id="navMobile">
    <a href="<?= BASE_URL ?>/index.php">🌐 Site</a>
    <a href="<?= BASE_URL ?>/professor/dashboard.php">📊 Dashboard</a>
    <a href="<?= BASE_URL ?>/auth/logout.php">🚪 Sair</a>
</div>

<!-- ============================================
     LAYOUT COM SIDEBAR
     ============================================ -->
<div class="dashboard-layout">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="sidebar-avatar">👨‍🏫</div>
            <div class="sidebar-name"><?= htmlspecialchars($professor['nome_completo']) ?></div>
            <div class="sidebar-role">Professor</div>
        </div>
        <nav class="sidebar-menu">
            <div class="sidebar-section">Gestão</div>
            <a href="<?= BASE_URL ?>/professor/dashboard.php" class="sidebar-link">
                <span class="link-icon">📊</span> Dashboard
            </a>
            <a href="<?= BASE_URL ?>/professor/gerenciar_inscricoes.php" class="sidebar-link">
                <span class="link-icon">📋</span> Inscrições
            </a>
            <a href="<?= BASE_URL ?>/professor/gerenciar_jogos.php" class="sidebar-link active">
                <span class="link-icon">🏅</span> Modalidades
            </a>
            <a href="<?= BASE_URL ?>/professor/placar.php" class="sidebar-link">
                <span class="link-icon">🏆</span> Placar
            </a>
            <a href="<?= BASE_URL ?>/professor/relatorio.php" class="sidebar-link">
                <span class="link-icon">📈</span> Relatórios
            </a>
            <a href="<?= BASE_URL ?>/professor/exportar_pdf.php" class="sidebar-link">
                <span class="link-icon">📄</span> Exportar PDF
            </a>
            <div class="sidebar-section">Sistema</div>
            <a href="<?= BASE_URL ?>/index.php" class="sidebar-link">
                <span class="link-icon">🌐</span> Site
            </a>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="sidebar-link">
                <span class="link-icon">🚪</span> Sair
            </a>
        </nav>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="dashboard-content">

        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>🏅 Gerenciar Modalidades</h1>
                <p class="breadcrumb">
                    <a href="<?= BASE_URL ?>/professor/dashboard.php">Dashboard</a>
                    › Modalidades de Jogos
                </p>
            </div>
            <button class="btn btn-primary btn-lg" id="btnCriarEvento" onclick="abrirModalCriar()">
                ➕ Criar Evento
            </button>
        </div>

        <!-- Alertas -->
        <?php if ($mensagem): ?>
            <?= alerta($tipoMensagem, $mensagem) ?>
        <?php endif; ?>

        <!-- Cards Resumo -->
        <div class="dashboard-cards" style="grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));">
            <?php
            $totalJogos     = count($jogos);
            $abertos        = count(array_filter($jogos, fn($j) => $j['status'] === 'aberto'));
            $emAndamento    = count(array_filter($jogos, fn($j) => $j['status'] === 'em_andamento'));
            $finalizados    = count(array_filter($jogos, fn($j) => $j['status'] === 'finalizado'));
            $totalInscritos = array_sum(array_column($jogos, 'inscritos_total'));
            ?>
            <div class="dash-card">
                <div class="dash-card-icon">🏅</div>
                <div class="dash-card-value"><?= $totalJogos ?></div>
                <div class="dash-card-label">Total de Modalidades</div>
            </div>
            <div class="dash-card">
                <div class="dash-card-icon">🟢</div>
                <div class="dash-card-value"><?= $abertos ?></div>
                <div class="dash-card-label">Abertas</div>
            </div>
            <div class="dash-card">
                <div class="dash-card-icon">▶️</div>
                <div class="dash-card-value"><?= $emAndamento ?></div>
                <div class="dash-card-label">Em Andamento</div>
            </div>
            <div class="dash-card">
                <div class="dash-card-icon">✅</div>
                <div class="dash-card-value"><?= $finalizados ?></div>
                <div class="dash-card-label">Finalizadas</div>
            </div>
            <div class="dash-card">
                <div class="dash-card-icon">👥</div>
                <div class="dash-card-value"><?= $totalInscritos ?></div>
                <div class="dash-card-label">Total de Inscritos</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros-bar">
            <span style="font-size: 0.85rem; color: #888; font-weight: 600;">Filtrar:</span>
            <button class="filtro-btn ativo" onclick="filtrar('todos', this)">
                Todos (<?= $totalJogos ?>)
            </button>
            <button class="filtro-btn" onclick="filtrar('aberto', this)">
                🟢 Abertos (<?= $abertos ?>)
            </button>
            <button class="filtro-btn" onclick="filtrar('em_andamento', this)">
                ▶️ Em Andamento (<?= $emAndamento ?>)
            </button>
            <button class="filtro-btn" onclick="filtrar('finalizado', this)">
                ✅ Finalizados (<?= $finalizados ?>)
            </button>
            <button class="filtro-btn" onclick="filtrar('fechado', this)">
                🔴 Fechados
            </button>
        </div>

        <!-- Grid de Cards -->
        <div class="jogos-manager-grid" id="jogosGrid">

            <!-- Card Adicionar Novo -->
            <button class="card-adicionar" onclick="abrirModalCriar()" 
                    data-status="adicionar" style="border: none; font-family: inherit;">
                <div class="card-adicionar-icon">➕</div>
                <h3>Criar Evento</h3>
                <p>Clique para criar um novo evento: escolha a modalidade e quantas pessoas participam</p>
            </button>

            <!-- Cards dos Jogos Existentes -->
            <?php foreach ($jogos as $jogo): ?>
            <?php
                $pct = $jogo['max_participantes'] > 0
                    ? round(($jogo['inscritos_total'] / $jogo['max_participantes']) * 100)
                    : 0;
                $corBarra = $pct >= 90 ? '#e53935' : ($pct >= 70 ? '#fb8c00' : '#43a047');
                $iconeJogo = !empty($jogo['icone']) ? $jogo['icone'] : getIconeJogo($jogo['nome']);
                $vagasRestantes = max(0, $jogo['max_participantes'] - $jogo['inscritos_total']);

                $statusLabels = [
                    'aberto'       => ['badge-aberto',       '🟢 Aberto'],
                    'fechado'      => ['badge-fechado',       '🔴 Fechado'],
                    'em_andamento' => ['badge-em_andamento',  '▶️ Ao Vivo'],
                    'finalizado'   => ['badge-finalizado',    '✅ Finalizado'],
                ];
                [$badgeClass, $statusTexto] = $statusLabels[$jogo['status']] ?? ['badge-fechado', $jogo['status']];
            ?>
            <div class="jogo-manager-card status-<?= $jogo['status'] ?>"
                 data-status="<?= $jogo['status'] ?>"
                 id="card-jogo-<?= $jogo['id'] ?>">

                <!-- Header do Card -->
                <div class="card-header-jogo">
                    <div class="card-icone-grande"><?= $iconeJogo ?></div>
                    <div class="card-header-info">
                        <div class="card-header-nome"><?= htmlspecialchars($jogo['nome']) ?></div>
                        <div class="card-header-status">
                            <span class="card-badge-status <?= $badgeClass ?>"><?= $statusTexto ?></span>
                        </div>
                    </div>
                    <?php if (!empty($jogo['criado_por']) && $jogo['criado_por'] == $profId): ?>
                        <span class="card-criado-por">📝 Criado por você</span>
                    <?php endif; ?>
                </div>

                <!-- Body do Card -->
                <div class="card-body-jogo">

                    <!-- Stats rápidas -->
                    <div class="card-stats-row">
                        <div class="card-stat">
                            <div class="card-stat-valor"><?= $jogo['inscritos_confirmados'] ?></div>
                            <div class="card-stat-label">Confirmados</div>
                        </div>
                        <div class="card-stat">
                            <div class="card-stat-valor"><?= $vagasRestantes ?></div>
                            <div class="card-stat-label">Vagas</div>
                        </div>
                        <div class="card-stat">
                            <div class="card-stat-valor"><?= $pct ?>%</div>
                            <div class="card-stat-label">Ocupação</div>
                        </div>
                    </div>

                    <!-- Barra de vagas -->
                    <div class="vagas-container">
                        <div class="vagas-header">
                            <span class="vagas-label">Inscrições</span>
                            <span class="vagas-count"><?= $jogo['inscritos_total'] ?> / <?= $jogo['max_participantes'] ?></span>
                        </div>
                        <div class="vagas-barra">
                            <div class="vagas-fill"
                                 style="width: <?= min(100, $pct) ?>%; background: <?= $corBarra ?>;"></div>
                        </div>
                    </div>

                    <!-- Informações -->
                    <div class="card-info-item">
                        <span class="info-icon">📅</span>
                        <span class="info-label">Período</span>
                        <span class="info-valor">
                            <?= date('d/m', strtotime($jogo['data_inicio'])) ?>
                            –
                            <?= date('d/m/Y', strtotime($jogo['data_fim'])) ?>
                        </span>
                    </div>
                    <div class="card-info-item">
                        <span class="info-icon">📍</span>
                        <span class="info-label">Local</span>
                        <span class="info-valor"><?= htmlspecialchars($jogo['local']) ?></span>
                    </div>
                    <?php if (!empty($jogo['descricao'])): ?>
                    <div class="card-info-item" style="align-items: flex-start;">
                        <span class="info-icon">📝</span>
                        <span class="info-label">Obs.</span>
                        <span class="info-valor" style="font-weight: 400; color: #666; font-size: 0.8rem;">
                            <?= htmlspecialchars(mb_substr($jogo['descricao'], 0, 60)) ?>
                            <?= mb_strlen($jogo['descricao']) > 60 ? '...' : '' ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <!-- Mini Placar -->
                    <div style="background: #f9f9f9; border-radius: 8px; padding: 10px 14px; margin-top: 12px; 
                                display: flex; justify-content: space-between; align-items: center; border: 1px solid #eee;">
                        <div style="text-align: center;">
                            <div style="font-size: 0.72rem; color: #888;"><?= htmlspecialchars($jogo['time_a'] ?? 'Time A') ?></div>
                            <div style="font-size: 1.6rem; font-weight: 900; color: #CC0000; line-height: 1.2;"><?= (int)($jogo['placar_a'] ?? 0) ?></div>
                        </div>
                        <div style="font-size: 0.85rem; color: #aaa; font-weight: 700;">×</div>
                        <div style="text-align: center;">
                            <div style="font-size: 0.72rem; color: #888;"><?= htmlspecialchars($jogo['time_b'] ?? 'Time B') ?></div>
                            <div style="font-size: 1.6rem; font-weight: 900; color: #CC0000; line-height: 1.2;"><?= (int)($jogo['placar_b'] ?? 0) ?></div>
                        </div>
                        <a href="<?= BASE_URL ?>/professor/placar.php"
                           style="font-size: 0.72rem; color: #CC0000; text-decoration: none; font-weight: 600;
                                  background: #fff5f5; padding: 4px 8px; border-radius: 6px; border: 1px solid #ffcdd2;">
                            ✏️ Editar
                        </a>
                    </div>
                </div>

                <!-- Ações -->
                <div class="card-acoes">
                    <!-- Editar -->
                    <button class="btn-card-acao btn-editar"
                            onclick="abrirModalEditar(
                                <?= $jogo['id'] ?>,
                                '<?= addslashes(htmlspecialchars($jogo['nome'])) ?>',
                                '<?= addslashes(htmlspecialchars($jogo['descricao'] ?? '')) ?>',
                                '<?= $jogo['data_inicio'] ?>',
                                '<?= $jogo['data_fim'] ?>',
                                '<?= addslashes(htmlspecialchars($jogo['local'])) ?>',
                                <?= $jogo['max_participantes'] ?>,
                                '<?= $jogo['status'] ?>',
                                '<?= $iconeJogo ?>'
                            )">
                        ✏️ Editar
                    </button>

                    <!-- Ver Inscritos -->
                    <a href="<?= BASE_URL ?>/professor/gerenciar_inscricoes.php?jogo=<?= $jogo['id'] ?>"
                       class="btn-card-acao btn-inscritos">
                        👥 <?= $jogo['inscritos_total'] ?> Inscritos
                    </a>

                    <!-- Alterar Status -->
                    <button class="btn-card-acao btn-status"
                            onclick="abrirModalStatus(<?= $jogo['id'] ?>, '<?= $jogo['nome'] ?>', '<?= $jogo['status'] ?>')">
                        🔄 Status
                    </button>

                    <!-- Excluir -->
                    <button class="btn-card-acao btn-excluir" style="margin-left: auto;"
                            onclick="abrirModalExcluir(<?= $jogo['id'] ?>, '<?= addslashes($jogo['nome']) ?>', <?= $jogo['inscritos_total'] ?>)">
                        🗑️
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div><!-- /jogosGrid -->

        <?php if (empty($jogos)): ?>
        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 14px; margin-top: 20px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.06);">
            <div style="font-size: 4rem; margin-bottom: 15px;">🏅</div>
            <h3 style="margin-bottom: 10px; color: #333;">Nenhuma modalidade cadastrada</h3>
            <p style="color: #888; margin-bottom: 25px;">
                Crie a primeira modalidade para os Jogos Escolares SESI!
            </p>
            <button class="btn btn-primary btn-lg" onclick="abrirModalCriar()">
                ➕ Criar Evento
            </button>
        </div>
        <?php endif; ?>

    </main><!-- /dashboard-content -->
</div><!-- /dashboard-layout -->


<!-- ============================================
     MODAL: CRIAR NOVA MODALIDADE
     ============================================ -->
<div class="modal-overlay" id="modalCriar">
    <div class="modal-box">
        <div class="modal-header">
            <h3>➕ Criar Evento</h3>
            <button class="modal-fechar" onclick="fecharModal('modalCriar')">✕</button>
        </div>
        <div class="modal-body">
            <form method="POST" id="formCriar" onsubmit="return validarFormJogo('formCriar')">
                    <?= csrfField() ?>
                <input type="hidden" name="action" value="criar">

                <!-- Preview do ícone -->
                <div class="icone-preview-container" id="previewCriar">
                    <div class="icone-preview-emoji" id="previewIconeCriar">🏆</div>
                    <div class="icone-preview-texto">
                        <strong id="previewNomeCriar">Nome da Modalidade</strong>
                        <span>Jogos Escolares SESI 2025</span>
                    </div>
                </div>

                <!-- Campo oculto do ícone selecionado -->
                <input type="hidden" name="icone" id="iconeSelecionadoCriar" value="🏆">

                <!-- Seletor de Ícone -->
                <div class="form-group">
                    <label class="form-label">Ícone da Modalidade <span>*</span></label>
                    <div class="icone-grid" id="iconeGridCriar">
                        <?php foreach ($iconesDisponiveis as $emoji => $titulo): ?>
                        <div class="icone-opcao <?= $emoji === '🏆' ? 'selecionado' : '' ?>"
                             data-emoji="<?= htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8') ?>"
                             title="<?= htmlspecialchars($titulo) ?>"
                             onclick="selecionarIcone('<?= $emoji ?>', 'Criar')">
                            <?= $emoji ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Nome -->
                <div class="form-group">
                    <label class="form-label">Nome da Modalidade <span>*</span></label>
                    <input type="text" name="nome" id="nomeCriar" class="form-control"
                           placeholder="Ex: Atletismo, Natação, Xadrez..."
                           maxlength="100" required
                           oninput="document.getElementById('previewNomeCriar').textContent = this.value || 'Nome da Modalidade'">
                    <div class="form-hint">Máximo 100 caracteres</div>
                </div>

                <!-- Descrição -->
                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <textarea name="descricao" class="form-control" rows="2"
                              placeholder="Descrição breve da modalidade..."
                              maxlength="500" style="resize: vertical;"></textarea>
                </div>

                <!-- Datas -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Data de Início</label>
                        <input type="date" name="data_inicio" id="dataInicioCriar" class="form-control"
                               value="<?= date('Y-m-d') ?>"
                               onchange="atualizarDataMinFim('dataInicioCriar', 'dataFimCriar')">
                        <div class="form-hint">Deixe como está para começar hoje</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data de Término</label>
                        <input type="date" name="data_fim" id="dataFimCriar" class="form-control"
                               value="<?= date('Y-m-d') ?>">
                        <div class="form-hint">Opcional — se vazio, usa a data de início</div>
                    </div>
                </div>

                <!-- Local -->
                <div class="form-group">
                    <label class="form-label">Local</label>
                    <input type="text" name="local" class="form-control"
                           placeholder="Ex: Quadra Poliesportiva SESI, Ginásio..."
                           maxlength="255">
                    <div class="form-hint">Opcional — se vazio, fica como "A definir"</div>
                </div>

                <!-- Vagas e Status -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Quantas pessoas vão participar? <span>*</span></label>
                        <input type="number" name="max_participantes" class="form-control"
                               value="30" min="2" max="500" required>
                        <div class="form-hint">Entre 2 e 500 participantes</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status Inicial</label>
                        <select name="status" class="form-control">
                            <option value="aberto">Aberto para Inscrições</option>
                            <option value="fechado">Fechado</option>
                            <option value="em_andamento">Em Andamento</option>
                        </select>
                    </div>
                </div>

                <!-- Botões -->
                <div style="display: flex; gap: 12px; margin-top: 10px;">
                    <button type="submit" class="btn btn-primary btn-lg" style="flex: 1;">
                        ➕ Criar Evento
                    </button>
                    <button type="button" class="btn btn-secondary btn-lg"
                            onclick="fecharModal('modalCriar')">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ============================================
     MODAL: EDITAR MODALIDADE
     ============================================ -->
<div class="modal-overlay" id="modalEditar">
    <div class="modal-box">
        <div class="modal-header">
            <h3>✏️ Editar Modalidade</h3>
            <button class="modal-fechar" onclick="fecharModal('modalEditar')">✕</button>
        </div>
        <div class="modal-body">
            <form method="POST" id="formEditar" onsubmit="return validarFormJogo('formEditar')">
                    <?= csrfField() ?>
                <input type="hidden" name="action" value="editar">
                <input type="hidden" name="jogo_id" id="editarJogoId">
                <input type="hidden" name="icone" id="iconeSelecionadoEditar">

                <!-- Preview -->
                <div class="icone-preview-container" id="previewEditar">
                    <div class="icone-preview-emoji" id="previewIconeEditar">🏆</div>
                    <div class="icone-preview-texto">
                        <strong id="previewNomeEditar">Modalidade</strong>
                        <span>Editando modalidade</span>
                    </div>
                </div>

                <!-- Seletor de Ícone -->
                <div class="form-group">
                    <label class="form-label">Ícone</label>
                    <div class="icone-grid" id="iconeGridEditar">
                        <?php foreach ($iconesDisponiveis as $emoji => $titulo): ?>
                        <div class="icone-opcao"
                             title="<?= htmlspecialchars($titulo) ?>"
                             data-emoji="<?= $emoji ?>"
                             onclick="selecionarIcone('<?= $emoji ?>', 'Editar')">
                            <?= $emoji ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Nome -->
                <div class="form-group">
                    <label class="form-label">Nome <span>*</span></label>
                    <input type="text" name="nome" id="editarNome" class="form-control"
                           maxlength="100" required
                           oninput="document.getElementById('previewNomeEditar').textContent = this.value">
                </div>

                <!-- Descrição -->
                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <textarea name="descricao" id="editarDescricao" class="form-control"
                              rows="2" maxlength="500" style="resize: vertical;"></textarea>
                </div>

                <!-- Datas -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Data de Início <span>*</span></label>
                        <input type="date" name="data_inicio" id="editarDataInicio" class="form-control" required
                               onchange="atualizarDataMinFim('editarDataInicio', 'editarDataFim')">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data de Término <span>*</span></label>
                        <input type="date" name="data_fim" id="editarDataFim" class="form-control" required>
                    </div>
                </div>

                <!-- Local -->
                <div class="form-group">
                    <label class="form-label">Local <span>*</span></label>
                    <input type="text" name="local" id="editarLocal" class="form-control"
                           maxlength="255" required>
                </div>

                <!-- Vagas e Status -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Máximo de Participantes</label>
                        <input type="number" name="max_participantes" id="editarMaxParticipantes"
                               class="form-control" min="2" max="500" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="editarStatus" class="form-control">
                            <option value="aberto">Aberto</option>
                            <option value="fechado">Fechado</option>
                            <option value="em_andamento">Em Andamento</option>
                            <option value="finalizado">Finalizado</option>
                        </select>
                    </div>
                </div>

                <!-- Botões -->
                <div style="display: flex; gap: 12px; margin-top: 10px;">
                    <button type="submit" class="btn btn-primary btn-lg" style="flex: 1;">
                        💾 Salvar Alterações
                    </button>
                    <button type="button" class="btn btn-secondary btn-lg"
                            onclick="fecharModal('modalEditar')">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ============================================
     MODAL: ALTERAR STATUS
     ============================================ -->
<div class="modal-overlay" id="modalStatus">
    <div class="modal-box" style="max-width: 440px;">
        <div class="modal-header">
            <h3>🔄 Alterar Status</h3>
            <button class="modal-fechar" onclick="fecharModal('modalStatus')">✕</button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom: 20px; color: #555;">
                Alterar status de: <strong id="statusJogoNome"></strong>
            </p>
            <form method="POST">
                    <?= csrfField() ?>
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="jogo_id" id="statusJogoId">

                <div class="status-grid">
                    <label class="status-opcao" id="statusOpcaoAberto">
                        <input type="radio" name="novo_status" value="aberto">
                        <span class="status-emoji">🟢</span>
                        <span class="status-text">Aberto</span>
                        <div style="font-size: 0.72rem; color: #888; margin-top: 3px;">Aceita inscrições</div>
                    </label>
                    <label class="status-opcao" id="statusOpcaoFechado">
                        <input type="radio" name="novo_status" value="fechado">
                        <span class="status-emoji">🔴</span>
                        <span class="status-text">Fechado</span>
                        <div style="font-size: 0.72rem; color: #888; margin-top: 3px;">Sem novas inscrições</div>
                    </label>
                    <label class="status-opcao" id="statusOpcaoAndamento">
                        <input type="radio" name="novo_status" value="em_andamento">
                        <span class="status-emoji">▶️</span>
                        <span class="status-text">Em Andamento</span>
                        <div style="font-size: 0.72rem; color: #888; margin-top: 3px;">Jogo acontecendo</div>
                    </label>
                    <label class="status-opcao" id="statusOpcaoFinalizado">
                        <input type="radio" name="novo_status" value="finalizado">
                        <span class="status-emoji">✅</span>
                        <span class="status-text">Finalizado</span>
                        <div style="font-size: 0.72rem; color: #888; margin-top: 3px;">Encerrado</div>
                    </label>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        ✅ Confirmar
                    </button>
                    <button type="button" class="btn btn-secondary"
                            onclick="fecharModal('modalStatus')">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ============================================
     MODAL: CONFIRMAR EXCLUSÃO
     ============================================ -->
<div class="modal-overlay" id="modalExcluir">
    <div class="modal-box modal-confirmar">
        <div class="modal-header" style="background: linear-gradient(135deg, #b71c1c, #e53935);">
            <h3>🗑️ Excluir Modalidade</h3>
            <button class="modal-fechar" onclick="fecharModal('modalExcluir')">✕</button>
        </div>
        <div class="modal-body" style="text-align: center;">
            <span class="confirmar-icone">⚠️</span>
            <h3 style="color: #b71c1c; margin-bottom: 12px;">Tem certeza?</h3>
            <p style="color: #555; margin-bottom: 8px;">
                Você está prestes a excluir a modalidade:
            </p>
            <p style="font-size: 1.1rem; font-weight: 800; color: #333; margin-bottom: 15px;"
               id="excluirJogoNome">—</p>
            <div id="excluirAviso" style="display: none; background: #ffebee; border: 1px solid #ffcdd2;
                  border-radius: 8px; padding: 12px; margin-bottom: 15px; font-size: 0.88rem; color: #b71c1c;">
                ❌ Esta modalidade possui inscrições ativas e <strong>não pode ser excluída</strong>.
                Cancele as inscrições primeiro.
            </div>
            <p style="font-size: 0.85rem; color: #888; margin-bottom: 25px;">
                Esta ação não pode ser desfeita. O placar e as inscrições canceladas também serão removidos.
            </p>
            <form method="POST">
                    <?= csrfField() ?>
                <input type="hidden" name="action" value="excluir">
                <input type="hidden" name="jogo_id" id="excluirJogoId">
                <div style="display: flex; gap: 12px;">
                    <button type="button" class="btn btn-secondary btn-lg" style="flex: 1;"
                            onclick="fecharModal('modalExcluir')">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger btn-lg" style="flex: 1;" id="btnConfirmarExcluir">
                        🗑️ Sim, Excluir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ============================================
     JAVASCRIPT
     ============================================ -->
<script>
// ==========================================
// FUNÇÕES DOS MODAIS
// ==========================================

function abrirModalCriar() {
    // Reset do formulário
    document.getElementById('formCriar').reset();
    document.getElementById('iconeSelecionadoCriar').value = '🏆';
    document.getElementById('previewIconeCriar').innerHTML = window.PlanerIcon ? PlanerIcon('ball') : '';
    document.getElementById('previewNomeCriar').textContent = 'Nome da Modalidade';

    // Reset seleção de ícones
    document.querySelectorAll('#iconeGridCriar .icone-opcao').forEach(el => {
        el.classList.remove('selecionado');
        if (el.dataset.emoji === '🏆') el.classList.add('selecionado');
    });

    // Data mínima = hoje
    const hoje = new Date().toISOString().split('T')[0];
    document.getElementById('dataInicioCriar').min = hoje;
    document.getElementById('dataFimCriar').min = hoje;

    abrirModal('modalCriar');
}

function abrirModalEditar(id, nome, descricao, dataInicio, dataFim, local, maxPart, status, icone) {
    // Preencher campos
    document.getElementById('editarJogoId').value           = id;
    document.getElementById('editarNome').value             = nome;
    document.getElementById('editarDescricao').value        = descricao;
    document.getElementById('editarDataInicio').value       = dataInicio;
    document.getElementById('editarDataFim').value          = dataFim;
    document.getElementById('editarLocal').value            = local;
    document.getElementById('editarMaxParticipantes').value = maxPart;
    document.getElementById('editarStatus').value          = status;
    document.getElementById('iconeSelecionadoEditar').value = icone;

    // Atualizar preview
    document.getElementById('previewIconeEditar').innerHTML = window.PlanerIcon ? PlanerIcon('ball') : '';
    document.getElementById('previewNomeEditar').textContent  = nome;

    // Marcar ícone selecionado
    document.querySelectorAll('#iconeGridEditar .icone-opcao').forEach(el => {
        el.classList.remove('selecionado');
        if (el.dataset.emoji === icone) el.classList.add('selecionado');
    });

    abrirModal('modalEditar');
}

function abrirModalStatus(jogoId, jogoNome, statusAtual) {
    document.getElementById('statusJogoId').value   = jogoId;
    document.getElementById('statusJogoNome').textContent = jogoNome;

    // Marcar status atual visualmente
    const opcoes = ['Aberto', 'Fechado', 'Andamento', 'Finalizado'];
    opcoes.forEach(o => {
        const el = document.getElementById('statusOpcao' + o);
        if (el) el.classList.remove('ativo');
    });

    const mapa = {
        'aberto':       'Aberto',
        'fechado':      'Fechado',
        'em_andamento': 'Andamento',
        'finalizado':   'Finalizado'
    };
    const elAtivo = document.getElementById('statusOpcao' + mapa[statusAtual]);
    if (elAtivo) {
        elAtivo.classList.add('ativo');
        elAtivo.querySelector('input[type="radio"]').checked = true;
    }

    // Feedback visual no clique
    document.querySelectorAll('.status-opcao').forEach(el => {
        el.addEventListener('click', function() {
            document.querySelectorAll('.status-opcao').forEach(e => e.classList.remove('ativo'));
            this.classList.add('ativo');
        });
    });

    abrirModal('modalStatus');
}

function abrirModalExcluir(jogoId, jogoNome, inscritos) {
    document.getElementById('excluirJogoId').value = jogoId;
    document.getElementById('excluirJogoNome').textContent = jogoNome;

    const aviso = document.getElementById('excluirAviso');
    const btnExcluir = document.getElementById('btnConfirmarExcluir');

    if (inscritos > 0) {
        aviso.style.display = 'block';
        btnExcluir.disabled = true;
        btnExcluir.style.opacity = '0.5';
        btnExcluir.style.cursor = 'not-allowed';
    } else {
        aviso.style.display = 'none';
        btnExcluir.disabled = false;
        btnExcluir.style.opacity = '1';
        btnExcluir.style.cursor = 'pointer';
    }

    abrirModal('modalExcluir');
}

function abrirModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function fecharModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}

// Fechar ao clicar no overlay
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) fecharModal(this.id);
    });
});

// Fechar com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => fecharModal(m.id));
    }
});

// ==========================================
// SELEÇÃO DE ÍCONE
// ==========================================
function selecionarIcone(emoji, contexto) {
    const grid    = document.getElementById('iconeGrid' + contexto);
    const input   = document.getElementById('iconeSelecionado' + contexto);
    const preview = document.getElementById('previewIcone' + contexto);

    // Remover seleção anterior
    grid.querySelectorAll('.icone-opcao').forEach(el => el.classList.remove('selecionado'));

    // Marcar nova seleção
    grid.querySelectorAll('.icone-opcao').forEach(el => {
        if (el.dataset.emoji === emoji) el.classList.add('selecionado');
    });

    // Atualizar input e preview
    input.value           = emoji;
    preview.innerHTML = window.PlanerIcon ? PlanerIcon('ball') : '';
}

// ==========================================
// DATAS
// ==========================================
function atualizarDataMinFim(idInicio, idFim) {
    const inicio = document.getElementById(idInicio).value;
    if (inicio) {
        document.getElementById(idFim).min = inicio;
        // Se data fim for anterior ao início, limpar
        const fim = document.getElementById(idFim).value;
        if (fim && fim < inicio) {
            document.getElementById(idFim).value = inicio;
        }
    }
}

// ==========================================
// VALIDAÇÃO DO FORMULÁRIO
// ==========================================
function validarFormJogo(formId) {
    const form = document.getElementById(formId);
    const nome = form.querySelector('[name="nome"]').value.trim();
    const dataInicio = form.querySelector('[name="data_inicio"]').value;
    const dataFim    = form.querySelector('[name="data_fim"]').value;
    const local      = form.querySelector('[name="local"]').value.trim();
    const maxPart    = parseInt(form.querySelector('[name="max_participantes"]').value);

    if (!nome) {
        mostrarErroForm('Informe o nome da modalidade!');
        return false;
    }
    // Na criação, datas e local são opcionais (o servidor preenche padrões).
    const exigeTudo = (formId !== 'formCriar');
    if (exigeTudo && (!dataInicio || !dataFim)) {
        mostrarErroForm('Datas de início e término são obrigatórias!');
        return false;
    }
    if (dataInicio && dataFim && dataFim < dataInicio) {
        mostrarErroForm('A data de término não pode ser anterior à data de início!');
        return false;
    }
    if (exigeTudo && !local) {
        mostrarErroForm('O local é obrigatório!');
        return false;
    }
    if (isNaN(maxPart) || maxPart < 2 || maxPart > 500) {
        mostrarErroForm('Número de participantes deve ser entre 2 e 500!');
        return false;
    }
    return true;
}

function mostrarErroForm(msg) {
    // Remove alerta anterior se existir
    const anterior = document.querySelector('.alerta-form-erro');
    if (anterior) anterior.remove();

    const div = document.createElement('div');
    div.className = 'alerta alerta-erro alerta-form-erro';
    div.style.marginBottom = '15px';
    div.textContent = msg;

    // Inserir no início da modal body ativa
    const modalAtiva = document.querySelector('.modal-overlay.active .modal-body');
    if (modalAtiva) modalAtiva.insertBefore(div, modalAtiva.firstChild);

    setTimeout(() => div.remove(), 4000);
}

// ==========================================
// FILTRO DE CARDS
// ==========================================
function filtrar(status, btn) {
    // Atualizar botões
    document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('ativo'));
    btn.classList.add('ativo');

    // Mostrar/ocultar cards
    const cards = document.querySelectorAll('#jogosGrid [data-status]');
    cards.forEach(card => {
        if (card.dataset.status === 'adicionar') return; // Nunca ocultar o card de adicionar

        if (status === 'todos' || card.dataset.status === status) {
            card.style.display = '';
            card.style.animation = 'fadeInUp 0.3s ease';
        } else {
            card.style.display = 'none';
        }
    });
}

// ==========================================
// ANIMAÇÃO DE ENTRADA DOS CARDS
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.jogo-manager-card');
    cards.forEach((card, i) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = `opacity 0.4s ease ${i * 0.07}s, transform 0.4s ease ${i * 0.07}s`;
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 50);
    });

    // Abrir modal criar se houver erro de criação
    <?php if ($modalAbrir === 'criar'): ?>
    setTimeout(() => abrirModalCriar(), 100);
    <?php endif; ?>
});

// ==========================================
// MENU MOBILE
// ==========================================
document.addEventListener('click', function(e) {
    const nav = document.getElementById('navMobile');
    const btn = document.querySelector('.menu-mobile');
    if (nav && btn && !nav.contains(e.target) && !btn.contains(e.target)) {
        nav.classList.remove('active');
    }
});
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>