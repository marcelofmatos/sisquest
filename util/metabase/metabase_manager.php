<?php
/*
 * metabase_manager.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/metabase/metabase_manager.php,v 1.87 2005/11/17 21:25:37 mlemos Exp $
 *
 */

class metabase_manager_class
{
	var $fail_on_invalid_names=1;
	var $error="";
	var $warnings=array();
	var $database=0;
	var $database_definition=array(
		"name"=>"",
		"create"=>0,
		"TABLES"=>array()
	);

	Function SetupDatabase(&$arguments)
	{
		if(IsSet($arguments["Connection"])
		&& strlen($error=MetabaseParseConnectionArguments($arguments["Connection"],$arguments)))
			return($error);
		if(IsSet($arguments["Debug"]))
			$this->debug=$arguments["Debug"];
		if(strlen($error=MetabaseSetupDatabase($arguments,$this->database)))
			return($error);
		if(!IsSet($arguments["Debug"]))
			MetabaseCaptureDebugOutput($this->database,1);
		return("");
	}

	Function CloseSetup()
	{
		if($this->database!=0)
		{
			MetabaseCloseSetup($this->database);
			$this->database=0;
		}
	}

	Function GetField(&$field,$field_name,$declaration,&$query)
	{
		if(!strcmp($field_name,""))
			return("it was not specified a valid field name (\"$field_name\")");
		switch($field["type"])
		{
			case "integer":
				if($declaration)
					$query=MetabaseGetIntegerFieldTypeDeclaration($this->database,$field_name,$field);
				else
					$query=$field_name;
				break;
			case "text":
				if($declaration)
					$query=MetabaseGetTextFieldTypeDeclaration($this->database,$field_name,$field);
				else
					$query=$field_name;
				break;
			case "clob":
				if($declaration)
					$query=MetabaseGetCLOBFieldTypeDeclaration($this->database,$field_name,$field);
				else
					$query=$field_name;
				break;
			case "blob":
				if($declaration)
					$query=MetabaseGetBLOBFieldTypeDeclaration($this->database,$field_name,$field);
				else
					$query=$field_name;
				break;
			case "boolean":
				if($declaration)
					$query=MetabaseGetBooleanFieldTypeDeclaration($this->database,$field_name,$field);
				else
					$query=$field_name;
				break;
			case "date":
				if($declaration)
					$query=MetabaseGetDateFieldTypeDeclaration($this->database,$field_name,$field);
				else
					$query=$field_name;
				break;
			case "timestamp":
				if($declaration)
					$query=MetabaseGetTimestampFieldTypeDeclaration($this->database,$field_name,$field);
				else
					$query=$field_name;
				break;
			case "time":
				if($declaration)
					$query=MetabaseGetTimeFieldTypeDeclaration($this->database,$field_name,$field);
				else
					$query=$field_name;
				break;
			case "float":
				if($declaration)
					$query=MetabaseGetFloatFieldTypeDeclaration($this->database,$field_name,$field);
				else
					$query=$field_name;
				break;
			case "decimal":
				if($declaration)
					$query=MetabaseGetDecimalFieldTypeDeclaration($this->database,$field_name,$field);
				else
					$query=$field_name;
				break;
			default:
				return("type \"".$field["type"]."\" is not yet supported");
		}
		return("");
	}

	Function GetFieldList($fields,$declaration,&$query_fields)
	{
		for($query_fields="",Reset($fields),$field_number=0;$field_number<count($fields);$field_number++,Next($fields))
		{
			if($field_number>0)
				$query_fields.=",";
			$field_name=Key($fields);
			if(strcmp($error=$this->GetField($fields[$field_name],$field_name,$declaration,$query),""))
				return($error);
			$query_fields.=$query;
		}
		return("");
	}

	Function GetFields($table,&$fields)
	{
		return($this->GetFieldList($this->database_definition["TABLES"][$table]["FIELDS"],0,$fields));
	}

	Function CreateTable($table_name,$table,$check)
	{
		if(!$check)
			MetabaseDebug($this->database,"Create table: ".$table_name);
		if(!MetabaseCreateDetailedTable($this->database,$table,$check))
			return(MetabaseError($this->database));
		$success=1;
		$error="";
		if(!$check
		&& IsSet($table["initialization"]))
		{
			$instructions=$table["initialization"];
			for(Reset($instructions),$instruction=0;$success && $instruction<count($instructions);$instruction++,Next($instructions))
			{
				switch($instructions[$instruction]["type"])
				{
					case "insert":
						$fields=$instructions[$instruction]["FIELDS"];
						for($query_fields=$query_values="",Reset($fields),$field_number=0;$field_number<count($fields);$field_number++,Next($fields))
						{
							if($field_number>0)
							{
								$query_fields.=",";
								$query_values.=",";
							}
							$field_name=Key($fields);
							$field=$table["FIELDS"][$field_name];
							if(strcmp($error=$this->GetField($field,$field_name,0,$query),""))
								return($error);
							$query_fields.=$query;
							$query_values.="?";
						}
						if(($success=($prepared_query=MetabasePrepareQuery($this->database,"INSERT INTO $table_name ($query_fields) VALUES ($query_values)"))))
						{
							for($lobs=array(),Reset($fields),$field_number=0;$field_number<count($fields);$field_number++,Next($fields))
							{
								$field_name=Key($fields);
								$field=$table["FIELDS"][$field_name];
								if(strcmp($error=$this->GetField($field,$field_name,0,$query),""))
									return($error);
								switch($field["type"])
								{
									case "integer":
										$success=MetabaseQuerySetInteger($this->database,$prepared_query,$field_number+1,intval($fields[$field_name]));
										break;
									case "text":
										$success=MetabaseQuerySetText($this->database,$prepared_query,$field_number+1,$fields[$field_name]);
										break;
									case "clob":
										$lob_definition=array(
											"Database"=>$this->database,
											"Error"=>"",
											"Data"=>$fields[$field_name]
										);
										$lob=count($lobs);
										if(!($success=MetabaseCreateLOB($lob_definition,$lobs[$lob])))
										{
											$error=$lob_definition["Error"];
											break;
										}
										$success=MetabaseQuerySetCLOB($this->database,$prepared_query,$field_number+1,$lobs[$lob],$field_name);
										break;
									case "blob":
										$lob_definition=array(
											"Database"=>$this->database,
											"Error"=>"",
											"Data"=>$fields[$field_name]
										);
										$lob=count($lobs);
										if(!($success=MetabaseCreateLOB($lob_definition,$lobs[$lob])))
										{
											$error=$lob_definition["Error"];
											break;
										}
										$success=MetabaseQuerySetBLOB($this->database,$prepared_query,$field_number+1,$lobs[$lob],$field_name);
										break;
									case "boolean":
										$success=MetabaseQuerySetBoolean($this->database,$prepared_query,$field_number+1,intval($fields[$field_name]));
										break;
									case "date":
										$success=MetabaseQuerySetDate($this->database,$prepared_query,$field_number+1,$fields[$field_name]);
										break;
									case "timestamp":
										$success=MetabaseQuerySetTimestamp($this->database,$prepared_query,$field_number+1,$fields[$field_name]);
										break;
									case "time":
										$success=MetabaseQuerySetTime($this->database,$prepared_query,$field_number+1,$fields[$field_name]);
										break;
									case "float":
										$success=MetabaseQuerySetFloat($this->database,$prepared_query,$field_number+1,doubleval($fields[$field_name]));
										break;
									case "decimal":
										$success=MetabaseQuerySetDecimal($this->database,$prepared_query,$field_number+1,$fields[$field_name]);
										break;
									default:
										$error="type \"".$field["type"]."\" is not yet supported";
										$success=0;
										break;
								}
								if(!$success
								&& $error=="")
								{
									$error=MetabaseError($this->database);
									break;
								}
							}
							if($success
							&& !($success=MetabaseExecuteQuery($this->database,$prepared_query)))
								$error=MetabaseError($this->database);
							for($lob=0;$lob<count($lobs);$lob++)
								MetabaseDestroyLOB($lobs[$lob]);
							MetabaseFreePreparedQuery($this->database,$prepared_query);
						}
						else
							$error=MetabaseError($this->database);
						break;
				}
			}
		}
		if($success
		&& IsSet($table["INDEXES"]))
		{
			if(!MetabaseSupport($this->database,"Indexes"))
				return("indexes are not supported");
			if(!$check)
			{
				$indexes=$table["INDEXES"];
				for($index=0,Reset($indexes);$index<count($indexes);Next($indexes),$index++)
				{
					if(!MetabaseCreateIndex($this->database,$table_name,Key($indexes),$indexes[Key($indexes)]))
					{
						$error=MetabaseError($this->database);
						$success=0;
						break;
					}
				}
			}
		}
		if(!$success
		&& !$check)
		{
			if(strlen($drop_error=$this->DropTable($this->database,$table,0)))
				$error="could not initialize the table \"".$table_name."\" (".$error.") and then could not drop the table (".$drop_error.")";
		}
		return($error);
	}

