<?php
    require_once('../lib/functions.inc.php');
    require_once('../db.php');
    $conexao->conecta();
    
        if(intval($_POST['idpergunta'])){
          if($conexao->query("UPDATE perguntas SET texto='".$_POST['texto']."',iddep=".intval($_POST['iddep'])." WHERE idpergunta=".intval($_POST['idpergunta']) ))
            echo $_POST['texto'];
          else
            echo '<span class="aviso">Erro ao alterar registro.</span>';
          $idpergunta = $_POST['idpergunta'];  
      }else{
          if( $conexao->query("INSERT INTO perguntas(texto,idgrupo) VALUES('".$_POST['texto']."',".intval($_POST['idgrupo']).")" ))
            echo $_POST['texto'];
          else
            echo '<span class="aviso" name="aviso">Erro ao cadastrar.</span>';
      }
?>
