<?php
require("../util/formsgeneration/forms.php");
require_once('../lib/functions.inc.php');
require_once("../db.php");

$idc = intval($_GET['id']); 
$idq = intval($_GET['idq']); 
$idp = intval($_GET['idp']);
$campoCopiado = (bool) $_GET['cp'];


  $conexao->conecta();
 
  if($idc){
      $conexao->query("SELECT * FROM campos WHERE idcampo=".$idc );
      $row = $conexao->fetch_array();
  }
  
#########

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
    
    #Campos
    #Id
    $campoidcampo['TYPE']='hidden';
    $campoidcampo['NAME']='idcampo';
    $campoidcampo['VALUE']=(!empty($row['idcampo']) && !$campoCopiado) ? $row['idcampo'] : '';
    $form->AddInput($campoidcampo);

    #Pergunta
    #$campoidpergunta['TYPE']='hidden';
    #$campoidpergunta['NAME']='idpergunta';
    #$campoidpergunta['VALUE']=($row['idpergunta']) ? $row['idpergunta'] : $idp;
    #$form->AddInput($campoidpergunta);

    #Pergunta
    $campo['TYPE'] = "select";
    $campo['NAME'] = "idpergunta";
    $campo['ID'] = "idpergunta";
    $campo['LABEL'] = "<u>P</u>ergunta";
    $campo['ACCESSKEY'] = "p";
    $campo['VALUE']=($row['idpergunta']) ? $row['idpergunta'] : $idp;
    # Lista de opções
    $opt[0] = "--- Selecione ---";
      if($idq) {
          $conexao->query("SELECT idpergunta,texto,identificador FROM perguntas p JOIN grupos g ON (p.idgrupo=g.idgrupo) WHERE p.idquest=$idq ORDER BY g.ordem,g.idgrupo, p.ordem, p.idpergunta");
          while($rowPerguntas = $conexao->fetch_array()){
              $k = $rowPerguntas['idpergunta'];
              $opt[$k] = $rowPerguntas['identificador'] . " - " . $rowPerguntas['texto'];
          }
      }
    $campo['OPTIONS'] = $opt;
    $campo['ValidateAsDifferentFromText'] = 0;
    $campo['ValidateAsDifferentFromTextErrorMessage'] = "Selecione uma opção válida";
    $form->AddInput($campo);
    
    
    #Tipo
    $campo['TYPE'] = "select";
    $campo['NAME'] = "tipo";
    $campo['ID'] = "tipo";
    $campo['LABEL'] = "<u>T</u>ipo";
    $campo['ACCESSKEY'] = "t";
    $campo['VALUE']=($row['tipo']) ? $row['tipo'] : 0; 
    # Lista de opções
    $opt = array();
    $opt[0]             = "--- Selecione ---";
    $opt['text']        = "Caixa de texto";
    $opt['textarea']    = "Caixa de texto extendida";
    $opt['radio']       = "Caixa de marcação única";
    $opt['checkbox']    = "Caixa de marcação múltipla";
    $opt['password']    = "Caixa de Senha";

    $campo['OPTIONS'] = $opt;
    $campo['ValidateAsDifferentFromText'] = 0;
    $campo['ValidateAsDifferentFromTextErrorMessage'] = "Selecione uma opção válida";
    $form->AddInput($campo);
    
    
    
    
    $campoNome['TYPE'] = "text";
    $campoNome['NAME'] = "nome";
    $campoNome['Capitalization']='lowercase';
    $campoNome['ID'] = "nome";
    $campoNome['VALUE']=($row['nome']) ? $row['nome'] : '';
    $campoNome['SIZE'] = 50;
    $campoNome['ValidationErrorMessage'] = "Nome inválido.";
    $campoNome['ValidateRegularExpression']=array(
            "^[a-zA-Z]",
            "^[a-zA-Z0-9]+\$"
        );
        
    $campoNome['ValidateRegularExpressionErrorMessage']=array(
            "O nome deve começar com uma letra.",
            "O nome deve conter apenas letras ou números."
        );
    $campoNome['LABEL'] = "<u>N</u>ome";
    $campoNome['ACCESSKEY'] = "n";
    $form->AddInput($campoNome);
    
    # Rotulo
    $campoRotulo['TYPE'] = "text";
    $campoRotulo['NAME'] = "rotulo";
    $campoRotulo['ID'] = "rotulo";
    $campoRotulo['VALUE']=($row['rotulo']) ? $row['rotulo'] : '';
    $campoRotulo['SIZE'] = 50;
    $campoRotulo['ValidationErrorMessage'] = "Rótulo inválido.";
    $campoRotulo['LABEL'] = "<u>R</u>ótulo";
    $campoRotulo['ACCESSKEY'] = "r";
    $form->AddInput($campoRotulo);
    
	# Valor
    $campoOrdem['TYPE'] = "text";
    $campoOrdem['NAME'] = "valor";
    $campoOrdem['ID'] = "valor";
    $campoOrdem['VALUE']=(!empty($row['valor'])) ? $row['valor'] : '';
    $campoOrdem['SIZE'] = 20;
    $campoOrdem['LABEL'] = "<u>V</u>alor";
    $campoOrdem['ACCESSKEY'] = "v";
    $form->AddInput($campoOrdem);
	
    # Parametros
    $campoParam['TYPE'] = "textarea";
    $campoParam['NAME'] = "params";
    $campoParam['ID'] = "params";
    $campoParam['VALUE']=($row['params']) ? $row['params'] : '';
    $campoParam['COLS'] = 50;
    $campoParam['ROWS'] = 10;
    $campoParam['ValidationErrorMessage'] = "Parâmetros inválidos.";
    $campoParam['LABEL'] = "<u>P</u>arâmetros";
    $campoParam['ACCESSKEY'] = "p";
    $form->AddInput($campoParam);
    
    #Ordem
    $campoOrdem['TYPE'] = "text";
    $campoOrdem['NAME'] = "ordem";
    $campoOrdem['ID'] = "ordem";
    $campoOrdem['VALUE']=($row['ordem']) ? $row['ordem'] : '';
    $campoOrdem['SIZE'] = 3;
    $campoOrdem['LABEL'] = "<u>O</u>rdem";
    $campoOrdem['ACCESSKEY'] = "o";
    $form->AddInput($campoOrdem);

    #Dependência
    $campo['TYPE']='select';
    $campo['NAME']='iddep';
    $campo['ID'] = "campoDepende";
    $iddep = ($_GET['iddep']) ? intval($_GET['iddep']) : 0;
    $campo['VALUE']=($row['iddep']) ? $row['iddep'] : $iddep;
    $campo['LABEL'] = "<u>D</u>epende do campo";
    $campo['ACCESSKEY'] = "D";
    # Lista de opções
    $opt = array();
    $opt[0] = "--- Nenhum ---";
      $conexao->query("SELECT idcampo,rotulo,texto,identificador FROM campos c JOIN perguntas p ON (c.idpergunta=p.idpergunta) JOIN grupos g ON (p.idgrupo=g.idgrupo) WHERE p.idquest=$idq ORDER by g.ordem,g.idgrupo,p.ordem,p.idpergunta,c.ordem,c.idcampo");
      while($rowPergGrupos = $conexao->fetch_array()){
          $k = $rowPergGrupos['idcampo'];
          $opt[$k] = $rowPergGrupos['identificador'] . " - " . $rowPergGrupos['texto'] . " - " . $rowPergGrupos['rotulo'];
      }
    $campo['OPTIONS'] = $opt;
    $campo['ValidateAsDifferentFromText'] = -1;
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
         
