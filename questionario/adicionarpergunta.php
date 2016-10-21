<?php
    require("../util/formsgeneration/forms.php");
    require_once('../lib/functions.inc.php');
    require_once('../db.php');

if(!isSet($_GET['idg'])) die(); 

    $get_idgrupo =  intval($_GET['idg']);

    $conexao->conecta(); 
    
    $conexao->query("SELECT * FROM grupos WHERE idgrupo=".$get_idgrupo );
    $rowGrupo = $conexao->fetch_array();


     
# Form

    $formNovo=new form_class;
    
    #Incluir estes parametros no Questionarios
    $formNovo->NAME="q_form";
    $formNovo->METHOD="POST";
    $formNovo->ACTION="";
    $formNovo->debug="trigger_error";
    $formNovo->ResubmitConfirmMessage= "Deseja mandar as informações novamente?";
    $formNovo->OutputPasswordValues=1;
    $formNovo->OptionsSeparator="<br />\n";
    $formNovo->ShowAllErrors=1;
    $formNovo->InvalidCLASS='invalid';
    $formNovo->ErrorMessagePrefix="- ";
    $formNovo->ErrorMessageSuffix="";
    
    $campoidgrupo['TYPE']='hidden';
    $campoidgrupo['NAME']='idgrupo';
    $campoidgrupo['VALUE']=($get_idgrupo) ? $get_idgrupo : '';
    $formNovo->AddInput($campoidgrupo);
    
    $campoAcaoAssocia['TYPE']='radio';
    $campoAcaoAssocia['NAME']='acao';
    $campoAcaoAssocia['ID']='acaoAssocia';
    $campoAcaoAssocia['VALUE']='associa';
    $formNovo->AddInput($campoAcaoAssocia);
    
    $campoAcaoNovo['TYPE']='radio';
    $campoAcaoNovo['NAME']='acao';
    $campoAcaoNovo['ID']='acaoNovo';
    $campoAcaoNovo['VALUE']='novo';
    $formNovo->AddInput($campoAcaoNovo);
    
    $campoQuest['TYPE'] = "select";
    $campoQuest['NAME'] = "idpergunta[]";
    $campoQuest['ID'] = "idpergunta";
    $campoQuest['LABEL'] = "<u>I</u>ncluir perguntas cadastradas";
    $campoTexto['ACCESSKEY'] = "i";
    $campoQuest['SIZE']=7;
    # Lista de questionários
    $conexao->query("SELECT * FROM perguntas ORDER BY texto");
    while($rowPerg = $conexao->fetch_array()){
      $key = $rowPerg['idpergunta'];
      $optPerg[$key] = $rowPerg['texto'];
    }
    $campoQuest['OPTIONS'] = $optPerg;
    $campoQuest['SELECTED']= array();
    $campoQuest['MULTIPLE']=1;
    $campoQuest['ValidateAsDifferentFromTextErrorMessage'] = "Selecione uma opção";
    $campoQuest['DependentValidation'] = "acaoAssocia";
    $campoQuest['ONCHANGE'] = "document.getElementById('".$campoAcaoAssocia['ID']."').checked=true";
    $formNovo->AddInput($campoQuest);
    
    $campoTexto['TYPE'] = "textarea";
    $campoTexto['NAME'] = "texto";
    $campoTexto['ID'] = "texto";
    $campoTexto['VALUE']=($row['texto']) ? $row['texto'] : '';
    $campoTexto['COLS'] = 50;
    $campoTexto['ValidateAsNotEmpty'] = 1;
    $campoTexto['ValidationErrorMessage'] = "Valor do texto inválido.";
    $campoTexto['DependentValidation'] = "acaoNovo";
    $campoTexto['LABEL'] = "<u>C</u>riar uma nova pergunta";
    $campoTexto['ACCESSKEY'] = "c";
    $campoTexto['ONCHANGE'] = "document.getElementById('".$campoAcaoNovo['ID']."').checked=true";
    $formNovo->AddInput($campoTexto);
    
    
    $formNovo->AddInput(array(
        "TYPE"=>"submit",
        "ID"=>"button_submit",
        "VALUE"=>"Salvar dados",
        "ACCESSKEY"=>"c"
    ));
    $formNovo->AddInput(array(
        "TYPE"=>"hidden",
        "NAME"=>"doit",
        "VALUE"=>1
    ));
    
    
  if($formNovo->WasSubmitted("doit")){
 
      switch($_POST['acao']){
        case 'novo':
            include('../pergunta/salvarpergunta.inc.php');
        case 'associa':
        default:
            include('associagrupopergunta.inc.php');
      }
  }
  

    
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Lista de Grupo de Questionários</title>
<link rel="stylesheet" type="text/css" href="estilo.css" />
<meta content="" name="" />
<style type="text/css"><!--
    body{
        margin: 34px;
        font-family: Verdana, Arial, Helvetica, sans-serif;
        font-size: 14px;
    }
    
    table{
        /*border: 1px solid #666; */
        float:none;
    }
    .grupoPerguntas tr td{
        font-family: "lucida grande", verdana, sans-serif;
        font-size: 8pt;
        background: #fff;
    }
    td.pergunta{
        padding: 3px 8px;
    }
    td.pergunta span{
        font-weight: bold;
        margin-right:3px;
    }
    thead td{
        color: #fff;
        background-color: #C8C028;
        font-weight: bold;
        /*border-bottom: 1px solid #999;/*/
    }
    tbody td{
            /*border-left: 1px solid #D9D9D9;/*/
    }
    tbody tr.even td{
        background: #eee;
    }
    tbody tr.selected td{
        background: #3d80df;
        color: #ffffff;
        font-weight: bold;
        /*border-left: 1px solid #346DBE;
        border-bottom: 1px solid #7DAAEA;/*/
    }
    tbody tr.ruled td{
        color: #000;
        background-color: #C6E3FF; 
        font-weight: bold;
        /*border-color: #3292FC;/*/
    }
    
    /* Opera fix */
    head:first-child+body tr.ruled td{
        background-color: #C6E3FF; 
    }
    fieldset{
        margin: 0 auto 15px auto;
        width:67%;
        min-width:450px;
    }
    body{
        text-align:center;
    }

.invalid { border-color: #ff0000; background-color: #ffcccc; }
// --></style>
</head>
<body>
<h1>Adicionar pergunta</h1>
<h2>Grupo: <?= $rowGrupo['titulo'] ?></h2>
<?
    $formNovo->StartLayoutCapture();
?>
<table>
<tr>
<td><fieldset>
<legend><? $formNovo->AddInputPart('acaoAssocia'); ?>
<? $formNovo->AddLabelPart(array("FOR"=>"idpergunta")); ?>
</legend>
<?     $formNovo->AddInputPart('idpergunta');
?>
    </fieldset>
</td>
</tr>
</table>

<table>
<tr>
<td><fieldset>
<legend><? $formNovo->AddInputPart('acaoNovo'); ?>
<? $formNovo->AddLabelPart(array("FOR"=>"texto")); ?>
</legend>
<?  
    $formNovo->AddInputPart('texto');
?>
    </fieldset>
</td>
</tr>
</table>
<br><br>
<?
  
    $formNovo->AddInputPart("idgrupo"); 
    $formNovo->AddInputPart("button_submit"); 
    $formNovo->AddInputPart("doit");
    
    $formNovo->EndLayoutCapture();
    
    $formNovo->DisplayOutput();
?>

<a href="perguntas.php?id=<?= intval($_GET['id']) ?>">Voltar ao questionário</a>
</body>
</html>