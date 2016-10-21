<?php
if(!defined("METABASE_OCI_INCLUDED"))
{
	define("METABASE_OCI_INCLUDED",1);

/*
 * metabase_oci.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/metabase/metabase_oci.php,v 1.34 2006/07/11 18:14:30 mlemos Exp $
 *
 */

class metabase_oci_class extends metabase_database_class
{
	var $auto_commit=1;
	var $uncommitedqueries=0;
	var $connection=0;
	var $connected_user="";
	var $connected_password="";

	var $results=array();
	var $current_row=array();
	var $columns=array();
	var $rows=array();
	var $limits=array();
	var $row_buffer=array();
	var $highest_fetched_row=array();
	var $escape_quotes="'";
	var $manager_class_name="metabase_manager_oci_class";
	var $manager_include="manager_oci.php";
	var $manager_included_constant="METABASE_MANAGER_OCI_INCLUDED";
	var $auto_increment_sequence_prefix="auto_increment_";
	var $auto_increment_trigger_suffix="_key_insert";
	var $escape_pattern="@";

	Function SetOCIError($scope,$message,$error)
	{
		return($this->SetError($scope,"$message. Error: ".$error["code"]." (".$error["message"].")"));
	}

	Function Connect($user,$password,$persistent)
	{
		if(IsSet($this->options["SID"]))
			$sid=$this->options["SID"];
		else
			$sid=getenv("ORACLE_SID");
		if(!strcmp($sid,""))
			return($this->SetError("Connect","it was not specified a valid Oracle Service IDentifier (SID)"));
		if($this->connection!=0)
		{
			if(!strcmp($this->connected_user,$user)
			&& !strcmp($this->connected_password,$password)
			&& $this->opened_persistent==$persistent)
				return(1);
			$this->Close();
		}
		if(IsSet($this->options["HOME"]))
			putenv("ORACLE_HOME=".$this->options["HOME"]);
		putenv("ORACLE_SID=".$sid);
		$function=($persistent ? "OCIPLogon" : "OCINLogon");
		if(!function_exists($function))
			return($this->SetError("Connect","Oracle OCI API support is not available in this PHP configuration"));
		if(!($this->connection=@$function($user,$password,$sid)))
			return($this->SetOCIError("Connect","Could not connect to Oracle server",OCIError()));
		if(!$this->DoQuery("ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'"))
		{
			$this->Close();
			return(0);
		}
		$this->connected_user=$user;
		$this->connected_password=$password;
		$this->opened_persistent=$persistent;
		return(1);
	}

	Function Close()
	{
		if($this->connection!=0)
		{
			OCILogOff($this->connection);
			$this->connection=0;
			$this->affected_rows=-1;
			$this->uncommitedqueries=0;
		}
	}

	Function GetCLOBFieldTypeDeclaration($name,&$field)
	{
		return("$name CLOB".(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetBLOBFieldTypeDeclaration($name,&$field)
	{
		return("$name BLOB".(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetIntegerFieldTypeDeclaration($name,&$field)
	{
		if(IsSet($field["unsigned"]))
			$this->warning="unsigned integer field \"$name\" is being declared as signed integer";
		return("$name ".$this->GetFieldTypeDeclaration($field).(IsSet($field["autoincrement"]) ? " DEFAULT NULL" : (IsSet($field["default"]) ? " DEFAULT ".$field["default"] : "")).(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetTextFieldTypeDeclaration($name,&$field)
	{
		return("$name ".$this->GetFieldTypeDeclaration($field).(IsSet($field["default"]) ? " DEFAULT ".$this->GetTextFieldValue($field["default"]) : "").(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetBooleanFieldTypeDeclaration($name,&$field)
	{
		return("$name ".$this->GetFieldTypeDeclaration($field).(IsSet($field["default"]) ? " DEFAULT ".$this->GetBooleanFieldValue($field["default"]) : "").(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetDateFieldTypeDeclaration($name,&$field)
	{
		return("$name ".$this->GetFieldTypeDeclaration($field).(IsSet($field["default"]) ? " DEFAULT ".$this->GetDateFieldValue($field["default"]) : "").(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetTimestampFieldTypeDeclaration($name,&$field)
	{
		return("$name ".$this->GetFieldTypeDeclaration($field).(IsSet($field["default"]) ? " DEFAULT ".$this->GetTimestampFieldValue($field["default"]) : "").(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetTimeFieldTypeDeclaration($name,&$field)
	{
		return("$name ".$this->GetFieldTypeDeclaration($field).(IsSet($field["default"]) ? " DEFAULT ".$this->GetTimeFieldValue($field["default"]) : "").(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetFloatFieldTypeDeclaration($name,&$field)
	{
		return("$name ".$this->GetFieldTypeDeclaration($field).(IsSet($field["default"]) ? " DEFAULT ".$this->GetFloatFieldValue($field["default"]) : "").(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetDecimalFieldTypeDeclaration($name,&$field)
	{
		return("$name ".$this->GetFieldTypeDeclaration($field).(IsSet($field["default"]) ? " DEFAULT ".$this->GetDecimalFieldValue($field["default"]) : "").(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetFieldTypeDeclaration(&$field)
	{
		switch($field["type"])
		{
			case "integer":
				return("INT");
			case "text":
				return("VARCHAR (".(IsSet($field["length"]) ? $field["length"] : (IsSet($this->options["DefaultTextFieldLength"]) ? $this->options["DefaultTextFieldLength"] : 4000)).")");
			case "boolean":
				return("CHAR (1)");
			case "date":
			case "time":
			case "timestamp":
				return("DATE");
			case "float":
				return("NUMBER");
			case "decimal":
				return("NUMBER(*,".$this->decimal_places.")");
		}
		return("");
	}

	Function GetCLOBFieldValue($prepared_query,$parameter,$clob,&$value)
	{
		$value="EMPTY_CLOB()";
		return(1);			
	}

	Function FreeCLOBValue($prepared_query,$clob,&$value,$success)
	{
		Unset($value);
	}

	Function GetBLOBFieldValue($prepared_query,$parameter,$blob,&$value)
	{
		$value="EMPTY_BLOB()";
		return(1);			
	}

	Function FreeBLOBValue($prepared_query,$blob,&$value,$success)
	{
		Unset($value);
	}

	Function GetDateFieldValue($value)
	{
		return(!strcmp($value,"NULL") ? "NULL" : "TO_DATE('$value','YYYY-MM-DD')");
	}

	Function GetTimestampFieldValue($value)
	{
		return(!strcmp($value,"NULL") ? "NULL" : "TO_DATE('$value','YYYY-MM-DD HH24:MI:SS')");
	}

	Function GetTimeFieldValue($value)
	{
		return(!strcmp($value,"NULL") ? "NULL" : "TO_DATE('0001-01-01 $value','YYYY-MM-DD HH24:MI:SS')");
	}

	Function GetFloatFieldValue($value)
	{
		return(!strcmp($value,"NULL") ? "NULL" : "$value");
	}

	Function GetDecimalFieldValue($value)
	{
		return(!strcmp($value,"NULL") ? "NULL" : "$value");
	}

	Function GetColumnNames($result,&$column_names)
	{
		$result_value=intval($result);
		if(!IsSet($this->highest_fetched_row[$result_value]))
			return($this->SetError("Get column names","it was specified an inexisting result set"));
		if(!IsSet($this->columns[$result_value]))
		{
			$this->columns[$result_value]=array();
			$columns=OCINumCols($result);
			for($column=0;$column<$columns;$column++)
				$this->columns[$result_value][strtolower(OCIColumnName($result,$column+1))]=$column;
		}
		$column_names=$this->columns[$result_value];
		return(1);
	}

	Function NumberOfColumns($result)
	{
		if(!IsSet($this->highest_fetched_row[intval($result)]))
		{
			$this->SetError("Number of columns","it was specified an inexisting result set");
			return(-1);
		}
		return(OCINumCols($result));
	}

	Function DoQuery($query,$first=0,$limit=0,$prepared_query=0)
	{
		$lobs=0;
		$success=1;
		$result=0;
		$descriptors=array();
		if($prepared_query)
		{
			$columns="";
			$variables="";
			for(Reset($this->clobs[$prepared_query]),$clob=0;$clob<count($this->clobs[$prepared_query]);$clob++,Next($this->clobs[$prepared_query]))
			{
				$position=Key($this->clobs[$prepared_query]);
				if(GetType($descriptors[$position]=OCINewDescriptor($this->connection,OCI_D_LOB))!="object")
				{
					$this->SetError("Do query","Could not create descriptor for clob parameter");
					$success=0;
					break;
				}
				$columns.=($lobs==0 ? " RETURNING " : ",").$this->prepared_queries[$prepared_query-1]["Fields"][$position-1];
				$variables.=($lobs==0 ? " INTO " : ",").":clob".$position;
				$lobs++;
			}
			if($success)
			{
				for(Reset($this->blobs[$prepared_query]),$blob=0;$blob<count($this->blobs[$prepared_query]);$blob++,Next($this->blobs[$prepared_query]))
				{
					$position=Key($this->blobs[$prepared_query]);
					if(GetType($descriptors[$position]=OCINewDescriptor($this->connection,OCI_D_LOB))!="object")
					{
						$this->SetError("Do query","Could not create descriptor for blob parameter");
						$success=0;
						break;
					}
					$columns.=($lobs==0 ? " RETURNING " : ",").$this->prepared_queries[$prepared_query-1]["Fields"][$position-1];
					$variables.=($lobs==0 ? " INTO " : ",").":blob".$position;
					$lobs++;
				}
				$query.=$columns.$variables;
			}
		}
		if($success)
		{
			if(($statement=@OCIParse($this->connection,$query)))
			{
				if($lobs)
				{
					for(Reset($this->clobs[$prepared_query]),$clob=0;$clob<count($this->clobs[$prepared_query]);$clob++,Next($this->clobs[$prepared_query]))
					{
						$position=Key($this->clobs[$prepared_query]);
						if(!OCIBindByName($statement,":clob".$position,$descriptors[$position],-1,OCI_B_CLOB))
						{
							$this->SetOCIError("Do query","Could not bind clob upload descriptor",OCIError($statement));
							$success=0;
							break;
						}
					}
					if($success)
					{
						for(Reset($this->blobs[$prepared_query]),$blob=0;$blob<count($this->blobs[$prepared_query]);$blob++,Next($this->blobs[$prepared_query]))
						{
							$position=Key($this->blobs[$prepared_query]);
							if(!OCIBindByName($statement,":blob".$position,$descriptors[$position],-1,OCI_B_BLOB))
							{
								$this->SetOCIError("Do query","Could not bind blob upload descriptor",OCIError($statement));
								$success=0;
								break;
							}
						}
					}
				}
				if($success)
				{
					if(($result=@OCIExecute($statement,($lobs==0 && $this->auto_commit) ? OCI_COMMIT_ON_SUCCESS : OCI_DEFAULT)))
					{
						if($lobs)
						{
							for(Reset($this->clobs[$prepared_query]),$clob=0;$clob<count($this->clobs[$prepared_query]);$clob++,Next($this->clobs[$prepared_query]))
							{
								$position=Key($this->clobs[$prepared_query]);
								$clob_stream=$this->prepared_queries[$prepared_query-1]["Values"][$position-1];
								for($value="";!MetabaseEndOfLOB($clob_stream);)
								{
									if(MetabaseReadLOB($clob_stream,$data,$this->lob_buffer_length)<0)
									{
										$this->SetError("Do query",MetabaseLOBError($clob));
										$success=0;
										break;
									}
									$value.=$data;
								}
								if($success
								&& !$descriptors[$position]->save($value))
								{
									$this->SetOCIError("Do query","Could not upload clob data",OCIError($statement));
									$success=0;
								}
							}
							if($success)
							{
								for(Reset($this->blobs[$prepared_query]),$blob=0;$blob<count($this->blobs[$prepared_query]);$blob++,Next($this->blobs[$prepared_query]))
								{
									$position=Key($this->blobs[$prepared_query]);
									$blob_stream=$this->prepared_queries[$prepared_query-1]["Values"][$position-1];
									for($value="";!MetabaseEndOfLOB($blob_stream);)
									{
										if(MetabaseReadLOB($blob_stream,$data,$this->lob_buffer_length)<0)
										{
											$this->SetError("Do query",MetabaseLOBError($blob));
											$success=0;
											break;
										}
										$value.=$data;
									}
									if($success
									&& !$descriptors[$position]->save($value))
									{
										$this->SetOCIError("Do query","Could not upload blob data",OCIError($statement));
										$success=0;
									}
								}
							}
						}
						if($this->auto_commit)
						{
							if($lobs)
							{
								if($success)
								{
									if(!OCICommit($this->connection))
									{
										$this->SetOCIError("Do query","Could not commit pending LOB updating transaction",OCIError());
										$success=0;
									}
								}
								else
								{
									if(!OCIRollback($this->connection))
										$this->SetOCIError("Do query",$this->Error()." and then could not rollback LOB updating transaction",OCIError());
								}
							}
						}
						else
							$this->uncommitedqueries++;
						if($success)
						{
							switch(OCIStatementType($statement))
							{
								case "SELECT":
									$result_value=intval($statement);
									$this->current_row[$result_value]=-1;
									if($limit>0)
										$this->limits[$result_value]=array($first,$limit,0);
									$this->highest_fetched_row[$result_value]=-1;
									break;
								default:
									$this->affected_rows=OCIRowCount($statement);
									OCIFreeCursor($statement);
									break;
							}
							$result=$statement;
						}
					}
					else
						$this->SetOCIError("Do query","Could not execute query",OCIError($statement));
				}
			}
			else
				$this->SetOCIError("Do query","Could not parse query",OCIError($this->connection));
		}
		for(Reset($descriptors),$descriptor=0;$descriptor<count($descriptors);$descriptor++,Next($descriptors))
			@OCIFreeDesc($descriptors[Key($descriptors)]);
		return($result);
	}

	Function Query($query)
	{
		$this->Debug("Query: $query");
		$first=$this->first_selected_row;
		$limit=$this->selected_row_limit;
		$this->first_selected_row=$this->selected_row_limit=0;
		if(!$this->Connect($this->user,$this->password,$this->persistent))
			return(0);
		return($this->DoQuery($query,$first,$limit));
	}

	Function ExecutePreparedQuery($prepared_query,$query)
	{
		$first=$this->first_selected_row;
		$limit=$this->selected_row_limit;
		$this->first_selected_row=$this->selected_row_limit=0;
		if(!$this->Connect($this->user,$this->password,$this->persistent))
			return(0);
		return($this->DoQuery($query,$first,$limit,$prepared_query));
	}

	Function SkipFirstRows($result)
	{
		$result_value=intval($result);
		$first=$this->limits[$result_value][0];
		for(;$this->limits[$result_value][2]<$first;$this->limits[$result_value][2]++)
		{
			if(!OCIFetch($result))
			{
				$this->limits[$result_value][2]=$first;
				return($this->SetError("Skip first rows","could not skip a query result row"));
			}
		}
		return(1);
	}

	Function FetchRow($result,$row)
	{
		$result_value=intval($result);
		if(!IsSet($this->current_row[$result_value]))
			return($this->SetError("Fetch row","attempted to fetch a row from an unknown query result"));
		if(IsSet($this->results[$result_value][$row]))
			return(1);
		if(IsSet($this->rows[$result_value]))
			return($this->SetError("Fetch row","there are no more rows to retrieve"));
		if(IsSet($this->limits[$result_value]))
		{
			if($row>=$this->limits[$result_value][1])
				return($this->SetError("Fetch row","attempted to fetch a row beyhond the number rows available in the query result"));
			if(!$this->SkipFirstRows($result))
				return(0);
		}
		if(IsSet($this->row_buffer[$result_value]))
		{
			$this->current_row[$result_value]++;
			$this->results[$result_value][$this->current_row[$result_value]]=$this->row_buffer[$result_value];
			Unset($this->row_buffer[$result_value]);
		}
		for(;$this->current_row[$result_value]<$row;$this->current_row[$result_value]++)
		{
			if(!OCIFetchInto($result,$this->results[$result_value][$this->current_row[$result_value]+1]))
			{
				$this->rows[$result_value]=$this->current_row[$result_value]+1;
				return($this->SetError("Fetch row","could not fetch the query result row"));
			}
		}
		return(1);
	}

	Function GetColumn($result,$field)
	{
		$result_value=intval($result);
		if(!$this->GetColumnNames($result,$column_names))
			return(-1);
		if(GetType($field)=="integer")
		{
			if(($column=$field)<0
			|| $column>=count($this->columns[$result_value]))
			{
				$this->SetError("Get column","attempted to fetch an query result column out of range");
				return(-1);
			}
		}
		else
		{
			$name=strtolower($field);
			if(!IsSet($this->columns[$result_value][$name]))
			{
				$this->SetError("Get column","attempted to fetch an unknown query result column");
				return(-1);
			}
			$column=$this->columns[$result_value][$name];
		}
		return($column);
	}

	Function EndOfResult($result)
	{
		$result_value=intval($result);
		if(!IsSet($this->current_row[$result_value]))
		{
			$this->SetError("End of result","attempted to check the end of an unknown result");
			return(-1);
		}
		if(IsSet($this->rows[$result_value]))
			return($this->highest_fetched_row[$result_value]>=$this->rows[$result_value]-1);
		if(IsSet($this->row_buffer[$result_value]))
			return(0);
		if(IsSet($this->limits[$result_value]))
		{
			if(!$this->SkipFirstRows($result)
			|| $this->current_row[$result_value]+1>=$this->limits[$result_value][1])
			{
				$this->rows[$result_value]=0;
				return(1);
			}
		}
		if(OCIFetchInto($result,$this->row_buffer[$result_value]))
			return(0);
		Unset($this->row_buffer[$result_value]);
		$this->rows[$result_value]=$this->current_row[$result_value]+1;
		return(1);
	}

	Function FetchResult($result,$row,$field)
	{
		$result_value=intval($result);
		if(($column=$this->GetColumn($result,$field))==-1
		|| !$this->FetchRow($result,$row))
			return("");
		if(!IsSet($this->results[$result_value][$row][$column]))
			return("");
		$this->highest_fetched_row[$result_value]=max($this->highest_fetched_row[$result_value],$row);
		return($this->results[$result_value][$row][$column]);
	}

	Function FetchResultArray($result,&$array,$row)
	{
		if(!$this->FetchRow($result,$row))
			return(0);
		$result_value=intval($result);
		$array=$this->results[$result_value][$row];
		$this->highest_fetched_row[$result_value]=max($this->highest_fetched_row[$result_value],$row);
		return($this->ConvertResultRow($result,$array));
	}

	Function ResultIsNull($result,$row,$field)
	{
		$result_value=intval($result);
		if(($column=$this->GetColumn($result,$field))==-1
		|| !$this->FetchRow($result,$row))
			return(0);
		$this->highest_fetched_row[$result_value]=max($this->highest_fetched_row[$result_value],$row);
		return(!IsSet($this->results[$result_value][$row][$column]));
	}

	Function RetrieveLOB($lob)
	{
		if(!IsSet($this->lobs[$lob]))
			return($this->SetError("Retrieve LOB","it was not specified a valid lob"));
		if(!IsSet($this->lobs[$lob]["Value"]))
		{
			Unset($lob_object);
			$result=$this->lobs[$lob]["Result"];
			$row=$this->lobs[$lob]["Row"];
			$field=$this->lobs[$lob]["Field"];
			$lob_object=$this->FetchResult($result,$row,$field);
			if(GetType($lob_object)!="object")
			{
				if(($column=$this->GetColumn($result,$field))==-1)
					return(0);
				if(IsSet($this->results[intval($result)][$row][$column]))
					return($this->SetError("Retrieve LOB","attemped to retrive a non LOB result column"));
				else
					return($this->SetError("Retrieve LOB","attemped to retrieve LOB from non existing or NULL column"));
			}
			$this->lobs[$lob]["Value"]=$lob_object->load();
		}
		return(1);
	}

	Function FetchCLOBResult($result,$row,$field)
	{
		return($this->FetchLOBResult($result,$row,$field));
	}

	Function FetchBLOBResult($result,$row,$field)
	{
		return($this->FetchLOBResult($result,$row,$field));
	}

	Function ConvertResult(&$value,$type)
	{
		switch($type)
		{
			case METABASE_TYPE_BOOLEAN:
				$value=(strcmp($value,"Y") ? 0 : 1);
				return(1);
			case METABASE_TYPE_DECIMAL:
				return(1);
			case METABASE_TYPE_FLOAT:
				$value=doubleval($value);
				return(1);
			case METABASE_TYPE_DATE:
				$value=substr($value,0,strlen("YYYY-MM-DD"));
				return(1);
			case METABASE_TYPE_TIME:
				$value=substr($value,strlen("YYYY-MM-DD "),strlen("HH:MI:SS"));
				return(1);
			case METABASE_TYPE_TIMESTAMP:
				return(1);
			default:
				return($this->BaseConvertResult($value,$type));
		}
	}

	Function NumberOfRows($result)
	{
		$result_value=intval($result);
		if(!IsSet($this->current_row[$result_value]))
			return($this->SetError("Number of rows","attemped to obtain the number of rows contained in an unknown query result"));
		if(!IsSet($this->rows[$result_value]))
		{
			if(!$this->GetColumnNames($result,$column_names))
				return(0);
			if(IsSet($this->limits[$result_value]))
			{
				if(!$this->SkipFirstRows($result))
				{
					$this->rows[$result_value]=0;
					return(0);
				}
				$limit=$this->limits[$result_value][1];
			}
			else
				$limit=0;
			if($limit==0
			|| $this->current_row[$result_value]+1<$limit)
			{
				if(IsSet($this->row_buffer[$result_value]))
				{
					$this->current_row[$result_value]++;
					$this->results[$result_value][$this->current_row[$result_value]]=$this->row_buffer[$result_value];
					Unset($this->row_buffer[$result_value]);
				}
				for(;($limit==0 || $this->current_row[$result_value]+1<$limit) && OCIFetchInto($result,$this->results[$result_value][$this->current_row[$result_value]+1]);$this->current_row[$result_value]++);
			}
			$this->rows[$result_value]=$this->current_row[$result_value]+1;
		}
		return($this->rows[$result_value]);
	}

	Function FreeResult($result)
	{
		$result_value=intval($result);
		if(!IsSet($this->current_row[$result_value]))
			return($this->SetError("Free result","attemped to free an unknown query result"));
		UnSet($this->highest_fetched_row[$result_value]);
		UnSet($this->row_buffer[$result_value]);
		UnSet($this->limits[$result_value]);
		UnSet($this->current_row[$result_value]);
		UnSet($this->results[$result_value]);
		UnSet($this->columns[$result_value]);
		UnSet($this->rows[$result_value]);
		UnSet($this->result_types[$result]);
		return(OCIFreeCursor($result));
	}

	Function GetNextKey($table,&$key)
	{
		$key=$this->auto_increment_sequence_prefix.$table.".NEXTVAL";
		return(1);
	}

	Function GetInsertedKey($table,&$value)
	{
		return($this->QueryField("SELECT ".$this->auto_increment_sequence_prefix.$table.".CURRVAL FROM DUAL",$value,"integer"));
	}

	Function GetSequenceNextValue($name,&$value)
	{
		if(!$this->Connect($this->user,$this->password,$this->persistent))
			return(0);
		if(!($result=$this->DoQuery("SELECT $name.NEXTVAL FROM DUAL",0,0)))
			return(0);
		if($this->NumberOfRows($result)==0)
		{
			$this->FreeResult($result);
			return($this->SetError("Get sequence next value","could not find next value in sequence table"));
		}
		$value=intval($this->FetchResult($result,0,0));
		$this->FreeResult($result);
		return(1);
	}

	Function AutoCommitTransactions($auto_commit)
	{
		$this->Debug("AutoCommit: ".($auto_commit ? "On" : "Off"));
		if(((!$this->auto_commit)==(!$auto_commit)))
			return(1);
		if($this->connection
		&& $auto_commit
		&& !$this->CommitTransaction())
			return(0);
		$this->auto_commit=$auto_commit;
		return($this->RegisterTransactionShutdown($auto_commit));
	}

	Function CommitTransaction()
	{
 		$this->Debug("Commit Transaction");
		if($this->auto_commit)
			return($this->SetError("Commit transaction","transaction changes are being auto commited"));
		if($this->uncommitedqueries)
		{
			if(!OCICommit($this->connection))
				return($this->SetOCIError("Commit transaction","Could not commit pending transaction",OCIError()));
			$this->uncommitedqueries=0;
		}
		return(1);
	}

	Function RollbackTransaction()
	{
 		$this->Debug("Rollback Transaction");
		if($this->auto_commit)
			return($this->SetError("Rollback transaction","transactions can not be rolled back when changes are auto commited"));
		if($this->uncommitedqueries)
		{
			if(!OCIRollback($this->connection))
				return($this->SetOCIError("Rollback transaction","Could not rollback pending transaction",OCIError()));
			$this->uncommitedqueries=0;
		}
		return(1);
	}

	Function EscapePatternText($text)
	{
		return(str_replace("_", "@_", str_replace("%", "@%", str_replace("@", "@@", $text))));
	}

	Function Setup()
	{
		$this->supported["Indexes"]=
		$this->supported["SummaryFunctions"]=
		$this->supported["OrderByText"]=
		$this->supported["AffectedRows"]=
		$this->supported["Sequences"]=
		$this->supported["Transactions"]=
		$this->supported["SelectRowRanges"]=
		$this->supported["LOBs"]=
		$this->supported["Replace"]=
		$this->supported["AutoIncrement"]=
		$this->supported["PrimaryKey"]=
		$this->supported["OmitInsertKey"]=
		$this->supported["PatternBuild"]=
			1;
		return("");
	}
};

}
?>