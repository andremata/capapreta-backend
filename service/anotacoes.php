<?php 

require_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$requisicao = $postjson['requisicao'];

$mensagem = "";

// Use the user ID from the decoded JWT token
$id_usuario_logado = $dados_usuario_token->id;

if ($requisicao == 'incluir') {
    $descricao = $postjson['descricao'];

    if (!is_string($descricao) || $descricao == "") {
        $result = json_encode(array('mensagem'=>'Campo Descrição não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $res = $pdo->prepare("INSERT INTO anotacoes SET descricao = :descricao, usuarioid = :usuarioid");
    $res->bindValue(":descricao", $descricao);
    $res->bindValue(":usuarioid", $id_usuario_logado);
    $res->execute();

    $mensagem = "Salvo com sucesso!";
}

if ($requisicao == 'alterar') {
    $id        = $postjson['id'];
    $descricao = $postjson['descricao'];

    if (!is_string($descricao) || $descricao == "") {
        $result = json_encode(array('mensagem'=>'Campo Descrição não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    // Security fix: ensure users can only update their own annotations
    $res = $pdo->prepare("UPDATE anotacoes SET descricao = :descricao WHERE id = :id AND usuarioid = :usuarioid");
    $res->bindValue(":descricao", $descricao);
    $res->bindValue(":id", $id);
    $res->bindValue(":usuarioid", $id_usuario_logado);
    $res->execute();

    if($res->rowCount() > 0){
        $mensagem = "Alterado com sucesso!";
    } else {
        $mensagem = "A anotação não foi encontrada ou você não tem permissão para alterá-la.";
    }
}

if ($requisicao == 'consultar') {
    $condicao = "%". $postjson['descricao'] ."%";
    $start = (int) $postjson['start'];
    $limit = (int) $postjson['limit'];
    
    // Correção de segurança: usar prepared statements para prevenir injeção de SQL
    $query = $pdo->prepare("SELECT * FROM anotacoes WHERE descricao LIKE :condicao AND usuarioid = :usuarioid ORDER BY id DESC LIMIT :start, :limit");
    $query->bindValue(":condicao", $condicao);
    $query->bindValue(":usuarioid", $id_usuario_logado);
    $query->bindValue(":start", $start, PDO::PARAM_INT);
    $query->bindValue(":limit", $limit, PDO::PARAM_INT);
    $query->execute();

    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $total = @count($res);

    if ($total <= 0) {
        $result = json_encode(array('mensagem'=>'Nenhuma anotação encontrada!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $dados = [];
    foreach ($res as $item) {
        $dados[] = array(
            'id'        => $item['id'],
            'descricao' => $item['descricao'],
        );
    }

    $result = json_encode(array('sucesso'=> true, 'anotacoes'=>$dados));

    echo $result;
    exit();
}

$result = json_encode(array('mensagem'=>$mensagem, 'sucesso'=> true));
echo $result;

?>