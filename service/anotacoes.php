<?php 

require_once('../conexao.php');

$postjson = json_decode(file_get_contents('php://input'), true);

$requisicao = $postjson['requisicao'];

$mensagem = "";

if ($requisicao == 'incluir') {
    $descricao     = $postjson['descricao'];
    $usuarioid     = $postjson['usuarioid'];

    if (!is_string($descricao) || $descricao == "") {
        $result = json_encode(array('mensagem'=>'Campo Descrição não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $res = $pdo->prepare("INSERT INTO anotacoes SET descricao = :descricao, usuarioid = :usuarioid");
    $res->bindValue(":descricao", $descricao);
    $res->bindValue(":usuarioid", $usuarioid);
    $res->execute();

    $mensagem = "Salvo com sucesso!";
}

if ($requisicao == 'alterar') {
    $id            = $postjson['id'];
    $descricao     = $postjson['descricao'];
    $usuarioid     = $postjson['usuarioid'];

    if (!is_string($descricao) || $descricao == "") {
        $result = json_encode(array('mensagem'=>'Campo Descrição não preenchido!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    $res = $pdo->prepare("UPDATE anotacoes SET descricao = :descricao WHERE id = :id");
    $res->bindValue(":descricao", $descricao);
    $res->bindValue(":id", $id);
    $res->execute();

    $mensagem = "Alterado com sucesso!";
}

if ($requisicao == 'consultar') {
    $condicao = "%". $postjson['descricao'] ."%";
    $usuarioid = $postjson['usuarioid'];
    
    $query = $pdo->query("SELECT * FROM anotacoes WHERE descricao LIKE '$condicao' AND usuarioid = $usuarioid ORDER BY id DESC LIMIT $postjson[start], $postjson[limit]");

    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $total = @count($res);

    if (@count($res) <= 0) {
        $result = json_encode(array('mensagem'=>'Nenhuma anotação encontrada!', 'sucesso'=> false));
        echo $result;
        exit();
    }

    if ($total > 0) {
        for($i=0; $i<$total; $i++){
            foreach ($res[$i] as $key => $value){}

            $dados[] = array(
                'id'        => $res[$i]['id'],
                'descricao' => $res[$i]['descricao'],
            );
        }

        $result = json_encode(array('sucesso'=> true, 'anotacoes'=>$dados));
        
    } else {
        $result = json_encode(array('sucesso'=> false, 'anotacoes'=>'0'));
    }

    echo $result;
    exit();
}

$result = json_encode(array('mensagem'=>$mensagem, 'sucesso'=> true));
echo $result;

?>