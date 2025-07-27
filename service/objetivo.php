<?php 

require_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$requisicao = $postjson['requisicao'];

$mensagem = "";

if ($requisicao == 'incluir') {
    $descricao     = $postjson['descricao'];
    $dataconclusao = $postjson['dataConclusao'];
    $situacao      = $postjson['situacao'];
    $usuarioid     = $postjson['usuarioid'];
    $prioridade    = $postjson['prioridade'];

    //$dataconclusao = date('Y-m-d', strtotime($data));

    if (!is_string($descricao) || $descricao == "") {
        $result = json_encode(array('mensagem'=>'Campo Objetivo não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }
    
    if ($dataconclusao == "") {
        $result = json_encode(array('mensagem'=>'Campo Data Conclusão não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }
    
    if (!is_string($situacao) || $situacao == "") {
        $result = json_encode(array('mensagem'=>'Campo Situação não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $query = $pdo->prepare("SELECT * FROM objetivos WHERE descricao = :descricao AND usuarioid = :usuarioid");
    $query->bindValue(":descricao", $descricao);
    $query->bindValue(":usuarioid", $usuarioid);
    $query->execute();

    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if (@count($res) > 0) {
        $result = json_encode(array('mensagem'=>'Já existe uma objetivo com este nome!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $res = $pdo->prepare("INSERT INTO objetivos SET descricao = :descricao, dataconclusao = :dataconclusao, prioridade = :prioridade, situacao = :situacao, usuarioid = :usuarioid");
    $res->bindValue(":descricao", $descricao);
    $res->bindValue(":dataconclusao", $dataconclusao);
    $res->bindValue(":prioridade", $prioridade);
    $res->bindValue(":situacao", $situacao);
    $res->bindValue(":usuarioid", $usuarioid);
    $res->execute();

    $mensagem = "Salvo com sucesso!";
}

if ($requisicao == 'alterar') {
    $id            = $postjson['id'];
    $descricao     = $postjson['descricao'];
    $dataconclusao = $postjson['dataConclusao'];
    $prioridade    = $postjson['prioridade'];
    $situacao      = $postjson['situacao'];
    $usuarioid     = $postjson['usuarioid'];

    if (!is_string($descricao) || $descricao == "") {
        $result = json_encode(array('mensagem'=>'Campo Objetivo não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }
    
    if ($dataconclusao == "") {
        $result = json_encode(array('mensagem'=>'Campo Data Conclusão não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }
    
    if (!is_string($situacao) || $situacao == "") {
        $result = json_encode(array('mensagem'=>'Campo Situação não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $query = $pdo->prepare("SELECT * FROM objetivos WHERE descricao = :descricao AND id <> :id AND usuarioid = :usuarioid");
    $query->bindValue(":descricao", $descricao);
    $query->bindValue(":id", $id);
    $query->bindValue(":usuarioid", $usuarioid);
    $query->execute();

    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if (@count($res) > 0) {
        $result = json_encode(array('mensagem'=>'Já existe uma objetivo com este nome!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $res = $pdo->prepare("UPDATE objetivos SET descricao = :descricao, dataconclusao = :dataconclusao, prioridade = :prioridade, situacao = :situacao WHERE id = :id");
    $res->bindValue(":descricao", $descricao);
    $res->bindValue(":dataconclusao", $dataconclusao);
    $res->bindValue(":prioridade", $prioridade);
    $res->bindValue(":situacao", $situacao);
    $res->bindValue(":id", $id);
    $res->execute();

    $mensagem = "Alterado com sucesso!";
}

if ($requisicao == 'excluir') {
    $id = $postjson['id'];
    $usuarioid = $postjson['usuarioid'];

    $query = $pdo->prepare("SELECT * FROM objetivos WHERE id = :id AND usuarioid = :usuarioid");
    $query->bindValue(":id", $id);
    $query->bindValue(":usuarioid", $usuarioid);
    $query->execute();

    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if (@count($res) > 0 && $res['situacao'] == 'CONCLUIDO') {
        $result = json_encode(array('mensagem'=>'Este objetivo não pode ser excluído!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $res = $pdo->query("DELETE FROM objetivos WHERE id = '$id'");

    $mensagem = "Excluido com sucesso!";
}

if ($requisicao == 'consultar') {
    $condicao = "%". $postjson['descricao'] ."%";
    $usuarioid = $postjson['usuarioid'];
    
    $query = $pdo->query("SELECT * FROM objetivos WHERE descricao LIKE '$condicao' AND usuarioid = $usuarioid ORDER BY id DESC LIMIT $postjson[start], $postjson[limit]");

    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $total = @count($res);

    if (@count($res) <= 0) {
        $result = json_encode(array('mensagem'=>'Nenhum objetivo encontrado!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    if ($total > 0) {
        for($i=0; $i<$total; $i++){
            foreach ($res[$i] as $key => $value){}

            $dados[] = array(
                'id'            => $res[$i]['id'],
                'descricao'     => $res[$i]['descricao'],
                'dataConclusao' => $res[$i]['dataconclusao'],
                'situacao'      => $res[$i]['situacao'],
                'prioridade'    => $res[$i]['prioridade'],
            );
        }

        $result = json_encode(array('sucesso'=> true, 'objetivos'=>$dados));
        
    } else {
        $result = json_encode(array('sucesso'=> false, 'objetivos'=>'0'));
    }

    echo $result;
    exit();
}

if ($requisicao == 'mass') {
    $usuarioid = $postjson['usuarioid'];
    
	$query = $pdo->query("SELECT id, descricao, prioridade FROM objetivos WHERE situacao = 'ABERTO'");

    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $total = @count($res);

    if (@count($res) <= 0) {
        $result = json_encode(array('mensagem'=>'Sem dados para exibir!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    if ($total > 0) {
        for($i=0; $i<$total; $i++){
            foreach ($res[$i] as $key => $value){}

            $dados[] = array(
                'id' => $res[$i]['id'],
                'descricao' => $res[$i]['descricao'],
				'prioridade' => $res[$i]['prioridade']
             );
        }

        $result = json_encode(array('sucesso'=> true, 'objetivos'=>$dados));
        
    } else {
        $result = json_encode(array('sucesso'=> false, 'objetivos'=>'0'));
    }

    echo $result;
    exit();
}

$result = json_encode(array('mensagem'=>$mensagem, 'sucesso'=> true));
echo $result;
?>