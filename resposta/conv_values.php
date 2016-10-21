<?php
require_once('../lib/functions.inc.php');
require_once("../db.php");
 
$conexao->conecta();
 
# Total
$sql = "SELECT idcampo,params FROM `campos` WHERE `params` LIKE '%VALUE=%' AND valor IS NULL";
$conexao->query($sql);
while($row = $conexao->fetch_array()){
    unset($params);
    $params = StringToArray($row['params']);

    $sql = "UPDATE `campos` SET valor = '".$params['VALUE']."' WHERE `idcampo` = ".$row['idcampo'];
    mysql_query($sql);
    $cont++;
}


 echo $cont;
?>

<a href="javascript:history.back()">Voltar</a>
</body>
</html>