#########


$form->LoadInputValues($form->WasSubmitted("doit"));

  if($form->WasSubmitted("doit")){
      
      if(intval($_POST['idcampo']) && !empty($_POST['idcampo'])){
          $sql = "UPDATE campos SET idpergunta=".intval($_POST['idpergunta']).",tipo='".$_POST['tipo']."',nome='".trim($_POST['nome'])."',rotulo='".trim($_POST['rotulo'])."',valor='".trim($_POST['valor'])."',params='".trim($_POST['params'])."',ordem='".trim($_POST['ordem'])."',iddep='".trim($_POST['iddep'])."' WHERE idcampo=".intval($_POST['idcampo']);
          if($conexao->query( $sql )){
              
            $mensagem = 'Registro alterado';
            
            $idp = $_POST['idpergunta'];
            
          }else
            $mensagem = 'Erro ao alterar registro: '. $conexao->erro();
            
      }else{
          if( $conexao->query("INSERT INTO campos(tipo,nome,rotulo,valor,params,idpergunta,ordem,iddep) VALUES('".$_POST['tipo']."','".$_POST['nome']."','".$_POST['rotulo']."','".$_POST['valor']."','".$_POST['params']."','".$_POST['idpergunta']."','".$_POST['ordem']."','".$_POST['iddep']."')" )){
            $mensagem = 'Registro cadastrado: '.$_POST['nome'].'('.$_POST['rotulo'].')';
            $idp = $_POST['idpergunta'];
          }else
            $mensagem = 'Erro ao cadastrar: '. $conexao->erro();
      }
  }
  
    if($idp){
      $conexao->query("SELECT * FROM perguntas WHERE idpergunta = ". $idp );
      $rowPerg = $conexao->fetch_array();
      $ancora = "#perg".$idp; 
    }
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Cadastrar Campos da Pergunta</title>
<link rel="stylesheet" type="text/css" href="estilo.css" /> 
</head>
<body>
<a style="float:right" href="../questionario/edita.php?id=<?=$idq?><?=$ancora?>">Voltar para o questionário</a>
<h1>Cadastrar Campos da Pergunta</h1>
<? if ($mensagem){ ?>
<span class="aviso" name="aviso"><?=$mensagem?></span>
<? } ?>

    <h2><?= ($rowPerg['identificador']) ?  $rowPerg['identificador']." - " : '' ?><?= $rowPerg['texto'] ?></h2>

    
