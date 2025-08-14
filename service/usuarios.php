<?php 

require_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$requisicao = $postjson['requisicao'];
$mensagem = "";

// Pega os dados do usuário a partir do token
$id_usuario_logado = $dados_usuario_token->id;
$nivel_usuario_logado = $dados_usuario_token->nivel;


// SOMENTE ADMIN: Incluir um novo usuário
if ($requisicao == 'incluir') {
    if ($nivel_usuario_logado !== 'ADMIN') {
        http_response_code(403);
        echo json_encode(array('mensagem' => 'Acesso negado: somente administradores podem incluir usuários.', 'sucesso' => false));
        exit();
    }

    $nome = $postjson['nome'];
    $email = $postjson['email'];
    $senha = $postjson['senha'];
    $situacao = $postjson['situacao'];
    $nivel = $postjson['nivel'];

    // Validações...
    if (empty($nome) || empty($email) || empty($senha) || empty($situacao) || empty($nivel)) {
        echo json_encode(array('mensagem' => 'Todos os campos são obrigatórios!', 'sucesso' => false));
        exit();
    }

    $query = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
    $query->bindValue(":email", $email);
    $query->execute();
    if (@count($query->fetchAll(PDO::FETCH_ASSOC)) > 0) {
        echo json_encode(array('mensagem' => 'Este e-mail já esta cadastrado!', 'sucesso' => false));
        exit();
    }

    $res = $pdo->prepare("INSERT INTO usuarios SET nome = :nome, email = :email, senha = :senha, nivel = :nivel, situacao = :situacao");
    $res->bindValue(":nome", $nome);
    $res->bindValue(":email", $email);
    $res->bindValue(":senha", password_hash($senha, PASSWORD_DEFAULT));
    $res->bindValue(":nivel", $nivel);
    $res->bindValue(":situacao", $situacao);
    $res->execute();

    $mensagem = "Salvo com sucesso!";
}

// ADMIN ou o próprio usuário podem alterar
if ($requisicao == 'alterar') {
    $id_alvo = $postjson['id'];
    $nome = $postjson['nome'];
    $email = $postjson['email'];
    $senha = $postjson['senha'];

    // Um usuário só pode alterar os próprios dados, a menos que seja um admin
    if ($nivel_usuario_logado !== 'ADMIN' && $id_usuario_logado != $id_alvo) {
        http_response_code(403);
        echo json_encode(array('mensagem' => 'Acesso negado: você não pode alterar dados de outro usuário.', 'sucesso' => false));
        exit();
    }

    // Verifica se o e-mail está duplicado
    $query = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email AND id <> :id");
    $query->bindValue(":email", $email);
    $query->bindValue(":id", $id_alvo);
    $query->execute();
    if (@count($query->fetchAll(PDO::FETCH_ASSOC)) > 0) {
        echo json_encode(array('mensagem' => 'Este e-mail já esta cadastrado para outro usuário!', 'sucesso' => false));
        exit();
    }

    // Se o usuário for ADMIN, ele pode alterar o nível e a situação
    if ($nivel_usuario_logado === 'ADMIN') {
        $situacao = $postjson['situacao'];
        $nivel = $postjson['nivel'];
        $res = $pdo->prepare("UPDATE usuarios SET nome = :nome, email = :email, senha = :senha, nivel = :nivel, situacao = :situacao WHERE id = :id");
        $res->bindValue(":nivel", $nivel);
        $res->bindValue(":situacao", $situacao);
    } else {
        // Usuários normais não podem alterar seu próprio nível ou situação
        $res = $pdo->prepare("UPDATE usuarios SET nome = :nome, email = :email, senha = :senha WHERE id = :id");
    }
    
    $res->bindValue(":nome", $nome);
    $res->bindValue(":email", $email);
    $res->bindValue(":senha", password_hash($senha, PASSWORD_DEFAULT));
    $res->bindValue(":id", $id_alvo);
    $res->execute();

    $mensagem = "Alterado com sucesso!";
}

