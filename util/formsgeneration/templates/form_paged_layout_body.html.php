<?php
/*
 * @(#) $Id: form_paged_layout_body.html.php,v 1.1 2008/02/20 07:44:43 mlemos Exp $
 *
 * Add the automatic layout custom input to render all inputs.
 */

?><div style="width: 30em; height: 50ex; overflow: auto;">
<?php

	$form->AddInputPart("layout");

?></div>
<?php

	if(!$doit)
	{

/*
 * If the form was submitted with valid values, there is no need to display
 * the submit button again.
 */

?>
<hr />
<center><?php
		$form->AddInputPart("image_subscribe");
?> <?php
		$form->AddInputPart("button_subscribe");
?></center><?php
	}
?>