	Function DropTable($table,$check)
	{
		return(MetabaseDropDetailedTable($this->database,$table,$check) ? "" : MetabaseError($this->database));
	}

	Function CreateSequence($sequence_name,$sequence,$created_on_table)
	{
		if(!MetabaseSupport($this->database,"Sequences"))
			return("sequences are not supported");
		MetabaseDebug($this->database,"Create sequence: ".$sequence_name);
		if(!IsSet($sequence_name)
		|| !strcmp($sequence_name,""))
			return("it was not specified a valid sequence name");
		$start=$sequence["start"];
		if(IsSet($sequence["on"])
		&& !$created_on_table)
		{
			$table=$sequence["on"]["table"];
			$field=$sequence["on"]["field"];
			if(MetabaseSupport($this->database,"SummaryFunctions"))
				$field="MAX($field)";
			if(!($result=MetabaseQuery($this->database,"SELECT $field FROM $table")))
				return(MetabaseError($this->database));
			if(($rows=MetabaseNumberOfRows($this->database,$result)))
			{
				for($row=0;$row<$rows;$row++)
				{
					if(!MetabaseResultIsNull($this->database,$result,$row,0)
					&& ($value=MetabaseFetchResult($this->database,$result,$row,0)+1)>$start)
						$start=$value;
				}
			}
			MetabaseFreeResult($this->database,$result);
		}
		if(!MetabaseCreateSequence($this->database,$sequence_name,$start))
			return(MetabaseError($this->database));
		return("");
	}

	Function DropSequence($sequence_name)
	{
		return(MetabaseDropSequence($this->database,$sequence_name) ? "" : MetabaseError($this->database));
	}

	Function CreateDatabase()
	{
		if(!IsSet($this->database_definition["name"])
		|| !strcmp($this->database_definition["name"],""))
			return("it was not specified a valid database name");
		for(Reset($this->database_definition["TABLES"]),$table=0;$table<count($this->database_definition["TABLES"]);Next($this->database_definition["TABLES"]),$table++)
		{
			$table_name=Key($this->database_definition["TABLES"]);
			if(strcmp($error=$this->CreateTable($table_name,$this->database_definition["TABLES"][$table_name],1),""))
				return("database driver is not able to perform the database instalation: ".$error);
		}
		if(IsSet($this->database_definition["SEQUENCES"])
		&& !MetabaseSupport($this->database,"Sequences"))
			return("database driver is not able to perform the database instalation: sequences are not supported");
		$create=(IsSet($this->database_definition["create"]) && $this->database_definition["create"]);
		if($create)
		{
			MetabaseDebug($this->database,"Create database: ".$this->database_definition["name"]);
			if(!MetabaseCreateDatabase($this->database,$this->database_definition["name"]))
			{
				$error=MetabaseError($this->database);
				MetabaseDebug($this->database,"Create database error: ".$error);
				return($error);
			}
		}
		$previous_database_name=MetabaseSetDatabase($this->database,$this->database_definition["name"]);
		if(($support_transactions=MetabaseSupport($this->database,"Transactions"))
		&& !MetabaseAutoCommitTransactions($this->database,0))
			return(MetabaseError($this->database));
		$created_objects=0;
		for($error="",Reset($this->database_definition["TABLES"]),$table=0;$table<count($this->database_definition["TABLES"]);Next($this->database_definition["TABLES"]),$table++)
		{
			$table_name=Key($this->database_definition["TABLES"]);
			if(strcmp($error=$this->CreateTable($table_name,$this->database_definition["TABLES"][$table_name],0),""))
				break;
			$created_objects++;
		}
		if(!strcmp($error,"")
		&& IsSet($this->database_definition["SEQUENCES"]))
		{
			for($error="",Reset($this->database_definition["SEQUENCES"]),$sequence=0;$sequence<count($this->database_definition["SEQUENCES"]);Next($this->database_definition["SEQUENCES"]),$sequence++)
			{
				$sequence_name=Key($this->database_definition["SEQUENCES"]);
				if(strcmp($error=$this->CreateSequence($sequence_name,$this->database_definition["SEQUENCES"][$sequence_name],1),""))
					break;
				$created_objects++;
			}
		}
		if(strcmp($error,""))
		{
			if($created_objects)
			{
				if($support_transactions)
				{
					if(!MetabaseRollbackTransaction($this->database))
						$error="Could not rollback the partially created database alterations: Rollback error: ".MetabaseError($this->database)." Creation error: $error";
				}
				else
					$error="the database was only partially created: $error";
			}
		}
		else
		{
			if($support_transactions)
			{
				if(!MetabaseAutoCommitTransactions($this->database,1))
					$error="Could not end transaction after successfully created the database: ".MetabaseError($this->database);
			}
		}
		MetabaseSetDatabase($this->database,$previous_database_name);
		if(strcmp($error,"")
		&& $create
		&& !MetabaseDropDatabase($this->database,$this->database_definition["name"]))
			$error="Could not drop the created database after unsuccessful creation attempt: ".MetabaseError($this->database)." Creation error: ".$error;
		return($error);
	}

	Function AddDefinitionChange(&$changes,$definition,$item,$change)
	{
		if(!IsSet($changes[$definition][$item]))
			$changes[$definition][$item]=array();
		for($change_number=0,Reset($change);$change_number<count($change);Next($change),$change_number++)
		{
			$name=Key($change);
			if(!strcmp(GetType($change[$name]),"array"))
			{
				if(!IsSet($changes[$definition][$item][$name]))
					$changes[$definition][$item][$name]=array();
				$change_parts=$change[$name];
				for($change_part=0,Reset($change_parts);$change_part<count($change_parts);Next($change_parts),$change_part++)
					$changes[$definition][$item][$name][Key($change_parts)]=$change_parts[Key($change_parts)];
			} 
			else
				$changes[$definition][$item][Key($change)]=$change[Key($change)];
		}
	}

