<?php
/*
 * test_form.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/forms/test_form.php,v 1.32 2006/12/20 06:21:16 mlemos Exp $
 *
 */

/*
 * Include form class code.
 */
	require("forms.php");

/*
 * Create a form object.
 */
	$form=new form_class;

/*
 * Define the name of the form to be used for example in Javascript
 * validation code generated by the class.
 */
	$form->NAME="subscription_form";

/*
 * Use the GET method if you want to see the submitted values in the form
 * processing URL, or POST otherwise.
 */
	$form->METHOD="POST";

/*
 * Make the form be displayed and also processed by this script.
 */
	$form->ACTION="";

/*
 * Specify a debug output function you really want to output any
 * programming errors.
 */
	$form->debug="trigger_error";

/*
 * Define a warning message to display by Javascript code when the user
 * attempts to submit the this form again from the same page.
 */
	$form->ResubmitConfirmMessage=
		"Are you sure you want to submit this form again?";

/*
 * Output previously set password values
 */
	$form->OutputPasswordValues=1;

/*
 * Output multiple select options values separated by line breaks
 */
	$form->OptionsSeparator="<br />\n";

/*
 * Output all validation errors at once.
 */
	$form->ShowAllErrors=1;

/*
 * CSS class to apply to all invalid inputs.
 * Set to a non-empty string to specify the invalid input CSS class
 */
	$form->InvalidCLASS='invalid';

/*
 * Style to apply to all invalid inputs when you just want to override a
 * few style attributes, instead of replacing the CSS class
 * Set to a non-empty string to specify the invalid input style attributes
 *
 * $form->InvalidSTYLE='background-color: #ffcccc; border-color: #000080';
 *
 */

/*
 * Text to prepend and append to the validation error messages.
 */
	$form->ErrorMessagePrefix="- ";
	$form->ErrorMessageSuffix="";

