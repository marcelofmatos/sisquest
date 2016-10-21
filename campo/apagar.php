<?php
  include("../db.php");
  
  $conexao->conecta();
  
  $conexao->query("DELETE FROM campos WHERE idcampo=".intval($_GET['id']));
  
  $conexao->desconecta();
  
  header('Location: '.$_SERVER['HTTP_REFERER']);
  
?>