	Function CompareDefinitions(&$previous_definition,&$changes)
	{
		$changes=array();
		for($defined_tables=array(),Reset($this->database_definition["TABLES"]),$table=0;$table<count($this->database_definition["TABLES"]);Next($this->database_definition["TABLES"]),$table++)
		{
			$table_name=Key($this->database_definition["TABLES"]);
			$was_table_name=$this->database_definition["TABLES"][$table_name]["was"];
			if(IsSet($previous_definition["TABLES"][$table_name])
			&& IsSet($previous_definition["TABLES"][$table_name]["was"])
			&& !strcmp($previous_definition["TABLES"][$table_name]["was"],$was_table_name))
				$was_table_name=$table_name;
			if(IsSet($previous_definition["TABLES"][$was_table_name]))
			{
				$information=array();
				if(strcmp($was_table_name,$table_name))
				{
					$this->AddDefinitionChange($changes,"TABLES",$was_table_name,array("name"=>$table_name));
					MetabaseDebug($this->database,"Renamed table '$was_table_name' to '$table_name'");
				}
				if(IsSet($defined_tables[$was_table_name]))
					return("the table '$was_table_name' was specified as base of more than of table of the database");
				$defined_tables[$was_table_name]=1;

				$fields=$this->database_definition["TABLES"][$table_name]["FIELDS"];
				$previous_fields=$previous_definition["TABLES"][$was_table_name]["FIELDS"];
				for($defined_fields=array(),Reset($fields),$field=0;$field<count($fields);Next($fields),$field++)
				{
					$field_name=Key($fields);
					$was_field_name=$fields[$field_name]["was"];
					if(IsSet($previous_fields[$field_name])
					&& IsSet($previous_fields[$field_name]["was"])
					&& !strcmp($previous_fields[$field_name]["was"],$was_field_name))
						$was_field_name=$field_name;
					if(IsSet($previous_fields[$was_field_name]))
					{
						if(strcmp($was_field_name,$field_name))
						{
							$field_declaration=$fields[$field_name];
							if(strcmp($error=$this->GetField($field_declaration,$field_name,1,$query),""))
								return($error);
							$this->AddDefinitionChange($changes,"TABLES",$was_table_name,array("RenamedFields"=>array($was_field_name=>array(
								"name"=>$field_name,
								"Declaration"=>$query
							))));
							MetabaseDebug($this->database,"Renamed field '$was_field_name' to '$field_name' in table '$table_name'");
						}
						if(IsSet($defined_fields[$was_field_name]))
							return("the field '$was_field_name' was specified as base of more than one field of table '$table_name'");
						$defined_fields[$was_field_name]=1;
						$change=array();
						if(!strcmp($fields[$field_name]["type"],$previous_fields[$was_field_name]["type"]))
						{
							switch($fields[$field_name]["type"])
							{
								case "integer":
									$previous_unsigned=IsSet($previous_fields[$was_field_name]["unsigned"]);
									$unsigned=IsSet($fields[$field_name]["unsigned"]);
									if(strcmp($previous_unsigned,$unsigned))
									{
										$change["unsigned"]=$unsigned;
										MetabaseDebug($this->database,"Changed field '$field_name' type from '".($previous_unsigned ? "unsigned " : "").$previous_fields[$was_field_name]["type"]."' to '".($unsigned ? "unsigned " : "").$fields[$field_name]["type"]."' in table '$table_name'");
									}
									if(($previous_auto_increment=IsSet($previous_fields[$was_field_name]["autoincrement"])))
										$this->AddDefinitionChange($information,"TABLES",$was_table_name,array("AutoIncrement"=>array("was"=>$was_field_name)));
									if(($auto_increment=IsSet($fields[$field_name]["autoincrement"])))
										$this->AddDefinitionChange($information,"TABLES",$was_table_name,array("AutoIncrement"=>array("field"=>$field_name)));
									if(strcmp($previous_auto_increment,$auto_increment))
									{
										$change["autoincrement"]=$auto_increment;
										MetabaseDebug($this->database,"Changed field '$field_name' from '".($previous_auto_increment ? "" : "no ")."autoincrement' to '".($auto_increment ? "" : "no ")."autoincrement' in table '$table_name'");
									}
									break;
								case "text":
								case "clob":
								case "blob":
									$previous_length=(IsSet($previous_fields[$was_field_name]["length"]) ? $previous_fields[$was_field_name]["length"] : 0);
									$length=(IsSet($fields[$field_name]["length"]) ? $fields[$field_name]["length"] : 0);
									if(strcmp($previous_length,$length))
									{
										$change["length"]=$length;
										MetabaseDebug($this->database,"Changed field '$field_name' length from '".$previous_fields[$was_field_name]["type"].($previous_length==0 ? " no length" : "($previous_length)")."' to '".$fields[$field_name]["type"].($length==0 ? " no length" : "($length)")."' in table '$table_name'");
									}
									break;
								case "date":
								case "timestamp":
								case "time":
								case "boolean":
								case "float":
								case "decimal":
									break;
								default:
									return("type \"".$fields[$field_name]["type"]."\" is not yet supported");
							}

							$previous_notnull=(IsSet($previous_fields[$was_field_name]["notnull"]) ? 1 : 0);
							$notnull=(IsSet($fields[$field_name]["notnull"]) ? 1 : 0);
							if($previous_notnull!=$notnull)
							{
								$change["ChangedNotNull"]=1;
								if($notnull)
									$change["notnull"]=IsSet($fields[$field_name]["notnull"]);
								MetabaseDebug($this->database,"Changed field '$field_name' notnull from $previous_notnull to $notnull in table '$table_name'");
							}

							$previous_default=IsSet($previous_fields[$was_field_name]["default"]);
							$default=IsSet($fields[$field_name]["default"]);
							if(strcmp($previous_default,$default))
							{
								$change["ChangedDefault"]=1;
								if($default)
									$change["default"]=$fields[$field_name]["default"];
								MetabaseDebug($this->database,"Changed field '$field_name' default from ".($previous_default ? "'".$previous_fields[$was_field_name]["default"]."'" : "NULL")." TO ".($default ? "'".$fields[$field_name]["default"]."'" : "NULL")." IN TABLE '$table_name'");
							}
							else
							{
								if($default
								&& strcmp($previous_fields[$was_field_name]["default"],$fields[$field_name]["default"]))
								{
									$change["ChangedDefault"]=1;
									$change["default"]=$fields[$field_name]["default"];
									MetabaseDebug($this->database,"Changed field '$field_name' default from '".$previous_fields[$was_field_name]["default"]."' to '".$fields[$field_name]["default"]."' in table '$table_name'");
								}
							}
						}
						else
						{
							$change["type"]=$fields[$field_name]["type"];
							MetabaseDebug($this->database,"Changed field '$field_name' type from '".$previous_fields[$was_field_name]["type"]."' to '".$fields[$field_name]["type"]."' in table '$table_name'");
						}
						if(count($change))
						{
							$field_declaration=$fields[$field_name];
							if(strcmp($error=$this->GetField($field_declaration,$field_name,1,$query),""))
								return($error);
							$change["Declaration"]=$query;
							$change["Definition"]=$field_declaration;
							$this->AddDefinitionChange($changes,"TABLES",$was_table_name,array("ChangedFields"=>array($field_name=>$change)));
						}
					}
					else
					{
						if(strcmp($field_name,$was_field_name))
							return("it was specified a previous field name ('$was_field_name') for field '$field_name' of table '$table_name' that does not exist");
						$field_declaration=$fields[$field_name];
						if(strcmp($error=$this->GetField($field_declaration,$field_name,1,$query),""))
							return($error);
						$field_declaration["Declaration"]=$query;
						$this->AddDefinitionChange($changes,"TABLES",$was_table_name,array("AddedFields"=>array($field_name=>$field_declaration)));
						MetabaseDebug($this->database,"Added field '$field_name' to table '$table_name'");
					}
				}
				for(Reset($previous_fields),$field=0;$field<count($previous_fields);Next($previous_fields),$field++)
				{
					$field_name=Key($previous_fields);
					if(!IsSet($defined_fields[$field_name]))
					{
						$this->AddDefinitionChange($changes,"TABLES",$was_table_name,array("RemovedFields"=>array($field_name=>array())));
						MetabaseDebug($this->database,"Removed field '$field_name' from table '$table_name'");
					}
				}
				$has_primary_key=IsSet($this->database_definition["TABLES"][$table_name]["PRIMARYKEY"]);
				if($has_primary_key)
					$this->AddDefinitionChange($information,"TABLES",$was_table_name,array("PrimaryKey"=>array("key"=>$this->database_definition["TABLES"][$table_name]["PRIMARYKEY"])));
				$had_primary_key=IsSet($previous_definition["TABLES"][$was_table_name]["PRIMARYKEY"]);
				if($had_primary_key)
					$this->AddDefinitionChange($information,"TABLES",$was_table_name,array("PrimaryKey"=>array("was"=>$previous_definition["TABLES"][$was_table_name]["PRIMARYKEY"])));
				if($has_primary_key)
				{
					if($had_primary_key)
					{
						$changed=0;
						$fields=$this->database_definition["TABLES"][$table_name]["PRIMARYKEY"]["FIELDS"];
						if(count($fields)!=count($previous_definition["TABLES"][$was_table_name]["PRIMARYKEY"]["FIELDS"]))
							$changed=1;
						else
						{
							for($field=0, Reset($fields); $field<count($fields); Next($fields), $field++)
							{
								$name=Key($fields);
								if(!IsSet($previous_definition["TABLES"][$was_table_name]["PRIMARYKEY"]["FIELDS"][$name])
								|| count($fields[$name])!=count($previous_definition["TABLES"][$was_table_name]["PRIMARYKEY"]["FIELDS"][$name])
								|| IsSet($fields[$name]["sorting"])!=IsSet($previous_definition["TABLES"][$was_table_name]["PRIMARYKEY"]["FIELDS"][$name]["sorting"])
								|| (IsSet($fields[$name]["sorting"])
								&& strcmp($fields[$name]["sorting"],$previous_definition["TABLES"][$was_table_name]["PRIMARYKEY"]["FIELDS"][$name]["sorting"])))
								{
									$changed=1;
									break;
								}
							}
						}
						if($changed)
						{
							$this->AddDefinitionChange($changes,"TABLES",$was_table_name,array("ChangedPrimaryKey"=>$this->database_definition["TABLES"][$table_name]["PRIMARYKEY"]));
							MetabaseDebug($this->database,"Changed primary key of table '$table_name'");
						}
					}
					else
					{
						$this->AddDefinitionChange($changes,"TABLES",$was_table_name,array("AddedPrimaryKey"=>$this->database_definition["TABLES"][$table_name]["PRIMARYKEY"]));
						MetabaseDebug($this->database,"Added primary key to table '$table_name'");
					}
				}
				elseif(IsSet($previous_definition["TABLES"][$was_table_name]["PRIMARYKEY"]))
				{
					$this->AddDefinitionChange($changes,"TABLES",$was_table_name,array("RemovedPrimaryKey"=>$previous_definition["TABLES"][$was_table_name]["PRIMARYKEY"]));
					MetabaseDebug($this->database,"Removed primary key from table '$table_name'");
				}
				if(IsSet($changes["TABLES"][$was_table_name]))
				{
					if(IsSet($information["TABLES"][$was_table_name]["AutoIncrement"]))
						$changes["TABLES"][$was_table_name]["AutoIncrement"]=$information["TABLES"][$was_table_name]["AutoIncrement"];
					if(IsSet($information["TABLES"][$was_table_name]["PrimaryKey"]))
						$changes["TABLES"][$was_table_name]["PrimaryKey"]=$information["TABLES"][$was_table_name]["PrimaryKey"];
				}
				$indexes=(IsSet($this->database_definition["TABLES"][$table_name]["INDEXES"]) ? $this->database_definition["TABLES"][$table_name]["INDEXES"] : array());
				$previous_indexes=(IsSet($previous_definition["TABLES"][$was_table_name]["INDEXES"]) ? $previous_definition["TABLES"][$was_table_name]["INDEXES"] : array());
				for($defined_indexes=array(),Reset($indexes),$index=0;$index<count($indexes);Next($indexes),$index++)
				{
					$index_name=Key($indexes);
					$was_index_name=$indexes[$index_name]["was"];
					if(IsSet($previous_indexes[$index_name])
					&& IsSet($previous_indexes[$index_name]["was"])
					&& !strcmp($previous_indexes[$index_name]["was"],$was_index_name))
						$was_index_name=$index_name;
					if(IsSet($previous_indexes[$was_index_name]))
					{
						$change=array();

						if(strcmp($was_index_name,$index_name))
						{
							$change["name"]=$was_index_name;
							MetabaseDebug($this->database,"Changed index '$was_index_name' name to '$index_name' in table '$table_name'");
						}
						if(IsSet($defined_indexes[$was_index_name]))
							return("the index '$was_index_name' was specified as base of more than one index of table '$table_name'");
						$defined_indexes[$was_index_name]=1;

						$previous_unique=IsSet($previous_indexes[$was_index_name]["unique"]);
						$unique=IsSet($indexes[$index_name]["unique"]);
						if($previous_unique!=$unique)
						{
							$change["ChangedUnique"]=1;
							if($unique)
								$change["unique"]=$unique;
							MetabaseDebug($this->database,"Changed index '$index_name' unique from $previous_unique to $unique in table '$table_name'");
						}

						$fields=$indexes[$index_name]["FIELDS"];
						$previous_fields=$previous_indexes[$was_index_name]["FIELDS"];
						for($defined_fields=array(),Reset($fields),$field=0;$field<count($fields);Next($fields),$field++)
						{
							$field_name=Key($fields);
							if(IsSet($previous_fields[$field_name]))
							{
								$defined_fields[$field_name]=1;
								$sorting=(IsSet($fields[$field_name]["sorting"]) ? $fields[$field_name]["sorting"] : "");
								$previous_sorting=(IsSet($previous_fields[$field_name]["sorting"]) ? $previous_fields[$field_name]["sorting"] : "");
								if(strcmp($sorting,$previous_sorting))
								{
									MetabaseDebug($this->database,"Changed index field '$field_name' sorting default from '$previous_sorting' to '$sorting' in table '$table_name'");
									$change["ChangedFields"]=1;
								}
							}
							else
							{
								$change["ChangedFields"]=1;
								MetabaseDebug($this->database,"Added field '$field_name' to index '$index_name' of table '$table_name'");
							}
						}
						for(Reset($previous_fields),$field=0;$field<count($previous_fields);Next($previous_fields),$field++)
						{
							$field_name=Key($previous_fields);
							if(!IsSet($defined_fields[$field_name]))
							{
								$change["ChangedFields"]=1;
								MetabaseDebug($this->database,"Removed field '$field_name' from index '$index_name' of table '$table_name'");
							}
						}

						if(count($change))
							$this->AddDefinitionChange($changes,"INDEXES",$table_name,array("ChangedIndexes"=>array($index_name=>$change)));

					}
					else
					{
						if(strcmp($index_name,$was_index_name))
							return("it was specified a previous index name ('$was_index_name') for index '$index_name' of table '$table_name' that does not exist");
						$this->AddDefinitionChange($changes,"INDEXES",$table_name,array("AddedIndexes"=>array($index_name=>$indexes[$index_name])));
						MetabaseDebug($this->database,"Added index '$index_name' to table '$table_name'");
					}
				}
				for(Reset($previous_indexes),$index=0;$index<count($previous_indexes);Next($previous_indexes),$index++)
				{
					$index_name=Key($previous_indexes);
					if(!IsSet($defined_indexes[$index_name]))
					{
						$this->AddDefinitionChange($changes,"INDEXES",$table_name,array("RemovedIndexes"=>array($index_name=>$was_table_name)));
						MetabaseDebug($this->database,"Removed index '$index_name' from table '$was_table_name'");
					}
				}
			}
			else
			{
				if(strcmp($table_name,$was_table_name))
					return("it was specified a previous table name ('$was_table_name') for table '$table_name' that does not exist");
				$this->AddDefinitionChange($changes,"TABLES",$table_name,array("Add"=>1));
				MetabaseDebug($this->database,"Added table '$table_name'");
			}
		}
		for(Reset($previous_definition["TABLES"]),$table=0;$table<count($previous_definition["TABLES"]);Next($previous_definition["TABLES"]),$table++)
		{
			$table_name=Key($previous_definition["TABLES"]);
			if(!IsSet($defined_tables[$table_name]))
			{
				$this->AddDefinitionChange($changes,"TABLES",$table_name,array("Remove"=>$previous_definition["TABLES"][$table_name]));
				MetabaseDebug($this->database,"Removed table '$table_name'");
			}
		}
		if(IsSet($this->database_definition["SEQUENCES"]))
		{
			for($defined_sequences=array(),Reset($this->database_definition["SEQUENCES"]),$sequence=0;$sequence<count($this->database_definition["SEQUENCES"]);Next($this->database_definition["SEQUENCES"]),$sequence++)
			{
				$sequence_name=Key($this->database_definition["SEQUENCES"]);
				$was_sequence_name=$this->database_definition["SEQUENCES"][$sequence_name]["was"];
				if(IsSet($previous_definition["SEQUENCES"][$sequence_name])
				&& IsSet($previous_definition["SEQUENCES"][$sequence_name]["was"])
				&& !strcmp($previous_definition["SEQUENCES"][$sequence_name]["was"],$was_sequence_name))
					$was_sequence_name=$sequence_name;
				if(IsSet($previous_definition["SEQUENCES"][$was_sequence_name]))
				{
					if(strcmp($was_sequence_name,$sequence_name))
					{
						$this->AddDefinitionChange($changes,"SEQUENCES",$was_sequence_name,array("name"=>$sequence_name));
						MetabaseDebug($this->database,"Renamed sequence '$was_sequence_name' to '$sequence_name'");
					}
					if(IsSet($defined_sequences[$was_sequence_name]))
						return("the sequence '$was_sequence_name' was specified as base of more than of sequence of the database");
					$defined_sequences[$was_sequence_name]=1;
					$change=array();
					if(strcmp($this->database_definition["SEQUENCES"][$sequence_name]["start"],$previous_definition["SEQUENCES"][$was_sequence_name]["start"]))
					{
						$change["start"]=$this->database_definition["SEQUENCES"][$sequence_name]["start"];
						MetabaseDebug($this->database,"Changed sequence '$sequence_name' start from '".$previous_definition["SEQUENCES"][$was_sequence_name]["start"]."' to '".$this->database_definition["SEQUENCES"][$sequence_name]["start"]."'");
					}
					if(strcmp($this->database_definition["SEQUENCES"][$sequence_name]["on"]["table"],$previous_definition["SEQUENCES"][$was_sequence_name]["on"]["table"])
					|| strcmp($this->database_definition["SEQUENCES"][$sequence_name]["on"]["field"],$previous_definition["SEQUENCES"][$was_sequence_name]["on"]["field"]))
					{
						$change["on"]=$this->database_definition["SEQUENCES"][$sequence_name]["on"];
						MetabaseDebug($this->database,"Changed sequence '$sequence_name' on table field from '".$previous_definition["SEQUENCES"][$was_sequence_name]["on"]["table"].".".$previous_definition["SEQUENCES"][$was_sequence_name]["on"]["field"]."' to '".$this->database_definition["SEQUENCES"][$sequence_name]["on"]["table"].".".$this->database_definition["SEQUENCES"][$sequence_name]["on"]["field"]."'");
					}
					if(count($change))
						$this->AddDefinitionChange($changes,"SEQUENCES",$was_sequence_name,array("Change"=>array($sequence_name=>array($change))));
				}
				else
				{
					if(strcmp($sequence_name,$was_sequence_name))
						return("it was specified a previous sequence name ('$was_sequence_name') for sequence '$sequence_name' that does not exist");
					$this->AddDefinitionChange($changes,"SEQUENCES",$sequence_name,array("Add"=>1));
					MetabaseDebug($this->database,"Added sequence '$sequence_name'");
				}
			}
		}
		if(IsSet($previous_definition["SEQUENCES"]))
		{
			for(Reset($previous_definition["SEQUENCES"]),$sequence=0;$sequence<count($previous_definition["SEQUENCES"]);Next($previous_definition["SEQUENCES"]),$sequence++)
			{
				$sequence_name=Key($previous_definition["SEQUENCES"]);
				if(!IsSet($defined_sequences[$sequence_name]))
				{
					$this->AddDefinitionChange($changes,"SEQUENCES",$sequence_name,array("Remove"=>1));
					MetabaseDebug($this->database,"Removed sequence '$sequence_name'");
				}
			}
		}
		return("");
	}

