<?php
require_once('../lib/functions.inc.php');
require_once("../db.php");


$idp = intval($_POST['idp']);
if($idp==0) die("Número da pergunta inválido");
$idq = ($_POST['idq']) ? intval($_POST['idq']) : 0;
if($idq==0) die("Número do questionário inválido");
$tipografico = ($_POST['tipografico']) ? $_POST['tipografico'] : 2; # Barras Horizontais
$altura = ($_POST['altura']) ? intval($_POST['altura']) : 600;
$largura = ($_POST['largura']) ? intval($_POST['largura']) : 800;
$porcentagem = ($_POST['porcentagem']) ? intval($_POST['porcentagem']) : 0;
$ordenar = ($_POST['ordenar']) ? intval($_POST['ordenar']) : 1;
$titulografico = ($_POST['titulografico']) ? $_POST['titulografico'] : "";
$subtitulografico = ($_POST['subtitulografico']) ? $_POST['subtitulografico'] : "";
$larguralegenda = 7;


$conexao->conecta();

# Pergunta
$sql = "SELECT idpergunta,texto,identificador,iddep FROM perguntas WHERE idquest=$idq AND idpergunta IN ($idp) ORDER BY ordem";
$conexao->query($sql);
$perg[1] = $conexao->fetch_array();

# Listar opções
listarOpcoes($perg[1]);

#Legenda e dados
unset($legend);
unset($data);
unset($cont);
$cont=0;
foreach($perg[1]['opcoes'] as $k => $opcaoP1){
    $legend[$cont] = $opcaoP1['rotulo'];
    $data[$cont] = $opcaoP1['contador'];
	$cont++;
}

# Tipo de grafico

include ("../lib/jpgraph/src/jpgraph.php");

switch ($tipografico){
	
	
case 1: # Barras Verticais

case 2: # Barras Horizontais
	include ("../lib/jpgraph/src/jpgraph_bar.php");

	// Set the basic parameters of the graph 
	$graph = new Graph($largura,$altura,'auto');
	$graph->SetScale("textlin");

	// Rotate graph 90 degrees and set margin
	$graph->Set90AndMargin( ($larguralegenda * 10 + 20), 30, 70, 30 ); # esquerda, direita, acima, abaixo

	// Nice shadow
	$graph->SetShadow();

	// Setup X-axis
	$graph->xaxis->SetTickLabels($legend);
	$graph->xaxis->SetFont(FF_VERDANA,FS_NORMAL,12);

	// Some extra margin looks nicer
	$graph->xaxis->SetLabelMargin(10);#margem da legenda p/ o grafico

	// Label align for X-axis
	$graph->xaxis->SetLabelAlign('right','center');


	// Add some grace to y-axis so the bars doesn't go
	// all the way to the end of the plot area
	$graph->yaxis->scale->SetGrace(20);
	$graph->yaxis->SetPos('max');
	// First make the labels look right
	$graph->yaxis->SetLabelAlign('center','top');
	$graph->yaxis->SetLabelSide(SIDE_RIGHT);
	// The fix the tick marks
	$graph->yaxis->SetTickSide(SIDE_LEFT);

	// We don't want to display Y-axis
	#$graph->yaxis->Hide();

	// Now create a bar pot
	$bplot = new BarPlot($data);
	$bplot->SetFillColor("orange");
	#$bplot->SetShadow();

	//You can change the width of the bars if you like
	//$bplot->SetWidth(0.5);

	// We want to display the value of each bar at the top
	$bplot->value->Show();
	$bplot->value->SetFont(FF_ARIAL,FS_BOLD,12);
	$bplot->value->SetAlign('left','center');
	$bplot->value->SetColor("black","darkred"); #cores do valor da barra
	

	
	if($porcentagem==1){
		$total = array_sum($data);
		$bplot->value->SetFormatCallback('formatPercentArrayMap');
	} else {
		$bplot->value->SetFormat('%s');
	}
	
	#$graph->SetY2Scale('lin',0,100);
	
	// Add the bar to the graph
	$graph->Add($bplot);
	
	break;
	
	
case 3: # Pizza
	include ("../lib/jpgraph/src/jpgraph_pie.php");
	
	$p1 = new PiePlot($data);
	$p1->SetLegends($legend);
	$p1->SetCenter(0.337);

	if($porcentagem==1){
		$p1->SetValueType(PIE_VALUE_PERCENTAGE);
		$p1->value->SetFormat('%.2f%%');
	} else {
		$p1->SetValueType(PIE_VALUE_ABS);
		$p1->value->SetFormat('%s');
	}
	

	$p1->value->SetFont(FF_VERDANA,FS_BOLD,12);
	$p1->SetGuideLines();
	$p1->SetGuideLinesAdjust(1.4);
	
	$p1->SetTheme("pastel");

	$graph = new PieGraph($largura,$altura,"auto");
	$graph->SetShadow();
	$graph->legend->SetFont(FF_VERDANA,FS_NORMAL,10);

	$graph->Add($p1);
	break;
	
	
case 4: # Linha
	include ("../lib/jpgraph/src/jpgraph_line.php");
	// Create the graph. These two calls are always required
	$graph = new Graph($largura,$altura,"auto");    
	$graph->SetScale("textlin");

	// Create the linear plot
	$lineplot=new LinePlot($data);
	$lineplot->SetColor("blue");

	// Add the plot to the graph
	$graph->Add($lineplot);
	
}

// Setup title
$graph->title->Set($titulografico);
$graph->title->SetFont(FF_VERDANA,FS_BOLD,14);
$graph->subtitle->Set($subtitulografico);
$graph->subtitle->SetFont(FF_VERDANA,FS_NORMAL,11);
$graph->Stroke("grafico$idp.png");

header("Location: grafico$idp.png");

?>
