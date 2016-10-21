<?php
if(!defined("METABASE_MANAGER_ODBC_MSACCESS_INCLUDED"))
{
	define("METABASE_MANAGER_ODBC_MSACCESS_INCLUDED",1);

/*
 * manager_odbc_msaccess.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/metabase/manager_odbc_msaccess.php,v 1.2 2002/12/30 14:20:47 mlemos Exp $
 *
 */

class metabase_manager_odbc_msaccess_class extends metabase_manager_odbc_class
{
	Function CreateDatabase(&$db,$name)
	{
		return($db->SetError("CreateDatabase","it is not possible to create Microsoft Access databases via ODBC using SQL"));
	}

	Function DropDatabase(&$db,$name)
	{
		return($db->SetError("DropDatabase","it is not possible to drop Microsoft Access databases via ODBC using SQL"));
	}

	// If there is ONLY the AUTOINCREMENT field, we have to insert a numeric value into it every time
	// That is fine here, but later we need the 'foo' field to insert NULL values into it for auto-incrementation
	Function CreateSequence(&$db,$name,$start)
	{
		if($db->Query("CREATE TABLE _sequence_$name (sequence AUTOINCREMENT NOT NULL PRIMARY KEY, foo BIT)"))
			return($db->Query("INSERT INTO _sequence_$name(sequence) VALUES(".(intval($start)-1).")"));
		return(0);
	}

	Function DropSequence(&$db,$name)
	{
		return($db->Query("DROP TABLE _sequence_$name"));
	}

	Function GetSequenceCurrentValue(&$db,$name,&$value)
	{
		if(!($result=$db->Query("SELECT sequence FROM _sequence_$name")))
			return(0);
		if($db->NumberOfRows($result)==0)
		{
			$db->FreeResult($result);
			return($db->SetError("Get sequence current value","could not find value in sequence table"));
		}
		$value=intval($db->FetchResult($result,0,0));
		$db->FreeResult($result);
		return(1);
	}

};
}
?>