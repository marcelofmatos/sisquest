<?php
if(!defined("METABASE_MANAGER_IBASE_INCLUDED"))
{
	define("METABASE_MANAGER_IBASE_INCLUDED",1);

/*
 * manager_ibase.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/metabase/manager_ibase.php,v 1.3 2003/07/06 04:28:08 mlemos Exp $
 *
 */

class metabase_manager_ibase_class extends metabase_manager_database_class
{
	Function DBAQuery(&$db,$database_file,$query)
	{
		if(!function_exists("ibase_connect"))
			return($db->SetError("DBA query","Interbase support is not available in this PHP configuration"));
		if(!IsSet($db->options[$option="DBAUser"])
		|| !IsSet($db->options[$option="DBAPassword"]))
			return($db->SetError("DBA query","it was not specified the Interbase $option option"));
		$database=$db->host.(strcmp($database_file,"") ? ":".$database_file : "");
		if(($connection=@ibase_connect($database,$db->options["DBAUser"],$db->options["DBAPassword"]))<=0)
			return($db->SetError("DBA query","Could not connect to Interbase server ($database): ".ibase_errmsg()));
		if(!($success=@ibase_query($connection,$query)))
			$db->SetError("DBA query","Could not execute query ($query): ".ibase_errmsg());
		ibase_close($connection);
		return($success);
	}

	Function CreateDatabase(&$db,$name)
	{
		return($this->DBAQuery($db,"","CREATE DATABASE '".$db->GetDatabaseFile($name)."'"));
	}

	Function DropDatabase(&$db,$name)
	{
		return($this->DBAQuery($db,$db->GetDatabaseFile($name),"DROP DATABASE"));
	}

	Function CheckSupportedChanges(&$db,&$changes)
	{
		for($change=0,Reset($changes);$change<count($changes);Next($changes),$change++)
		{
			switch(Key($changes))
			{
				case "ChangedNotNull":
				case "notnull":
					return($db->SetError("Check supported changes","it is not supported changes to field not null constraint"));
				case "ChangedDefault":
				case "default":
					return($db->SetError("Check supported changes","it is not supported changes to field default value"));
				case "length":
					return($db->SetError("Check supported changes","it is not supported changes to field length"));
				case "unsigned":
				case "type":
				case "Declaration":
				case "Definition":
					break;
				default:
					return($db->SetError("Check supported changes","it is not supported field changes of type \"".Key($changes)."\""));
			}
		}
		return(1);
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
						break;
					case "ChangedFields":
						$fields=$changes["ChangedFields"];
						for($field=0,Reset($fields);$field<count($fields);Next($fields),$field++)
						{
							if(!$this->CheckSupportedChanges($fields[Key($fields)]))
								return(0);
						}
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
				$fields=$changes["RenamedFields"];
				for($field=0,Reset($fields);$field<count($fields);Next($fields),$field++)
				{
					if(strcmp($query,""))
						$query.=", ";
					$query.="ALTER ".Key($fields)." TO ".$fields[Key($fields)]["name"];
				}
			}
			if(IsSet($changes["ChangedFields"]))
			{
				$fields=$changes["ChangedFields"];
				for($field=0,Reset($fields);$field<count($fields);Next($fields),$field++)
				{
					$field_name=Key($fields);
					if(!$this->CheckSupportedChanges($fields[$field_name]))
						return(0);
					if(strcmp($query,""))
						$query.=", ";
					$query.="ALTER $field_name TYPE ".$db->GetFieldTypeDeclaration($fields[$field_name]["Definition"]);
				}
			}
			return($db->Query("ALTER TABLE $name $query"));
		}
	}

	Function CreateSequence(&$db,$name,$start)
	{
		if(!$db->Query("CREATE GENERATOR $name"))
			return(0);
		if($db->Query("SET GENERATOR $name TO ".($start-1)))
			return(1);
		$error=$db->Error();
		if(!$db->DropSequence($name))
			return($db->SetError("","Could not setup sequence start value ($error) and then it was not possible to drop it (".$db->Error().")"));
		return(0);
	}

	Function DropSequence(&$db,$name)
	{
		return($db->Query("DELETE FROM RDB\$GENERATORS WHERE RDB\$GENERATOR_NAME='".strtoupper($name)."'"));
	}

	Function GetSequenceCurrentValue(&$db,$name,&$value)
	{
		if(($result=$db->Query("SELECT GEN_ID($name,0) as the_value FROM RDB\$DATABASE "))==0)
			return($db->SetError("Get sequence current value", ibase_errmsg()));
		$value=intval($db->FetchResult($result,0,0));
		$db->FreeResult($result);
		return(1);
	}

	Function CreateIndex(&$db,$table,$name,$definition)
	{
		for($query_sort="",$query_fields="",$field=0,Reset($definition["FIELDS"]);$field<count($definition["FIELDS"]);$field++,Next($definition["FIELDS"]))
		{
			if($field>0)
				$query_fields.=",";
			$field_name=Key($definition["FIELDS"]);
			$query_fields.=$field_name;
			if(!strcmp($query_sort,"")
			&& $db->Support("IndexSorting")
			&& IsSet($definition["FIELDS"][$field_name]["sorting"]))
			{
				switch($definition["FIELDS"][$field_name]["sorting"])
				{
					case "ascending":
						$query_sort=" ASC";
						break;
					case "descending":
						$query_sort=" DESC";
						break;
				}
			}
		}
		return($db->Query("CREATE".(IsSet($definition["unique"]) ? " UNIQUE" : "")."$query_sort INDEX $name ON $table ($query_fields)"));
	}
};
}
?>