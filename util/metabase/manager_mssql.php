<?php
if(!defined("METABASE_MANAGER_MSSQL_INCLUDED"))
{
	define("METABASE_MANAGER_MSSQL_INCLUDED",1);

/*
 * manager_mssql.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/metabase/manager_mssql.php,v 1.3 2005/09/20 04:27:47 mlemos Exp $
 *
 */

class metabase_manager_mssql_class extends metabase_manager_database_class
{
	var $primary_key_index_prefix="_primary_key_";

	Function StandaloneQuery(&$db,$query)
	{
		if(!function_exists("mssql_connect"))
			return($db->SetError("Standalone query","Microsoft SQL server support is not available in this PHP configuration"));
		if(($connection=mssql_connect($db->host,$db->user,$db->password))==0)
			return($db->SetMSSQLError("Standalone query","Could not connect to the Microsoft SQL server"));
		if(!($success=@mssql_query($query,$connection)))
			$db->SetMSSQLError("Standalone query","Could not query a Microsoft SQL server");
		mssql_close($connection);
		return($success);
	}

	Function GetPrimaryKeyIndex($table)
	{
		return($this->primary_key_index_prefix.$table);
	}

	Function GetTableFieldsAndOptions(&$db,&$table,&$sql,&$options)
	{
		$options="";
		if(!$this->GetFieldList($db,$table["FIELDS"],$sql))
			return(0);
		if(IsSet($table["PRIMARYKEY"]))
		{
			if(!$this->GetPrimaryKeyDeclaration($db,$table["PRIMARYKEY"],$key))
				return(0);
			$sql.=", CONSTRAINT ".$this->GetPrimaryKeyIndex($table["name"])." ".$key;
		}
		return(1);
	}

	Function CreateDatabase(&$db,$name)
	{
		return($this->StandaloneQuery($db,"CREATE DATABASE $name ON ".(IsSet($db->options["DatabaseDevice"]) ? $db->options["DatabaseDevice"] : "DEFAULT").(IsSet($db->options["DatabaseSize"]) ? "=".$db->options["DatabaseSize"] : "")));
	}

	Function DropDatabase(&$db,$name)
	{
		return($this->StandaloneQuery($db,"DROP DATABASE $name"));
	}

	Function DropIndex(&$db,$table,$name)
	{
		return($db->Query("DROP INDEX ".$table.".".$name));
	}

	Function AlterTable(&$db,$name,&$changes,$check)
	{
		$sql=array();
		if(IsSet($changes["name"]))
		{
			$name_text=$name;
			$db->EscapeText($name_text);
			$new_name_text=$new_name=$changes["name"];
			$db->EscapeText($new_name_text);
			$sql[]="EXEC sp_rename '".$name_text."', '".$new_name_text."'";
		}
		else
			$new_name=$name;
		if(IsSet($changes["RemovedPrimaryKey"])
		|| IsSet($changes["ChangedPrimaryKey"])
		|| (IsSet($changes["name"])
		&& IsSet($changes["PrimaryKey"]["was"])))
			$sql[]="ALTER TABLE ".$new_name." DROP CONSTRAINT ".$this->GetPrimaryKeyIndex($name);
		if(IsSet($changes["RemovedFields"]))
		{
			$fields=$changes["RemovedFields"];
			for($field=0,Reset($fields);$field<count($fields);Next($fields),$field++)
			{
				$field_name=Key($fields);
				$sql[]="ALTER TABLE ".$new_name." DROP COLUMN ".$field_name;
			}
		}
		if(IsSet($changes["ChangedFields"]))
		{
			$fields=$changes["ChangedFields"];
			for($field=0,Reset($fields);$field<count($fields);Next($fields),$field++)
			{
				$field_name=Key($fields);
				for($field_change=0, Reset($fields[$field_name]); $field_change<count($fields[$field_name]); Next($fields[$field_name]), $field_change++)
				{
					switch($change_type=Key($fields[$field_name]))
					{
						case "Definition":
						case "Declaration":
						case "unsigned":
						case "notnull":
						case "default":
							break;
						case "autoincrement":
						case "ChangedNotNull":
						case "ChangedDefault":
						case "length":
						default:
							return($db->SetError("Alter table","change field \"".$change_type."\" is not yet supported"));
					}
				}
			}
		}
		if(IsSet($changes["AddedFields"]))
		{
			$query="ALTER TABLE ".$new_name." ADD ";
			$fields=$changes["AddedFields"];
			for($field=0,Reset($fields);$field<count($fields);Next($fields),$field++)
			{
				if($field>0)
					$query.=", ";
				$query.=$fields[Key($fields)]["Declaration"];
			}
			$sql[]=$query;
		}
		if(IsSet($changes["AddedPrimaryKey"])
		|| IsSet($changes["ChangedPrimaryKey"])
		|| (IsSet($changes["name"])
		&& IsSet($changes["PrimaryKey"]["key"])))
		{
			$primary_key=(IsSet($changes["AddedPrimaryKey"]) ? $changes["AddedPrimaryKey"] : (IsSet($changes["ChangedPrimaryKey"]) ? $changes["ChangedPrimaryKey"] : $changes["PrimaryKey"]["key"]));
			if(!$this->GetPrimaryKeyDeclaration($db,$primary_key,$key))
				return(0);
			$sql[]="ALTER TABLE ".$new_name." ADD CONSTRAINT ".$this->GetPrimaryKeyIndex($new_name)." ".$key;
		}
		if($check)
		{
			for($change=0,Reset($changes);$change<count($changes);Next($changes),$change++)
			{
				switch(Key($changes))
				{
					case "AddedFields":
					case "AutoIncrement":
					case "PrimaryKey":
					case "SQL":
					case "name":
					case "ChangedPrimaryKey":
					case "AddedPrimaryKey":
					case "RemovedPrimaryKey":
					case "ChangedFields":
					case "RemovedFields":
						break;
					case "RenamedFields":
					default:
						return($db->SetError("Alter table","change type \"".Key($changes)."\" not yet supported"));
				}
			}
			return(1);
		}
		if(IsSet($changes["SQL"]))
			$changes["SQL"]=$sql;
		if(!$check)
		{
			for($statement=0;$statement<count($sql);$statement++)
			{
				if(!$db->Query($sql[$statement]))
					return(0);
			}
		}
		return(1);
	}

	Function CreateSequence(&$db,$name,$start)
	{
		return($db->Query("CREATE TABLE _sequence_$name (sequence INT NOT NULL IDENTITY($start,1) PRIMARY KEY CLUSTERED)"));
	}

	Function DropSequence(&$db,$name)
	{
		return($db->Query("DROP TABLE _sequence_$name"));
	}
};
}
?>