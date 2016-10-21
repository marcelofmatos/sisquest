<?php
require("../util/formsgeneration/forms.php");
require_once('../lib/functions.inc.php');
require_once("../db.php");

$idg = intval($_GET['id']); 
$idq = intval($_GET['idq']); 

?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Cadastrar Grupo de Questionário</title>
<link rel="stylesheet" type="text/css" href="estilo.css" /> 
</head>
<body>
<h1>Cadastrar Grupo de Questionário</h1>
<?php

  $conexao->conecta();
 
  if(intval($idg)){
	  $sqlGrupos = "SELECT * FROM grupos WHERE idgrupo=".intval($idg);
      $conexao->query( $sqlGrupos );
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
    $campoidgrupo['TYPE']='hidden';
    $campoidgrupo['NAME']='idgrupo';
    $campoidgrupo['VALUE']=($row['idgrupo']) ? $row['idgrupo'] : '';
    $form->AddInput($campoidgrupo);

    #Questionário
	/*
    $campo['TYPE'] = "select";
    $campo['NAME'] = "idquest";
    $campo['ID'] = "idquest";
    $campo['LABEL'] = "<u>Q</u>uestionário";
    $campo['ACCESSKEY'] = "q";
    $idquest = ($_GET['idq']) ? intval($_GET['idq']) : 0;
    $campo['VALUE']=($row['idquest']) ? $row['idquest'] : $idquest;
    # Lista de opções
    $opt[0] = "--- Selecione ---";
		$sqlGrupos = "SELECT idquest,titulo FROM questionarios";
		if(intval($_GET['idq'])) $sqlGrupos .= " WHERE idquest=".intval($idq);
      $conexao->query( $sqlGrupos );
      while($rowPerguntas = $conexao->fetch_array()){
          $k = $rowPerguntas['idquest'];
          $opt[$k] = $rowPerguntas['titulo'];
      }
	  	  
    $campo['OPTIONS'] = $opt;
    $campo['ValidateAsDifferentFromText'] = 0;
    $campo['ValidateAsDifferentFromTextErrorMessage'] = "Selecione uma opção válida";
    $form->AddInput($campo);
	* 
	  */
	  
    $campo['TYPE'] = "hidden";
    $campo['NAME'] = "idquest";
    $campo['ID'] = "idquest";
    $campo['VALUE']=($row['idquest']) ? $row['idquest'] : $idq;
    $campo['ValidateAsDifferentFromText'] = 0;
    $campo['ValidateAsDifferentFromTextErrorMessage'] = "ID do Questionário inválido";
    $form->AddInput($campo);
	  

    
	# Titulo
    $campoTitulo['TYPE'] = "text";
    $campoTitulo['NAME'] = "titulo";
    $campoTitulo['ID'] = "titulo";
    $campoTitulo['VALUE']=($row['titulo']) ? $row['titulo'] : '';
    $campoTitulo['SIZE'] = 50;
    $campoTitulo['ValidationErrorMessage'] = "Título inválido.";
    $campoTitulo['LABEL'] = "<u>T</u>ítulo";
    $campoTitulo['ACCESSKEY'] = "t";
    $form->AddInput($campoTitulo);
    
    $campoDesc['TYPE'] = "textarea";
    $campoDesc['NAME'] = "descricao";
    $campoDesc['ID'] = "descricao";
    $campoDesc['VALUE']=($row['descricao']) ? $row['descricao'] : '';
    $campoDesc['COLS'] = 50;
    $campoDesc['ValidationErrorMessage'] = "Descrição inválida.";
    $campoDesc['LABEL'] = "<u>D</u>escrição";
    $campoDesc['ACCESSKEY'] = "d";
    $form->AddInput($campoDesc);
    
    
    $campoAtivo['TYPE'] = "checkbox";
    $campoAtivo['NAME'] = "ativo";
    $campoAtivo['ID'] = "ativo";
    $campoAtivo['VALUE'] = "1";
    $campoAtivo['CHECKED'] = $row['ativo'];
    $campoAtivo['LABEL'] = "<u>A</u>tivo";
    $campoAtivo['ACCESSKEY'] = "a";
    $campoAtivo['ReadOnlyMark'] = "[X]";
    
    $form->AddInput($campoAtivo);
    
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
      
      if(intval($_POST['idgrupo'])){
          $sql = "UPDATE grupos SET idquest=".intval($_POST['idquest']).",titulo='".$_POST['titulo']."',descricao='".$_POST['descricao']."' WHERE idgrupo=".intval($_POST['idgrupo']);
          if($conexao->query( $sql )){
 
              
            echo '<span class="aviso">Registro alterado</span>';
          }else
            echo '<span class="aviso">Erro ao alterar registro: '. $conexao->erro() .'</span>';
            
      }else{
          if( $conexao->query("INSERT INTO grupos(idquest,titulo,descricao) VALUES(".intval($_POST['idquest']).",'".$_POST['titulo']."','".$_POST['descricao']."')" ))
            echo '<span class="aviso" name="aviso">Registro cadastrado</span>';
          else
            echo '<span class="aviso" name="aviso">Erro ao cadastrar: '. $conexao->erro().'</span>';
      }
      $form->ReadOnly=1;
  }

?>

<?php

/*
 * Compose the form output by including a HTML form template with PHP code
 * interleaaved with calls to insert form input field
 * parts in the layout HTML.
 */

    $form->StartLayoutCapture();


?>
    <table>
    <tr><th><? $form->AddLabelPart(array("FOR"=>"titulo")); ?></th><td><? $form->AddInputPart("titulo"); ?></td></tr>
    <tr><th><? $form->AddLabelPart(array("FOR"=>"descricao")); ?></th><td><? $form->AddInputPart("descricao"); ?></td></tr>
    <tr><th><? $form->AddLabelPart(array("FOR"=>"ativo")); ?></th><td><? $form->AddInputPart("ativo"); ?></td></tr>

    <tr>
    <td colspan="2"><? 
		$form->AddInputPart("idquest");
        $form->AddInputPart("idgrupo");
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