	Function CheckTableAutoIncrement(&$table_definition)
	{
		$fields=$table_definition["FIELDS"];
		for($field=0, Reset($fields); $field<count($fields); Next($fields), $field++)
		{
			$name=Key($fields);
			if(IsSet($fields[$name]["autoincrement"]))
				return(1);
		}
		return(0);
	}

	Function AlterDatabase(&$previous_definition,&$changes)
	{
		if(IsSet($changes["TABLES"]))
		{
			for($change=0,Reset($changes["TABLES"]);$change<count($changes["TABLES"]);Next($changes["TABLES"]),$change++)
			{
				$table_name=Key($changes["TABLES"]);
				$name=(IsSet($changes["TABLES"][$table_name]["name"]) ? $changes["TABLES"][$table_name]["name"] : $table_name);
				if(IsSet($changes["TABLES"][$table_name]["Remove"]))
				{
					if(strcmp($error=$this->DropTable($changes["TABLES"][$table_name]["Remove"],1),""))
						return("database driver is not able to perform the requested alterations: ".$error);
					continue;
				}
				if($this->CheckTableAutoIncrement($this->database_definition["TABLES"][$name])
				&& !MetabaseSupport($this->database,"AutoIncrement"))
					return("database driver does not support table autoincrement fields");
				if(IsSet($changes["TABLES"][$table_name]["Add"]))
				{
					if(strcmp($error=$this->CreateTable($table_name,$this->database_definition["TABLES"][$name],1),""))
						return("database driver is not able to perform the requested alterations: ".$error);
				}
				elseif(!MetabaseAlterTable($this->database,$table_name,$changes["TABLES"][$table_name],1))
					return("database driver is not able to perform the requested alterations: ".MetabaseError($this->database));
			}
		}
		if(IsSet($changes["SEQUENCES"]))
		{
			if(!MetabaseSupport($this->database,"Sequences"))
				return("sequences are not supported");
			for($change=0,Reset($changes["SEQUENCES"]);$change<count($changes["SEQUENCES"]);Next($changes["SEQUENCES"]),$change++)
			{
				$sequence_name=Key($changes["SEQUENCES"]);
				if(IsSet($changes["SEQUENCES"][$sequence_name]["Add"])
				|| IsSet($changes["SEQUENCES"][$sequence_name]["Remove"])
				|| IsSet($changes["SEQUENCES"][$sequence_name]["Change"]))
					continue;
				return("some sequences changes are not yet supported");
			}
		}
		if(IsSet($changes["INDEXES"]))
		{
			if(!MetabaseSupport($this->database,"Indexes"))
				return("indexes are not supported");
			for($change=0,Reset($changes["INDEXES"]);$change<count($changes["INDEXES"]);Next($changes["INDEXES"]),$change++)
			{
				$table_name=Key($changes["INDEXES"]);
				$table_changes=count($changes["INDEXES"][$table_name]);
				if(IsSet($changes["INDEXES"][$table_name]["AddedIndexes"]))
					$table_changes--;
				if(IsSet($changes["INDEXES"][$table_name]["RemovedIndexes"]))
					$table_changes--;
				if(IsSet($changes["INDEXES"][$table_name]["ChangedIndexes"]))
					$table_changes--;
				if($table_changes)
					return("index alteration not yet supported");
			}
		}
		$previous_database_name=MetabaseSetDatabase($this->database,$this->database_definition["name"]);
		if(($support_transactions=MetabaseSupport($this->database,"Transactions"))
		&& !MetabaseAutoCommitTransactions($this->database,0))
			return(MetabaseError($this->database));
		$error="";
		$alterations=0;
		if(IsSet($changes["INDEXES"]))
		{
			for($change=0,Reset($changes["INDEXES"]);$change<count($changes["INDEXES"]);Next($changes["INDEXES"]),$change++)
			{
				$table_name=Key($changes["INDEXES"]);
				if(IsSet($changes["INDEXES"][$table_name]["RemovedIndexes"]))
				{
					$indexes=$changes["INDEXES"][$table_name]["RemovedIndexes"];
					for($index=0,Reset($indexes);$index<count($indexes);Next($indexes),$index++)
					{
						if(!MetabaseDropIndex($this->database,$indexes[Key($indexes)],Key($indexes)))
						{
							$error=MetabaseError($this->database);
							break;
						}
						$alterations++;
					}
				}
				if(!strcmp($error,"")
				&& IsSet($changes["INDEXES"][$table_name]["ChangedIndexes"]))
				{
					$indexes=$changes["INDEXES"][$table_name]["ChangedIndexes"];
					for($index=0,Reset($indexes);$index<count($indexes);Next($indexes),$index++)
					{
						$name=Key($indexes);
						$was_name=(IsSet($indexes[$name]["name"]) ? $indexes[$name]["name"] : $name);
						if(!MetabaseDropIndex($this->database,$table_name,$was_name))
						{
							$error=MetabaseError($this->database);
							break;
						}
						$alterations++;
					}
				}
				if(strcmp($error,""))
					break;
			}
		}
		if(!strcmp($error,"")
		&& IsSet($changes["TABLES"]))
		{
			for($change=0,Reset($changes["TABLES"]);$change<count($changes["TABLES"]);Next($changes["TABLES"]),$change++)
			{
				$table_name=Key($changes["TABLES"]);
				if(IsSet($changes["TABLES"][$table_name]["Remove"]))
				{
					if(!strcmp($error=$this->DropTable($changes["TABLES"][$table_name]["Remove"],0),""))
						$alterations++;
				}
				else
				{
					if(!IsSet($changes["TABLES"][$table_name]["Add"]))
					{
						if(!MetabaseAlterTable($this->database,$table_name,$changes["TABLES"][$table_name],0))
							$error=MetabaseError($this->database);
						else
							$alterations++;
					}
				}
				if(strcmp($error,""))
					break;
			}
			for($change=0,Reset($changes["TABLES"]);$change<count($changes["TABLES"]);Next($changes["TABLES"]),$change++)
			{
				$table_name=Key($changes["TABLES"]);
				if(IsSet($changes["TABLES"][$table_name]["Add"]))
				{
					if(!strcmp($error=$this->CreateTable($table_name,$this->database_definition["TABLES"][$table_name],0),""))
						$alterations++;
				}
				if(strcmp($error,""))
					break;
			}
		}
		if(!strcmp($error,"")
		&& IsSet($changes["SEQUENCES"]))
		{
			for($change=0,Reset($changes["SEQUENCES"]);$change<count($changes["SEQUENCES"]);Next($changes["SEQUENCES"]),$change++)
			{
				$sequence_name=Key($changes["SEQUENCES"]);
				if(IsSet($changes["SEQUENCES"][$sequence_name]["Add"]))
				{
					$created_on_table=0;
					if(IsSet($this->database_definition["SEQUENCES"][$sequence_name]["on"]))
					{
						$table=$this->database_definition["SEQUENCES"][$sequence_name]["on"]["table"];
						if(IsSet($changes["TABLES"])
						&& IsSet($changes["TABLES"][$table_name])
						&& IsSet($changes["TABLES"][$table_name]["Add"]))
							$created_on_table=1;
					}
					if(!strcmp($error=$this->CreateSequence($sequence_name,$this->database_definition["SEQUENCES"][$sequence_name],$created_on_table),""))
						$alterations++;
				}
				else
				{
					if(IsSet($changes["SEQUENCES"][$sequence_name]["Remove"]))
					{
						if(!strcmp($error=$this->DropSequence($sequence_name),""))
							$alterations++;
					}
					else
					{
						if(IsSet($changes["SEQUENCES"][$sequence_name]["Change"]))
						{
							$created_on_table=0;
							if(IsSet($this->database_definition["SEQUENCES"][$sequence_name]["on"]))
							{
								$table=$this->database_definition["SEQUENCES"][$sequence_name]["on"]["table"];
								if(IsSet($changes["TABLES"])
								&& IsSet($changes["TABLES"][$table_name])
								&& IsSet($changes["TABLES"][$table_name]["Add"]))
									$created_on_table=1;
							}
							if(!strcmp($error=$this->DropSequence($this->database_definition["SEQUENCES"][$sequence_name]["was"]),"")
							&& !strcmp($error=$this->CreateSequence($sequence_name,$this->database_definition["SEQUENCES"][$sequence_name],$created_on_table),""))
								$alterations++;
						}
						else
							$error="changing sequences is not yet supported";
					}
				}
				if(strcmp($error,""))
					break;
			}
		}
		if(!strcmp($error,"")
		&& IsSet($changes["INDEXES"]))
		{
			for($change=0,Reset($changes["INDEXES"]);$change<count($changes["INDEXES"]);Next($changes["INDEXES"]),$change++)
			{
				$table_name=Key($changes["INDEXES"]);
				if(IsSet($changes["INDEXES"][$table_name]["ChangedIndexes"]))
				{
					$indexes=$changes["INDEXES"][$table_name]["ChangedIndexes"];
					for($index=0,Reset($indexes);$index<count($indexes);Next($indexes),$index++)
					{
						if(!MetabaseCreateIndex($this->database,$table_name,Key($indexes),$this->database_definition["TABLES"][$table_name]["INDEXES"][Key($indexes)]))
						{
							$error=MetabaseError($this->database);
							break;
						}
						$alterations++;
					}
				}
				if(!strcmp($error,"")
				&& IsSet($changes["INDEXES"][$table_name]["AddedIndexes"]))
				{
					$indexes=$changes["INDEXES"][$table_name]["AddedIndexes"];
					for($index=0,Reset($indexes);$index<count($indexes);Next($indexes),$index++)
					{
						if(!MetabaseCreateIndex($this->database,$table_name,Key($indexes),$this->database_definition["TABLES"][$table_name]["INDEXES"][Key($indexes)]))
						{
							$error=MetabaseError($this->database);
							break;
						}
						$alterations++;
					}
				}
				if(strcmp($error,""))
					break;
			}
		}
		if($alterations
		&& strcmp($error,""))
		{
			if($support_transactions)
			{
				if(!MetabaseRollbackTransaction($this->database))
					$error="Could not rollback the partially implemented the requested database alterations: Rollback error: ".MetabaseError($this->database)." Alterations error: $error";
			}
			else
				$error="the requested database alterations were only partially implemented: $error";
		}
		if($support_transactions)
		{
			if(!MetabaseAutoCommitTransactions($this->database,1))
				$this->warnings[]="Could not end transaction after successfully implemented the requested database alterations: ".MetabaseError($this->database);
		}
		MetabaseSetDatabase($this->database,$previous_database_name);
		return($error);
	}

