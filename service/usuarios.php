<?php 

require_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$requisicao = $postjson['requisicao'];

$mensagem = "";

if ($requisicao == 'incluir') {
    $nome       = $postjson['nome'];
    $email      = $postjson['email'];
    $senha      = $postjson['senha'];
    $situacao   = $postjson['situacao'];
    $nivel      = $postjson['nivel'];

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

    if (!is_string($nivel) || $nivel == "") {
        $result = json_encode(array('mensagem'=>'Campo Nível não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    if (!is_string($situacao) || $situacao == "") {
        $result = json_encode(array('mensagem'=>'Campo Situação não preenchido!', 'sucesso'=> false));
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
    
    $query = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
    $query->bindValue(":usuario", $usuario);
    $query->execute();

    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if (@count($res) > 0) {
        $result = json_encode(array('mensagem'=>'Este usuário já existe!', 'sucesso'=> false));
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

if ($requisicao == 'alterar') {
    $id         = $postjson['id'];
    $nome       = $postjson['nome'];
    $email      = $postjson['email'];
    $senha      = $postjson['senha'];
    $situacao   = $postjson['situacao'];
    $nivel      = $postjson['nivel'];

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

    if (!is_string($nivel) || $nivel == "") {
        $result = json_encode(array('mensagem'=>'Campo Nível não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    if (!is_string($situacao) || $situacao == "") {
        $result = json_encode(array('mensagem'=>'Campo Situação não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $query = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email AND id <> :id");
    $query->bindValue(":email", $email);
    $query->bindValue(":id", $id);
    $query->execute();

    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if (@count($res) > 0) {
        $result = json_encode(array('mensagem'=>'Este e-mail já esta cadastrado para outro usuário!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $res = $pdo->prepare("UPDATE usuarios SET nome = :nome, email = :email, senha = :senha, nivel = :nivel, situacao = :situacao WHERE id = :id");
    $res->bindValue(":nome", $nome);
    $res->bindValue(":email", $email);
    $res->bindValue(":senha", $senha);
    $res->bindValue(":nivel", $nivel);
    $res->bindValue(":situacao", $situacao);
    $res->bindValue(":id", $id);
    $res->execute();

    $mensagem = "Alterado com sucesso!";
}

if ($requisicao == 'consulta_por_id') {
    $query = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
    $query->bindValue(":id", $id);
    $query->execute();

    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if (@count($res) <= 0) {
        $result = json_encode(array('mensagem'=>'Usuário não encontrado!', 'sucesso'=> false));
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

    $result = json_encode(array('sucesso'=> true, 'user'=>$dados));
    echo $result;
    exit();
}

if ($requisicao == 'consultar') {
    $condicao = "%". $postjson['nome'] ."%";
    
    $query = $pdo->query("SELECT * FROM usuarios WHERE nivel <> 'ADMINISTRADOR' AND nome LIKE '$condicao' ORDER BY id DESC LIMIT $postjson[start], $postjson[limit]");

    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $total = @count($res);

    if (@count($res) <= 0) {
        $result = json_encode(array('mensagem'=>'Nenhum usuário encontrado!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    if ($total > 0) {
        for($i=0; $i<$total; $i++){
            foreach ($res[$i] as $key => $value){}

            $dados[] = array(
                'id'       => $res[$i]['id'],
                'nome'     => $res[$i]['nome'],
                'email'    => $res[$i]['email'],
                'senha'    => $res[$i]['senha'],
                'nivel'    => $res[$i]['nivel'],
                'situacao' => $res[$i]['situacao'],
            );
        }

        $result = json_encode(array('sucesso'=> true, 'users'=>$dados));
        
    } else {
        $result = json_encode(array('sucesso'=> false, 'users'=>'0'));
    }

    echo $result;
    exit();
}

if ($requisicao == 'recuperar') {
    $email = $postjson['email'];

    if (!is_string($email) || $email == "") {
        $result = json_encode(array('mensagem'=>'Campo E-mail não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $query = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
    $query->bindValue(":email", $email);
    $query->execute();

    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if (@count($res) <= 0) {
        $result = json_encode(array('mensagem'=>'Este e-mail não esta cadastrado!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $dados = array(
        'id'       => $res[0]['id'],
        'email'    => $res[0]['email'],
    );

    $result = json_encode(array('sucesso'=> true, 'user'=>$res[0]['id']));
    echo $result;
    exit();
}

if ($requisicao == 'alterar_senha') {
    $id     = $postjson['id'];
    $senha  = $postjson['senha'];
    $senha2 = $postjson['senha2'];
    $email  = $postjson['email'];

    if (!is_string($senha) || $senha == "") {
        $result = json_encode(array('mensagem'=>'Campo Senha não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    if (!is_string($senha2) || $senha2 == "") {
        $result = json_encode(array('mensagem'=>'Campo Repita Senha não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    if ($senha != $senha2) {
        $result = json_encode(array('mensagem'=>'As senhas não são iguais!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $query = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email AND id = :id");
    $query->bindValue(":email", $email);
    $query->bindValue(":id", $id);
    $query->execute();

    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if (@count($res) <= 0) {
        $result = json_encode(array('mensagem'=>'Este e-mail não esta cadastrado!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $res = $pdo->prepare("UPDATE usuarios SET senha = :senha WHERE id = :id");
    $res->bindValue(":senha", $senha);
    $res->bindValue(":id", $id);
    $res->execute();

    $mensagem = "Sua senha foi alterada com sucesso, faça o login novamente!";
}


if ($requisicao == 'excluir') {
    $id = $postjson['id'];

    $res = $pdo->query("DELETE FROM usuarios WHERE id = '$id'");

    $mensagem = "Excluido com sucesso!";
}

$result = json_encode(array('mensagem'=>$mensagem, 'sucesso'=> true));
echo $result;
?>