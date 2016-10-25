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
        
    if( is_array($grupos) )
    foreach($grupos as $rowGrupo){
        
        ?>
        <div class="grupo">
		<div class="edit" style="float:right">
		  <a id="btnEdit_<?= $rowGrupo['idgrupo'] ?>" class="edit" href="../grupo/cadastro.php?id=<?= $rowGrupo['idgrupo'] ?>&amp;idq=<?= $rowGrupo['idquest'] ?>">Editar</a>
		  |
		  <a id="btnEdit_<?= $rowGrupo['idgrupo'] ?>" class="edit" href="../grupo/apagar.php?id=<?= $rowGrupo['idgrupo'] ?>" onclick="return confirm('Tem certeza??')">Apagar</a>
		</div>
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
