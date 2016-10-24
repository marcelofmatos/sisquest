<?php
require("../util/formsgeneration/forms.php");
require_once('../lib/functions.inc.php');
require_once("../db.php");

$idp = 0;
$idq = ($_GET['idq']) ? intval($_GET['idq']) : 0;
$tipografico = 2;

$conexao->conecta();


    $form=new form_class;
    
    #Incluir estes parametros no Questionarios
    $form->NAME="q_form_Graph";
    $form->METHOD="POST";
    #$form->METHOD="GET";
    $form->ACTION="graficoImg.php";
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


    #Titulo do grafico
    unset($campo);
    $campo['TYPE'] = "text";
    $campo['NAME'] = "titulografico";
    $campo['ID'] = "titulografico";
    $campo['LABEL'] = "<u>T</u>ítulo";
    $campo['ACCESSKEY'] = "t";
    $campo['VALUE']="";
    $campo['SIZE']=50;
    $form->AddInput($campo);
	
	
    #Usar texto da pergunta 1
    unset($campo);
    $campo['TYPE'] = "checkbox";
    $campo['NAME'] = "titulotxtpergunta";
    $campo['ID'] = "titulotxtpergunta";
    $campo['LABEL'] = "<u>U</u>sar texto da pergunta";
    $campo['ACCESSKEY'] = "u";
    $campo['VALUE']=1;
    $campo['CHECKED']=true;
    $campo['ONCLICK']="textoDaLista('titulografico','idp', this.id)";
    $campo['ReadOnlyMark']="[X]";
    $form->AddInput($campo);
	

    #Subtitulo do grafico
    unset($campo);
    $campo['TYPE'] = "text";
    $campo['NAME'] = "subtitulografico";
    $campo['ID'] = "subtitulografico";
    $campo['LABEL'] = "<u>S</u>ubtítulo";
    $campo['ACCESSKEY'] = "s";
    $campo['VALUE']="";
	$campo['SIZE']=50;
    $form->AddInput($campo);
	
	#Usar texto da pergunta 2
    unset($campo);
    $campo['TYPE'] = "checkbox";
    $campo['NAME'] = "subtitulotxtpergunta";
    $campo['ID'] = "subtitulotxtpergunta";
    $campo['LABEL'] = "<u>U</u>sar texto da pergunta";
    $campo['ACCESSKEY'] = "u";
    $campo['VALUE']=1;
    $campo['CHECKED']=false;
    $campo['ONCLICK']="textoDaLista('subtitulografico','idp', this.id)";
    $campo['ReadOnlyMark']="[X]";
    $form->AddInput($campo);
	
    #Tipo de grafico
    unset($campo);
    $campo['TYPE'] = "select";
    $campo['NAME'] = "tipografico";
    $campo['ID'] = "tipografico";
    $campo['LABEL'] = "T<u>i</u>po de gráfico";
    $campo['ACCESSKEY'] = "i";
    $campo['VALUE']=($tipografico) ? $tipografico : 2;
    # Lista de opções
    unset($opt);
    #$opt[1] = "Barras Verticais";
    $opt[2] = "Barras Horizontais";
    $opt[3] = "Pizza";
    #$opt[4] = "Linhas";

    $campo['OPTIONS'] = $opt;
    $campo['ValidateAsDifferentFromText'] = 0;
    $campo['ValidateAsDifferentFromTextErrorMessage'] = "Selecione uma opção válida";
    $form->AddInput($campo);
    
    #Pergunta
    unset($campo);
    $campo['TYPE'] = "select";
    $campo['NAME'] = "idp";
    $campo['ID'] = "idp";
    $campo['LABEL'] = "<u>P</u>ergunta";
    $campo['ACCESSKEY'] = "p";
    $campo['VALUE']=($row['idpergunta']) ? $row['idpergunta'] : $idp;
    # Lista de opções
    unset($opt);
    $opt[0] = "--- Selecione ---";
      if($idq) {
          $conexao->query("SELECT idpergunta,texto,identificador FROM perguntas p JOIN grupos g ON (p.idgrupo=g.idgrupo) WHERE p.idquest=$idq ORDER BY g.ordem,g.idgrupo, p.ordem, p.idpergunta");
          while($rowPerguntas = $conexao->fetch_array()){
              $k = $rowPerguntas['idpergunta'];
              $opt[$k] = (($rowPerguntas['identificador']) ? $rowPerguntas['identificador'] : '--') . " - " . $rowPerguntas['texto'];
          }
      }
    $campo['OPTIONS'] = $opt;
    $campo['ONCHANGE'] = "textoDaLista('titulografico','idp', 'titulotxtpergunta');textoDaLista('subtitulografico','idp', 'subtitulotxtpergunta')";
    $campo['ValidateAsDifferentFromText'] = 0;
    $campo['ValidateAsDifferentFromTextErrorMessage'] = "Selecione uma opção válida";
    $form->AddInput($campo);
    
  
    #Mostrar em porcentagem
    unset($campo);
    $campo['TYPE'] = "checkbox";
    $campo['NAME'] = "porcentagem";
    $campo['ID'] = "porcentagem";
    $campo['LABEL'] = "<u>M</u>ostrar em porcentagem";
    $campo['ACCESSKEY'] = "m";
    $campo['VALUE']=1;
    $campo['CHECKED']=false;
    $campo['ReadOnlyMark']="[X]";
    $form->AddInput($campo);
	
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

    #Configurar altura do grafico
    unset($campo);
    $campo['TYPE'] = "text";
    $campo['NAME'] = "altura";
    $campo['ID'] = "altura";
    $campo['LABEL'] = "<u>A</u>ltura (px)";
    $campo['ACCESSKEY'] = "m";
	$campo['SIZE']=5;
    $campo['VALUE']=600;
    $form->AddInput($campo);

    #Configurar largura do grafico
    unset($campo);
    $campo['TYPE'] = "text";
    $campo['NAME'] = "largura";
    $campo['ID'] = "largura";
    $campo['LABEL'] = "<u>L</u>argura (px)";
    $campo['ACCESSKEY'] = "m";
	$campo['SIZE']=5;
    $campo['VALUE']=800;
    $form->AddInput($campo);
    
    $form->AddInput(array(
        "TYPE"=>"submit",
        "ID"=>"button_submit",
        "VALUE"=>"Criar gráfico",
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

?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Gerar Relatório</title>
<link rel="stylesheet" type="text/css" href="estilo.css" />
<script>
function textoDaLista(idcampotexto,idcamposelect,idcheckbox){
	checkbox = document.getElementById(idcheckbox);
	if(checkbox.checked){
		campotext = document.getElementById(idcampotexto);
		camposelect = document.getElementById(idcamposelect);
		
		var texto=camposelect.options[camposelect.selectedIndex].text;
		
		var re = new RegExp("-- - ", "g");
		campotext.value = texto.replace(re, "");
	}
	
}
</script>

</head>
<body>
<a style="float:right" href="../">Voltar para Questionários</a>
<br clear="all">


<? if ($mensagem){ ?>
<span class="aviso" name="aviso"><?=$mensagem?></span>
<? } ?>

<?  
  $form->StartLayoutCapture();
?>
<fieldset>

<legend>Gráfico</legend>

    <table>

    <tr><th><? $form->AddLabelPart(array("FOR"=>"tipografico")); ?></th><td><? $form->AddInputPart("tipografico"); ?></td></tr>
    
    <tr><th><? $form->AddLabelPart(array("FOR"=>"idp")); ?></th><td><? $form->AddInputPart("idp"); ?></td></tr>

    <tr>
	<th><? $form->AddLabelPart(array("FOR"=>"titulografico")); ?></th>
	<td><? $form->AddInputPart("titulografico"); ?> <? $form->AddInputPart("titulotxtpergunta"); ?><? $form->AddLabelPart(array("FOR"=>"titulotxtpergunta")); ?></td>
	</tr>
	
    <tr>
	<th><? $form->AddLabelPart(array("FOR"=>"subtitulografico")); ?></th>
	<td><? $form->AddInputPart("subtitulografico"); ?> <? $form->AddInputPart("subtitulotxtpergunta"); ?><? $form->AddLabelPart(array("FOR"=>"subtitulotxtpergunta")); ?></td>
	</tr>
	
	<tr><th><? $form->AddLabelPart(array("FOR"=>"ordenar")); ?></th><td><? $form->AddInputPart("ordenar"); ?></td></tr>
	<tr><th><? $form->AddLabelPart(array("FOR"=>"porcentagem")); ?></th><td><? $form->AddInputPart("porcentagem"); ?></td></tr>
    

    <tr><th><? $form->AddLabelPart(array("FOR"=>"altura")); ?></th><td><? $form->AddInputPart("altura"); ?></td></tr>
    <tr><th><? $form->AddLabelPart(array("FOR"=>"largura")); ?></th><td><? $form->AddInputPart("largura"); ?></td></tr>

    <tr>
    <td colspan="2"><? 
        $form->AddInputPart("idq");

        $form->AddInputPart("button_submit"); 
        $form->AddInputPart("doit");
    ?></td>
    </tr>
    </table>
</fieldset>
<?     

    $form->EndLayoutCapture();
    $form->DisplayOutput();

 
  $form->StartLayoutCapture();
?>

<br>
<br>
<a href="../">Voltar para Questionários</a>
</body>
</html>
