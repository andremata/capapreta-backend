<?php 

require_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$requisicao = $postjson['requisicao'];

$mensagem = "";

if ($requisicao == 'incluir') {
    $descricao  = $postjson['descricao'];
    $situacao   = $postjson['situacao'];
    $usuarioid  = $postjson['usuarioid'];

    if (!is_string($descricao) || $descricao == "") {
        $result = json_encode(array('mensagem'=>'Campo Tarefa não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $query = $pdo->prepare("SELECT * FROM tarefas WHERE descricao = :descricao AND usuarioid = :usuarioid");
    $query->bindValue(":descricao", $descricao);
    $query->bindValue(":usuarioid", $usuarioid);
    $query->execute();

    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if (@count($res) > 0) {
        $result = json_encode(array('mensagem'=>'Já existe uma tarefa com este nome!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $res = $pdo->prepare("INSERT INTO tarefas SET descricao = :descricao, situacao = :situacao, usuarioid = :usuarioid");
    $res->bindValue(":descricao", $descricao);
    $res->bindValue(":situacao", $situacao);
    $res->bindValue(":usuarioid", $usuarioid);
    $res->execute();

    $mensagem = "Salvo com sucesso!";
}

if ($requisicao == 'alterar') {
    $id         = $postjson['id'];
    $descricao  = $postjson['descricao'];
    $situacao   = $postjson['situacao'];
    $usuarioid  = $postjson['usuarioid'];

    if (!is_string($descricao) || $descricao == "") {
        $result = json_encode(array('mensagem'=>'Campo Tarefa não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $query = $pdo->prepare("SELECT * FROM tarefas WHERE descricao = :descricao AND id <> :id AND usuarioid = :usuarioid");
    $query->bindValue(":descricao", $descricao);
    $query->bindValue(":id", $id);
    $query->bindValue(":usuarioid", $usuarioid);
    $query->execute();

    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if (@count($res) > 0) {
        $result = json_encode(array('mensagem'=>'Já existe uma tarefa com este nome!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $res = $pdo->prepare("UPDATE tarefas SET descricao = :descricao, situacao = :situacao WHERE id = :id");
    $res->bindValue(":descricao", $descricao);
    $res->bindValue(":situacao", $situacao);
    $res->bindValue(":id", $id);
    $res->execute();

    $mensagem = "Alterado com sucesso!";
}

if ($requisicao == 'excluir') {
    $id = $postjson['id'];
    $usuarioid = $postjson['usuarioid'];

    $query = $pdo->prepare("SELECT * FROM tarefas WHERE id = :id AND usuarioid = :usuarioid");
    $query->bindValue(":id", $id);
    $query->bindValue(":usuarioid", $usuarioid);
    $query->execute();

    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if (@count($res) > 0 && $res['situacao'] == 'FINALIZADA') {
        $result = json_encode(array('mensagem'=>'Este tarefa não pode ser excluída!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $res = $pdo->query("DELETE FROM tarefas WHERE id = '$id'");

    $mensagem = "Excluido com sucesso!";
}

if ($requisicao == 'consultar') {
    $condicao = "%". $postjson['descricao'] ."%";
    $usuarioid = $postjson['usuarioid'];
    
    $query = $pdo->query("SELECT * FROM tarefas WHERE descricao LIKE '$condicao' AND usuarioid =  $usuarioid ORDER BY id DESC LIMIT $postjson[start], $postjson[limit]");

    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $total = @count($res);

    if (@count($res) <= 0) {
        $result = json_encode(array('mensagem'=>'Nenhuma tarefa encontrada!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    if ($total > 0) {
        for($i=0; $i<$total; $i++){
            foreach ($res[$i] as $key => $value){}

            $dados[] = array(
                'id'        => $res[$i]['id'],
                'descricao' => $res[$i]['descricao'],
                'situacao'  => $res[$i]['situacao'],
            );
        }

        $result = json_encode(array('sucesso'=> true, 'tarefas'=>$dados));
        
    } else {
        $result = json_encode(array('sucesso'=> false, 'tarefas'=>'0'));
    }

    echo $result;
    exit();
}

$result = json_encode(array('mensagem'=>$mensagem, 'sucesso'=> true));
echo $result;
?>