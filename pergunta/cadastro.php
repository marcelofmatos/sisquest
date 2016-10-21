<?php
require("../util/formsgeneration/forms.php");
require_once('../lib/functions.inc.php');
require_once("../db.php");

$idq = intval($_GET['idq']);


  $conexao->conecta();
 
  if(intval($_GET['id'])){
      $conexao->query("SELECT * FROM perguntas WHERE idpergunta=".intval($_GET['id']) );
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
    $campoIdperg['TYPE']='hidden';
    $campoIdperg['NAME']='idpergunta';
    $campoIdperg['VALUE']=($row['idpergunta']) ? $row['idpergunta'] : '';

    $form->AddInput($campoIdperg);
    
    #Id Questionário
    $campo['TYPE']='hidden';
    $campo['NAME']='idquest';
    $campo['VALUE']=($row['idquest']) ? $row['idquest'] : $idq;
    $form->AddInput($campo);
    
    #Grupo
    $campoIdGrupo['TYPE']='select';
    $campoIdGrupo['NAME']='idgrupo';
    $campoIdGrupo['ID'] = "grupo";
    $idgrupo = ($_GET['idg']) ? intval($_GET['idg']) : 0;
    $campoIdGrupo['VALUE']=($row['idgrupo']) ? $row['idgrupo'] : $idgrupo;
    $campoIdGrupo['LABEL'] = "<u>G</u>rupo";
    $campoIdGrupo['ACCESSKEY'] = "g";
    # Lista de opções
    $opt = array();
    $opt[0] = "--- Selecione ---";
      $conexao->query("SELECT idgrupo,titulo FROM grupos WHERE idquest=".$idq);
      while($rowGrupos = $conexao->fetch_array()){
          $k = $rowGrupos['idgrupo'];
          $opt[$k] = $rowGrupos['titulo'];
      }
    $campoIdGrupo['OPTIONS'] = $opt;
    $campoIdGrupo['ValidateAsDifferentFromText'] = 0;
    $campoIdGrupo['ValidateAsDifferentFromTextErrorMessage'] = "Selecione uma opção válida";
    
    $form->AddInput($campoIdGrupo); 
    
    
    $campoTexto['TYPE'] = "textarea";
    $campoTexto['NAME'] = "texto";
    $campoTexto['ID'] = "texto";
    $campoTexto['VALUE']=($row['texto']) ? $row['texto'] : '';
    $campoTexto['COLS'] = 50;
    $campoTexto['ValidationErrorMessage'] = "Texto inválido.";
    $campoTexto['LABEL'] = "<u>T</u>exto";
    $campoTexto['ACCESSKEY'] = "t";
    $form->AddInput($campoTexto);
    
    #Identificador
    $campoIdentificador['TYPE'] = "text";
    $campoIdentificador['NAME'] = "identificador";
    $campoIdentificador['ID'] = "identificador";
    $campoIdentificador['VALUE']=($row['identificador']) ? $row['identificador'] : '';
    $campoIdentificador['SIZE'] = 50;
    $campoIdentificador['ValidationErrorMessage'] = "Identificador inválido.";
    $campoIdentificador['LABEL'] = "<u>I</u>dentificador";
    $campoIdentificador['ACCESSKEY'] = "i";
    $form->AddInput($campoIdentificador);
    
    #Ordem
    $campoOrdem['TYPE'] = "text";
    $campoOrdem['NAME'] = "ordem";
    $campoOrdem['ID'] = "ordem";
    $campoOrdem['VALUE']=($row['ordem']) ? $row['ordem'] : '';
    $campoOrdem['SIZE'] = 3;
    $campoOrdem['LABEL'] = "<u>O</u>rdem";
    $campoOrdem['ACCESSKEY'] = "o";
    $form->AddInput($campoOrdem);
    
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
      
        if(intval($_POST['idpergunta'])){
          if($conexao->query("UPDATE perguntas SET texto='".$_POST['texto']."',identificador='".$_POST['identificador']."',iddep=".intval($_POST['iddep']).",idquest=".intval($_POST['idquest']).",idgrupo=".intval($_POST['idgrupo']).",ordem='".intval($_POST['ordem'])."' WHERE idpergunta=".intval($_POST['idpergunta']) )){
            $mensagem = 'Registro alterado';
        }else
            $mensagem = 'Erro ao alterar registro: '. $conexao->erro();
          $idpergunta = $_POST['idpergunta'];  
      }else{
          if( $conexao->query("INSERT INTO perguntas(texto,idgrupo,idquest,identificador,ordem) VALUES('".$_POST['texto']."',".intval($_POST['idgrupo']).",".intval($_POST['idquest']).",'".$_POST['identificador']."','".$_POST['ordem']."')" ))
            $mensagem = 'Registro cadastrado';
          else
            $mensagem = 'Erro ao cadastrar: '. $conexao->erro();
          
          $idpergunta = mysql_insert_id();  
            
      }
      
  }

  
/*
 * Compose the form output by including a HTML form template with PHP code
 * interleaaved with calls to insert form input field
 * parts in the layout HTML.
 */

    $form->StartLayoutCapture();

    $ancora = "#perg".$row['idpergunta'];

?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Cadastrar Pergunta</title>
<link rel="stylesheet" type="text/css" href="estilo.css" /> 
</head>
<body>
<a style="float:right" href="../questionario/edita.php?id=<?=$idq?><?=$ancora?>">Voltar para o questionário</a> 
<h1>Cadastrar Pergunta</h1>
<? if ($mensagem){ ?>
<span class="aviso" name="aviso"><?=$mensagem?></span>
<? } ?>
    <table>
    <tr><th><? $form->AddLabelPart(array("FOR"=>"grupo")); ?></th><td><? $form->AddInputPart("grupo"); ?><a class="new" href="../grupo/cadastro.php">Novo grupo</a></td></tr>
    <tr><th><? $form->AddLabelPart(array("FOR"=>"identificador")); ?></th><td><? $form->AddInputPart("identificador"); ?></td></tr>
    <tr><th><? $form->AddLabelPart(array("FOR"=>"texto")); ?></th><td><? $form->AddInputPart("texto"); ?></td></tr>
    <tr><th><? $form->AddLabelPart(array("FOR"=>"ordem")); ?></th><td><? $form->AddInputPart("ordem"); ?></td></tr>

    <tr>
    <td colspan="2"><? 

        $form->AddInputPart("idpergunta");
        $form->AddInputPart("idquest");

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
