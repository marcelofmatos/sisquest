<?php
  include("../db.php");
  
  $conexao->conecta();
  
  $conexao->begin();
  
  $conexao->query("DELETE FROM dados WHERE idresposta=".intval($_GET['id']));
  $conexao->query("DELETE FROM respostas WHERE idresposta=".intval($_GET['id']));

  $conexao->commit();
  
  $conexao->desconecta();
  
  header('Location: /');
  
?>
