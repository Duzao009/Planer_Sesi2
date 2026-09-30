<?php
// ============================================
// CONFIGURAÇÃO DE CONEXÃO COM BANCO DE DADOS
// ============================================

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '12345678');
define('DB_NAME', getenv('DB_NAME') ?: 'planer_sesi');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_CHARSET', 'utf8mb4');

// Configurações de E-mail
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', getenv('MAIL_USER') ?: '');
define('MAIL_PASS', getenv('MAIL_PASS') ?: '');
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@localhost');
define('MAIL_FROM_NAME', 'Planer SESI');

// ============================================
// BASE_URL — detectada automaticamente
// Funciona em qualquer pasta (htdocs/DU_Planer_Sesi, htdocs/planer, etc.)
// ============================================
if (!defined('BASE_URL')) {
    if (PHP_SAPI === 'cli') {
        define('BASE_URL', 'http://localhost');
    } else {
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Descobre em qual subpasta o projeto está, comparando a pasta real
        // do script com a URL pedida pelo navegador. Funciona em qualquer
        // pasta do htdocs e também na raiz do domínio.
        $raizProjeto = str_replace('\\', '/', dirname(__DIR__));
        $scriptFile  = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
        $scriptName  = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');

        $pastaScript = rtrim(dirname($scriptFile), '/');
        $niveis      = 0;
        if ($pastaScript !== '' && $pastaScript !== $raizProjeto) {
            $relativo = trim(str_replace($raizProjeto, '', $pastaScript), '/');
            $niveis   = ($relativo === '') ? 0 : count(explode('/', $relativo));
        }

        $partes = explode('/', trim($scriptName, '/'));
        array_pop($partes);                       // remove o arquivo .php
        for ($i = 0; $i < $niveis; $i++) {        // sobe até a raiz do projeto
            array_pop($partes);
        }
        $subPasta = $partes ? '/' . implode('/', $partes) : '';

        define('BASE_URL', $protocolo . '://' . $host . $subPasta);
    }
}

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// ============================================
// CONEXÃO PDO
// ============================================
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log('Falha ao conectar ao banco: ' . $e->getMessage());
    http_response_code(500);
    die("<main style='font-family:Arial,sans-serif;max-width:620px;margin:80px auto;padding:24px'><h1>Não foi possível acessar o sistema</h1><p>Verifique se o Apache e o MySQL estão ativos e tente novamente.</p></main>");
}

// Iniciar sessão com cookie protegido
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
?>