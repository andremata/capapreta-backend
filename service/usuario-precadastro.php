<?php 

require_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$requisicao = $postjson['requisicao'];

$mensagem = "";

if ($requisicao == 'incluir') {
    $nome       = $postjson['nome'];
    $email      = $postjson['email'];
    $senha      = $postjson['senha'];
    $situacao   = 'PENDENTE';
    $nivel      = 'USUARIO';

    //Validações
    if (!is_string($nome) || $nome == "") {
        $result = json_encode(array('mensagem'=>'Campo Nome não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    if (!is_string($email) || $email == "") {
        $result = json_encode(array('mensagem'=>'Campo E-mail não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    if (!is_string($senha) || $senha == "") {
        $result = json_encode(array('mensagem'=>'Campo Senha não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }
    
    $query = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
    $query->bindValue(":email", $email);
    $query->execute();

    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if (@count($res) > 0) {
        $result = json_encode(array('mensagem'=>'Este e-mail já esta cadastrado!', 'sucesso'=> false));
        echo $result;
        exit();
    }
 
    $res = $pdo->prepare("INSERT INTO usuarios SET nome = :nome, email = :email, senha = :senha, nivel = :nivel, situacao = :situacao");
    $res->bindValue(":nome", $nome);
    $res->bindValue(":email", $email);
     $res->bindValue(":senha", $senha);
    $res->bindValue(":nivel", $nivel);
    $res->bindValue(":situacao", $situacao);
    $res->execute();

    $mensagem = "Salvo com sucesso!";
}

$result = json_encode(array('mensagem'=>$mensagem, 'sucesso'=> true));
echo $result;
?>