<?php
    require_once('functions.inc.php');
    require_once('db.php');
    $conexao->conecta();
    
  parse_str($_POST['data']);
   for ($i = 0; $i < count($list_1); $i++) {  
     $sql = "UPDATE perguntas SET ordem = ".($i+1)." WHERE idpergunta = ".$list_1[$i];
     $conexao->query($sql,true);  
   } 
?>
