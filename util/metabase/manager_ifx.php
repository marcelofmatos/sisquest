<?php
if(!defined("METABASE_MANAGER_IFX_INCLUDED"))
{
	define("METABASE_MANAGER_IFX_INCLUDED",1);

/*
 * manager_ifx.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/metabase/manager_ifx.php,v 1.2 2003/01/08 18:25:29 mlemos Exp $
 *
 */

class metabase_manager_ifx_class extends metabase_manager_database_class
{
	Function DBAQuery(&$db,$query)
	{
		if(!IsSet($db->options[$option="DBAUser"])
		|| !IsSet($db->options[$option="DBAPassword"]))
			return($db->SetError("DBA query","it was not specified the Informix $option option"));
		return($db->Connect(1)
		&& $db->DoQuery($query));
	}

	Function CreateDatabase(&$db,$name)
	{
		if(IsSet($db->options["Logging"]))
		{
			switch($db->options["Logging"])
			{
				case "Unbuffered":
					$logging=" WITH LOG";
					break;
				case "Buffered":
					$logging=" WITH BUFFERED LOG";
					break;
				case "ANSI":
					$logging=" WITH LOG MODE ANSI";
					break;
				default:
					return($db->SetError("Create database",$db->options["Logging"]." is not a support logging type"));
			}
		}
		else
			$logging="";
		if(!$this->DBAQuery($db,"CREATE DATABASE $name$logging"))
			return(0);
		if(strcmp((IsSet($db->options[$option="DBAUser"]) ? $db->options[$option="DBAUser"] : ""),$db->user)
		&& !$this->DBAQuery($db,"GRANT RESOURCE TO ".$db->user))
		{
			$error=$db->Error();
			if(!$this->DropDatabase($name))
				return($db->SetError("Create database","Could not drop the created the database (".$db->Error().") after not being able to grant access privileges to the specified user ($error)"));
			return(0);
		}
		return(1);
	}

	Function DropDatabase(&$db,$name)
	{
		return($this->DBAQuery($db,"DROP DATABASE $name"));
	}

	Function AlterTable(&$db,$name,&$changes,$check)
	{
		if($check)
		{
			for($change=0,Reset($changes);$change<count($changes);Next($changes),$change++)
			{
				switch(Key($changes))
				{
					case "AddedFields":
					case "RemovedFields":
					case "RenamedFields":
					case "ChangedFields":
					case "name":
						break;
					default:
						return($db->SetError("Alter table","change type \"".Key($changes)."\" not yet supported"));
				}
			}
			return(1);
		}
		else
		{
			$query="";
			if(IsSet($changes["RemovedFields"]))
			{
				$fields=$changes["RemovedFields"];
				for($field=0,Reset($fields);$field<count($fields);Next($fields),$field++)
				{
					if(strcmp($query,""))
						$query.=", ";
					$query.="DROP ".Key($fields);
				}
			}
			if(IsSet($changes["RenamedFields"]))
			{
				if(strcmp($query,"")
				&& !$db->Query("ALTER TABLE $name $query"))
					return(0);
				$query="";
				$fields=$changes["RenamedFields"];
				for($field=0,Reset($fields);$field<count($fields);Next($fields),$field++)
				{
					if(!$db->Query("RENAME COLUMN $name.".Key($fields)." TO ".$fields[Key($fields)]["name"]))
						return(0);
				}
			}
			if(IsSet($changes["ChangedFields"]))
			{
				$fields=$changes["ChangedFields"];
				for($field=0,Reset($fields);$field<count($fields);Next($fields),$field++)
				{
					if(strcmp($query,""))
						$query.=", ";
					$query.="MODIFY ".$fields[Key($fields)]["Declaration"];
				}
			}
			if(IsSet($changes["AddedFields"]))
			{
				$fields=$changes["AddedFields"];
				for($field=0,Reset($fields);$field<count($fields);Next($fields),$field++)
				{
					if(strcmp($query,""))
						$query.=", ";
					$query.="ADD ".$fields[Key($fields)]["Declaration"];
				}
			}
			if(strcmp($query,"")
			&& !$db->Query("ALTER TABLE $name $query"))
				return(0);
			return(IsSet($changes["name"]) ? $db->Query("RENAME TABLE $name TO ".$changes["name"]) : 1);
		}
	}

	Function CreateSequence(&$db,$name,$start)
	{
		if(!$db->Query("CREATE TABLE _sequence_$name (sequence ".((IsSet($db->options["Use8ByteIntegers"]) && $db->options["Use8ByteIntegers"]) ?  "SERIAL8" : "SERIAL")." NOT NULL)"))
			return(0);
		if($start==1
		|| $db->Query("INSERT INTO _sequence_$name (sequence) VALUES (".($start-1).")"))
			return(1);
		$error=$db->Error();
		if(!$db->Query("DROP TABLE _sequence_$name"))
			$db->warning="could not drop inconsistent sequence table";
		return($db->SetError("Create sequence",$error));
	}

	Function DropSequence(&$db,$name)
	{
		return($db->Query("DROP TABLE _sequence_$name"));
	}
};
}
?>