	Function EscapeSpecialCharacters($string)
	{
		if(GetType($string)!="string")
			$string=strval($string);
		for($escaped="",$character=0;$character<strlen($string);$character++)
		{
			switch($string[$character])
			{
				case "\"":
				case ">":
				case "<":
				case "&":
					$escaped.=HtmlEntities($string[$character]);
					break;
				default:
					$code=Ord($string[$character]);
					if($code<32
					|| $code>127)
					{
						$escaped.="&#$code;";
						break;
					}
					$escaped.=$string[$character];
					break;
			}
		}
		return($escaped);
	}

	Function DumpSequence($sequence_name,$output,$eol,$dump_definition)
	{
		$sequence_definition=$this->database_definition["SEQUENCES"][$sequence_name];
		if($dump_definition)
			$start=$sequence_definition["start"];
		else
		{
			if(MetabaseSupport($this->database,"GetSequenceCurrentValue"))
			{
				if(!MetabaseGetSequenceCurrentValue($this->database,$sequence_name,$start))
					return(0);
				$start++;
			}
			else
			{
				if(!MetabaseGetSequenceNextValue($this->database,$sequence_name,$start))
					return(0);
				$this->warnings[]="database does not support getting current sequence value, the sequence value was incremented";
			}
		}
		$output("$eol <sequence>$eol  <name>$sequence_name</name>$eol  <start>$start</start>$eol");
		if(IsSet($sequence_definition["on"]))
			$output("  <on>$eol   <table>".$sequence_definition["on"]["table"]."</table>$eol   <field>".$sequence_definition["on"]["field"]."</field>$eol  </on>$eol");
		$output(" </sequence>$eol");
		return(1);
	}

