<?php
if(!defined("METABASE_MANAGER_OCI_INCLUDED"))
{
	define("METABASE_MANAGER_OCI_INCLUDED",1);

/*
 * manager_oci.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/metabase/manager_oci.php,v 1.8 2005/09/19 05:59:57 mlemos Exp $
 *
 */

class metabase_manager_oci_class extends metabase_manager_database_class
{
	Function BeforeCreateTable(&$db, &$table, $check, &$sql)
	{
		for($f=0, $fields=count($table["FIELDS"]), Reset($table["FIELDS"]); $f<$fields; $f++)
		{
			$field=Key($table["FIELDS"]);
			if(IsSet($table["FIELDS"][$field]["autoincrement"]))
			{
				$sql[]="CREATE SEQUENCE ".$db->auto_increment_sequence_prefix.$table["name"]." START WITH 1 INCREMENT BY 1";
				return(1);
			}
		}
		return(1);
	}

	Function CreateAutoIncrementTrigger(&$db, $table, $field)
	{
		return("CREATE TRIGGER ".$table.$db->auto_increment_trigger_suffix." BEFORE INSERT ON ".$table." FOR EACH ROW BEGIN IF (:new.".$field." IS NULL) THEN SELECT ".$db->auto_increment_sequence_prefix.$table.".NEXTVAL INTO :new.".$field." FROM DUAL; END IF; END;");
	}

	Function DropAutoIncrementTrigger(&$db, $table)
	{
		return("DROP TRIGGER ".$table.$db->auto_increment_trigger_suffix);
	}

	Function AfterCreateTable(&$db, &$table, $check, &$sql)
	{
		for($f=0, $fields=count($table["FIELDS"]), Reset($table["FIELDS"]); $f<$fields; $f++)
		{
			$field=Key($table["FIELDS"]);
			if(IsSet($table["FIELDS"][$field]["autoincrement"]))
			{
				$sql[]=$this->CreateAutoIncrementTrigger($db, $table["name"], $field);
				return(1);
			}
		}
		return(1);
	}

	Function BeforeDropTable(&$db, &$table, $check, &$sql)
	{
		for($f=0, $fields=count($table["FIELDS"]), Reset($table["FIELDS"]); $f<$fields; $f++)
		{
			$field=Key($table["FIELDS"]);
			if(IsSet($table["FIELDS"][$field]["autoincrement"]))
			{
				$sql[]=$this->DropAutoIncrementTrigger($db, $table["name"]);
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
		if(!IsSet($db->options[$option="DBAUser"])
		|| !IsSet($db->options[$option="DBAPassword"]))
			return($db->SetError("Create database","it was not specified the Oracle $option option"));
		$success=0;
		if($db->Connect($db->options["DBAUser"],$db->options["DBAPassword"],0))
		{
			if($db->DoQuery("CREATE USER ".$db->user." IDENTIFIED BY ".$db->password.(IsSet($db->options["DefaultTablespace"]) ? " DEFAULT TABLESPACE ".$db->options["DefaultTablespace"] : "")))
			{
				if($db->DoQuery("GRANT CREATE SESSION, CREATE TABLE, CREATE TRIGGER, UNLIMITED TABLESPACE, CREATE SEQUENCE TO ".$db->user))
					$success=1;
				else
				{
					$error=$db->Error();
					if(!$db->DoQuery("DROP USER ".$db->user." CASCADE"))
						$error="could not setup the database user ($error) and then could drop its records (".$db->Error().")";
					return($db->SetError("Create database",$error));
				}
			}
		}
		return($success);
	}

	Function DropDatabase(&$db,$name)
	{
		if(!IsSet($db->options[$option="DBAUser"])
		|| !IsSet($db->options[$option="DBAPassword"]))
			return($db->SetError("Drop database","it was not specified the Oracle $option option"));
		return($db->Connect($db->options["DBAUser"],$db->options["DBAPassword"],0)
		&& $db->DoQuery("DROP USER ".$db->user." CASCADE"));
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
		$sql[]="CREATE SEQUENCE ".$db->auto_increment_sequence_prefix.$table." START WITH ".$start." INCREMENT BY 1";
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
			$changed_auto_increment=(IsSet($changes["AutoIncrement"]["was"]) && IsSet($changes["AutoIncrement"]["field"]) && !strcmp($changes["AutoIncrement"]["was"],$changes["AutoIncrement"]["field"]));
			if($changed_auto_increment)
			{
				$sql[]=$this->DropAutoIncrementTrigger($db, $name);
				$this->DropAutoIncrementSequence($db, $name, $sql);
			}
			$sql[]="ALTER TABLE ".$name." RENAME TO ".$new_name;
			if($changed_auto_increment)
			{
				if(!$this->CreateAutoIncrementSequence($db, $name, $new_name, $changes["AutoIncrement"]["field"], $check, $sql))
					return(0);
				$sql[]=$this->CreateAutoIncrementTrigger($db, $new_name, $changes["AutoIncrement"]["field"]);
			}
		}
		else
			$new_name=$name;
		if(IsSet($changes["RemovedPrimaryKey"])
		|| IsSet($changes["ChangedPrimaryKey"]))
			$sql[]="ALTER TABLE ".$new_name." DROP PRIMARY KEY";
		if(IsSet($changes["RemovedFields"]))
		{
			$fields=$changes["RemovedFields"];
			for($field=0,Reset($fields);$field<count($fields);Next($fields),$field++)
			{
				$field_name=Key($fields);
				$sql[]="ALTER TABLE ".$new_name." DROP (".$field_name.")";
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
								$sql[]="ALTER TABLE ".$new_name." MODIFY (".$field_name." DEFAULT NULL)";
								$sql[]=$this->CreateAutoIncrementTrigger($db, $new_name, $field_name);
							}
							else
							{
								$sql[]=$this->DropAutoIncrementTrigger($db, $new_name);
								$sql[]="ALTER TABLE ".$new_name." MODIFY (".$field_name." ".(IsSet($fields[$field_name]["Definition"]["default"]) ? "DEFAULT ".$fields[$field_name]["Definition"]["default"] : "NULL").")";
								$this->DropAutoIncrementSequence($db, $name, $sql);
							}
							break;
						case "ChangedDefault":
							$type=$fields[$field_name]["Definition"]["type"];
							if(strcmp($type,"integer")
							|| !IsSet($fields[$field_name]["Definition"]["autoincrement"])
							|| !$fields[$field_name]["Definition"]["autoincrement"])
							{
								if(IsSet($fields[$field_name]["Definition"]["default"]))
								{
									if(!$db->GetTypedFieldValue($type, $fields[$field_name]["Definition"]["default"], $value, "AlterTable"))
										return(0);
								}
								else
									$value="NULL";
								$sql[]="ALTER TABLE ".$new_name." MODIFY (".$field_name." DEFAULT ".$value.")";
							}
							break;
						case "ChangedNotNull":
							if(IsSet($fields[$field_name]["notnull"])
							&& $fields[$field_name]["notnull"])
								$sql[]="ALTER TABLE ".$new_name." MODIFY (".$field_name." NOT NULL)";
							else
								$sql[]="ALTER TABLE ".$new_name." MODIFY (".$field_name." NULL)";
							break;
						case "length":
						case "type":
							$sql[]="ALTER TABLE ".$new_name." MODIFY (".$field_name." ".$db->GetFieldTypeDeclaration($fields[$field_name]["Definition"]).")";
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
				if(!$this->GetField($db,$fields[$field_name],$field_name,$declaration))
					return(0);
				$sql[]="ALTER TABLE ".$new_name." ADD (".$declaration.")";
			}
		}
		if(IsSet($changes["AddedPrimaryKey"])
		|| IsSet($changes["ChangedPrimaryKey"]))
		{
			$primary_key=(IsSet($changes["AddedPrimaryKey"]) ? $changes["AddedPrimaryKey"] : $changes["ChangedPrimaryKey"]);
			if(!$this->GetPrimaryKeyDeclaration($db,$primary_key,$key))
				return(0);
			$sql[]="ALTER TABLE ".$new_name." ADD ".$key;
		}
		for($change=0,Reset($changes);$change<count($changes);Next($changes),$change++)
		{
			switch(Key($changes))
			{
				case "name":
				case "AddedFields":
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
		return($db->Query("CREATE SEQUENCE $name START WITH $start INCREMENT BY 1".($start<1 ? " MINVALUE $start" : "")));
	}

	Function DropSequence(&$db,$name)
	{
		return($db->Query("DROP SEQUENCE $name"));
	}
};
}
?>