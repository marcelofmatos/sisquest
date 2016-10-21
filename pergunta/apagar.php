<?php
  include("../db.php");
  
  $conexao->conecta();
  
  $conexao->query("DELETE FROM perguntas WHERE idpergunta=".intval($_GET['id']));
  
  $conexao->desconecta();
  
  header('Location: '.$_SERVER['HTTP_REFERER']);
  
?>
