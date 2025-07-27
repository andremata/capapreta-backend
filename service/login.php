<?php

require_once('../conexao.php');

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

    $dados = array(
        'id'       => $res[0]['id'],
        'nome'     => $res[0]['nome'],
        'email'    => $res[0]['email'],
        'senha'    => $res[0]['senha'],
		'nivel'    => $res[0]['nivel'],
        'situacao' => $res[0]['situacao'],
    );

    $result = json_encode(array('mensagem' => 'Logado com sucesso.', 'sucesso'=> true, 'user'=>$dados));

    echo $result;
} else {
    $result = json_encode(array('mensagem' => 'Dados incorretos.', 'sucesso'=> false));

    echo $result;
}




