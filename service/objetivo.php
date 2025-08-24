<?php 

require_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$requisicao = $postjson['requisicao'];
$mensagem = "";

// Use the user ID from the decoded JWT token
$id_usuario_logado = $dados_usuario_token->id;

if ($requisicao == 'incluir') {
    $descricao     = $postjson['descricao'];
    $dataconclusao = $postjson['dataConclusao'];
    $situacao      = $postjson['situacao'];
    $prioridade    = $postjson['prioridade'];

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
    $query->bindValue(":usuarioid", $id_usuario_logado);
    $query->execute();
    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if (@count($res) > 0) {
        $result = json_encode(array('mensagem'=>'Já existe um objetivo com este nome!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $res = $pdo->prepare("INSERT INTO objetivos SET descricao = :descricao, dataconclusao = :dataconclusao, prioridade = :prioridade, situacao = :situacao, usuarioid = :usuarioid");
    $res->bindValue(":descricao", $descricao);
    $res->bindValue(":dataconclusao", $dataconclusao);
    $res->bindValue(":prioridade", $prioridade);
    $res->bindValue(":situacao", $situacao);
    $res->bindValue(":usuarioid", $id_usuario_logado);
    $res->execute();

    $mensagem = "Salvo com sucesso!";
}

if ($requisicao == 'alterar') {
    $id            = $postjson['id'];
    $descricao     = $postjson['descricao'];
    $dataconclusao = $postjson['dataConclusao'];
    $prioridade    = $postjson['prioridade'];
    $situacao      = $postjson['situacao'];

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
    $query->bindValue(":usuarioid", $id_usuario_logado);
    $query->execute();
    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if (@count($res) > 0) {
        $result = json_encode(array('mensagem'=>'Já existe um objetivo com este nome!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    // Security Fix: Ensure user can only update their own objectives
    $res = $pdo->prepare("UPDATE objetivos SET descricao = :descricao, dataconclusao = :dataconclusao, prioridade = :prioridade, situacao = :situacao WHERE id = :id AND usuarioid = :usuarioid");
    $res->bindValue(":descricao", $descricao);
    $res->bindValue(":dataconclusao", $dataconclusao);
    $res->bindValue(":prioridade", $prioridade);
    $res->bindValue(":situacao", "ABERTO");
    $res->bindValue(":id", $id);
    $res->bindValue(":usuarioid", $id_usuario_logado);
    $res->execute();

    if($res->rowCount() > 0){
        $mensagem = "Alterado com sucesso!";
    } else {
        $mensagem = "O objetivo não foi encontrado ou você não tem permissão para alterá-lo.";
    }
}

if ($requisicao == 'excluir') {
    $id = $postjson['id'];

    // Security Fix: Use prepared statements and check ownership before deleting
    $query = $pdo->prepare("SELECT situacao FROM objetivos WHERE id = :id AND usuarioid = :usuarioid");
    $query->bindValue(":id", $id);
    $query->bindValue(":usuarioid", $id_usuario_logado);
    $query->execute();
    $res = $query->fetch(PDO::FETCH_ASSOC);

    if (!$res) {
        $result = json_encode(array('mensagem'=>'Objetivo não encontrado ou você não tem permissão para excluí-lo.', 'sucesso'=> false));
        echo $result;
        exit();
    }

    if ($res['situacao'] == 'CONCLUIDO') {
        $result = json_encode(array('mensagem'=>'Este objetivo já foi concluído e não pode ser excluído!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $res = $pdo->prepare("DELETE FROM objetivos WHERE id = :id AND usuarioid = :usuarioid");
    $res->bindValue(":id", $id);
    $res->bindValue(":usuarioid", $id_usuario_logado);
    $res->execute();

    $mensagem = "Excluído com sucesso!";
}

if ($requisicao == 'consultar') {
    $condicao = "%". $postjson['descricao'] ."%";
    $start = (int) $postjson['start'];
    $limit = (int) $postjson['limit'];
    
    // Security fix: Use prepared statements to prevent SQL injection
    $query = $pdo->prepare("SELECT * FROM objetivos WHERE descricao LIKE :condicao AND usuarioid = :usuarioid ORDER BY id DESC LIMIT :start, :limit");
    $query->bindValue(":condicao", $condicao);
    $query->bindValue(":usuarioid", $id_usuario_logado);
    $query->bindValue(":start", $start, PDO::PARAM_INT);
    $query->bindValue(":limit", $limit, PDO::PARAM_INT);
    $query->execute();

    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $total = @count($res);

    if ($total <= 0) {
        $result = json_encode(array('mensagem'=>'Nenhum objetivo encontrado!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $dados = [];
    foreach ($res as $item) {
        $dados[] = array(
            'id'            => $item['id'],
            'descricao'     => $item['descricao'],
            'dataConclusao' => $item['dataconclusao'],
            'situacao'      => $item['situacao'],
            'prioridade'    => $item['prioridade'],
        );
    }

    $result = json_encode(array('sucesso'=> true, 'objetivos'=>$dados));
    echo $result;
    exit();
}

if ($requisicao == 'mass') {
    // Correção de segurança: buscar objetivos apenas para o usuário logado
    $query = $pdo->prepare("SELECT id, descricao, prioridade FROM objetivos WHERE situacao = 'ABERTO' AND usuarioid = :usuarioid AND prioridade <= 3");
    $query->bindValue(":usuarioid", $id_usuario_logado);
    $query->execute();

    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $total = @count($res);

    if ($total <= 0) {
        $result = json_encode(array('mensagem'=>'Sem dados para exibir!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $dados = [];
    foreach ($res as $item) {
        $dados[] = array(
            'id' => $item['id'],
            'descricao' => $item['descricao'],
            'prioridade' => $item['prioridade']
        );
    }

    $result = json_encode(array('sucesso'=> true, 'objetivos'=>$dados));
    echo $result;
    exit();
}

$result = json_encode(array('mensagem'=>$mensagem, 'sucesso'=> true));
echo $result;
?>