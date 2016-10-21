<?php
        if(intval($_POST['idgrupo']) && $idpergunta){
          if($conexao->query("INSERT INTO relgruposperg(idpergunta,idgrupo,ativo) VALUES(".intval($idpergunta).",".intval($_POST['idgrupo']).",1)" ))
            echo '<span class="aviso">Pergunta associada ao grupo</span>';
          else
            echo '<span class="aviso">Erro ao associar registro.</span>';
            
        }
        
?>