<?php

    $formTemp=new form_class;
    
    #Incluir estes parametros no Questionarios
    $formTemp->NAME="q_form";
    $formTemp->METHOD="POST";
    $formTemp->ACTION="";
    $formTemp->debug="trigger_error";
    $formTemp->ResubmitConfirmMessage= "Deseja mandar as informações novamente?";
    $formTemp->OutputPasswordValues=1;
    $formTemp->OptionsSeparator="<br />\n";
    $formTemp->ShowAllErrors=1;
    $formTemp->InvalidCLASS='invalid';
    $formTemp->ErrorMessagePrefix="- ";
    $formTemp->ErrorMessageSuffix="";
    
    $formTemp->StartLayoutCapture();
    
    #Mostra campos
    if($idp){
      $conexao->query("SELECT * FROM campos WHERE idpergunta = ". $idp ." ORDER BY ordem");
      while($rowOpc = $conexao->fetch_array()){
                $params = StringToArray($rowOpc['params']);
                $params['ID'] = getIdCampo($rowOpc['idcampo']);
                $params['TYPE'] = $rowOpc['tipo'];
                $params['NAME'] = $rowOpc['nome'];
                $params['LABEL'] = ($rowOpc['rotulo']!='') ? $rowOpc['rotulo'] : '&nbsp;';
				if( !empty( $rowOpc['valor'] ) ) $params['VALUE'] = $rowOpc['valor'];

                $formTemp->AddInput( $params );
                
                
                #Link de editar
                $lnkOpt = '
                <div class="edit" style="white-space:nowrap;display:inline">
                <a id="btnCampoEdit_'. $rowOpc['idcampo'] .'" class="edit" href="../campo/cadastro.php?id='. $rowOpc['idcampo'] .'&idq='. $idq .'&idp='. $idp .'">Editar</a>
                |
                <a id="btnCampoEdit_'. $rowOpc['idcampo'] .'" class="edit" href="../campo/cadastro.php?id='. $rowOpc['idcampo'] .'&idq='. $idq .'&idp='. $idp .'&cp=1">Copiar</a>
                |
                <a id="btnDel_'. $rowOpc['idcampo'] .'" class="edit" href="../campo/apagar.php?id='. $rowOpc['idcampo'] .'" onclick="return confirm(\'Tem certeza??\')">Apagar</a>
                </div>
                ';
                
                switch($params['TYPE']){    
                    case "checkbox":
                    case "radio":
                        $formTemp->AddInputPart($params['ID']);
                        $formTemp->AddLabelPart(array("FOR"=>$params['ID']));
                        echo "$lnkOpt<br />";

                        break;
                        
                    case "text":
                    default:
                        $formTemp->AddLabelPart(array("FOR"=>$params['ID']));
                        $formTemp->AddInputPart($params['ID']);
                        echo "$lnkOpt<br />";
                    
                }
      }
      
    $formTemp->EndLayoutCapture();

    $formTemp->DisplayOutput();
      
    }
?>
 
    <hr>
    <?= ($campoCopiado) ?'Copiar:':'Editar:' ?>
    
<?     
    $form->StartLayoutCapture();
?>
    <table>
    <tr><th><? $form->AddLabelPart(array("FOR"=>"idpergunta")); ?></th><td><? $form->AddInputPart("idpergunta"); ?></td></tr> 
    <tr><th><? $form->AddLabelPart(array("FOR"=>"tipo")); ?></th><td><? $form->AddInputPart("tipo"); ?></td></tr> 
    <tr><th><? $form->AddLabelPart(array("FOR"=>"nome")); ?></th><td><? $form->AddInputPart("nome"); ?></td></tr>
    <tr><th><? $form->AddLabelPart(array("FOR"=>"rotulo")); ?></th><td><? $form->AddInputPart("rotulo"); ?></td></tr>
    <tr><th><? $form->AddLabelPart(array("FOR"=>"valor")); ?></th><td><? $form->AddInputPart("valor"); ?></td></tr>
    <tr><th><? $form->AddLabelPart(array("FOR"=>"params")); ?></th><td><? $form->AddInputPart("params"); ?></td></tr>
    <tr><th><? $form->AddLabelPart(array("FOR"=>"ordem")); ?></th><td><? $form->AddInputPart("ordem"); ?></td></tr>
    <tr><th><? $form->AddLabelPart(array("FOR"=>"campoDepende")); ?></th><td><? $form->AddInputPart("campoDepende"); ?></td></tr>


    <tr>
    <td colspan="2"><? 
        $form->AddInputPart("idcampo");
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

<br>
<br>
<a href="../questionario/edita.php?id=<?=$idq?><?=$ancora?>">Voltar para o questionário</a>

</body>
</html>