/*
 * Define the form field properties even if they may not be displayed.
 */
	$form->AddInput(array(
		"TYPE"=>"text",
		"NAME"=>"email",
		"ID"=>"email",
		"MAXLENGTH"=>100,
		"Capitalization"=>"lowercase",
		"ValidateAsEmail"=>1,
		"ValidationErrorMessage"=>"It was not specified a valid e-mail address.",
		"LABEL"=>"<u>E</u>-mail address",
		"ACCESSKEY"=>"E"
	));
	$form->AddInput(array(
		"TYPE"=>"select",
		"NAME"=>"credit_card_type",
		"ID"=>"credit_card_type",
		"VALUE"=>"pick",
		"SIZE"=>1,
		"OPTIONS"=>array(
			"pick"=>"Pick a credit card type",
			"unknown"=>"Unknown",
			"mastercard"=>"Master Card",
			"visa"=>"Visa",
			"amex"=>"American Express",
			"dinersclub"=>"Diners Club",
			"carteblanche"=>"Carte Blanche",
			"discover"=>"Discover",
			"enroute"=>"enRoute",
			"jcb"=>"JCB"
		),
		"ValidateAsDifferentFromText"=>"pick",
		"ValidateAsDifferentFromTextErrorMessage"=>
			"Pick the credit card type or set to Unknown if you do not know the type.",
		"ValidationErrorMessage"=>"It was not specified a valid credit card type.",
		"LABEL"=>"Credit card t<u>y</u>pe",
		"ACCESSKEY"=>"y"
	));
	$form->AddInput(array(
		"TYPE"=>"text",
		"NAME"=>"credit_card_number",
		"ID"=>"credit_card_number",
		"SIZE"=>20,
		"ValidateOptionalValue"=>"",
		"ValidateAsCreditCard"=>"field",
		"ValidationCreditCardTypeField"=>"credit_card_type",
		"ValidationErrorMessage"=>"It wasn't specified a valid credit card number.",
		"LABEL"=>"Credit card <u>n</u>umber",
		"ACCESSKEY"=>"n"
	));
	$form->AddInput(array(
		"TYPE"=>"text",
		"NAME"=>"user_name",
		"ID"=>"user_name",
		"MAXLENGTH"=>60,
		"ValidateAsNotEmpty"=>1,
		"ValidationErrorMessage"=>"It was not specified a valid name.",
		"LABEL"=>"<u>P</u>ersonal name",
		"ACCESSKEY"=>"P"
	));
	$form->AddInput(array(
		"TYPE"=>"text",
		"NAME"=>"age",
		"ID"=>"age",
		"ValidateAsInteger"=>1,
		"ValidationLowerLimit"=>18,
		"ValidationUpperLimit"=>65,
		"ValidationErrorMessage"=>"It was not specified a valid age.",
		"LABEL"=>"<u>A</u>ge",
		"ACCESSKEY"=>"A"
	));
	$form->AddInput(array(
		"TYPE"=>"text",
		"NAME"=>"weight",
		"ID"=>"weight",
		"ValidateAsFloat"=>1,
		"ValidationLowerLimit"=>10,
		"ValidationErrorMessage"=>"It was not specified a valid weight.",
		"LABEL"=>"<u>W</u>eight",
		"ACCESSKEY"=>"W"
	));
	$form->AddInput(array(
		"TYPE"=>"text",
		"NAME"=>"home_page",
		"ID"=>"home_page",
		"ReplacePatterns"=>array(
			/* trim whitespace at the beginning of the text value */
			"^[ \t\r\n]+"=>"",

			/* trim whitespace at the end of the text value */
			"[ \t\r\n]+\$"=>"",

			/* Assume that URLs starting with www. start with http://www. */
			"^([wW]{3}\\.)"=>"http://\\1",

			/* Assume that URLs that do not have a : in them are http:// */
			"^([^:]+)\$"=>"http://\\1",


			/* Assume at least / as URI . */
			"^(http|https)://(([-!#\$%&'*+.0-9=?A-Z^_`a-z{|}~]+\.)+[A-Za-z]{2,6}(:[0-9]+)?)\$"=>"\\1://\\2/"
		),
		"ValidateRegularExpression"=>
			'^(http|https)\://(([-!#\$%&\'*+.0-9=?A-Z^_`a-z{|}~]+\.)+[A-Za-z]{2,6})(\:[0-9]+)?(/)?/',
		"ValidationErrorMessage"=>"It was not specified a valid home page URL.",
		"LABEL"=>"H<u>o</u>me page",
		"ACCESSKEY"=>"o"
	));
	$form->AddInput(array(
		"TYPE"=>"text",
		"NAME"=>"alias",
		"ID"=>"alias",
		"MAXLENGTH"=>20,
		"Capitalization"=>"uppercase",
		"ValidateRegularExpression"=>array(
			"^[a-zA-Z]",
			"^[a-zA-Z0-9]+\$"
		),
		"ValidateRegularExpressionErrorMessage"=>array(
			"The alias must start with a letter.",
			"The alias may only contain letters and digits."
		),
		"ValidateAsNotEmpty"=>1,
		"ValidateAsNotEmptyErrorMessage"=>"It was not specified the alias.",
		"ValidateMinimumLength"=>5,
		"ValidateMinimumLengthErrorMessage"=>
			"It was specified an alias shorter than 5 characters.",
		"LABEL"=>"Acce<u>s</u>s name",
		"ACCESSKEY"=>"s"
	));
	$form->AddInput(array(
		"TYPE"=>"password",
		"NAME"=>"password",
		"ID"=>"password",
		"ONCHANGE"=>"if(value.toLowerCase) value=value.toLowerCase()",
		"ValidateAsNotEmpty"=>1,
		"ValidationErrorMessage"=>"It was not specified a valid password.",
		"LABEL"=>"Passwor<u>d</u>",
		"ACCESSKEY"=>"d",
		"ReadOnlyMark"=>"********"
	));
	$form->AddInput(array(
		"TYPE"=>"password",
		"NAME"=>"confirm_password",
		"ID"=>"confirm_password",
		"ONCHANGE"=>"if(value.toLowerCase) value=value.toLowerCase()",
		"ValidateAsEqualTo"=>"password",
		"ValidationErrorMessage"=>
			"The password is not equal to the confirmation.",
		"LABEL"=>"<u>C</u>onfirm password",
		"ACCESSKEY"=>"C",
		"ReadOnlyMark"=>"********"
	));
	$form->AddInput(array(
		"TYPE"=>"text",
		"NAME"=>"reminder",
		"ID"=>"reminder",
		"ValidateAsNotEmpty"=>1,
		"ValidateAsNotEmptyErrorMessage"=>
			"It was not specified a reminder phrase.",
		"ValidateAsDifferentFrom"=>"password",
		"ValidateAsDifferentFromErrorMessage"=>
			"The reminder phrase may not be equal to the password.",
		"LABEL"=>"Password <u>r</u>eminder",
		"ACCESSKEY"=>"r"
	));
	$form->AddInput(array(
		"TYPE"=>"select",
		"MULTIPLE"=>1,
		"NAME"=>"interests",
		"ID"=>"interests",
		"SELECTED"=>array(
			"other"
		),
		"SIZE"=>4,
		"OPTIONS"=>array(
			"arts"=>"Arts",
			"business"=>"Business",
			"computers"=>"Computers",
			"education"=>"Education",
			"entertainment"=>"Entertainment",
			"health"=>"Health",
			"news"=>"News",
			"politics"=>"Politics",
			"sports"=>"Sports",
			"science"=>"Science",
			"other"=>"Other"
		),
		"ValidateAsSet"=>1,
		"ValidationErrorMessage"=>"It were not specified any interests.",
		"LABEL"=>"<u>I</u>nterests",
		"ACCESSKEY"=>"I"
	));
	$form->AddInput(array(
		"TYPE"=>"checkbox",
		"NAME"=>"notification",
		"ID"=>"email_notification",
		"VALUE"=>"email",
		"CHECKED"=>0,
		"MULTIPLE"=>1,
		"ValidateAsSet"=>1,
		"ValidateAsSetErrorMessage"=>
			"It were not specified any types of notification.",
		"LABEL"=>"E-<u>m</u>ail",
		"ACCESSKEY"=>"m",
		"ReadOnlyMark"=>"[X]"
	));
	$form->AddInput(array(
		"TYPE"=>"checkbox",
		"NAME"=>"notification",
		"ID"=>"phone_notification",
		"VALUE"=>"phone",
		"CHECKED"=>0,
		"MULTIPLE"=>1,
		"LABEL"=>"P<u>h</u>one",
		"ACCESSKEY"=>"h",
		"ReadOnlyMark"=>"[X]"
	));
	$form->AddInput(array(
		"TYPE"=>"radio",
		"NAME"=>"subscription_type",
		"VALUE"=>"administrator",
		"ID"=>"administrator_subscription",
		"ValidateAsSet"=>1,
		"ValidateAsSetErrorMessage"=>
			"It was not specified the subscription type.",
		"LABEL"=>"Adm<u>i</u>nistrator",
		"ACCESSKEY"=>"i",
		"ReadOnlyMark"=>"[X]"
	));
	$form->AddInput(array(
		"TYPE"=>"radio",
		"NAME"=>"subscription_type",
		"VALUE"=>"user",
		"ID"=>"user_subscription",
		"LABEL"=>"<u>U</u>ser",
		"ACCESSKEY"=>"U",
		"ReadOnlyMark"=>"[X]"
	));
	$form->AddInput(array(
		"TYPE"=>"radio",
		"NAME"=>"subscription_type",
		"VALUE"=>"guest",
		"ID"=>"guest_subscription",
		"LABEL"=>"<u>G</u>uest",
		"ACCESSKEY"=>"G",
		"ReadOnlyMark"=>"[X]"
	));
	$form->AddInput(array(
		"TYPE"=>"button",
		"NAME"=>"toggle",
		"ID"=>"toggle",
		"VALUE"=>"On",
		"ONCLICK"=>"this.value=(this.value=='On' ? 'Off' : 'On'); alert('The button is '+this.value);",
		"LABEL"=>"Toggle <u>b</u>utton",
		"ACCESSKEY"=>"b"
	));
	$form->AddInput(array(
		"TYPE"=>"checkbox",
		"NAME"=>"agree",
		"ID"=>"agree",
		"VALUE"=>"Yes",
		"ValidateAsSet"=>1,
		"ValidateAsSetErrorMessage"=>"You have not agreed with the subscription terms.",
		"LABEL"=>"Agree with the <u>t</u>erms",
		"ACCESSKEY"=>"t"
	));

	$form->AddInput(array(
		"TYPE"=>"submit",
		"ID"=>"button_subscribe",
		"VALUE"=>"Submit subscription",
		"ACCESSKEY"=>"u"
	));
	$form->AddInput(array(
		"TYPE"=>"image",
		"ID"=>"image_subscribe",
		"SRC"=>"http://www.phpclasses.org/graphics/add.gif",
		"ALT"=>"Submit subscription",
		"STYLE"=>"border-width: 0px;"
	));

