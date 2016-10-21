<?php
if(!defined("METABASE_TEMPLATE_INCLUDED"))
{
	define("METABASE_TEMPLATE_INCLUDED",1);

/*
 * metabase_template.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/metabase/metabase_template.php,v 1.5 2002/12/11 22:52:24 mlemos Exp $
 *
 */

class metabase_template_class extends metabase_database_class
{
	/* PRIVATE DATA */
	
	var $last_error="";

	/* PUBLIC METHODS */

	Function CreateDatabase($name)
	{
		echo "CREATE DATABASE $name\n";
		return(1);
	}

	Function Query($query)
	{
		echo $query,"\n";
		switch(strtok($query," "))
		{
			case "CREATE":
			case "INSERT":
				return(1);
		}
		$this->last_error="method not implemented";
		return(0);
	}

	Function Error()
	{
		return($this->last_error);
	}
};

}
?>