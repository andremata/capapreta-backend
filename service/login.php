<?php

require_once('../conexao.php');
require_once __DIR__ . '/../vendor/autoload.php'; // Include Composer's autoloader

use Firebase\JWT\JWT;

$postjson = json_decode(file_get_contents('php://input'), true);

$email = $postjson['email'];
$senha   = $postjson['senha'];

if ($email == "") {
    echo json_encode(array('mensagem' => 'Preencha o campo e-mail!'));
    exit();
}

if ($senha == "") {
    echo json_encode(array('mensagem' => 'Preencha o campo senha!'));
    exit();
}

$query = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email and senha = :senha");
$query->bindValue(":email", $email);
$query->bindValue(":senha", $senha);
$query->execute();

$res = $query->fetchAll(PDO::FETCH_ASSOC);

if (@count($res) > 0) {
    if ($res[0]['situacao'] == "BLOQUEADO") {
        $result = json_encode(array('mensagem' => 'Este usuário está bloqueado.', 'sucesso'=> false));
        echo $result;
        exit();
    }

    if ($res[0]['situacao'] == "PENDENTE") {
        $result = json_encode(array('mensagem' => 'Este usuário ainda não foi autorizado.', 'sucesso'=> false));
        echo $result;
        exit();
    }

    // JWT Generation
    $secret_key = "YOUR_SECRET_KEY"; // TODO: Armazene isso de forma segura!
    $issuer_claim = "http://localhost/capapretaapp"; // The issuer of the token
    $audience_claim = "http://localhost/capapretaapp"; // The audience of the token
    $issuedat_claim = time(); // Issued at: current timestamp
    $expire_claim = $issuedat_claim + 3600; // Expire time is 1 hour

    $payload = array(
        "iss" => $issuer_claim,
        "aud" => $audience_claim,
        "iat" => $issuedat_claim,
        "exp" => $expire_claim,
        "data" => array(
            "id" => $res[0]['id'],
            "nome" => $res[0]['nome'],
            "email" => $res[0]['email'],
            "nivel" => $res[0]['nivel']
        )
    );

    $jwt = JWT::encode($payload, $secret_key, 'HS256');

    $result = json_encode(array(
        'mensagem' => 'Logado com sucesso.', 
        'sucesso'=> true, 
        'token' => $jwt
    ));

    echo $result;
} else {
    $result = json_encode(array('mensagem' => 'Dados incorretos.', 'sucesso'=> false));

    echo $result;
}