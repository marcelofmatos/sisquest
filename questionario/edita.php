<?
    require("../util/formsgeneration/forms.php");
    require_once('../lib/functions.inc.php');
    require_once('../db.php');
    
    $idq = intval($_GET['id']);

if(!isSet($_GET['id'])) die(); 
     
    $sql = "SELECT idquest,titulo,descricao FROM questionarios "
    ." WHERE idquest = ". intval($_GET['id']);

    ;
    $conexao->conecta();
    $conexao->query($sql,true);
    
    if($conexao->num_rows<=0) die('Questionário inexistente');
    list($idquest,$titulo) = $conexao->fetch_array();
    
    
# Form

    $form=new form_class;
    
    #Incluir estes parametros no Questionarios
    $form->NAME="q_form";
    $form->METHOD="POST";
    $form->ACTION="";
    $form->debug="trigger_error";
    $form->ResubmitConfirmMessage= "Deseja mandar as informações novamente?";
    $form->OutputPasswordValues=1;
    $form->OptionsSeparator="<br />\n";
    $form->ShowAllErrors=1;
    $form->InvalidCLASS='invalid';
    $form->ErrorMessagePrefix="- ";
    $form->ErrorMessageSuffix="";

    $form->AddInput(array(
        "TYPE"=>"submit",
        "ID"=>"button_submit",
        "VALUE"=>"Salvar dados",
        "ACCESSKEY"=>"c"
    ));
    $form->AddInput(array(
        "TYPE"=>"hidden",
        "NAME"=>"doit",
        "VALUE"=>1
    ));
        
    #Grupos
    $sqlGrupos = "SELECT g.idgrupo,g.titulo,g.descricao,g.ordem,g.idquest FROM grupos g "
    ." WHERE g.idquest = ". intval($_GET['id']);
    ;

    $conexao->query($sqlGrupos,true);

    #if($conexao->num_rows<=0) die('Sem grupos');
    while($rowGrupo = $conexao->fetch_array()){
        $grupos[] = $rowGrupo;

        
            #Campos
            $sqlCampos = "SELECT c.idcampo,c.nome,c.tipo,c.rotulo,c.valor,c.iddep,c.params FROM campos c, perguntas p "
            ." WHERE c.idpergunta=p.idpergunta AND p.idgrupo = ". intval($rowGrupo['idgrupo']);
            ;
            $qryCampos = mysql_query($sqlCampos);
            $numRows = mysql_num_rows($qryCampos);
            
            while($rowOpc= mysql_fetch_array($qryCampos)){
                
                $params = StringToArray($rowOpc['params']);
                $params['ID'] = getIdCampo($rowOpc['idcampo']);
                $params['TYPE'] = $rowOpc['tipo'];
                $params['NAME'] = $rowOpc['nome'];
                $params['LABEL'] = ($rowOpc['rotulo']!='') ? $rowOpc['rotulo'] : '&nbsp;';

				if( !empty( $rowOpc['valor'] ) ) $params['VALUE'] = $rowOpc['valor'];


                $form->AddInput( $params );


            } // FimWhile Opc 
        
        
    }

    
    $form->LoadInputValues($form->WasSubmitted("doit"));
    
    $verify=array();
    
if($form->WasSubmitted("doit"))
    {

        if(($error_message=$form->Validate($verify))=="")
        {

            $doit=1;

        }
        else
        {

            $doit=0;
            $error_message=nl2br(HtmlSpecialChars($error_message));
        }
    }
    else
    {

        $error_message="";
        $doit=0;
    }
  if($doit)
  {

      $form->ReadOnly=1;
  }

    if(!$doit)
    {
        if(strlen($error_message))
        {

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
<style type="text/css"><!--
    body{
        margin: 34px;
        font-family: Verdana, Arial, Helvetica, sans-serif;
        font-size: 0.9em;
    }
    h3,h4{
        margin:0;
        padding:0;
    }
    h4{
        margin:0 0 5px 0;
        padding:0;
        font-size:10px;
    }
    table{
        /*border: 1px solid #666;*/
        float:none;
    }
    .grupoPerguntas tr td{
        font-family: "lucida grande", verdana, sans-serif;
        font-size: 8pt;
        background: #fff;
    }

    thead td{
        color: #fff;
        background-color: #C8C028;
        font-weight: bold;
        /*border-bottom: 1px solid #999;/*/
    }
    tbody td{
            /*border-left: 1px solid #D9D9D9;/*/
            font-size: 0.8em;
    }
    .even, .even td{
        background: #EEE;
    }
    .grupo{
        padding:3px;
        margin:0px 0px 10px 0px;
        background-color: #FEFEFE;
        border: 1px solid #DDD;
        text-align:left;
    }
    .pergunta{
        padding:3px;
        margin:2px;
        text-align:left;
    }
    .selected td{
        background: #3d80df;
        color: #ffffff;
        font-weight: bold;
        /*border-left: 1px solid #346DBE;
        border-bottom: 1px solid #7DAAEA;/*/
    }
    .ruled td{
        color: #000;
        background-color: #C6E3FF; 
        font-weight: bold;
        /*border-color: #3292FC;/*/
    }
    
    /* Opera fix */
    head:first-child+body tr.ruled td{
        background-color: #C6E3FF; 
    }

    body{
        text-align:center;
        margin: auto;
    }
    .edit{
        font-size:9px;
        font-weight: bold;
    }
	input[type=submit]{ 
	margin: 2px auto;
	}
	
	
	

.invalid { border-color: #ff0000; background-color: #ffcccc; }
// --></style>
<script>
var wEdit = null;
var reload = null;
function openWindow(){
    wEdit = window.open('','wEdit','menu=no,resizable=yes,height=400,width=500');
    wEdit.onblur=function(){
        reload = setTimeout("window.location.href=window.location.href;",1000)
    };
}
</script>
</head>
<? $onload = HtmlSpecialChars($form->PageLoad()); ?>
<body bgcolor="#FFFFFF" onload="<?= $onload ?>">
<noscript>Javascript não está funcionando. Este sistema não funcionará corretamente.</noscript>
<div id="debug"></div>
<div id="aviso"></div>
<center><h1><?= $titulo ?></h1></center>
<div style="text-algn:center">
<a href="../grupo/cadastro.php?idq=<?=$idq?>">Novo grupo</a> 
<?php if( is_array($grupos) ): ?>
| 
<a href="../pergunta/cadastro.php?idq=<?= $idq ?>" style="text-algn:center">Nova questão</a>
<?php endif; ?>
</div>
<div align="left" style="width:50%;margin:auto">
<?php

/*
 * Compose the form output by including a HTML form template with PHP code
 * interleaaved with calls to insert form input field
 * parts in the layout HTML.
 */

	$form->StartLayoutCapture();
	$perguntas_template="perguntas.html.php";
    
    $countPerg = -2;
	require("grupos.html.php");

 	
    //$form->AddInputPart("button_submit"); 
    $form->AddInputPart("doit");
    
    $form->EndLayoutCapture();

/*
 * Output the form using the function named Output.
 */
	$form->DisplayOutput();
?>
</div>
</body>
</html>