	Function DumpDatabase($arguments)
	{
		if(!IsSet($arguments["Output"]))
			return("it was not specified a valid output function");
		$output=$arguments["Output"];
		$eol=(IsSet($arguments["EndOfLine"]) ? $arguments["EndOfLine"] : "\n");
		$dump_definition=IsSet($arguments["Definition"]);
		$sequences=array();
		if(IsSet($this->database_definition["SEQUENCES"]))
		{
			for($error="",Reset($this->database_definition["SEQUENCES"]),$sequence=0;$sequence<count($this->database_definition["SEQUENCES"]);Next($this->database_definition["SEQUENCES"]),$sequence++)
			{
				$sequence_name=Key($this->database_definition["SEQUENCES"]);
				if(IsSet($this->database_definition["SEQUENCES"][$sequence_name]["on"]))
					$table=$this->database_definition["SEQUENCES"][$sequence_name]["on"]["table"];
				else
					$table="";
				$sequences[$table][]=$sequence_name;
			}
		}
		$previous_database_name=(strcmp($this->database_definition["name"],"") ? MetabaseSetDatabase($this->database,$this->database_definition["name"]) : "");
		$output("<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>$eol");
		$output("<database>$eol$eol <name>".$this->database_definition["name"]."</name>$eol <create>".$this->database_definition["create"]."</create>$eol");
		for($error="",Reset($this->database_definition["TABLES"]),$table=0;$table<count($this->database_definition["TABLES"]);Next($this->database_definition["TABLES"]),$table++)
		{
			$table_name=Key($this->database_definition["TABLES"]);
			$output("$eol <table>$eol$eol  <name>$table_name</name>$eol");
			$output("$eol  <declaration>$eol");
			$fields=$this->database_definition["TABLES"][$table_name]["FIELDS"];
			for(Reset($fields),$field_number=0;$field_number<count($fields);$field_number++,Next($fields))
			{
				$field_name=Key($fields);
				$field=$fields[$field_name];
				if(!IsSet($field["type"]))
					return("it was not specified the type of the field \"$field_name\" of the table \"$table_name\"");
				$output("$eol   <field>$eol    <name>$field_name</name>$eol    <type>".$field["type"]."</type>$eol");
				switch($field["type"])
				{
					case "integer":
						if(IsSet($field["unsigned"]))
							$output("    <unsigned>1</unsigned>$eol");
						if(IsSet($field["autoincrement"]))
							$output("    <autoincrement>1</autoincrement>$eol");
						break;
					case "text":
					case "clob":
					case "blob":
						if(IsSet($field["length"]))
							$output("    <length>".$field["length"]."</length>$eol");
						break;
					case "boolean":
					case "date":
					case "timestamp":
					case "time":
					case "float":
					case "decimal":
						break;
					default:
						return("type \"".$field["type"]."\" is not yet supported");
				}
				if(IsSet($field["notnull"]))
					$output("    <notnull>1</notnull>$eol");
				if(IsSet($field["default"]))
					$output("    <default>".$this->EscapeSpecialCharacters($field["default"])."</default>$eol");
				$output("   </field>$eol");
			}

			if(IsSet($this->database_definition["TABLES"][$table_name]["PRIMARYKEY"]))
			{
				$output("$eol   <primarykey>$eol");
				$fields=$this->database_definition["TABLES"][$table_name]["PRIMARYKEY"]["FIELDS"];
				for(Reset($fields),$field_number=0;$field_number<count($fields);$field_number++,Next($fields))
				{
					$field_name=Key($fields);
					$field=$fields[$field_name];
					$output("    <field>$eol     <name>$field_name</name>$eol");
					if(IsSet($field["sorting"]))
						$output("     <sorting>".$field["sorting"]."</sorting>$eol");
					$output("    </field>$eol");
				}
				$output("   </primarykey>$eol");
			}

			if(IsSet($this->database_definition["TABLES"][$table_name]["INDEXES"]))
			{
				$indexes=$this->database_definition["TABLES"][$table_name]["INDEXES"];
				for(Reset($indexes),$index_number=0;$index_number<count($indexes);$index_number++,Next($indexes))
				{
					$index_name=Key($indexes);
					$index=$indexes[$index_name];
					$output("$eol   <index>$eol    <name>$index_name</name>$eol");
					if(IsSet($indexes[$index_name]["unique"]))
						$output("    <unique>1</unique>$eol");
					for(Reset($index["FIELDS"]),$field_number=0;$field_number<count($index["FIELDS"]);$field_number++,Next($index["FIELDS"]))
					{
						$field_name=Key($index["FIELDS"]);
						$field=$index["FIELDS"][$field_name];
						$output("    <field>$eol     <name>$field_name</name>$eol");
						if(IsSet($field["sorting"]))
							$output("     <sorting>".$field["sorting"]."</sorting>$eol");
						$output("    </field>$eol");
					}
					$output("   </index>$eol");
				}
			}

			$output("$eol  </declaration>$eol");
			if($dump_definition)
			{
				if(IsSet($this->database_definition["TABLES"][$table_name]["initialization"]))
				{
					$output("$eol  <initialization>$eol");
					$instructions=$this->database_definition["TABLES"][$table_name]["initialization"];
					for(Reset($instructions),$instruction=0;$instruction<count($instructions);$instruction++,Next($instructions))
					{
						switch($instructions[$instruction]["type"])
						{
							case "insert":
								$output("$eol   <insert>$eol");
								$fields=$instructions[$instruction]["FIELDS"];
								for(Reset($fields),$field_number=0;$field_number<count($fields);$field_number++,Next($fields))
								{
									$field_name=Key($fields);
									$output("$eol    <field>$eol     <name>$field_name</name>$eol     <value>".$this->EscapeSpecialCharacters($fields[$field_name])."</value>$eol    </field>$eol");
								}
								$output("$eol   </insert>$eol");
								break;
						}
					}
					$output("$eol  </initialization>$eol");
				}
			}
			else
			{
				if(count($this->database_definition["TABLES"][$table_name]["FIELDS"])==0)
					return("the definition of the table \"$table_name\" does not contain any fields");
				if(strcmp($error=$this->GetFields($table_name,$query_fields),""))
					return($error);
				if(($support_summary_functions=MetabaseSupport($this->database,"SummaryFunctions")))
				{
					if(($result=MetabaseQuery($this->database,"SELECT COUNT(*) FROM $table_name"))==0)
						return(MetabaseError($this->database));
					$rows=MetabaseFetchResult($this->database,$result,0,0);
					MetabaseFreeResult($this->database,$result);
				}
				if(($result=MetabaseQuery($this->database,"SELECT $query_fields FROM $table_name"))==0)
					return(MetabaseError($this->database));
				if(!$support_summary_functions)
					$rows=MetabaseNumberOfRows($this->database,$result);
				if($rows>0)
				{
					$fields=$this->database_definition["TABLES"][$table_name]["FIELDS"];
					$output("$eol  <initialization>$eol");
					for($row=0;$row<$rows;$row++)
					{
						$output("$eol   <insert>$eol");
						for(Reset($fields),$field_number=0;$field_number<count($fields);$field_number++,Next($fields))
						{
							$field_name=Key($fields);
							if(!MetabaseResultIsNull($this->database,$result,$row,$field_name))
							{
								$field=$fields[$field_name];
								$output("$eol    <field>$eol     <name>$field_name</name>$eol     <value>");
								switch($field["type"])
								{
									case "integer":
									case "text":
										$output($this->EscapeSpecialCharacters(MetabaseFetchResult($this->database,$result,$row,$field_name)));
										break;
									case "clob":
										if(!($lob=MetabaseFetchCLOBResult($this->database,$result,$row,$field_name)))
											return(MetabaseError($this->database));
										while(!MetabaseEndOfLOB($lob))
										{
											if(MetabaseReadLOB($lob,$data,8000)<0)
												return(MetabaseLOBError($lob));
											$output($this->EscapeSpecialCharacters($data));
										}
										MetabaseDestroyLOB($lob);
										break;
									case "blob":
										if(!($lob=MetabaseFetchBLOBResult($this->database,$result,$row,$field_name)))
											return(MetabaseError($this->database));
										while(!MetabaseEndOfLOB($lob))
										{
											if(MetabaseReadLOB($lob,$data,8000)<0)
												return(MetabaseLOBError($lob));
											$output(bin2hex($data));
										}
										MetabaseDestroyLOB($lob);
										break;
									case "float":
										$output($this->EscapeSpecialCharacters(MetabaseFetchFloatResult($this->database,$result,$row,$field_name)));
										break;
									case "decimal":
										$output($this->EscapeSpecialCharacters(MetabaseFetchDecimalResult($this->database,$result,$row,$field_name)));
										break;
									case "boolean":
										$output($this->EscapeSpecialCharacters(MetabaseFetchBooleanResult($this->database,$result,$row,$field_name)));
										break;
									case "date":
										$output($this->EscapeSpecialCharacters(MetabaseFetchDateResult($this->database,$result,$row,$field_name)));
										break;
									case "timestamp":
										$output($this->EscapeSpecialCharacters(MetabaseFetchTimestampResult($this->database,$result,$row,$field_name)));
										break;
									case "time":
										$output($this->EscapeSpecialCharacters(MetabaseFetchTimeResult($this->database,$result,$row,$field_name)));
										break;
									default:
										return("type \"".$field["type"]."\" is not yet supported");
								}
								$output("</value>$eol    </field>$eol");
							}
						}
						$output("$eol   </insert>$eol");
					}
					$output("$eol  </initialization>$eol");
				}
				MetabaseFreeResult($this->database,$result);
			}
			$output("$eol </table>$eol");
			if(IsSet($sequences[$table_name]))
			{
				for($sequence=0;$sequence<count($sequences[$table_name]);$sequence++)
				{
					if(!$this->DumpSequence($sequences[$table_name][$sequence],$output,$eol,$dump_definition))
						return(MetabaseError($this->database));
				}
			}
		}
		if(IsSet($sequences[""]))
		{
			for($sequence=0;$sequence<count($sequences[""]);$sequence++)
			{
				if(!$this->DumpSequence($sequences[""][$sequence],$output,$eol,$dump_definition))
					return(MetabaseError($this->database));
			}
		}
		$output("$eol</database>$eol");
		if(strcmp($previous_database_name,""))
			MetabaseSetDatabase($this->database,$previous_database_name);
		return($error);
	}

