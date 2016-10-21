<?php
  include("../db.php");
  
  $conexao->conecta();
  
  $conexao->query("DELETE FROM questionarios WHERE idquest=".intval($_GET['id']));
  
  $conexao->desconecta();
  
  header('Location: listar.php');
  
?>
