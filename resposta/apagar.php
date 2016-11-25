<?php
  include("../db.php");
  
  $conexao->conecta();
  
  $conexao->query("DELETE FROM respostas WHERE idresposta=".intval($_GET['id']));
  
  $conexao->desconecta();
  
  header('Location: /');
  
?>