// ADMIN ou o próprio usuário podem visualizar
if ($requisicao == 'consulta_por_id') {
    $id_alvo = $postjson['id'];

    if ($nivel_usuario_logado !== 'ADMIN' && $id_usuario_logado != $id_alvo) {
        http_response_code(403);
        echo json_encode(array('mensagem' => 'Acesso negado.', 'sucesso' => false));
        exit();
    }

    $query = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
    $query->bindValue(":id", $id_alvo);
    $query->execute();
    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if (@count($res) > 0) {
        $dados = [
            'id' => $res[0]['id'], 'nome' => $res[0]['nome'], 'email' => $res[0]['email'],
            'senha' => $res[0]['senha'], 'nivel' => $res[0]['nivel'], 'situacao' => $res[0]['situacao'],
        ];
        echo json_encode(array('sucesso' => true, 'user' => $dados));
    } else {
        echo json_encode(array('mensagem' => 'Usuário não encontrado!', 'sucesso' => false));
    }
    exit();
}

// SOMENTE ADMIN: Listar usuários
if ($requisicao == 'consultar') {
    if ($nivel_usuario_logado !== 'ADMIN') {
        http_response_code(403);
        echo json_encode(array('mensagem' => 'Acesso negado.', 'sucesso' => false));
        exit();
    }

    $condicao = "%". $postjson['nome'] ."%";
    $start = (int) $postjson['start'];
    $limit = (int) $postjson['limit'];

    $query = $pdo->prepare("SELECT * FROM usuarios WHERE nivel <> 'ADMIN' AND nome LIKE :condicao ORDER BY id DESC LIMIT :start, :limit");
    $query->bindValue(":condicao", $condicao);
    $query->bindValue(":start", $start, PDO::PARAM_INT);
    $query->bindValue(":limit", $limit, PDO::PARAM_INT);
    $query->execute();
    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if (@count($res) > 0) {
        echo json_encode(array('sucesso' => true, 'users' => $res));
    } else {
        echo json_encode(array('mensagem' => 'Nenhum usuário encontrado!', 'sucesso' => false));
    }
    exit();
}

// O usuário só pode alterar a própria senha
if ($requisicao == 'alterar_senha') {
    $senha = $postjson['senha'];
    $senha2 = $postjson['senha2'];

    if (empty($senha) || empty($senha2)) {
        echo json_encode(array('mensagem' => 'Preencha as senhas!', 'sucesso' => false));
        exit();
    }
    if ($senha != $senha2) {
        echo json_encode(array('mensagem' => 'As senhas não são iguais!', 'sucesso' => false));
        exit();
    }

    $res = $pdo->prepare("UPDATE usuarios SET senha = :senha WHERE id = :id");
    $res->bindValue(":senha", password_hash($senha, PASSWORD_DEFAULT));
    $res->bindValue(":id", $id_usuario_logado);
    $res->execute();

    $mensagem = "Sua senha foi alterada com sucesso, faça o login novamente!";
}

// SOMENTE ADMIN: Excluir um usuário
if ($requisicao == 'excluir') {
    if ($nivel_usuario_logado !== 'ADMIN') {
        http_response_code(403);
        echo json_encode(array('mensagem' => 'Acesso negado.', 'sucesso' => false));
        exit();
    }

    $id_alvo = $postjson['id'];

    if ($id_alvo == $id_usuario_logado) {
        echo json_encode(array('mensagem' => 'Você não pode excluir a si mesmo!', 'sucesso' => false));
        exit();
    }

    $res = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
    $res->bindValue(":id", $id_alvo);
    $res->execute();

    $mensagem = "Excluido com sucesso!";
}

// Resposta de sucesso padrão para ações que não terminam antes
$result = json_encode(array('mensagem' => $mensagem, 'sucesso' => true));
echo $result;

?>