<?php
    require_once("../db.php");
    require_once('../lib/functions.inc.php');
    $sql = "SELECT idquest,titulo,descricao FROM questionarios  WHERE idquest = ". intval($_GET['id']);
    $conexao->conecta();
    $conexao->query($sql,true);

    if($conexao->num_rows<=0) die('Questionário inexistente');
    list($idquest,$titulo,$descricao) = $conexao->fetch_array();
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>Lista de Respostas - <?= $titulo ?></title>
<link rel="stylesheet" type="text/css" href="../estilo.css" />
<meta content="" name="" />
</head>
<body>
<h1>Lista de Respostas - <?= $titulo ?></h1>
<br>
<?php
  $conexao->conecta();
  
  #campos
  $conexao->query("SELECT * FROM perguntas WHERE idquest = ".intval($_GET['id']));
  while($row = $conexao->fetch_array()){
      $k=$row['idpergunta'];
      $campos[$k]=$row['texto'];
  }

  
  
?>
<table border="1">
<tr>
<th>Usuário</th>
<th>Data</th>
<?
foreach($campos as $campo){
    echo "<th>$campo</th>";
}
?>
</tr>
<?
# Carrega respostas
    $conexao->query( "SELECT * from respostas r, questionarios q WHERE r.idquest=q.idquest AND r.idquest=".intval($_GET['id']) );
    $rowResp = $conexao->fetch_array();
    if($conexao->num_rows<=0) die('Erro ao selecionar resposta');
    
    $idquest = $rowResp['idquest'];
    $idresposta = $rowResp['idresposta'];
    $titulo = $rowResp['titulo'];
    $idpergunta = $row['idpergunta'];
    
    #valores
    $sqlResp = "SELECT c.idcampo,c.tipo,c.rotulo,c.idpergunta,c.tipo,d.valor FROM dados d, campos c WHERE d.idcampo=c.idcampo AND idresposta = ". intval($idresposta);
    $conexao->query($sqlResp,true);
 
  while($rowVal = $conexao->fetch_array()){
      $kp=$rowVal['idpergunta'];
      $kc=$rowVal['idcampo'];
      switch($rowVal['tipo']){
          case "checkbox":
          case "radio":
            $valores[$kp][] = ($rowVal['rotulo']) ? $rowVal['rotulo']  : $rowVal['valor'];
            break;
            
          case "text" :
          case "textarea" :
            $valores[$kp][] = ($rowVal['rotulo']) ? $rowVal['rotulo']." ".$rowVal['valor']  : $rowVal['valor'];
            break;
          
      }
  }
  
  $conexao->query("SELECT * FROM respostas WHERE idquest = ".intval($_GET['id']));  
  while($row = $conexao->fetch_array()){
?><tr>
<td><?= $row['usuario'] ?></td> 
<td><?= MudaData($row['data']) ?></td>

<?
    
  
  #valores
    foreach($campos as $key=>$campo){
    echo "<td>";
    if(is_array($valores[$key]) && count($valores[$key]))
        foreach($valores[$key] as $valor)
            echo "<span style='white-space:nowrap'>$valor</span></br>";
    echo "</td>";
    }
  
    
?>
</td>
</tr>

<? 
  
  }

?>
</table>

 

</body>
</html>
