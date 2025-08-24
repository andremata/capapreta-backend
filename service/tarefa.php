<?php 

require_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$requisicao = $postjson['requisicao'];
$mensagem = "";

// Use the user ID from the decoded JWT token
$id_usuario_logado = $dados_usuario_token->id;

if ($requisicao == 'incluir') {
    $descricao  = $postjson['descricao'];
    $situacao   = $postjson['situacao'];

    if (!is_string($descricao) || $descricao == "") {
        $result = json_encode(array('mensagem'=>'Campo Tarefa não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $query = $pdo->prepare("SELECT * FROM tarefas WHERE descricao = :descricao AND usuarioid = :usuarioid");
    $query->bindValue(":descricao", $descricao);
    $query->bindValue(":usuarioid", $id_usuario_logado);
    $query->execute();
    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if ( @count($res) > 0) {
        $result = json_encode(array('mensagem'=>'Já existe uma tarefa com este nome!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $res = $pdo->prepare("INSERT INTO tarefas SET descricao = :descricao, situacao = :situacao, usuarioid = :usuarioid");
    $res->bindValue(":descricao", $descricao);
    $res->bindValue(":situacao", $situacao);
    $res->bindValue(":usuarioid", $id_usuario_logado);
    $res->execute();

    $mensagem = "Salvo com sucesso!";
}

if ($requisicao == 'alterar') {
    $id         = $postjson['id'];
    $descricao  = $postjson['descricao'];
    $situacao   = $postjson['situacao'];

    if (!is_string($descricao) || $descricao == "") {
        $result = json_encode(array('mensagem'=>'Campo Tarefa não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $query = $pdo->prepare("SELECT * FROM tarefas WHERE descricao = :descricao AND id <> :id AND usuarioid = :usuarioid");
    $query->bindValue(":descricao", $descricao);
    $query->bindValue(":id", $id);
    $query->bindValue(":usuarioid", $id_usuario_logado);
    $query->execute();
    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if ( @count($res) > 0) {
        $result = json_encode(array('mensagem'=>'Já existe uma tarefa com este nome!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    // Security Fix: Ensure user can only update their own tasks
    $res = $pdo->prepare("UPDATE tarefas SET descricao = :descricao, situacao = :situacao WHERE id = :id AND usuarioid = :usuarioid");
    $res->bindValue(":descricao", $descricao);
    $res->bindValue(":situacao", $situacao);
    $res->bindValue(":id", $id);
    $res->bindValue(":usuarioid", $id_usuario_logado);
    $res->execute();

    if($res->rowCount() > 0){
        $mensagem = "Alterado com sucesso!";
    } else {
        $mensagem = "A tarefa não foi encontrada ou você não tem permissão para alterá-la.";
    }
}

if ($requisicao == 'concluir') {
    $id = $postjson['id'];

    $res = $pdo->prepare("UPDATE tarefas SET situacao = 'FINALIZADA' WHERE id = :id AND usuarioid = :usuarioid AND situacao = 'PENDENTE'");
    $res->bindValue(":id", $id);
    $res->bindValue(":usuarioid", $id_usuario_logado);
    $res->execute();

    if($res->rowCount() > 0){
        $mensagem = "Tarefa concluída com sucesso!";
    } else {
        $result = json_encode(array('mensagem'=>'A tarefa não está pendente ou você não tem permissão para alterá-la.', 'sucesso'=> false));
        echo $result;
        exit();
    }
}

if ($requisicao == 'excluir') {
    $id = $postjson['id'];

    // Security Fix: Use prepared statements and check ownership before deleting
    $query = $pdo->prepare("SELECT situacao FROM tarefas WHERE id = :id AND usuarioid = :usuarioid");
    $query->bindValue(":id", $id);
    $query->bindValue(":usuarioid", $id_usuario_logado);
    $query->execute();
    $res = $query->fetch(PDO::FETCH_ASSOC);

    if (!$res) {
        $result = json_encode(array('mensagem'=>'Tarefa não encontrada ou você não tem permissão para excluí-la.', 'sucesso'=> false));
        echo $result;
        exit();
    }

    if ($res['situacao'] == 'FINALIZADA') {
        $result = json_encode(array('mensagem'=>'Esta tarefa não pode ser excluída!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $res = $pdo->prepare("DELETE FROM tarefas WHERE id = :id AND usuarioid = :usuarioid");
    $res->bindValue(":id", $id);
    $res->bindValue(":usuarioid", $id_usuario_logado);
    $res->execute();

    $mensagem = "Excluído com sucesso!";
}

if ($requisicao == 'consultar') {
    $condicao = "%". $postjson['descricao'] ."%";
    $start = (int) $postjson['start'];
    $limit = (int) $postjson['limit'];
    
    // Correção de segurança: usar prepared statements para prevenir injeção de SQL
    $query = $pdo->prepare("SELECT * FROM tarefas WHERE descricao LIKE :condicao AND usuarioid = :usuarioid ORDER BY id DESC LIMIT :start, :limit");
    $query->bindValue(":condicao", $condicao);
    $query->bindValue(":usuarioid", $id_usuario_logado);
    $query->bindValue(":start", $start, PDO::PARAM_INT);
    $query->bindValue(":limit", $limit, PDO::PARAM_INT);
    $query->execute();

    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $total = @count($res);

    if ($total <= 0) {
        $result = json_encode(array('mensagem'=>'Nenhuma tarefa encontrada!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $dados = [];
    foreach ($res as $item) {
        $dados[] = array(
            'id'        => $item['id'],
            'descricao' => $item['descricao'],
            'situacao'  => $item['situacao'],
        );
    }

    $result = json_encode(array('sucesso'=> true, 'tarefas'=>$dados));
    echo $result;
    exit();
}

$result = json_encode(array('mensagem'=>$mensagem, 'sucesso'=> true));
echo $result;
?>