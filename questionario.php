<?
    require("util/formsgeneration/forms.php");
    require_once('lib/functions.inc.php');
    require_once('db.php');
    
function salvaForm($inputs){
    global $conexao,$avisos;

    
    if( $conexao->query( "INSERT INTO respostas(idquest,usuario,data) VALUES ('".intval($_POST['idquest'])."','".$_SERVER['PHP_AUTH_USER']."',NOW())" ) ) {
        $idresposta = mysql_insert_id();
    } else {
        $avisos .= 'Erro ao cadastrar questionário.';
        return;
    }
    
    
    $sql  = "INSERT INTO dados(idresposta,idpergunta,idcampo,valor) VALUES ";
      
    foreach($inputs as $myinput){
                        
        #exclui alguns campos
        switch($myinput['NAME']){
             case "doit":  
             case "button_submit":
             case "idquest":
                continue 2;
        }
       
        #exclui campos não marcados ou submits
        switch($myinput['TYPE']){
             case "submit":  
                continue 2;
             case "radio":
             case "checkbox":
                if(!$myinput['CHECKED']) 
                    continue 2;           
        }

        
        
        eregi('resposta_([0-9]+)',$myinput['NAME'],$matches);
        $idpergunta = intval($matches[1]);
        
        eregi('campo_([0-9]+)',$myinput['ID'],$matches);
        $idcampo = intval($matches[1]);
        
        $sql .= "('".$idresposta."','".$idpergunta."','".$idcampo."','".trim($myinput['VALUE'])."'),";
                
    }
    $sql = eregi_replace(',$','',$sql);
    
          if( $conexao->query( $sql ) ){
                $avisos .= 'Questionário cadastrado com o número <br><b>'. $idresposta .'</b><br><a href="resposta/listar.php?id='.intval($_GET['id']).'">Visualizar respostas</a>';
              }else{
                $avisos .= 'Erro ao cadastrar questionário.';
	}
    
}

if(!isSet($_GET['id'])) die(); 
     
    $sql = "SELECT idquest,titulo,descricao FROM questionarios  WHERE idquest = ". intval($_GET['id']);

    $conexao->conecta();
    $conexao->query($sql,true);
    
    if($conexao->num_rows()<=0) die('Questionário inexistente');
    list($idquest,$titulo) = $conexao->fetch_array();
    
    
