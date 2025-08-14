<?php

header('Access-Control-Allow-Origin: http://localhost:8100');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With'); 
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Define os scripts que não requerem autenticação
$unprotected_scripts = ['login.php', 'usuario-precadastro.php'];
$current_script = basename($_SERVER['PHP_SELF']);

// Se o script não estiver na lista de desprotegidos, valide o token
if (!in_array($current_script, $unprotected_scripts)) {
    $authHeader = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } else {
        http_response_code(401);
        echo json_encode(['mensagem' => 'Token de autorização não encontrado.']);
        exit();
    }

    list($jwt) = sscanf($authHeader, 'Bearer %s');

    if (!$jwt) {
        http_response_code(401);
        echo json_encode(['mensagem' => 'Token malformado.']);
        exit();
    }

    $secret_key = "YOUR_SECRET_KEY"; // TODO: Armazene isso de forma segura!

    try {
        $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
        // Os dados do usuário agora estão disponíveis em $decoded->data
        $dados_usuario_token = $decoded->data;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['mensagem' => 'Acesso não autorizado: ' . $e->getMessage()]);
        exit();
    }
}


date_default_timezone_set('America/Sao_Paulo');
@session_start();

$server   = 'localhost';
$user     = 'root';
$pass     = '';
$database = 'capapreta';

try {
    $pdo = new PDO("mysql:dbname=$database;host=$server", "$user", "$pass");
    
} catch (Exception $e) {
    echo 'Erro ao conectar com o banco! '. $e;
}

?>