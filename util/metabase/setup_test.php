<?php
/*
 * setup_test.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/metabase/setup_test.php,v 1.7 2002/12/11 22:52:24 mlemos Exp $
 *
 */

	require("metabase_parser.php");
	require("metabase_manager.php");
	require("metabase_database.php");
	require("metabase_interface.php");
	require("xml_parser.php");

Function Output($message)
{
	echo $message,"\n";
}

Function Dump($output)
{
	echo $output;
}

	$input_file=($argc<2 ? "test.schema" : $argv[1]);
	$variables=array(
		"create"=>"1"
	);
	$arguments=array(
		"Type"=>"mysql",
		"User"=>"root",
		"Debug"=>"Output"
	);
	$manager=new metabase_manager_class;
	$manager->debug="Output";
	$success=$manager->UpdateDatabase($input_file,$input_file.".before",$arguments,$variables);
	if($success)
	{
		echo $manager->DumpDatabase(array(
			"Output"=>"Dump",
			"EndOfLine"=>"\n"
		));
	}
	else
		echo "Error: ".$manager->error."\n";
	if(count($manager->warnings)>0)
		echo "WARNING:\n",implode($manager->warnings,"!\n"),"\n";
	if($manager->database)
		echo MetabaseDebugOutput($manager->database);

?>