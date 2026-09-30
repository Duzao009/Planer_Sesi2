<?php
// ============================================
// API - get_placar.php
// ============================================

// Caminho correto: está em api/ que é subpasta da raiz
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/conexao.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    $stmt = $pdo->query("
        SELECT 
            p.jogo_id,
            p.time_a,
            p.time_b,
            p.placar_a,
            p.placar_b,
            p.status,
            p.atualizado_em,
            j.nome as jogo_nome
        FROM placar p 
        JOIN jogos j ON p.jogo_id = j.id
        ORDER BY p.jogo_id
    ");
    $placares = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'sucesso' => true,
        'dados'   => $placares,
        'hora'    => date('H:i:s'),
        'total'   => count($placares)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'sucesso'  => false,
        'mensagem' => 'Erro ao buscar placar',
        'hora'     => date('H:i:s')
    ]);
}
?>