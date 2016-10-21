<?php


#######################
#  1º passo: Instanciando a variável para conexão

			// Incluindo a classe para conectar com o banco
            include("lib/db_mysql5.php");
			
			// Instanciando a variável $conexao
            $conexao = new db_mysql5('sisquest','dbserver','root','EkqtDzo4foycE');
            global $conexao;

function desconectaDB($conexao){
    $conexao->desconecta();
}
register_shutdown_function('desconectaDB',$conexao);

  
?>