# Form

    $form=new form_class;
    
    #Incluir estes parametros no Questionarios
    $form->NAME="q_form";
    $form->METHOD="POST";
    $form->ACTION="";
    #$form->debug="trigger_error";
    $form->ResubmitConfirmMessage= "Deseja mandar as informações novamente?";
    $form->OutputPasswordValues=1;
    $form->OptionsSeparator="<br />\n";
    $form->ShowAllErrors=1;
    $form->InvalidCLASS='invalid';
    $form->ErrorMessagePrefix="- ";
    $form->ErrorMessageSuffix="";
    
    $form->AddInput(array(
        "TYPE"=>"hidden",
        "NAME"=>"idquest",
        "VALUE"=>intval($_GET['id'])
    ));
    
    $form->AddInput(array(
        "TYPE"=>"hidden",
        "NAME"=>"doit",
        "VALUE"=>1
    ));
    
    $form->AddInput(array(
        "TYPE"=>"submit",
        "ID"=>"button_submit",
        "VALUE"=>"Salvar dados",
        "ACCESSKEY"=>"c"
    ));


        
    #Grupos
    $sqlGrupos = "SELECT g.idgrupo,g.titulo,g.descricao,g.ordem FROM grupos g "
    ." WHERE g.idquest = ". intval($_GET['id']);
    ;

    $conexao->query($sqlGrupos,true);

    while($rowGrupo = $conexao->fetch_array()){
        $grupos[] = $rowGrupo;

        
            #Campos
            $sqlCampos = "SELECT c.idcampo,c.idpergunta,c.nome,c.tipo,c.rotulo,c.valor,c.iddep,c.params FROM campos c, perguntas p "
            ." WHERE c.idpergunta=p.idpergunta AND p.idgrupo = ". intval($rowGrupo['idgrupo']);
            ;
            $qryCampos = mysql_query($sqlCampos);
            $numRows = mysql_num_rows($qryCampos);
            
            while($rowOpc= mysql_fetch_array($qryCampos)){
                
                $params = StringToArray($rowOpc['params']);
                $params['ID'] = getIdCampo($rowOpc['idcampo']);
                $params['TYPE'] = $rowOpc['tipo'];
                #$params['NAME'] = $rowOpc['nome'];
                $params['NAME'] = getNomeCampo($rowOpc['idpergunta'],$rowOpc['nome']);
                $params['LABEL'] = ($rowOpc['rotulo']!='') ? $rowOpc['rotulo'] : '&nbsp;';
				
				if( !empty( $rowOpc['valor'] ) ) $params['VALUE'] = $rowOpc['valor'];
				if( ($params['TYPE'] == "radio" || $params['TYPE'] == "checkbox") && empty( $rowOpc['ReadOnlyMark'] ) ) $params['ReadOnlyMark'] = "[X]";
				
                
                # Usar o ID do campo caso seja opção de marcar
                #switch($params['TYPE']){
                #    case "radio":
                #    case "checkbox":
                #        $params['VALUE'] = $rowOpc['idcampo'];
                #}
                
				if(!empty($params['ValidationErrorMessage']) && !empty($rowOpc['identificador'])) $params['ValidationErrorMessage'] = "Resposta da Questão ". $rowOpc['identificador'] ." está vazia ou é inválida";
                if($rowOpc['iddep']) $params['DependentValidation']=getIdCampo($rowOpc['iddep']);

                $form->AddInput( $params );


            } // FimWhile Opc 
        
        
    }

    
    $form->LoadInputValues($form->WasSubmitted("doit"));
    
    $verify=array();
    
    if($form->WasSubmitted("doit")){

        if(($error_message=$form->Validate($verify))==""){

            $doit=1;
            # Salva dados
            salvaForm($form->inputs);

        }else{

            $doit=0;
            $error_message=nl2br(HtmlSpecialChars($error_message));
        }
        
    }else{

        $error_message="";
        $doit=0;
    }
    
    
  if($doit){
      $form->ReadOnly=1;
  }

    if(!$doit){
        if(strlen($error_message)){

            Reset($verify);
            $focus=Key($verify);
            $form->ConnectFormToInput($focus, 'ONLOAD', 'Focus', array());
        }
    }

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?= $titulo ?></title>
<script>
this.name='questionario';
</script>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="estilo.css" rel="stylesheet">
<script>
var wEdit = null;
var reload = null;
function openWindow(){
    wEdit = window.open('','wEdit','menu=no,resizable=yes,height=400,width=500');
    wEdit.onblur=function(){
        reload = setTimeout("window.location.href=window.location.href;",1000)
    };
}

function mostra(id,val) {
    var obj = document.getElementById(id);
    if(val == 1) {
        obj.style.display = "block"
    } else {
        obj.style.display = "none"
    }
}
</script>
</head>
<? $onload = HtmlSpecialChars($form->PageLoad()); ?>
<body bgcolor="#FFFFFF" onload="<?= $onload ?>">
<noscript>Javascript não está funcionando. Este sistema não funcionará corretamente.</noscript>
<div id="debug"></div>
<div id="aviso"></div>
<center><h1><?= $titulo ?></h1></center>
<?php if(!empty($avisos)): ?>
<span class="aviso" name="aviso"><?= $avisos ?></span>
<?php endif; ?>
<br>
<div align="left" style="width:50%;margin:auto">
<?php

/*
 * Compose the form output by including a HTML form template with PHP code
 * interleaaved with calls to insert form input field
 * parts in the layout HTML.
 */

	$form->StartLayoutCapture();
	$perguntas_template="perguntas.html.php";
    
	require("grupos.html.php");

    $form->AddInputPart("doit");     
    $form->AddInputPart("idquest");
?>
    <div align="center">
    <? $form->AddInputPart("button_submit"); ?>
    </div>
<?php
   
    $form->EndLayoutCapture();

/*
 * Output the form using the function named Output.
 */
	$form->DisplayOutput();
?>
</div>
</body>
</html>
