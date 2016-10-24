<?php
require("../util/formsgeneration/forms.php");
require_once('../lib/functions.inc.php');
require_once("../db.php");

$idp1 = intval($_POST['idp1']);
$idp2 = intval($_POST['idp2']);
$idq = ($_POST['idq']) ? intval($_POST['idq']) : 0;
$ordenar = ($_POST['ordenar']) ? intval($_POST['ordenar']) : 1;
$formatoValor = ($_POST['formatoValor']) ? intval($_POST['formatoValor']) : 1;
$tipo_grafico = intval($_POST['tipo_grafico']);

$conexao->conecta();

# Total
$conexao->query("SELECT count(*) FROM respostas WHERE idquest=$idq");
list($totalResp) = $conexao->fetch_array();

# Pergunta 1
$conexao->query("SELECT idpergunta,texto,identificador,iddep FROM perguntas WHERE idquest=$idq AND idpergunta IN ($idp1) ORDER BY ordem");
$perg[1] = $conexao->fetch_array();

# Pergunta 2
$conexao->query("SELECT idpergunta,texto,identificador,iddep FROM perguntas WHERE idquest=$idq AND idpergunta IN ($idp2) ORDER BY ordem");
$perg[2] = $conexao->fetch_array();


# Listar as opções da Pergunta 1
listarOpcoes($perg[1]);

# Listar as opções da Pergunta 2
listarOpcoes($perg[2]);

# Valor total
$conexao->query("SELECT count(*) FROM respostas r JOIN dados d ON r.idresposta=d.idresposta WHERE idquest=$idq AND idpergunta=$idp1");
list($totalRespostas) = $conexao->fetch_array(); 

?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Gerar Relatório</title>
<link rel="stylesheet" type="text/css" href="estilo.css" />
<style>
.perg1{ color: blue }
.perg2{ color: green }
.zero { color: gray }
</style> 
</head>
<body>
<a style="float:right" href="javascript:history.back()">Voltar</a>
<h1>Relatório</h1>

<h2>
<span class="perg1"><?= ereg_replace('[:?\.]$','',$perg[1]['identificador'] ." ". $perg[1]['texto']) ?></span>
por
<span class="perg2"><?= ereg_replace('[:?\.]$','',$perg[2]['identificador'] ." ". $perg[2]['texto']) ?></span>
<?= ($formatoValor > 1) ? '(Em %)' : '' ?>
</h2>


<table border=1>

<? #Montar a primeira linha com os campos da Perg. 2 ?>
   <tr>
   <th></th>
   
<?
      #foreach2 - Mostrar colunas
      foreach($perg[2]['opcoes'] as $k => $opcaoP2){

?>     
     
      <th><span class="perg2"><?= (empty($opcaoP2['rotulo'])) ? '(vazio)' : $opcaoP2['rotulo'] ?></span></th>
     
<?    }  // Fim foreach2 ?>
   <th>Total</th>
   </tr>

<?
  #foreach1 - Mostrar linha
  foreach($perg[1]['opcoes'] as $k => $opcaoP1){
	 
      # valor - contagem inteira da linha atual
      $sql = "
SELECT valor,count(*) as contador FROM 
(SELECT idcampo,valor,idresposta FROM dados WHERE idpergunta = '".$idp2."') as resultadopergunta
WHERE (SELECT count(*) FROM dados WHERE idresposta=resultadopergunta.idresposta AND idcampo = '".$opcaoP1['idcampo']."' AND valor LIKE '".$opcaoP1['valor']."' ) = 1 
GROUP BY valor
";
# teste
#echo $sql."<br>";
	$conexao->query($sql);

	unset($rowVal,$rowValTotal,$dados);
	while ($rowDados = $conexao->fetch_array()) {
		$chave = $rowDados['valor'];
		$dados[$chave] = $rowDados['contador'];
	}
	
	$rowValTotal = $opcaoP1['contador'];
	
	# Formatar o valor total
	switch($formatoValor){
		case 3 :
			$rowValTotal = formatPercent($rowValTotal,$totalRespostas);
			break;
		case 2 :
			$rowValTotal = formatPercent($rowValTotal,$opcaoP1['contador']);
			break;
		#case 1 :
		
	}

?>
    <tr>
    <th><span class="perg1"><?= (empty($opcaoP1['rotulo'])) ? '(vazio)' : $opcaoP1['rotulo'] ?></span></th>
<?
	
		#foreach2 - Mostrar colunas
		foreach($perg[2]['opcoes'] as $k => $opcaoP2){
			
			$chave = $opcaoP2['valor'];
			$rowVal = intval($dados[$chave]);
			
			# Formatar o valor
			switch($formatoValor){
				case 3 :
					$rowVal = formatPercent($rowVal,$totalRespostas);
					break;
				case 2 :
					$rowVal = formatPercent($rowVal,$opcaoP1['contador']);
					break;
				#case 1 :
				
			}
?>     
     
      <td align="right"><?= ($rowVal==0) ? '<span class="zero">'.$rowVal.'</span>' : $rowVal ?></td>
     
<?    }  // Fim foreach2 ?>
<td align="right"><?= $rowValTotal ?></td>
    </tr>   
<? } // Fim foreach1 ?>
</table>
Total de respostas: <?= $totalRespostas ?>
<br>
<br>
<a href="javascript:history.back()">Voltar</a>
</body>
</html>