	Function ParseDatabaseDefinitionFile($input_file,&$database_definition,&$variables,$fail_on_invalid_names=1)
	{
		if(!($file=@fopen($input_file,"r")))
		{
			$error="Could not open input file \"$input_file\"";
			if(IsSet($php_errormsg))
				$error.=" (".$php_errormsg.")";
			return($error);
		}
		$parser=new metabase_parser_class;
		$parser->variables=$variables;
		$parser->fail_on_invalid_names=$fail_on_invalid_names;
		if(strcmp($error=$parser->ParseStream($file),""))
			$error.=" Line ".$parser->error_line." Column ".$parser->error_column." Byte index ".$parser->error_byte_index;
		else
			$database_definition=$parser->database;
		fclose($file);
		return($error);
	}

	Function DumpDatabaseChanges(&$changes)
	{
		if(IsSet($changes["TABLES"]))
		{
			for($change=0,Reset($changes["TABLES"]);$change<count($changes["TABLES"]);Next($changes["TABLES"]),$change++)
			{
				$table_name=Key($changes["TABLES"]);
				MetabaseDebug($this->database,"$table_name:");
				if(IsSet($changes["tables"][$table_name]["Add"]))
					MetabaseDebug($this->database,"\tAdded table '$table_name'");
				else
				{
					if(IsSet($changes["TABLES"][$table_name]["Remove"]))
						MetabaseDebug($this->database,"\tRemoved table '$table_name'");
					else
					{
						if(IsSet($changes["TABLES"][$table_name]["name"]))
							MetabaseDebug($this->database,"\tRenamed table '$table_name' to '".$changes["TABLES"][$table_name]["name"]."'");
						if(IsSet($changes["TABLES"][$table_name]["AddedFields"]))
						{
							$fields=$changes["TABLES"][$table_name]["AddedFields"];
							for($field=0,Reset($fields);$field<count($fields);$field++,Next($fields))
								MetabaseDebug($this->database,"\tAdded field '".Key($fields)."'");
						}
						if(IsSet($changes["TABLES"][$table_name]["RemovedFields"]))
						{
							$fields=$changes["TABLES"][$table_name]["RemovedFields"];
							for($field=0,Reset($fields);$field<count($fields);$field++,Next($fields))
								MetabaseDebug($this->database,"\tRemoved field '".Key($fields)."'");
						}
						if(IsSet($changes["TABLES"][$table_name]["RenamedFields"]))
						{
							$fields=$changes["TABLES"][$table_name]["RenamedFields"];
							for($field=0,Reset($fields);$field<count($fields);$field++,Next($fields))
								MetabaseDebug($this->database,"\tRenamed field '".Key($fields)."' to '".$fields[Key($fields)]["name"]."'");
						}
						if(IsSet($changes["TABLES"][$table_name]["ChangedFields"]))
						{
							$fields=$changes["TABLES"][$table_name]["ChangedFields"];
							for($field=0,Reset($fields);$field<count($fields);$field++,Next($fields))
							{
								$field_name=Key($fields);
								if(IsSet($fields[$field_name]["type"]))
									MetabaseDebug($this->database,"\tChanged field '$field_name' type to '".$fields[$field_name]["type"]."'");
								if(IsSet($fields[$field_name]["unsigned"]))
									MetabaseDebug($this->database,"\tChanged field '$field_name' type to '".($fields[$field_name]["unsigned"] ? "" : "not ")."unsigned'");
								if(IsSet($fields[$field_name]["autoincrement"]))
									MetabaseDebug($this->database,"\tChanged field '$field_name' type to '".($fields[$field_name]["autoincrement"] ? "" : "not ")."autoincrement'");
								if(IsSet($fields[$field_name]["length"]))
									MetabaseDebug($this->database,"\tChanged field '$field_name' length to '".($fields[$field_name]["length"]==0 ? "no length" : $fields[$field_name]["length"])."'");
								if(IsSet($fields[$field_name]["ChangedDefault"]))
									MetabaseDebug($this->database,"\tChanged field '$field_name' default to ".(IsSet($fields[$field_name]["default"]) ? "'".$fields[$field_name]["default"]."'" : "NULL"));
								if(IsSet($fields[$field_name]["ChangedNotNull"]))
									MetabaseDebug($this->database,"\tChanged field '$field_name' notnull to ".(IsSet($fields[$field_name]["notnull"]) ? "'1'" : "0"));
							}
						}
						if(IsSet($changes["TABLES"][$table_name]["RemovedPrimaryKey"]))
							MetabaseDebug($this->database,"\tRemoved primary key");
						if(IsSet($changes["TABLES"][$table_name]["AddedPrimaryKey"]))
							MetabaseDebug($this->database,"\tAdded primary key");
						if(IsSet($changes["TABLES"][$table_name]["ChangedPrimaryKey"]))
							MetabaseDebug($this->database,"\tChanged primary key");
					}
				}
			}
		}
		if(IsSet($changes["SEQUENCES"]))
		{
			for($change=0,Reset($changes["SEQUENCES"]);$change<count($changes["SEQUENCES"]);Next($changes["SEQUENCES"]),$change++)
			{
				$sequence_name=Key($changes["SEQUENCES"]);
				MetabaseDebug($this->database,"$sequence_name:");
				if(IsSet($changes["SEQUENCES"][$sequence_name]["Add"]))
					MetabaseDebug($this->database,"\tAdded sequence '$sequence_name'");
				else
				{
					if(IsSet($changes["SEQUENCES"][$sequence_name]["Remove"]))
						MetabaseDebug($this->database,"\tRemoved sequence '$sequence_name'");
					else
					{
						if(IsSet($changes["SEQUENCES"][$sequence_name]["name"]))
							MetabaseDebug($this->database,"\tRenamed sequence '$sequence_name' to '".$changes["SEQUENCES"][$sequence_name]["name"]."'");
						if(IsSet($changes["SEQUENCES"][$sequence_name]["Change"]))
						{
							$sequences=$changes["SEQUENCES"][$sequence_name]["Change"];
							for($s=0,Reset($sequences);$s<count($sequences);$s++,Next($sequences))
							{
								$name=Key($sequences);
								for($sequence=0;$sequence<count($sequences[$name]);$sequence++)
								{
									if(IsSet($sequences[$name][$sequence]["start"]))
										MetabaseDebug($this->database,"\tChanged sequence '$name' start to '".$sequences[$name][$sequence]["start"]."'");
									if(IsSet($sequences[$name][$sequence]["on"]))
										MetabaseDebug($this->database,"\tChanged sequence '$name' to on field '".$sequences[$name][$sequence]["on"]["field"]."' of table '".$sequences[$name][$sequence]["on"]["table"]."'");
								}
							}
						}
					}
				}
			}
		}
		if(IsSet($changes["INDEXES"]))
		{
			for($change=0,Reset($changes["INDEXES"]);$change<count($changes["INDEXES"]);Next($changes["INDEXES"]),$change++)
			{
				$table_name=Key($changes["INDEXES"]);
				MetabaseDebug($this->database,"$table_name:");
				if(IsSet($changes["INDEXES"][$table_name]["AddedIndexes"]))
				{
					$indexes=$changes["INDEXES"][$table_name]["AddedIndexes"];
					for($index=0,Reset($indexes);$index<count($indexes);Next($indexes),$index++)
						MetabaseDebug($this->database,"\tAdded index '".Key($indexes)."' of table '$table_name'");
				}
				if(IsSet($changes["INDEXES"][$table_name]["RemovedIndexes"]))
				{
					$indexes=$changes["INDEXES"][$table_name]["RemovedIndexes"];
					for($index=0,Reset($indexes);$index<count($indexes);Next($indexes),$index++)
						MetabaseDebug($this->database,"\tRemoved index '".Key($indexes)."' of table '".$indexes[Key($indexes)]."'");
				}
				if(IsSet($changes["INDEXES"][$table_name]["ChangedIndexes"]))
				{
					$indexes=$changes["INDEXES"][$table_name]["ChangedIndexes"];
					for($index=0,Reset($indexes);$index<count($indexes);Next($indexes),$index++)
					{
						if(IsSet($indexes[Key($indexes)]["name"]))
							MetabaseDebug($this->database,"\tRenamed index '".Key($indexes)."' to '".$indexes[Key($indexes)]["name"]."' on table '$table_name'");
						if(IsSet($indexes[Key($indexes)]["ChangedUnique"]))
							MetabaseDebug($this->database,"\tChanged index '".Key($indexes)."' unique to '".IsSet($indexes[Key($indexes)]["unique"])."' on table '$table_name'");
						if(IsSet($indexes[Key($indexes)]["ChangedFields"]))
							MetabaseDebug($this->database,"\tChanged index '".Key($indexes)."' on table '$table_name'");
					}
				}
			}
		}
	}

