<?php
if(!defined("METABASE_MANAGER_PGSQL_INCLUDED"))
{
	define("METABASE_MANAGER_PGSQL_INCLUDED",1);

/*
 * manager_pgsql.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/metabase/manager_pgsql.php,v 1.17 2005/09/07 22:17:36 mlemos Exp $
 *
 */

class metabase_manager_pgsql_class extends metabase_manager_database_class
{
	var $primary_key_index_prefix="_primary_key_";

	Function StandaloneQuery(&$db,$query)
	{
		if(($connection=$db->DoConnect("template1",0))==0)
			return(0);
		if(!($success=@pg_Exec($connection,"$query")))
			$db->SetError("Standalone query",pg_ErrorMessage($connection));
		pg_Close($connection);
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

	Function BeforeCreateTable(&$db, &$table, $check, &$sql)
	{
		for($f=0, $fields=count($table["FIELDS"]), Reset($table["FIELDS"]); $f<$fields; $f++)
		{
			$field=Key($table["FIELDS"]);
			if(IsSet($table["FIELDS"][$field]["autoincrement"]))
			{
				$sql[]="CREATE SEQUENCE ".$db->auto_increment_sequence_prefix.$table["name"]." INCREMENT 1 START 1";
				return(1);
			}
		}
		return(1);
	}

	Function AfterDropTable(&$db, &$table, $check, &$sql)
	{
		for($f=0, $fields=count($table["FIELDS"]), Reset($table["FIELDS"]); $f<$fields; $f++)
		{
			$field=Key($table["FIELDS"]);
			if(IsSet($table["FIELDS"][$field]["autoincrement"]))
			{
				$this->DropAutoIncrementSequence($db, $table["name"], $sql);
				return(1);
			}
		}
		return(1);
	}

	Function CreateDatabase(&$db,$name)
	{
		return($this->StandaloneQuery($db,"CREATE DATABASE $name"));
	}

	Function DropDatabase(&$db,$name)
	{
		return($this->StandaloneQuery($db,"DROP DATABASE $name"));
	}

	Function AlterTableFieldDefault(&$db, $table, $field_name, &$definition, &$sql)
	{
		$type=$definition["type"];
		if(strcmp($type,"integer")
		|| !IsSet($definition["autoincrement"])
		|| !$definition["autoincrement"])
		{
			if(IsSet($definition["default"]))
			{
				if(!$db->GetTypedFieldValue($type, $definition["default"], $value, "AlterTable"))
					return(0);
			}
			else
				$value="NULL";
			$sql[]="ALTER TABLE ".$table." ALTER ".$field_name." SET DEFAULT ".$value;
		}
		return(1);
	}

	Function AlterTableFieldNotNull($table, &$fields, $field_name, &$sql)
	{
		if(IsSet($fields[$field_name]["notnull"])
		&& $fields[$field_name]["notnull"])
			$sql[]="ALTER TABLE ".$table." ALTER ".$field_name." SET NOT NULL";
		else
			$sql[]="ALTER TABLE ".$table." ALTER ".$field_name." DROP NOT NULL";
	}

	Function CreateAutoIncrementSequence(&$db, $was_table, $table, $field_name, $check, &$sql)
	{
		if($check)
			$start=1;
		else
		{
			if(!$db->QueryField("SELECT MAX(".$field_name.") FROM ".$was_table,$start,"integer"))
				return(0);
			$start=(IsSet($start) ? $start+1 : 1);
		}
		$sql[]="CREATE SEQUENCE ".$db->auto_increment_sequence_prefix.$table." START ".$start." INCREMENT 1";
		return(1);
	}

	Function DropAutoIncrementSequence($db, $table, &$sql)
	{
		$sql[]="DROP SEQUENCE ".$db->auto_increment_sequence_prefix.$table;
	}

	Function AlterTable(&$db,$name,&$changes,$check)
	{
		$sql=array();
		if(IsSet($changes["name"]))
		{
			$new_name=$changes["name"];
			$changed_auto_increment=(IsSet($changes["AutoIncrement"]["was"]) && IsSet($changes["AutoIncrement"]["field"]));
			if($changed_auto_increment)
			{
				$was_auto_increment_field=$changes["AutoIncrement"]["was"];
				$auto_increment_field=$changes["AutoIncrement"]["field"];
				if(IsSet($changes["RenamedFields"][$was_auto_increment_field]))
					$auto_increment_field=$was_auto_increment_field;
				$changed_auto_increment=!strcmp($changes["AutoIncrement"]["was"],$auto_increment_field);
			}
			if($changed_auto_increment)
			{
				$sql[]="ALTER TABLE ".$name." ALTER ".$auto_increment_field." SET DEFAULT NULL";
				$this->DropAutoIncrementSequence($db, $name, $sql);
			}
			$sql[]="ALTER TABLE ".$name." RENAME TO ".$new_name;
			if($changed_auto_increment)
			{
				if(!$this->CreateAutoIncrementSequence($db, $name, $new_name, $auto_increment_field, $check, $sql))
					return(0);
				$sql[]="ALTER TABLE ".$new_name." ALTER ".$auto_increment_field." SET DEFAULT NEXTVAL('".$db->auto_increment_sequence_prefix.$new_name."')";
			}
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
				$sql[]="ALTER TABLE ".$new_name." DROP ".$field_name;
			}
		}
		if(IsSet($changes["RenamedFields"]))
		{
			$fields=$changes["RenamedFields"];
			for($field=0,Reset($fields);$field<count($fields);Next($fields),$field++)
			{
				$field_name=Key($fields);
				$sql[]="ALTER TABLE ".$new_name." RENAME ".$field_name." TO ".$fields[$field_name]["name"];
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
						case "autoincrement":
							if($fields[$field_name]["autoincrement"])
							{
								if(!$this->CreateAutoIncrementSequence($db, $name, $new_name, $field_name, $check, $sql))
									return(0);
								$sql[]="ALTER TABLE ".$new_name." ALTER ".$field_name." SET DEFAULT NEXTVAL('".$db->auto_increment_sequence_prefix.$new_name."')";
							}
							else
							{
								$sql[]="ALTER TABLE ".$new_name." ALTER ".$field_name." ".(IsSet($fields[$field_name]["Definition"]["default"]) ? "SET DEFAULT ".$fields[$field_name]["Definition"]["default"] : "DROP DEFAULT");
								$this->DropAutoIncrementSequence($db, $name, $sql);
							}
							break;
						case "ChangedDefault":
							if(!$this->AlterTableFieldDefault($db, $new_name, $field_name, $fields[$field_name]["Definition"], $sql))
								return(0);
							break;
						case "ChangedNotNull":
							$this->AlterTableFieldNotNull($new_name, $fields, $field_name, $sql);
							break;
						case "length":
							if(!function_exists("pg_version"))
								return($db->SetError("Alter table","it was not possible to determine the PostgreSQL server version"));
							if(!$check)
							{
								if(!$db->Connect())
									return(0);
								$version=pg_version($db->connection);
								if(intval($version["server_version"])<8)
									return($db->SetError("Alter table","field length alteration requires at least PostgreSQL 8"));
							}
							$sql[]="ALTER TABLE ".$new_name." ALTER ".$field_name." TYPE ".($fields[$field_name]["length"] ? "VARCHAR(".$fields[$field_name]["length"].")" : "TEXT");
							break;
						case "Declaration":
						case "Definition":
						case "notnull":
						case "default":
						case "unsigned":
							break;
						default:
							return($db->SetError("Alter table","change field \"".$change_type."\" is not yet supported"));
					}
				}
			}
		}
		if(IsSet($changes["AddedFields"]))
		{
			$fields=$changes["AddedFields"];
			for($field=0,Reset($fields);$field<count($fields);Next($fields),$field++)
			{
				$field_name=Key($fields);
				$definition=$fields[$field_name];
				$has_default=$definition["default"];
				if($has_default)
					UnSet($definition["default"]);
				$has_notnull=$definition["notnull"];
				if($has_notnull)
					UnSet($definition["notnull"]);
				if(!$this->GetField($db,$definition,$field_name,$declaration))
					return(0);
				$sql[]="ALTER TABLE ".$new_name." ADD ".$declaration;
				if($has_default
				&& !$this->AlterTableFieldDefault($db, $new_name, $field_name, $fields[$field_name], $sql))
					return(0);
				if($has_notnull)
					$this->AlterTableFieldNotNull($new_name, $fields, $field_name, $sql);
			}
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
		for($change=0,Reset($changes);$change<count($changes);Next($changes),$change++)
		{
			switch(Key($changes))
			{
				case "name":
				case "AddedFields":
				case "RenamedFields":
				case "RemovedFields":
				case "ChangedFields":
				case "AddedPrimaryKey":
				case "RemovedPrimaryKey":
				case "ChangedPrimaryKey":
				case "AutoIncrement":
				case "PrimaryKey":
				case "SQL":
					break;
				default:
					return($db->SetError("Alter table","change type \"".Key($changes)."\" not yet supported"));
			}
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
		return($db->Query("CREATE SEQUENCE $name INCREMENT 1".($start<1 ? " MINVALUE $start" : "")." START $start"));
	}

	Function DropSequence(&$db,$name)
	{
		return($db->Query("DROP SEQUENCE $name"));
	}

	Function GetSequenceCurrentValue(&$db,$name,&$value)
	{
		if(!($result=$db->Query("SELECT last_value FROM $name")))
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