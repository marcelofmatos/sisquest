<?php
if(!defined("METABASE_MANAGER_MYSQL_INCLUDED"))
{
	define("METABASE_MANAGER_MYSQL_INCLUDED",1);

/*
 * manager_mysql.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/metabase/manager_mysql.php,v 1.14 2005/09/06 02:00:24 mlemos Exp $
 *
 */

class metabase_manager_mysql_class extends metabase_manager_database_class
{
	var $verified_table_types=array();

	Function VerifyTransactionalTableType(&$db,$table_type)
	{
		switch(strtoupper($table_type))
		{
			case "BERKELEYDB":
			case "BDB":
				$check=array("have_bdb");
				break;
			case "INNODB":
				$check=array("have_innodb","have_innobase");
				break;
			case "GEMINI":
				$check=array("have_gemini");
				break;
			case "HEAP":
			case "ISAM":
			case "MERGE":
			case "MRG_MYISAM":
			case "MYISAM":
			case "":
				return(1);
			default:
				return($db->SetError("Verify transactional table",$table_type." is not a supported table type"));
		}
		if(!$db->Connect())
			return(0);
		if(IsSet($this->verified_table_types[$table_type])
		&& $this->verified_table_types[$table_type]==$db->connection)
			return(1);
		for($has_any=$type=0;$type<count($check);$type++)
		{
			$result=mysql_query("SHOW VARIABLES LIKE '".$check[$type]."'",$db->connection);
			if(!$result)
				return($db->SetError("Verify transactional table type",mysql_error($db->connection)));
			$has=((mysql_num_rows($result) && !strcmp(strtolower(trim(mysql_result($result,0,1))),"yes")) ? 1 : 0);
			mysql_free_result($result);
			$has_any+=$has;
			if($has)
				break;
		}
		if(count($has_any)==0)
			return($db->SetError("Verify transactional table","could not tell if ".$table_type." is a supported table type"));
		if(!$has)
			return($db->SetError("Verify transactional table",$table_type." is not a supported table type by this MySQL database server"));
		$this->verified_table_types[$table_type]=$db->connection;
		return(1);
	}

	Function GetTableFieldsAndOptions(&$db,&$table,&$sql,&$options)
	{
		if(!$this->VerifyTransactionalTableType($db,$db->default_table_type))
			return(0);
		$options=(strlen($db->default_table_type) ? " TYPE=".$db->default_table_type : "");
		if(!$this->GetFieldList($db,$table["FIELDS"],$sql))
			return(0);
		if(IsSet($table["PRIMARYKEY"]))
		{
			if(!$this->GetPrimaryKeyDeclaration($db,$table["PRIMARYKEY"],$key))
				return(0);
			$sql.=", ".$key;
		}
		else
		{
			if(IsSet($db->supported["Transactions"])
			&& $db->default_table_type=="BDB")
				$sql.=", ".$db->dummy_primary_key." INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (".$db->dummy_primary_key.")";
		}
	  return(1);
	}

	Function CreateDatabase(&$db,$name)
	{
		if(!$db->Connect())
			return(0);
		if(function_exists("mysql_create_db"))
			$success=@mysql_create_db($name,$db->connection);
		else
		{
			$db->EscapeText($name);
			$success=mysql_query("CREATE DATABASE $name",$db->connection);
		}
		if(!$success)
			return($db->SetError("Create database",mysql_error($db->connection)));
		return(1);
	}

	Function DropDatabase(&$db,$name)
	{
		if(!$db->Connect())
			return(0);
		if(function_exists("mysql_drop_db"))
			$success=@mysql_drop_db($name,$db->connection);
		else
		{
			$db->EscapeText($name);
			$success=mysql_query("DROP DATABASE $name",$db->connection);
		}
		if(!$success)
			return($db->SetError("Drop database",mysql_error($db->connection)));
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
					case "ChangedFields":
					case "RenamedFields":
					case "name":
					case "AddedPrimaryKey":
					case "RemovedPrimaryKey":
					case "ChangedPrimaryKey":
					case "PrimaryKey":
					case "AutoIncrement":
						break;
					default:
						return($db->SetError("Alter table","change type \"".Key($changes)."\" not yet supported"));
				}
			}
		}
		$query=(IsSet($changes["name"]) ? "RENAME AS ".$changes["name"] : "");
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
		$renamed_fields=array();
		if(IsSet($changes["RenamedFields"]))
		{
			$fields=$changes["RenamedFields"];
			for($field=0,Reset($fields);$field<count($fields);Next($fields),$field++)
				$renamed_fields[$fields[Key($fields)]["name"]]=Key($fields);
		}
		if(IsSet($changes["ChangedFields"]))
		{
			$fields=$changes["ChangedFields"];
			for($field=0,Reset($fields);$field<count($fields);Next($fields),$field++)
			{
				if(strcmp($query,""))
					$query.=", ";
				if(IsSet($renamed_fields[Key($fields)]))
				{
					$field_name=$renamed_fields[Key($fields)];
					UnSet($renamed_fields[Key($fields)]);
				}
				else
					$field_name=Key($fields);
				$query.="CHANGE $field_name ".$fields[Key($fields)]["Declaration"];
			}
		}
		if(count($renamed_fields))
		{
			for($field=0,Reset($renamed_fields);$field<count($renamed_fields);Next($renamed_fields),$field++)
			{
				if(strcmp($query,""))
					$query.=", ";
				$old_field_name=$renamed_fields[Key($renamed_fields)];
				$query.="CHANGE $old_field_name ".$changes["RenamedFields"][$old_field_name]["Declaration"];
			}
		}
		$remove_primary_key=0;
		$primary_key_fields=array();
		if(IsSet($changes["RemovedPrimaryKey"]))
			$remove_primary_key=1;
		if(IsSet($changes["AddedPrimaryKey"]))
			$primary_key_fields=$changes["AddedPrimaryKey"]["FIELDS"];
		if(IsSet($changes["ChangedPrimaryKey"]))
		{
			$remove_primary_key=1;
			$primary_key_fields=$changes["ChangedPrimaryKey"]["FIELDS"];
		}
		if($remove_primary_key)
		{
			if(strcmp($query,""))
				$query.=", ";
			$query.="DROP PRIMARY KEY";
		}
		if(count($primary_key_fields))
		{
			if(strcmp($query,""))
				$query.=", ";
			$query.="ADD PRIMARY KEY (";
			for($field=0,Reset($primary_key_fields);$field<count($primary_key_fields);Next($primary_key_fields),$field++)
			{
				if($field>0)
					$query.=", ";
				$query.=Key($primary_key_fields);
			}
			$query.=")";
		}
		$query="ALTER TABLE $name $query";
		if(IsSet($changes["SQL"]))
			$changes["SQL"]=array($query);
		return($check ? 1 : $db->Query($query));
	}

	Function ListTables(&$db,&$tables)
	{
		if(!$db->QueryColumn("SHOW TABLES",$table_names))
			return(0);
		$prefix_length=strlen($db->sequence_prefix);
		for($tables=array(),$table=0;$table<count($table_names);$table++)
		{
			if(substr($table_names[$table],0,$prefix_length)!=$db->sequence_prefix)
				$tables[]=$table_names[$table];
		}
		return(1);
	}

	Function ListTableFields(&$db,$table,&$fields)
	{
		if(!($result=$db->Query("SHOW COLUMNS FROM $table")))
			return(0);
		if(!$db->GetColumnNames($result,$columns))
		{
			$db->FreeResult($result);
			return(0);
		}
		if(!IsSet($columns["field"]))
		{
			$db->FreeResult($result);
			return($db->SetError("List table fields","show columns does not return the table field names"));
		}
		$field_column=$columns["field"];
		for($fields=array(),$field=0;!$db->EndOfResult($result);$field++)
		{
			$field_name=$db->FetchResult($result,$field,$field_column);
			if($field_name!=$db->dummy_primary_key)
				$fields[]=$field_name;
		}
		$db->FreeResult($result);
		return(1);
	}

	Function GetTableFieldDefinition(&$db,$table,$field,&$definition)
	{
		$field_name=strtolower($field);
		if($field_name==$db->dummy_primary_key)
			return($db->SetError("Get table field definition",$db->dummy_primary_key." is an hidden column"));
		if(!($result=$db->Query("SHOW COLUMNS FROM $table")))
			return(0);
		if(!$db->GetColumnNames($result,$columns))
		{
			$db->FreeResult($result);
			return(0);
		}
		if(!IsSet($columns[$column="field"])
		|| !IsSet($columns[$column="type"]))
		{
			$db->FreeResult($result);
			return($db->SetError("Get table field definition","show columns does not return the column $column"));
		}
		$field_column=$columns["field"];
		$type_column=$columns["type"];
		for($field_row=0;!$db->EndOfResult($result);$field_row++)
		{
			if(!$db->FetchResultArray($result,$row,$field_row))
			{
				$db->FreeResult($result);
				return(0);
			}
			if($field_name==strtolower($row[$field_column]))
			{
				$db_type=strtolower($row[$type_column]);
				$db_type=strtok($db_type,"(), ");
				if($db_type=="national")
					$db_type=strtok("(), ");
				$length=strtok("(), ");
				$decimal=strtok("(), ");
				$type=array();
				switch($db_type)
				{
					case "tinyint":
					case "smallint":
					case "mediumint":
					case "int":
					case "integer":
					case "bigint":
						$type[0]="integer";
						if($length=="1")
							$type[1]="boolean";
						break;

					case "tinytext":
					case "mediumtext":
					case "longtext":
					case "text":
					case "char":
					case "varchar":
						$type[0]="text";
						if($decimal=="binary")
							$type[1]="blob";
						elseif($length=="1")
							$type[1]="boolean";
						elseif(strstr($db_type,"text"))
							$type[1]="clob";
						break;

					case "enum":
					case "set":
						$type[0]="text";
						$type[1]="integer";
						break;

					case "date":
						$type[0]="date";
						break;

					case "datetime":
					case "timestamp":
						$type[0]="timestamp";
						break;

					case "time":
						$type[0]="time";
						break;

					case "float":
					case "double":
					case "real":
						$type[0]="float";
						break;

					case "decimal":
					case "numeric":
						$type[0]="decimal";
						break;

					case "tinyblob":
					case "mediumblob":
					case "longblob":
					case "blob":
						$type[0]="blob";
						break;

					case "year":
						$type[0]="integer";
						$type[1]="date";
						break;

					default:
						return($db->SetError("Get table field definition","unknown database attribute type"));
				}
				UnSet($notnull);
				if(IsSet($columns["null"])
				&& $row[$columns["null"]]!="YES")
					$notnull=1;
				UnSet($default);
				if(IsSet($columns["default"])
				&& IsSet($row[$columns["default"]]))
					$default=$row[$columns["default"]];
				for($definition=array(),$datatype=0;$datatype<count($type);$datatype++)
				{
					$definition[$datatype]=array(
						"type"=>$type[$datatype]
					);
					if(!strcmp($type[$datatype],"integer")
					&& IsSet($columns["extra"])
					&& !strcmp($row[$columns["extra"]],"auto_increment"))
						$definition[$datatype]["autoincrement"]=1;
					if(IsSet($notnull))
						$definition[$datatype]["notnull"]=1;
					if(IsSet($default))
						$definition[$datatype]["default"]=$default;
					if(strlen($length))
						$definition[$datatype]["length"]=$length;
				}
				$db->FreeResult($result);
				return(1);
			}
		}
		$db->FreeResult($result);
		return($db->SetError("Get table field definition","it was not specified an existing table column"));
	}

	Function ListTableKeys(&$db, $table, $primary, &$keys)
	{
		if(!$primary)
			return($db->SetError("List table keys","list table non-primary keys is not yet supported"));
		if(!($result=$db->Query("SHOW INDEX FROM $table")))
			return(0);
		if(!$db->GetColumnNames($result,$columns))
		{
			$db->FreeResult($result);
			return(0);
		}
		if(!IsSet($columns[$field="key_name"]))
		{
			$db->FreeResult($result);
			return($db->SetError("List table keys","show index does not return the table index names"));
		}
		$key_name_column=$columns["key_name"];
		for($found=$keys=array(),$key=0;!$db->EndOfResult($result);$keys++)
		{
			$key_name=$db->FetchResult($result,$key,$key_name_column);
			if(!strcmp($key_name,"PRIMARY"))
			{
				$keys[]=$key_name;
				break;
			}
		}
		$db->FreeResult($result);
		return(1);
	}

	Function GetTableKeyDefinition(&$db, $table, $key, $primary, &$definition)
	{
		if(!$primary)
			return($db->SetError("Get table key definition","get table non-primary key definition is not yet supported"));
		$key_name=strtolower($key);
		if($key_name!="primary")
			return($db->SetError("Get table key definition",$key." is not the table primary key name"));
		if(!($result=$db->Query("SHOW INDEX FROM ".$table)))
			return(0);
		if(!$db->GetColumnNames($result,$columns))
		{
			$db->FreeResult($result);
			return(0);
		}
		if(!IsSet($columns[$column="key_name"])
		|| !IsSet($columns[$column="column_name"])
		|| !IsSet($columns[$column="collation"]))
		{
			$db->FreeResult($result);
			return($db->SetError("Get table key definition","show index does not return the column $column"));
		}
		$key_name_column=$columns["key_name"];
		$column_name_column=$columns["column_name"];
		$collation_column=$columns["collation"];
		$definition=array();
		for($key_row=0;!$db->EndOfResult($result);$key_row++)
		{
			if(!$db->FetchResultArray($result,$row,$key_row))
			{
				$db->FreeResult($result);
				return(0);
			}
			if(!strcmp($key_name,strtolower($row[$key_name_column])))
			{
				$column_name=$row[$column_name_column];
				$definition["FIELDS"][$column_name]=array();
				if(IsSet($row[$collation_column]))
					$definition['FIELDS'][$column_name]['sorting']=($row[$collation_column]=="A" ? "ascending" : "descending");
			}
		}
		$db->FreeResult($result);
		return(IsSet($definition["FIELDS"]) ? 1 : $db->SetError("Get table key definition","it was not specified an existing table key"));
	}

	Function ListTableIndexes(&$db,$table,&$indexes)
	{
		if(!($result=$db->Query("SHOW INDEX FROM $table")))
			return(0);
		if(!$db->GetColumnNames($result,$columns))
		{
			$db->FreeResult($result);
			return(0);
		}
		if(!IsSet($columns["key_name"]))
		{
			$db->FreeResult($result);
			return($db->SetError("List table indexes","show index does not return the table index names"));
		}
		$key_name_column=$columns["key_name"];
		for($found=$indexes=array(),$index=0;!$db->EndOfResult($result);$index++)
		{
			$index_name=$db->FetchResult($result,$index,$key_name_column);
			if($index_name!="PRIMARY"
			&& !IsSet($found[$index_name]))
			{
				$indexes[]=$index_name;
				$found[$index_name]=1;
			}
		}
		$db->FreeResult($result);
		return(1);
	}

	Function GetTableIndexDefinition(&$db,$table,$index,&$definition)
	{
		$index_name=strtolower($index);
		if($index_name=="primary")
			return($db->SetError("Get table index definition",$index." is an hidden index"));
		if(!($result=$db->Query("SHOW INDEX FROM $table")))
			return(0);
		if(!$db->GetColumnNames($result,$columns))
		{
			$db->FreeResult($result);
			return(0);
		}
		if(!IsSet($columns[$column="non_unique"])
		|| !IsSet($columns[$column="key_name"])
		|| !IsSet($columns[$column="column_name"])
		|| !IsSet($columns[$column="collation"]))
		{
			$db->FreeResult($result);
			return($db->SetError("Get table index definition","show index does not return the column $column"));
		}
		$non_unique_column=$columns["non_unique"];
		$key_name_column=$columns["key_name"];
		$column_name_column=$columns["column_name"];
		$collation_column=$columns["collation"];
		$definition=array();
		for($index_row=0;!$db->EndOfResult($result);$index_row++)
		{
			if(!$db->FetchResultArray($result,$row,$index_row))
			{
				$db->FreeResult($result);
				return(0);
			}
			$key_name=$row[$key_name_column];
			if(!strcmp($index,$key_name))
			{
				if(!$row[$non_unique_column])
					$definition["unique"]=1;
				$column_name=$row[$column_name_column];
				$definition["FIELDS"][$column_name]=array();
				if(IsSet($row[$collation_column]))
					$definition['FIELDS'][$column_name]['sorting']=($row[$collation_column]=="A" ? "ascending" : "descending");
			}
		}
		$db->FreeResult($result);
		return(IsSet($definition["FIELDS"]) ? 1 : $db->SetError("Get table index definition","it was not specified an existing table index"));
	}

	Function ListSequences(&$db,&$sequences)
	{
		if(!$db->QueryColumn("SHOW TABLES",$table_names))
			return(0);
		$prefix_length=strlen($db->sequence_prefix);
		for($sequences=array(),$table=0;$table<count($table_names);$table++)
		{
			if(substr($table_names[$table],0,$prefix_length)==$db->sequence_prefix)
				$sequences[]=substr($table_names[$table],$prefix_length);
		}
		return(1);
	}

	Function GetSequenceDefinition(&$db,$sequence,&$definition)
	{
		if(!$db->QueryColumn("SHOW TABLES",$table_names))
			return(0);
		$prefix_length=strlen($db->sequence_prefix);
		for($table=0;$table<count($table_names);$table++)
		{
			if(substr($table_names[$table],0,$prefix_length)==$db->sequence_prefix
			&& !strcmp(substr($table_names[$table],$prefix_length),$sequence))
			{
				if(!$db->QueryField("SELECT MAX(sequence) FROM ".$table_names[$table],$start))
					return(0);
				$definition=array("start"=>$start+1);
				return(1);
			}
		}
		return($db->SetError("Get sequence definition","it was not specified an existing sequence"));
	}

	Function CreateSequence(&$db,$name,$start)
	{
		if(!$this->VerifyTransactionalTableType($db,$db->default_table_type))
			return(0);
		if(!$db->Query("CREATE TABLE _sequence_$name (sequence INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (sequence))".(strlen($db->default_table_type) ? " TYPE=".$db->default_table_type : "")))
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

	Function GetSequenceCurrentValue(&$db,$name,&$value)
	{
		return($db->QueryField("SELECT MAX(sequence) FROM ".$db->sequence_prefix.$name,$value,"integer"));
	}

	Function CreateIndex(&$db,$table,$name,&$definition)
	{
		$query="ALTER TABLE $table ADD ".(IsSet($definition["unique"]) ? "UNIQUE" : "INDEX")." $name (";
		for($field=0,Reset($definition["FIELDS"]);$field<count($definition["FIELDS"]);$field++,Next($definition["FIELDS"]))
		{
			if($field>0)
				$query.=",";
			$query.=Key($definition["FIELDS"]);
		}
		$query.=")";
		return($db->Query($query));
	}

	Function DropIndex(&$db,$table,$name)
	{
		return($db->Query("ALTER TABLE $table DROP INDEX $name"));
	}
};
}
?>