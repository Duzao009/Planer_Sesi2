<?php
// ============================================
// INDEX.PHP - PÁGINA PRINCIPAL
// Caminho: C:\xampp\htdocs\DU_Planer_Sesi\index.php
// ============================================

// Definir o caminho raiz do projeto
define('ROOT_PATH', __DIR__);

// Carregar configurações e funções
require_once ROOT_PATH . '/config/conexao.php';
require_once ROOT_PATH . '/includes/functions.php';

$pageTitle = 'Início';

// Buscar dados
$jogos = getJogos($pdo);

// Contagens
$totalInscricoes = $pdo->query("SELECT COUNT(*) FROM inscricoes WHERE status != 'cancelada'")->fetchColumn();
$totalAlunos     = $pdo->query("SELECT COUNT(*) FROM alunos")->fetchColumn();
$totalJogos      = count($jogos);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planer SESI - Jogos Escolares 2025</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #CC0000;
            color: white;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .live-dot {
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            animation: blink 1s infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }
        .cta-section {
            background: linear-gradient(135deg, #CC0000, #990000);
            color: white;
            padding: 60px 0;
            text-align: center;
        }
        .progress-bar {
            background: #f0f0f0;
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
            margin-top: 6px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #CC0000, #FF3333);
            border-radius: 10px;
            transition: width 0.5s ease;
        }
    </style>
    <link rel="icon" href="<?= BASE_URL ?>/favicon.png" type="image/png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modern.css">
</head>
<body>

<!-- HEADER -->
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
            <a href="<?= BASE_URL ?>/index.php" class="nav-link">Início</a>
            <a href="#jogos" class="nav-link">Jogos</a>
            <a href="#placar" class="nav-link">Placar</a>
            
            <?php if (isAlunoLogado()): ?>
                <a href="<?= BASE_URL ?>/aluno/dashboard.php" class="nav-link">Meu Painel</a>
                <a href="<?= BASE_URL ?>/aluno/inscricao.php" class="nav-btn">Inscrever-se</a>
                <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-link nav-logout">Sair</a>
            <?php elseif (isProfessorLogado()): ?>
                <a href="<?= BASE_URL ?>/professor/dashboard.php" class="nav-link">Painel Admin</a>
                <a href="<?= BASE_URL ?>/professor/placar.php" class="nav-link">Placar</a>
                <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-link nav-logout">Sair</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/auth/login.php" class="nav-btn">Entrar</a>
                <a href="<?= BASE_URL ?>/auth/cadastro_aluno.php" class="nav-link">Cadastrar</a>
            <?php endif; ?>
        </nav>
        
        <button class="menu-mobile" onclick="toggleMenu()">☰</button>
    </div>
</header>

<!-- MENU MOBILE -->
<div class="nav-mobile" id="navMobile">
    <a href="<?= BASE_URL ?>/index.php">🏠 Início</a>
    <a href="#jogos">🏅 Jogos</a>
    <a href="#placar">🏆 Placar</a>
    <?php if (isAlunoLogado()): ?>
        <a href="<?= BASE_URL ?>/aluno/dashboard.php">📊 Meu Painel</a>
        <a href="<?= BASE_URL ?>/aluno/inscricao.php">✍️ Inscrever-se</a>
        <a href="<?= BASE_URL ?>/auth/logout.php">🚪 Sair</a>
    <?php elseif (isProfessorLogado()): ?>
        <a href="<?= BASE_URL ?>/professor/dashboard.php">📊 Painel Admin</a>
        <a href="<?= BASE_URL ?>/professor/placar.php">🏆 Placar</a>
        <a href="<?= BASE_URL ?>/auth/logout.php">🚪 Sair</a>
    <?php else: ?>
        <a href="<?= BASE_URL ?>/auth/login.php">🔑 Entrar</a>
        <a href="<?= BASE_URL ?>/auth/cadastro_aluno.php">📝 Cadastrar</a>
    <?php endif; ?>
</div>

<!-- HERO -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <p style="font-size: 0.9rem; opacity: 0.8; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 3px;">
                🏆 Jogos Escolares 2025
            </p>
            <h1>Planer SESI</h1>
            <p>Sistema oficial de gerenciamento dos Jogos Escolares SESI.<br>
               Inscreva-se, acompanhe os resultados e viva a emoção dos jogos!</p>
            
            <div class="hero-btns">
                <?php if (!isAlunoLogado() && !isProfessorLogado()): ?>
                    <a href="<?= BASE_URL ?>/auth/cadastro_aluno.php" class="btn-hero-primary">
                        🎓 Cadastrar como Aluno
                    </a>
                    <a href="<?= BASE_URL ?>/auth/login.php" class="btn-hero-secondary">
                        Já tenho conta →
                    </a>
                <?php elseif (isAlunoLogado()): ?>
                    <a href="<?= BASE_URL ?>/aluno/inscricao.php" class="btn-hero-primary">
                        ✍️ Inscrever-se nos Jogos
                    </a>
                    <a href="<?= BASE_URL ?>/aluno/dashboard.php" class="btn-hero-secondary">
                        📊 Meu Painel
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/professor/dashboard.php" class="btn-hero-primary">
                        ⚙️ Painel Administrativo
                    </a>
                    <a href="<?= BASE_URL ?>/professor/placar.php" class="btn-hero-secondary">
                        🏆 Gerenciar Placar
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= $totalJogos ?></span>
                    <span class="stat-label">Modalidades</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $totalAlunos ?></span>
                    <span class="stat-label">Alunos Cadastrados</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $totalInscricoes ?></span>
                    <span class="stat-label">Inscrições Ativas</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- JOGOS -->
<section class="section" id="jogos">
    <div class="container">
        <div class="section-title">
            <h2>Modalidades dos Jogos</h2>
            <p>Escolha sua modalidade e inscreva-se para competir!</p>
        </div>
        
        <?php if (empty($jogos)): ?>
            <div style="text-align: center; padding: 40px; background: white; border-radius: 12px;">
                <div style="font-size: 3rem; margin-bottom: 15px;">⚠️</div>
                <h3>Nenhum jogo cadastrado</h3>
                <p style="color: #777; margin-top: 10px;">
                    Certifique-se de importar o banco de dados SQL.
                </p>
            </div>
        <?php else: ?>
        <div class="jogos-grid">
            <?php foreach ($jogos as $jogo): ?>
            <?php
                // Contar inscritos neste jogo
                $stmtInscritos = $pdo->prepare(
                    "SELECT COUNT(*) FROM inscricoes WHERE jogo_id = ? AND status != 'cancelada'"
                );
                $stmtInscritos->execute([$jogo['id']]);
                $qtdInscritos = (int)$stmtInscritos->fetchColumn();
                $vagasRestantes = $jogo['max_participantes'] - $qtdInscritos;
                $percentual = $jogo['max_participantes'] > 0 
                    ? round(($qtdInscritos / $jogo['max_participantes']) * 100) 
                    : 0;
            ?>
            <div class="jogo-card">
                <div class="jogo-card-header">
                    <span class="jogo-icone"><?= getIconeJogo($jogo['nome']) ?></span>
                    <div class="jogo-nome"><?= htmlspecialchars($jogo['nome']) ?></div>
                </div>
                
                <div class="jogo-card-body">
                    <div class="jogo-info">
                        <span>📅 Início</span>
                        <span><?= date('d/m/Y', strtotime($jogo['data_inicio'])) ?></span>
                    </div>
                    <div class="jogo-info">
                        <span>🏁 Término</span>
                        <span><?= date('d/m/Y', strtotime($jogo['data_fim'])) ?></span>
                    </div>
                    <div class="jogo-info">
                        <span>📍 Local</span>
                        <span><?= htmlspecialchars($jogo['local']) ?></span>
                    </div>
                    <div class="jogo-info">
                        <span>👥 Inscritos</span>
                        <span><?= $qtdInscritos ?> / <?= $jogo['max_participantes'] ?></span>
                    </div>
                    
                    <!-- Barra de progresso de vagas -->
                    <div style="margin-top: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #888; margin-bottom: 4px;">
                            <span>Ocupação</span>
                            <span style="color: <?= $vagasRestantes <= 0 ? '#CC0000' : '#555' ?>; font-weight: 600;">
                                <?= max(0, $vagasRestantes) ?> vagas restantes
                            </span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= min(100, $percentual) ?>%;
                                 background: <?= $percentual >= 90 ? '#e53935' : ($percentual >= 70 ? '#fb8c00' : '#CC0000') ?>;"></div>
                        </div>
                    </div>
                    
                    <span class="jogo-status status-<?= $jogo['status'] ?>" style="margin-top: 12px; display: inline-block;">
                        <?php
                        $statusTexto = [
                            'aberto'       => '🟢 Aberto',
                            'fechado'      => '🔴 Fechado',
                            'em_andamento' => '🟡 Em Andamento',
                            'finalizado'   => '⚫ Finalizado',
                        ];
                        echo $statusTexto[$jogo['status']] ?? ucfirst($jogo['status']);
                        ?>
                    </span>
                </div>
                
                <div class="jogo-card-footer">
                    <span style="font-size: 0.8rem; color: #888;">
                        <?= htmlspecialchars(mb_substr($jogo['descricao'] ?? '', 0, 40)) ?>...
                    </span>
                    
                    <?php if (isAlunoLogado() && $jogo['status'] === 'aberto' && $vagasRestantes > 0): ?>
                        <a href="<?= BASE_URL ?>/aluno/inscricao.php?jogo=<?= $jogo['id'] ?>" 
                           class="btn btn-primary btn-sm">
                            ✍️ Inscrever
                        </a>
                    <?php elseif (!isAlunoLogado() && !isProfessorLogado()): ?>
                        <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-secondary btn-sm">
                            🔑 Login
                        </a>
                    <?php elseif ($vagasRestantes <= 0): ?>
                        <span style="font-size: 0.82rem; color: #CC0000; font-weight: 700;">
                            ❌ Esgotado
                        </span>
                    <?php else: ?>
                        <span style="font-size: 0.82rem; color: #888;">—</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- PLACAR AO VIVO -->
<section class="section section-bg" id="placar">
    <div class="container">
        <div class="section-title">
            <div style="margin-bottom: 12px;">
                <span class="live-badge">
                    <span class="live-dot"></span> AO VIVO
                </span>
            </div>
            <h2>Placar dos Jogos</h2>
            <p>Acompanhe os resultados em tempo real — atualizado a cada 5 segundos</p>
        </div>
        
        <div class="placar-grid" id="placarContainer">
            <?php foreach ($jogos as $jogo): ?>
            <div class="placar-card" id="placar-card-<?= $jogo['id'] ?>">
                <div class="placar-header">
                    <span class="placar-jogo-nome">
                        <?= getIconeJogo($jogo['nome']) ?> <?= htmlspecialchars($jogo['nome']) ?>
                    </span>
                    <span class="placar-status-badge" id="status-badge-<?= $jogo['id'] ?>">
                        <?php
                        $statusPlacar = [
                            'aguardando'    => '⏳ Aguardando',
                            'em_andamento'  => '▶️ Ao Vivo',
                            'finalizado'    => '✅ Finalizado',
                        ];
                        echo $statusPlacar[$jogo['placar_status']] ?? $jogo['placar_status'];
                        ?>
                    </span>
                </div>
                <div class="placar-body">
                    <div class="placar-times">
                        <div class="placar-time">
                            <div class="placar-time-nome" id="time-a-nome-<?= $jogo['id'] ?>">
                                <?= htmlspecialchars($jogo['time_a']) ?>
                            </div>
                            <div class="placar-numero" id="placar-a-<?= $jogo['id'] ?>">
                                <?= (int)$jogo['placar_a'] ?>
                            </div>
                        </div>
                        
                        <div class="placar-vs">VS</div>
                        
                        <div class="placar-time">
                            <div class="placar-time-nome" id="time-b-nome-<?= $jogo['id'] ?>">
                                <?= htmlspecialchars($jogo['time_b']) ?>
                            </div>
                            <div class="placar-numero" id="placar-b-<?= $jogo['id'] ?>">
                                <?= (int)$jogo['placar_b'] ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="placar-atualizado" id="atualizado-<?= $jogo['id'] ?>">
                        🔄 Sincronizando...
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA - Chamada para Ação -->
<?php if (!isAlunoLogado() && !isProfessorLogado()): ?>
<section class="cta-section">
    <div class="container">
        <div style="font-size: 3rem; margin-bottom: 15px;">🏆</div>
        <h2 style="font-size: 2rem; font-weight: 900; margin-bottom: 10px;">
            Inscrições Abertas!
        </h2>
        <p style="font-size: 1.05rem; opacity: 0.9; margin-bottom: 30px; max-width: 500px; margin-left: auto; margin-right: auto;">
            Cadastre-se agora e participe dos Jogos Escolares SESI 2025. 
            7 modalidades te esperam!
        </p>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <a href="<?= BASE_URL ?>/auth/cadastro_aluno.php" 
               class="btn btn-lg" 
               style="background: white; color: #CC0000; font-weight: 700; padding: 15px 35px; border-radius: 50px;">
                🎓 Sou Aluno
            </a>
            <a href="<?= BASE_URL ?>/auth/cadastro_professor.php" 
               class="btn btn-lg" 
               style="background: rgba(255,255,255,0.15); color: white; border: 2px solid rgba(255,255,255,0.6); font-weight: 700; padding: 15px 35px; border-radius: 50px;">
                👨‍🏫 Sou Professor
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- FOOTER -->
<footer class="site-footer"><div class="container"><div><div class="site-footer-brand" aria-label="Planer SESI"></div><p>Organização dos Jogos Escolares SESI.</p></div><p class="site-footer-copy">&copy; <?= date('Y') ?> Planer SESI. Todos os direitos reservados.</p></div></footer>

<!-- SCRIPTS -->
<script>
// ==========================================
// MENU MOBILE
// ==========================================
function toggleMenu() {
    const nav = document.getElementById('navMobile');
    nav.classList.toggle('active');
}

document.addEventListener('click', function(e) {
    const nav = document.getElementById('navMobile');
    const btn = document.querySelector('.menu-mobile');
    if (nav && btn && !nav.contains(e.target) && !btn.contains(e.target)) {
        nav.classList.remove('active');
    }
});

// ==========================================
// SCROLL SUAVE
// ==========================================
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href === '#') return;
        const target = document.querySelector(href);
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// ==========================================
// ATUALIZAÇÃO DO PLACAR EM TEMPO REAL
// ==========================================
let ultimoEstado = {};

