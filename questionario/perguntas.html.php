<table width="100%" border="0" cellspacing="0" cellpadding="3" class="perguntas">
<?php
        
        #Perguntas
        $sqlPerg = "SELECT p.idpergunta,p.texto,p.ordem,p.idgrupo,p.idquest,p.identificador FROM perguntas p "
        ." WHERE p.idgrupo = ". intval($rowGrupo['idgrupo'])
        ." ORDER BY p.ordem"
        ;

        $qryPerg = mysql_query($sqlPerg);
        $numRows = mysql_num_rows($qryPerg);
        
        $even = false;
        $cssName = '';
        
        #if($numRows<=0) die('Sem perguntas');
        while($rowPerg = mysql_fetch_array($qryPerg)){
        
        if($identificador != $rowPerg['identificador'] && $rowPerg['identificador']!=''){
            $identificador = $rowPerg['identificador'];
            $even = !$even;
        }
                                                            
        if($even) 
            $cssName = " even"; 
        else 
            $cssName='';

?>
   
    <tr class="<?= $cssName ?>">
      <td valign="top" width="20" style="white-space: nowrap"><a name="perg<?= $rowPerg['idpergunta'] ?>"><?= $rowPerg['identificador'] ?></a></td>
      <td  valign="top" class="pergunta">
      <div class="edit" style="float:right">
      <a id="btnEdit_<?= $rowPerg['idpergunta'] ?>" class="edit" href="../pergunta/cadastro.php?id=<?= $rowPerg['idpergunta'] ?>&amp;idq=<?= $rowPerg['idquest'] ?>">Editar</a>
      |
      <a class="edit" href="../campo/cadastro.php?idp=<?= $rowPerg['idpergunta'] ?>&amp;idq=<?= $rowPerg['idquest'] ?>">Nova opção</a>
      |
      <a id="btnEdit_<?= $rowPerg['idpergunta'] ?>" class="edit" href="../pergunta/apagar.php?id=<?= $rowPerg['idpergunta'] ?>" onclick="return confirm('Tem certeza??')">Apagar</a>
      </div>
      <div id="pergunta_<?= $rowPerg['idpergunta'] ?>"><?= $rowPerg['texto'] ?></div>
       <table class="resposta" cellpadding="0" cellspacing="0" style="padding:0;margin:3px 0">
<?php

            #Campos
            $sqlCampos = "SELECT idcampo,nome,tipo,rotulo,iddep,params FROM campos c "
            ." WHERE idpergunta = ". intval($rowPerg['idpergunta']) 
            ." ORDER BY c.ordem"
            ;
            $qryCampos = mysql_query($sqlCampos);
            $numRows = mysql_num_rows($qryCampos);
            
            # if($numRows<=0) die('Sem campos');

            while($rowOpc=mysql_fetch_array($qryCampos)){
                $idp = $rowPerg['idpergunta'];
                
                $params = StringToArray($rowOpc['params']);
                $params['ID'] = getIdCampo($rowOpc['idcampo']);
                $params['TYPE'] = $rowOpc['tipo'];
                
                
                $lnkOpt = '
                <div class="edit" style="white-space:nowrap;display:inline">
                <a id="btnCampoEdit_'. $rowOpc['idcampo'] .'" class="edit" href="../campo/cadastro.php?id='. $rowOpc['idcampo'] .'&idq='. $idq .'&idp='. $idp .'">Editar</a>
                |
                <a id="btnCampoEdit_'. $rowOpc['idcampo'] .'" class="edit" href="../campo/cadastro.php?id='. $rowOpc['idcampo'] .'&idq='. $idq .'&cp=1">Copiar</a>
                |
                <a id="btnDel_'. $rowOpc['idcampo'] .'" class="edit" href="../campo/apagar.php?id='. $rowOpc['idcampo'] .'" onclick="return confirm(\'Tem certeza??\')">Apagar</a>
                </div>
                ';
                
                switch($params['TYPE']){    
                    case "radio":
                    case "checkbox":
                    ?><tr>
                    <td width="5" align="right"><? $form->AddInputPart($params['ID']); ?></td>
                    <td><? $form->AddLabelPart(array("FOR"=>$params['ID'])); ?></td>
                    <td nowrap="nowrap"><?= $lnkOpt ?></td>
                    </tr>
                   <?

                        break;
                        
                    case "text":
                    default:
                        ?><tr>
                            <td align="left"><? $form->AddLabelPart(array("FOR"=>$params['ID'])); ?><? $form->AddInputPart($params['ID']); ?></td>
                            <td align="left"><?= $lnkOpt ?></td>
                        </tr>
                       <?
                    
                }

            } // FimWhile rowOpc
             
?>   
     </table>
    </td></tr>

<?php
    } // FimForeach Perg
    
    $rowPerg = null;
    $rowOpc = null;
?> 
</table>
<a href="../pergunta/cadastro.php?idg=<?= $rowGrupo['idgrupo'] ?>&amp;idq=<?= $_GET['id'] ?>" style="text-algn:center">Nova questão</a>
