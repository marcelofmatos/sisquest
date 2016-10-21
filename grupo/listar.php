<?php
  require_once("../db.php");
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Lista de Grupo de Questionários</title>
<link rel="stylesheet" type="text/css" href="estilo.css" />
<meta content="" name="" />
</head>
<body>
<h1>Lista de Grupo de Questionários</h1>

<div id="toolbar"><a href="cadastro.php" class="link_cadastrar">Cadastrar</a></div>

<table border="1">
<tr>
<th>Título</th>
<th>Descrição</th>
<th>Opções</th>
</tr>

<?php
  $conexao->conecta();
  $conexao->query("SELECT * FROM grupos");
  
  while($row = $conexao->fetch_array()){
  
?><tr>
<td><?= $row['titulo'] ?></td>
<td><?= $row['descricao'] ?></td>
<td><a href="cadastro.php?id=<?= intval($row['idgrupo']) ?>">Alterar</a> | <a href="apagar.php?id=<?= intval($row['idgrupo']) ?>" onclick="return confirm('Esta ação apagará o seguinte registro:\n\n <?= $row['titulo'] ?>\n\nTem certeza?')">Apagar</a></td>
</tr>

<? 
  
  }

?>
</table>
 

</body>
</html>
