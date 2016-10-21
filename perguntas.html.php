<table width="100%" border="0" cellspacing="0" cellpadding="3" class="perguntas">
<?
        
        #Perguntas
        $sqlPerg = "SELECT p.idpergunta,p.texto,p.ordem,p.idgrupo,p.identificador FROM perguntas p "
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
      <td valign="top" width="20" class="borda1"><?= $rowPerg['identificador'] ?></td>
      <td  valign="top" class="pergunta borda1">
      <div id="pergunta_<?= $rowPerg['idpergunta'] ?>"><?= $rowPerg['texto'] ?></div>
       <table cellpadding="0" cellspacing="0" style="padding:0;margin:3px 0">
<?

            #Campos
            $sqlCampos = "SELECT c.idcampo,c.idpergunta,c.nome,c.tipo,c.rotulo,c.iddep,c.params,p.identificador FROM campos c, perguntas p"
            ." WHERE c.idpergunta = p.idpergunta AND c.idpergunta = ". intval($rowPerg['idpergunta'])
            ." ORDER BY c.ordem"
            ;

            $qryCampos = mysql_query($sqlCampos);
            $numRows = mysql_num_rows($qryCampos);
            
            # if($numRows<=0) die('Sem campos');
            while($rowOpc=mysql_fetch_array($qryCampos)){
                
				
                $params['ID'] = getIdCampo($rowOpc['idcampo']);
                $params['TYPE'] = $rowOpc['tipo'];

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
                    case "textarea":
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
