<div id="questionario">
<?php
	if($error_message!="")
	{

/*
 * There was a validation error.  Display the error message associated with
 * the first field in error.
 */
 		$active = 0;
 		$output = '<b>'.$error_message.'</b>';
 		$title = "Erros";
 		$icon='';
 		require(dirname(__FILE__).'/message.html.php');
 		$active = 1;
	}
        
    
    foreach($grupos as $rowGrupo){
        
        ?>
        <div class="grupo">
        <h3><?= $rowGrupo['titulo'] ?></h3>
        <h4><?= $rowGrupo['descricao'] ?></h4>
            <div class="perguntas">
            <?php

	            include($perguntas_template);

            ?>
            </div>
        </div>
        <?php
        
    } // FimForeach

?>

</div>