	Function UpdateDatabase($current_schema_file,$previous_schema_file,&$arguments,&$variables, $check=0)
	{
		if(strcmp($error=$this->ParseDatabaseDefinitionFile($current_schema_file,$this->database_definition,$variables,$this->fail_on_invalid_names),""))
		{
			$this->error="Could not parse database schema file: $error";
			return(0);
		}
		if(strcmp($error=$this->SetupDatabase($arguments),""))
		{
			$this->error="Could not setup database: $error";
			return(0);
		}
		$copy=0;
		if(file_exists($previous_schema_file))
		{
			if(!strcmp($error=$this->ParseDatabaseDefinitionFile($previous_schema_file,$database_definition,$variables,0),"")
			&& !strcmp($error=$this->CompareDefinitions($database_definition,$changes),"")
			&& count($changes))
			{
				if($check
				|| !strcmp($error=$this->AlterDatabase($database_definition,$changes),""))
				{
					
					$copy=!$check;
					$this->DumpDatabaseChanges($changes);
				}
			}
		}
		else
		{
			if(!$check
			&& !strcmp($error=$this->CreateDatabase(),""))
				$copy=1;
		}
		if(strcmp($error,""))
		{
			$this->error="Could not install database: $error";
			return(0);
		}
		if($copy
		&& !copy($current_schema_file,$previous_schema_file))
		{
			$this->error="could not copy the new database definition file to the current file";
			return(0);
		}
		return(1);
	}

	Function DumpDatabaseContents($schema_file,&$setup_arguments,&$dump_arguments,&$variables)
	{
		if(strcmp($error=$this->ParseDatabaseDefinitionFile($schema_file,$database_definition,$variables,$this->fail_on_invalid_names),""))
			return("Could not parse database schema file: $error");
		$this->database_definition=$database_definition;
		if(strcmp($error=$this->SetupDatabase($setup_arguments),""))
			return("Could not setup database: $error");
		return($this->DumpDatabase($dump_arguments));
	}

	Function GetDefinitionFromDatabase(&$arguments)
	{
		if(strcmp($error=$this->SetupDatabase($arguments),""))
			return($this->error="Could not setup database: $error");
		MetabaseSetDatabase($this->database,$database=MetabaseSetDatabase($this->database,""));
		if(strlen($database)==0)
			return("it was not specified a valid database name");
		$this->database_definition=array(
			"name"=>$database,
			"create"=>1,
			"TABLES"=>array()
		);
		if(!MetabaseListTables($this->database,$tables))
			return(MetabaseError($this->database));
		for($table=0;$table<count($tables);$table++)
		{
			$table_name=$tables[$table];
			if(!MetabaseListTableFields($this->database,$table_name,$fields))
				return(MetabaseError($this->database));
			$this->database_definition["TABLES"][$table_name]=array(
				"FIELDS"=>array()
			);
			for($field=0;$field<count($fields);$field++)
			{
				$field_name=$fields[$field];
				if(!MetabaseGetTableFieldDefinition($this->database,$table_name,$field_name,$definition))
					return(MetabaseError($this->database));
				$this->database_definition["TABLES"][$table_name]["FIELDS"][$field_name]=$definition[0];
			}
			if(!MetabaseListTableKeys($this->database,$table_name,1,$keys))
				return(MetabaseError($this->database));
			if(count($keys)
			&& !MetabaseGetTableKeyDefinition($this->database,$table_name,$keys[0],1,$this->database_definition["TABLES"][$table_name]["PRIMARYKEY"]))
				return(MetabaseError($this->database));
			if(!MetabaseListTableIndexes($this->database,$table_name,$indexes))
				return(MetabaseError($this->database));
			if(count($indexes))
			{
				$this->database_definition["TABLES"][$table_name]["INDEXES"]=array();
				for($index=0;$index<count($indexes);$index++)
				{
					$index_name=$indexes[$index];
					if(!MetabaseGetTableIndexDefinition($this->database,$table_name,$index_name,$definition))
						return(MetabaseError($this->database));
					$this->database_definition["TABLES"][$table_name]["INDEXES"][$index_name]=$definition;
				}
			}
		}
		if(!MetabaseListSequences($this->database,$sequences))
			return(MetabaseError($this->database));
		for($sequence=0;$sequence<count($sequences);$sequence++)
		{
			$sequence_name=$sequences[$sequence];
			if(!MetabaseGetSequenceDefinition($this->database,$sequence_name,$definition))
				return(MetabaseError($this->database));
			$this->database_definition["SEQUENCES"][$sequence_name]=$definition;
		}
		return("");
	}
};

?>