/*
 * Give a name to hidden input field so you can tell whether the form is to
 * be outputted for the first or otherwise it was submitted by the user.
 */
	$form->AddInput(array(
		"TYPE"=>"hidden",
		"NAME"=>"doit",
		"VALUE"=>1
	));

/*
 * Hidden fields can be used to pass context values between form pages,
 * like for instance database record identifiers or other information
 * that may help your application form processing scripts determine
 * the context of the information being submitted with this form.
 *
 * You are encouraged to use the DiscardInvalidValues argument to help
 * preventing security exploits performed by attackers that may spoof
 * invalid values that could be used for instance in SQL injection attacks.
 *
 * In this example, any value that is not an integer is discarded. If the
 * value was meant to be used in a SQL query, with this attack prevention
 * measure an attacker cannot submit SQL code that could be used to make
 * your SQL query retrieve unauthorized information to abuse your system.
 */
	$form->AddInput(array(
		"TYPE"=>"hidden",
		"NAME"=>"user_track",
		"VALUE"=>"0",
		"ValidateAsInteger"=>1,
		"DiscardInvalidValues"=>1
	));

/*
 * The following lines are for testing purposes.
 * Remove these lines when adapting this example to real applications.
 */
	if(defined("__TEST"))
	{
		if(IsSet($__test_options["ShowAllErrors"]))
			$form->ShowAllErrors=$__test_options["ShowAllErrors"];
		if(IsSet($__test_options["ErrorMessagePrefix"]))
			$form->ErrorMessagePrefix=$__test_options["ErrorMessagePrefix"];
		if(IsSet($__test_options["ErrorMessageSuffix"]))
			$form->ErrorMessageSuffix=$__test_options["ErrorMessageSuffix"];
	}

