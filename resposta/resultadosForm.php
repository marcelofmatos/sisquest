<?php
require("../util/formsgeneration/forms.php");
require_once('../lib/functions.inc.php');
require_once("../db.php");

$idp1 = 0;
$idp2 = 0;
$idq = ($_GET['idq']) ? intval($_GET['idq']) : 0;
$tipo_grafico = 1;

$conexao->conecta();

# Form 

    $form=new form_class;
    
    #Incluir estes parametros no Questionarios
    $form->NAME="q_form";
    $form->METHOD="POST";
    $form->ACTION="resultados.php";
    $form->debug="trigger_error";
    #$form->ResubmitConfirmMessage= "Deseja mandar as informações novamente?";
    $form->OutputPasswordValues=1;
    $form->OptionsSeparator="<br />\n";
    $form->ShowAllErrors=1;
    $form->InvalidCLASS='invalid';
    $form->ErrorMessagePrefix="- ";
    $form->ErrorMessageSuffix="";

    #Id Questionário
    unset($campo);
    $campo['TYPE']='hidden';
    $campo['NAME']='idq';
    $campo['VALUE']=($row['idquest']) ? $row['idquest'] : $idq;
    $form->AddInput($campo);
    
    #Pergunta 1
    unset($campo);
    $campo['TYPE'] = "select";
    $campo['NAME'] = "idp1";
    $campo['ID'] = "idp1";
    $campo['LABEL'] = "<u>P</u>ergunta 1";
    $campo['ACCESSKEY'] = "p";
    $campo['VALUE']=($row['idpergunta']) ? $row['idpergunta'] : $idp1;
    # Lista de opções
    $opt[0] = "--- Selecione ---";
      if($idq) {
          $conexao->query("SELECT idpergunta,texto,identificador FROM perguntas p JOIN grupos g ON (p.idgrupo=g.idgrupo) WHERE p.idquest=$idq ORDER BY g.ordem, p.ordem");
          while($rowPerguntas = $conexao->fetch_array()){
              $k = $rowPerguntas['idpergunta'];
              $opt[$k] = (($rowPerguntas['identificador']) ? $rowPerguntas['identificador'] : '--') . " - " . $rowPerguntas['texto'];
          }
      }
    $campo['OPTIONS'] = $opt;
    $campo['ValidateAsDifferentFromText'] = 0;
    $campo['ValidateAsDifferentFromTextErrorMessage'] = "Selecione uma opção válida";
    $form->AddInput($campo);
    
    #Pergunta 2
    unset($campo);
    $campo['TYPE'] = "select";
    $campo['NAME'] = "idp2";
    $campo['ID'] = "idp2";
    $campo['LABEL'] = "P<u>e</u>rgunta 2";
    $campo['ACCESSKEY'] = "e";
    $campo['VALUE']=($row['idpergunta']) ? $row['idpergunta'] : $idp2;
    # Lista de opções
    unset($opt);
    $opt[0] = "--- Selecione ---";
      if($idq) {
          $conexao->query("SELECT idpergunta,texto,identificador FROM perguntas p JOIN grupos g ON (p.idgrupo=g.idgrupo) WHERE p.idquest=$idq ORDER BY g.ordem, p.ordem");
          while($rowPerguntas = $conexao->fetch_array()){
              $k = $rowPerguntas['idpergunta'];
              $opt[$k] = (($rowPerguntas['identificador']) ? $rowPerguntas['identificador'] : '--') . " - " . $rowPerguntas['texto'];
          }
      }
    $campo['OPTIONS'] = $opt;
    $campo['ValidateAsDifferentFromText'] = 0;
    $campo['ValidateAsDifferentFromTextErrorMessage'] = "Selecione uma opção válida";
    $form->AddInput($campo);
    

    #Tipo Gráfico
    unset($campo);
    $campo['TYPE'] = "select";
    $campo['NAME'] = "tipo_grafico";
    $campo['ID'] = "tipo_grafico";
    $campo['LABEL'] = "<u>T</u>ipo do gráfico";
    $campo['ACCESSKEY'] = "t";
    $campo['VALUE']=($row['tipo_grafico']) ? $row['tipo_grafico'] : $tipo_grafico;
    # Lista de opções
    unset($opt);
    $opt[1] = "Pizza";
    $opt[2] = "Barra vertical";
    $campo['OPTIONS'] = $opt;
    $campo['ValidateAsDifferentFromText'] = 0;
    $campo['ValidateAsDifferentFromTextErrorMessage'] = "Selecione uma opção válida";
    # Desabilitado por enqto
    #$form->AddInput($campo);

    #Ordenar
    unset($campo);
    $campo['TYPE'] = "select";
    $campo['NAME'] = "ordenar";
    $campo['ID'] = "ordenar";
    $campo['LABEL'] = "<u>O</u>rdem";
    $campo['ACCESSKEY'] = "i";
    $campo['VALUE']=($ordenar) ? $ordenar : 1;
    # Lista de opções
    unset($opt);
    $opt[1] = "Crescente";
    $opt[2] = "Decrescente";
    $campo['OPTIONS'] = $opt;
    $campo['ValidateAsDifferentFromText'] = 0;
    $campo['ValidateAsDifferentFromTextErrorMessage'] = "Selecione uma opção válida";
    $form->AddInput($campo);


    unset($campo);
    $campo['TYPE'] = "select";
    $campo['NAME'] = "formatoValor";
    $campo['ID'] = "formatoValor";
    $campo['LABEL'] = "<u>M</u>ostrar valor";
    $campo['ACCESSKEY'] = "m";
    $campo['VALUE']=($row['formatoValor']) ? $row['formatoValor'] : 1;
    # Lista de opções
    unset($opt);
    $opt[1] = "Contagem Normal";
    $opt[2] = "Porcentagem por resposta";
    $opt[3] = "Porcentagem total";
    $campo['OPTIONS'] = $opt;
    $campo['ValidateAsDifferentFromText'] = 0;
    $campo['ValidateAsDifferentFromTextErrorMessage'] = "Selecione uma opção válida";
    $form->AddInput($campo);
	
    
    $form->AddInput(array(
        "TYPE"=>"submit",
        "ID"=>"button_submit",
        "VALUE"=>"Enviar",
        "ACCESSKEY"=>"c"
    ));
    $form->AddInput(array(
        "TYPE"=>"hidden",
        "NAME"=>"doit",
        "VALUE"=>1
    ));
    
  $form->LoadInputValues($form->WasSubmitted("doit"));

  if($form->WasSubmitted("doit")){
      
  }
  
  $form->StartLayoutCapture();
  
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Gerar Relatório</title>
<link rel="stylesheet" type="text/css" href="estilo.css" /> 
</head>
<body>
<a style="float:right" href="../">Voltar para Questionários</a>
<br clear="all">
<fieldset><legend>Cruzar perguntas</legend>
<? if ($mensagem){ ?>
<span class="aviso" name="aviso"><?=$mensagem?></span>
<? } ?>
    <table>
    <tr><th><? $form->AddLabelPart(array("FOR"=>"idp1")); ?></th><td><? $form->AddInputPart("idp1"); ?></td></tr>
    <tr><th><? $form->AddLabelPart(array("FOR"=>"idp2")); ?></th><td><? $form->AddInputPart("idp2"); ?></td></tr>
    <tr><th><? $form->AddLabelPart(array("FOR"=>"ordenar")); ?></th><td><? $form->AddInputPart("ordenar"); ?></td></tr>
	<tr>
	<th><? $form->AddLabelPart(array("FOR"=>"formatoValor")); ?></th>
	<td>
	<? $form->AddInputPart("formatoValor"); ?>
	</td>
	</tr>
    
    <? # Desabilitado por enqto
    /* <tr><th><? $form->AddLabelPart(array("FOR"=>"tipo_grafico")); ?></th><td><? $form->AddInputPart("tipo_grafico"); ?></td></tr>
    */
    ?>
    <tr>
    <td colspan="2"><? 
        $form->AddInputPart("idq");

        $form->AddInputPart("button_submit"); 
        $form->AddInputPart("doit");
    ?></td>
    </tr>
    </table>
<?     

    $form->EndLayoutCapture();

/*
 * Output the form using the function named Output.
 */
    $form->DisplayOutput();
?>
</fieldset>
<br>
<br>
<a href="../">Voltar para Questionários</a>
</body>
</html>
