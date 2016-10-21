<?php
  require_once("../db.php");
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Lista de Perguntas</title>
<link rel="stylesheet" type="text/css" href="estilo.css" />
<meta content="" name="" />
</head>
<body>
<h1>Lista de Perguntas</h1>

<div id="toolbar"><a href="cadastro.php" class="link_cadastrar">Cadastrar</a></div>

<table border="1">
<tr>
<th>Descrição</th>
<th>Opções</th>
</tr>

<?php
  $conexao->conecta();
  $conexao->query("SELECT * FROM perguntas");
  
  while($row = $conexao->fetch_array()){
  
?><tr>
<td><?= $row['texto'] ?></td>
<td><a href="cadastro.php?id=<?= intval($row['idpergunta']) ?>">Alterar</a> | <a href="../campo/cadastro.php?p=<?= intval($row['idpergunta']) ?>">Campos</a> | <a href="apagar.php?id=<?= intval($row['idpergunta']) ?>" onclick="return confirm('Esta ação apagará o seguinte registro:\n\n <?= $row['titulo'] ?>\n\nTem certeza?')">Apagar</a></td>
</tr>

<? 
  
  }

?>
</table>
 

</body>
</html>
