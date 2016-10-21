<?php
if(!defined("METABASE_MANAGER_MSQL_INCLUDED"))
{
	define("METABASE_MANAGER_MSQL_INCLUDED",1);

/*
 * manager_msql.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/metabase/manager_msql.php,v 1.1 2003/01/08 04:47:32 mlemos Exp $
 *
 */

class metabase_manager_msql_class extends metabase_manager_database_class
{
	Function CreateDatabase(&$db,$name)
	{
		if(!$db->Connect())
			return(0);
		if(!msql_create_db($name,$db->connection))
			return($db->SetError("Create database",msql_error()));
		return(1);
	}

	Function DropDatabase(&$db,$name)
	{
		if(!$db->Connect())
			return(0);
		if(!msql_drop_db($name,$db->connection))
			return($db->SetError("Drop database",msql_error()));
		return(1);
	}

	Function CreateSequence(&$db,$name,$start)
	{
		if(!$db->Query("CREATE TABLE sequence_$name (dummy INT)"))
			return(0);
		if($db->Query("CREATE SEQUENCE ON sequence_$name STEP 1 VALUE $start"))
			return(1);
		$error=$db->Error();
		if(!$db->Query("DROP TABLE sequence_$name"))
			$db->warning="could not drop inconsistent sequence table";
		return($db->SetError("Create sequence",$error));
		return(0);
	}

	Function DropSequence(&$db,$name)
	{
		return($db->Query("DROP TABLE sequence_$name"));
	}

};
}
?>