/*
 * Load form input values eventually from the submitted form.
 */
	$form->LoadInputValues($form->WasSubmitted("doit"));

/*
 * Empty the array that will list the values with invalid field after validation.
 */
	$verify=array();


/*
 * Check if the global array variable corresponding to hidden input field is
 * defined, meaning that the form was submitted as opposed to being displayed
 * for the first time.
 */
	if($form->WasSubmitted("doit"))
	{


/*
 * Therefore we need to validate the submitted form values.
 */
		if(($error_message=$form->Validate($verify))=="")
		{

/*
 * It's valid, set the $doit flag variable to 1 to tell the form is ready to
 * processed.
 */
			$doit=1;

		}
		else
		{

/*
 * It's invalid, set the $doit flag to 0 and encode the returned error message
 * to escape any HTML special characters.
 */
			$doit=0;
			$error_message=nl2br(HtmlSpecialChars($error_message));
		}
	}
	else
	{

/*
 * The form is being displayed for the first time, so it is not ready to be processed
 * and there is no error message to display.
 */
		$error_message="";
		$doit=0;
	}
  if($doit)
  {

/*
 * The form is ready to be processed, just output it again as read only to
 * display the submitted values.  A real form processing script usually may
 * do something else like storing the form values in a database.
 */
  	$form->ReadOnly=1;
  }

/*
 * If the form was not submitted or was not valid, make the page ONLOAD
 * event give the focus to the first form field or the first invalid field.
 */
	if(!$doit)
	{
		if(strlen($error_message))
		{
/*
 * If there is at least one field with invalid values, get the name of the
 * first field in error to make it get the input focus when the page is
 * loaded.
 */
			Reset($verify);
			$focus=Key($verify);
		}
		else
		{
/*
 * Make the email field get the input focus when the page is loaded
 * if there was no previous validation error.
 */
			$focus='email';
		}
/*
 * Connect the form to the field to get the input focus when the page
 * loads.
 */
		$form->ConnectFormToInput($focus, 'ONLOAD', 'Focus', array());
	}

	$onload = HtmlSpecialChars($form->PageLoad());

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Test for Manuel Lemos' PHP form class</title>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1">
<style type="text/css"><!--
.invalid { border-color: #ff0000; background-color: #ffcccc; }
// --></style>
</head>
<body onload="<?php	echo $onload; ?>" bgcolor="#cccccc">
<center><h1>Test for Manuel Lemos' PHP form class</h1></center>
<hr />
<?php

/*
 * Compose the form output by including a HTML form template with PHP code
 * interleaaved with calls to insert form input field
 * parts in the layout HTML.
 */

	$form->StartLayoutCapture();
	$title="Form class test";
	$body_template="form_body.html.php";
	require("templates/form_frame.html.php");
 	$form->EndLayoutCapture();

/*
 * Output the form using the function named Output.
 */
	$form->DisplayOutput();
?>
</body>
</html>
