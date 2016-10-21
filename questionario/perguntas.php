<?php
      require("../util/formsgeneration/forms.php");
    require_once('../lib/functions.inc.php');
    require_once('../db.php');

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
    $sqlGrupos = "SELECT g.idgrupo,g.titulo,g.descricao,g.ordem FROM grupos g "
    ." WHERE g.idquest = ". intval($_GET['id']);
    ;

    $conexao->query($sqlGrupos,true);

    if($conexao->num_rows<=0) die('Sem grupos');
    while($rowGrupo = $conexao->fetch_array()){
        $grupos[] = $rowGrupo;

        
            #Campos
            $sqlCampos = "SELECT idcampo,nome,tipo,rotulo,iddep,params FROM campos c, relgruposperg rgp "
            ." WHERE c.idpergunta=rgp.idpergunta AND idgrupo = ". intval($rowGrupo['idgrupo']);
            ;

            $qryCampos = mysql_query($sqlCampos);
            $numRows = mysql_num_rows($qryCampos);
            
            while($rowOpc= mysql_fetch_array($qryCampos)){
                
                $params = StringToArray($rowOpc['params']);
                $params['ID'] = getIdCampo($rowOpc['idcampo']);
                $params['TYPE'] = $rowOpc['tipo'];
                $params['NAME'] = $rowOpc['nome'];
                $params['LABEL'] = $rowOpc['rotulo'];
                

                $form->AddInput( $params );


            } // FimWhile Opc 
        
        
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
<h1>Lista de Grupo de Questionários</h1>

<div id="toolbar"><a href="../grupo/cadastro.php" class="link_cadastrar">Cadastrar grupo</a></div>



<?php
    
    $form->StartLayoutCapture();

    $perguntas_template = "perguntas.html.php";
    require("grupos.html.php"); 
  
    $form->AddInputPart("button_submit"); 
    $form->AddInputPart("doit");
    
    $form->EndLayoutCapture();
    
    $form->DisplayOutput(); 

?>
</table>
 

</body>
</html>
