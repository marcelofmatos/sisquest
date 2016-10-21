<?php
  include("../db.php");
  
  $conexao->conecta();
  
  $conexao->query("DELETE FROM relgruposperg WHERE idrel=".intval($_GET['id']));
  
  $conexao->desconecta();
  
  header('Location: listar.php');
  
?>