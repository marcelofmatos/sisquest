<?php
  require_once("db.php");
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Lista de Questionários</title>
<link rel="stylesheet" type="text/css" href="estilo.css" />
<meta content="" name="" />
</head>
<body>
<h1>Lista de Questionários</h1>


<table border="1">
<tr>
<th>Título</th>
<th>Descrição</th>
<th>Opções</th>
</tr>

<?php
  $conexao->conecta();
  $conexao->query("SELECT * FROM questionarios");
  
  while($row = $conexao->fetch_array()){
  
?><tr>
<td><a href="questionario.php?id=<?= intval($row['idquest']) ?>"><?= $row['titulo'] ?></a></td>
<td><?= $row['descricao'] ?></td>
<td>
<a href="resposta/listar.php?id=<?= intval($row['idquest']) ?>">Ver respostas</a>
|
<a href="resposta/resultadosListaForm.php?idq=<?= intval($row['idquest']) ?>">Lista de respostas por pergunta</a>
|
<a href="resposta/resultadosForm.php?idq=<?= intval($row['idquest']) ?>">Cruzar perguntas</a>
|
<a href="resposta/graficoForm.php?idq=<?= intval($row['idquest']) ?>">Gráfico</a>
</td>
</tr>

<?php
  
  }

?>
</table>
 

</body>
</html>