function atualizarPlacar() {
    fetch('<?= BASE_URL ?>/api/get_placar.php')
        .then(res => {
            if (!res.ok) throw new Error('Erro na requisição');
            return res.json();
        })
        .then(data => {
            if (!data.sucesso || !data.dados) return;
            
            data.dados.forEach(placar => {
                const id = placar.jogo_id;
                const chaveAtual = `${placar.placar_a}-${placar.placar_b}-${placar.status}`;
                
                // Atualizar placar A
                const elA = document.getElementById(`placar-a-${id}`);
                if (elA) {
                    if (elA.textContent.trim() !== placar.placar_a.toString()) {
                        elA.textContent = placar.placar_a;
                        piscar(elA);
                    }
                }
                
                // Atualizar placar B
                const elB = document.getElementById(`placar-b-${id}`);
                if (elB) {
                    if (elB.textContent.trim() !== placar.placar_b.toString()) {
                        elB.textContent = placar.placar_b;
                        piscar(elB);
                    }
                }
                
                // Atualizar nomes
                const nomeA = document.getElementById(`time-a-nome-${id}`);
                if (nomeA) nomeA.textContent = placar.time_a;
                
                const nomeB = document.getElementById(`time-b-nome-${id}`);
                if (nomeB) nomeB.textContent = placar.time_b;
                
                // Atualizar status badge
                const badge = document.getElementById(`status-badge-${id}`);
                if (badge) {
                    const textos = {
                        'aguardando':   '⏳ Aguardando',
                        'em_andamento': '▶️ Ao Vivo',
                        'finalizado':   '✅ Finalizado'
                    };
                    badge.textContent = textos[placar.status] || placar.status;
                }
                
                // Atualizar horário
                const horario = document.getElementById(`atualizado-${id}`);
                if (horario) {
                    horario.textContent = `🔄 Atualizado às ${data.hora}`;
                }
                
                ultimoEstado[id] = chaveAtual;
            });
        })
        .catch(err => {
            // Silencioso - não mostra erro ao usuário
            console.log('Aguardando conexão com servidor...');
        });
}

// Iniciar atualização automática
if (document.getElementById('placarContainer')) {
    atualizarPlacar(); // Primeira chamada imediata
    setInterval(atualizarPlacar, 5000); // A cada 5 segundos
}

function piscar(el) {
    el.style.transition = 'all 0.3s ease';
    el.style.transform = 'scale(1.4)';
    el.style.color = '#FF0000';
    setTimeout(() => {
        el.style.transform = 'scale(1)';
        el.style.color = '#CC0000';
    }, 400);
}

// ==========================================
// ANIMAÇÃO DE ENTRADA DOS CARDS
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.jogo-card, .placar-card');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, i) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, i * 80);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(25px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(card);
    });
});
</script>

<script src="<?= BASE_URL ?>/assets/js/icons.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>