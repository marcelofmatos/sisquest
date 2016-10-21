<?php 
        if(intval($_POST['idpergunta'])){
          if($conexao->query("UPDATE perguntas SET texto='".$_POST['texto']."',iddep=".intval($_POST['iddep']).",idgrupo=".intval($_POST['idgrupo'])." WHERE idpergunta=".intval($_POST['idpergunta']) )){
            echo '<span class="aviso">Registro alterado</span><script>opener.reload();';
        }else
            echo '<span class="aviso">Erro ao alterar registro.</span>';
          $idpergunta = $_POST['idpergunta'];  
      }else{
          if( $conexao->query("INSERT INTO perguntas(texto,idgrupo) VALUES('".$_POST['texto']."',".intval($_POST['idgrupo']).")" ))
            echo '<span class="aviso" name="aviso">Registro cadastrado</span>';
          else
            echo '<span class="aviso" name="aviso">Erro ao cadastrar.</span>';
          
          $idpergunta = mysql_insert_id();  
            
      }
?>
