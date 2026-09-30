<?php
require_once '../config/conexao.php';
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!isProfessorLogado()) {
    echo json_encode(['erro' => true, 'mensagem' => 'Não autorizado!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['erro' => true, 'mensagem' => 'Método inválido!']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) { http_response_code(400); echo json_encode(['erro'=>true,'mensagem'=>'Dados inválidos.']); exit; }
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? '');
if (!is_string($token) || !hash_equals(csrfToken(), $token)) { http_response_code(403); echo json_encode(['erro'=>true,'mensagem'=>'Solicitação inválida.']); exit; }

$jogoId  = (int)($data['jogo_id'] ?? 0);
$placarA = (int)($data['placar_a'] ?? 0);
$placarB = (int)($data['placar_b'] ?? 0);
$timeA   = sanitize($data['time_a'] ?? 'Time A');
$timeB   = sanitize($data['time_b'] ?? 'Time B');
$status  = sanitize($data['status'] ?? 'aguardando');
$profId  = $_SESSION['professor_id'];
$statusValidos = ['aguardando','em_andamento','finalizado'];
if (!in_array($status, $statusValidos, true)) $status = 'aguardando';
$placarA = max(0, min(999, $placarA));
$placarB = max(0, min(999, $placarB));

if (!$jogoId) {
    echo json_encode(['erro' => true, 'mensagem' => 'ID do jogo inválido!']);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE placar SET 
        placar_a = ?, placar_b = ?,
        time_a = ?, time_b = ?,
        status = ?, professor_id = ?
    WHERE jogo_id = ?
");

if ($stmt->execute([$placarA, $placarB, $timeA, $timeB, $status, $profId, $jogoId])) {
    echo json_encode(['sucesso' => true, 'mensagem' => 'Placar atualizado!']);
} else {
    echo json_encode(['erro' => true, 'mensagem' => 'Erro ao atualizar placar!']);
}
?>