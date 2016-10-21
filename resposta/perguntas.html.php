<table width="100%" border="0" cellspacing="0" cellpadding="3" class="perguntas">
<?
        
        #Perguntas
        $sqlPerg = "SELECT p.idpergunta,p.texto,p.ordem,p.idgrupo FROM perguntas p "
        ." WHERE p.idgrupo = ". intval($rowGrupo['idgrupo']);
        ;

        $qryPerg = mysql_query($sqlPerg);
        $numRows = mysql_num_rows($qryPerg);
        
        $even = true;
        $cssName = '';
        
        #if($numRows<=0) die('Sem perguntas');
        while($rowPerg = mysql_fetch_array($qryPerg)){
 
        if($even) 
            $cssName = " even"; 
        else 
            $cssName='';
        $even = !$even;

?>
   
    <tr class="<?= $cssName ?>">
      <td valign="top" width="20"><?= ++$pergCount ?></td>
      <td  valign="top" class="pergunta">
      <div id="pergunta_<?= $rowPerg['idpergunta'] ?>"><?= $rowPerg['texto'] ?></div>
       <table cellpadding="0" cellspacing="0" style="padding:0;margin:3px 0">
<?

            #Campos
            $sqlCampos = "SELECT idcampo,idpergunta,nome,tipo,rotulo,iddep,params FROM campos c "
            ." WHERE idpergunta = ". intval($rowPerg['idpergunta']);
            ;

            $qryCampos = mysql_query($sqlCampos);
            $numRows = mysql_num_rows($qryCampos);
            
            # if($numRows<=0) die('Sem campos');
            while($rowOpc=mysql_fetch_array($qryCampos)){
                
                $params['ID'] = getIdCampo($rowOpc['idcampo']);
                $params['TYPE'] = $rowOpc['tipo'];
                #$params = StringToArray($rowOpc['params']);
                #$params['NAME'] = getNomeCampo($rowOpc['idcampo'],$rowOpc['nome']);
                #$params['LABEL'] = $rowOpc['rotulo'];
                
                #if($rowOpc['iddep']) $params['DependentValidation']=getIdCampo($rowOpc['iddep']);

                switch($params['TYPE']){    
                    case "radio":
                    case "checkbox":
                    ?><tr>
                    <td width="5" align="right"><b><? $form->AddInputPart($params['ID']); ?></b></td>
                    <td><? $form->AddLabelPart(array("FOR"=>$params['ID'])); ?></td>
                    </tr>
                   <?

                        break;
                        
                    case "text":
                    default:
                        ?><tr>
                            <td align="right"><? $form->AddLabelPart(array("FOR"=>$params['ID'])); ?></td>
                            <td align="left"><b><? $form->AddInputPart($params['ID']); ?></b></td>
                        </tr>
                       <?
                    
                }

            } // FimWhile Opc
             
?>   
     </table>
    </td></tr>

<?
    } // FimForeach Perg
    
    $rowPerg = null;
    $rowOpc = null;
?> 
</table>