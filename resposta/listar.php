<?php
    require_once("../db.php");
    require_once('../lib/functions.inc.php');
    $sql = "SELECT idquest,titulo,descricao FROM questionarios  WHERE idquest = ". intval($_GET['id']);
    $conexao->conecta();
    $conexao->query($sql,true);

    if($conexao->num_rows<=0) die('Questionário inexistente');
    list($idquest,$titulo,$descricao) = $conexao->fetch_array();
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Lista de Respostas - <?= $titulo ?></title>
<link rel="stylesheet" type="text/css" href="../estilo.css" />
<meta content="" name="" />
</head>
<body>
<a style="float:right" href="../">Voltar para Questionários</a>
<h1>Lista de Respostas - <?= $titulo ?></h1>

<div id="toolbar"><a href="../questionario.php?id=<?= intval($_GET['id']) ?>" class="link_cadastrar">Preencher novo questionário</a></div>
<br>
<?php
  $conexao->conecta();
  $conexao->query("SELECT * FROM respostas WHERE idquest = ".intval($_GET['id']));
?>
Total: <?= $conexao->num_rows ?>
<table border="1">
<tr>
<th>ID</th>
<th>Usuário</th>
<th>Data</th>
<th>Opções</th>
</tr>
<?
  while($row = $conexao->fetch_array()){
?><tr>
<td><?= $row['idresposta'] ?></td> 
<td><?= $row['usuario'] ?></td> 
<td><?= MudaData($row['data']) ?></td>
<td>
<a href="resposta.php?id=<?= intval($row['idresposta']) ?>">Abrir</a>
|
<a href="resposta.php?id=<?= intval($row['idresposta']) ?>&amp;edit=1">Editar</a>
</td>
</tr>

<? 
  
  }

?>
</table>
<br>
<br>
<a href="../">Voltar para Questionários</a>
 

</body>
</html>
