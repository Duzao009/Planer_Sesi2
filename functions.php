<?php
// ============================================
// FUNÇÕES AUXILIARES - PLANER SESI
// ============================================

// Garante que a conexão existe
if (!isset($pdo)) {
    require_once dirname(__DIR__) . '/config/conexao.php';
}


/** Proteção contra envio de formulários por páginas externas. */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}
function validarCsrf() {
    $recebido = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($recebido) || !hash_equals(csrfToken(), $recebido)) {
        http_response_code(403);
        exit('Solicitação inválida. Atualize a página e tente novamente.');
    }
}

/**
 * Sanitiza string
 */
function sanitize($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

/**
 * Limpa um valor para GRAVAR no banco (sem converter para entidades HTML).
 * Use limpar() ao salvar e htmlspecialchars() ao exibir — assim o texto
 * não fica com &amp;quot; e outros códigos estranhos no banco.
 */
function limpar($str) {
    return trim(strip_tags((string)$str));
}

/**
 * Valida uma data no formato YYYY-MM-DD
 */
function validarData($data, $formato = 'Y-m-d') {
    if (!is_string($data) || $data === '') return false;
    $d = DateTime::createFromFormat($formato, $data);
    return $d && $d->format($formato) === $data;
}

/**
 * Valida CPF
 */
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11) return false;
    if (preg_match('/(\d)\1{10}/', $cpf)) return false;
    
    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) return false;
    }
    return true;
}

/**
 * Formata CPF
 */
function formatarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

/**
 * Verifica se CPF já existe
 */
function cpfExiste($pdo, $cpf, $tabela, $excludeId = null) {
    $cpf = formatarCPF($cpf);
    $sql = "SELECT id FROM $tabela WHERE cpf = ?";
    $params = [$cpf];
    
    if ($excludeId) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() !== false;
}

/**
 * Verifica login de aluno
 */
function verificarLoginAluno($pdo, $email, $senha) {
    $stmt = $pdo->prepare("SELECT * FROM alunos WHERE email = ? AND status = 'ativo'");
    $stmt->execute([$email]);
    $aluno = $stmt->fetch();
    
    if ($aluno && password_verify($senha, $aluno['senha'])) {
        return $aluno;
    }
    return false;
}

/**
 * Verifica login de professor
 */
function verificarLoginProfessor($pdo, $email, $senha) {
    $stmt = $pdo->prepare("SELECT * FROM professores WHERE email = ? AND status = 'ativo'");
    $stmt->execute([$email]);
    $professor = $stmt->fetch();
    
    if ($professor && password_verify($senha, $professor['senha'])) {
        return $professor;
    }
    return false;
}

/**
 * Verifica se está logado como aluno
 */
function isAlunoLogado() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['aluno_id']);
}

/**
 * Verifica se está logado como professor
 */
function isProfessorLogado() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['professor_id']);
}

/**
 * Redireciona
 */
function redirecionar($url) {
    header("Location: $url");
    exit;
}

/**
 * Busca todos os jogos com placar
 */
function getJogos($pdo) {
    $stmt = $pdo->query("
        SELECT 
            j.id,
            j.nome,
            j.descricao,
            j.data_inicio,
            j.data_fim,
            j.local,
            j.max_participantes,
            j.status,
            j.criado_em,
            COALESCE(j.icone, 'esporte') as icone,
            COALESCE(p.placar_a, 0) as placar_a,
            COALESCE(p.placar_b, 0) as placar_b,
            COALESCE(p.time_a, 'Time A') as time_a,
            COALESCE(p.time_b, 'Time B') as time_b,
            COALESCE(p.status, 'aguardando') as placar_status
        FROM jogos j 
        LEFT JOIN placar p ON j.id = p.jogo_id 
        ORDER BY j.id
    ");
    return $stmt->fetchAll();
}

/**
 * Busca placar de um jogo
 */
function getPlacar($pdo, $jogoId) {
    $stmt = $pdo->prepare("SELECT * FROM placar WHERE jogo_id = ?");
    $stmt->execute([$jogoId]);
    return $stmt->fetch();
}

/**
 * Conta inscrições por série
 */
function getInscricoesPorSerie($pdo, $jogoId = null) {
    $sql = "
        SELECT i.serie, COUNT(*) as total, j.nome as jogo 
        FROM inscricoes i 
        JOIN jogos j ON i.jogo_id = j.id 
        WHERE i.status != 'cancelada'
    ";
    
    $params = [];
    if ($jogoId) {
        $sql .= " AND i.jogo_id = ?";
        $params[] = $jogoId;
    }
    
    $sql .= " GROUP BY i.serie, i.jogo_id ORDER BY j.nome, i.serie";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Ícone do jogo
 */
function getIconeJogo($nome) { return ''; }

/**
 * Envia e-mail de confirmação
 */
function enviarEmailConfirmacao($email, $nome, $jogo, $serie) {
    $assunto = "=?UTF-8?B?" . base64_encode("Confirmação de Inscrição - Jogos Escolares SESI") . "?=";
    $mensagem = "
    <html>
    <body style='font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px;'>
        <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);'>
            <div style='background: linear-gradient(135deg, #990000, #CC0000); padding: 30px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 1.8rem;'>Planer SESI</h1>
                <p style='color: rgba(255,255,255,0.9); margin: 5px 0 0;'>Jogos Escolares 2025</p>
            </div>
            <div style='padding: 30px;'>
                <h2 style='color: #CC0000;'>Inscrição confirmada</h2>
                <p>Olá, <strong>" . htmlspecialchars($nome) . "</strong>!</p>
                <p>Sua inscrição foi confirmada com sucesso!</p>
                <div style='background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #CC0000;'>
                    <p style='margin: 0 0 8px;'><strong>Modalidade:</strong> " . htmlspecialchars($jogo) . "</p>
                    <p style='margin: 0 0 8px;'><strong>Série:</strong> " . htmlspecialchars($serie) . "</p>
                    <p style='margin: 0;'><strong>Status:</strong> Confirmada</p>
                </div>
                <p>Boa sorte nos jogos!</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='color: #CC0000; font-weight: bold; margin: 0;'>Equipe Planer SESI</p>
            </div>
        </div>
    </body>
    </html>";
    
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Tenta enviar, retorna true/false
    return @mail($email, $assunto, $mensagem, $headers);
}

/**
 * Gera alerta HTML
 */
function alerta($tipo, $mensagem) {
    $tiposPermitidos = ['sucesso', 'erro', 'aviso', 'info'];
    if (!in_array($tipo, $tiposPermitidos, true)) $tipo = 'info';
    return "<div class='alerta alerta-{$tipo}'>" . htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') . "</div>";
}
?>