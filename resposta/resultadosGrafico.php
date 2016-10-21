<?php
require("../util/formsgeneration/forms.php");
require_once('../lib/functions.inc.php');
require_once("../db.php");


$idp = intval($_POST['idp']);
$idq = ($_POST['idq']) ? intval($_POST['idq']) : 0;
$tipo_grafico = intval($_POST['tipo_grafico']);

$conexao->conecta();




# Pergunta 1
$conexao->query("SELECT idpergunta,texto,identificador,iddep FROM perguntas WHERE idquest=$idq AND idpergunta IN ($idp1) ORDER BY ordem");
$perg[1] = $conexao->fetch_array();




# Contar as opções


##############
# Fazer com que os dados fiquem numa array
# Colocar o cabeçalho com as opções da Perg. (1/2) numa linha
# Depois em cada linha colocar uma das opções da Perg (1/2) e colocar os valores
# Montar a tabela a partir desta array
##############
  
?>
