<?php
if(!defined("METABASE_IFX_INCLUDED"))
{
	define("METABASE_IFX_INCLUDED",1);

/*
 * metabase_ifx.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/metabase/metabase_ifx.php,v 1.19 2004/07/27 06:26:03 mlemos Exp $
 *
 */

class metabase_ifx_class extends metabase_database_class
{
	var $connection=0;
	var $connected_host;
	var $connected_database_name="";
	var $connected_user;
	var $connected_password;
	var $opened_persistent="";

	var $current_row=array();
	var $limits=array();
	var $columns=array();
	var $column_names=array();
	var $results=array();
	var $rows=array();
	var $row_buffer=array();
	var $highest_fetched_row=array();
	var $query_parameters=array();
	var $query_parameter_values=array();
	var $escape_quotes="'";
	var $manager_class_name="metabase_manager_ifx_class";
	var $manager_include="manager_ifx.php";
	var $manager_included_constant="METABASE_MANAGER_IFX_INCLUDED";

	Function SetIFXError($scope,$message)
	{
		$error=ifx_error();
		$position=strpos($error,"SQLCODE=");
		if(GetType($position)=="integer")
		{
			$position+=strlen("SQLCODE=");
			if(GetType($code_end=strpos($error," ]",$position))=="integer"
			|| GetType($code_end=strpos($error,"]",$position))=="integer")
				$code=substr($error,$position,$code_end-$position);
			else
				$code=substr($error,$position);
		}
		else
			$code="";
		return($this->SetError($scope,$message.": ".$code." ".ifx_errormsg()));
	}

	Function Connect($dba)
	{
		if(!$this->auto_commit
		&& !IsSet($this->options["Logging"]))
			return($this->SetError("Connect","transactions are not supported on databases without logging"));
		if($dba)
		{
			$user=(IsSet($this->options[$option="DBAUser"]) ? $this->options[$option="DBAUser"] : "");
			$password=(IsSet($this->options[$option="DBAPassword"]) ? $this->options[$option="DBAPassword"] : "");
			$database_name="";
			$persistent=0;
		}
		else
		{
			$user=$this->user;
			$password=$this->password;
			$database_name=$this->database_name;
			$persistent=$this->persistent;
		}
		if(!strcmp($user,""))
			return($this->SetError("Connect","it was not specified a non-empty Informix user name"));
		if($this->connection!=0)
		{
			if(strcmp($this->connected_host,$this->host)
			|| strcmp($this->connected_user,$user)
			|| strcmp($this->connected_password,$password)
			|| $this->opened_persistent!=$persistent)
			{
				ifx_Close($this->connection);
				$this->connection=0;
				$this->connected_database_name="";
				$this->affected_rows=-1;
			}
		}
		if($this->connection==0)
		{
			$function=($this->persistent ? "ifx_pconnect" : "ifx_connect");
			if(!function_exists($function))
				return($this->SetError("Connect","Informix support is not available in this PHP configuration"));
			Putenv("INFORMIXSERVER=".$this->host);
			Putenv("DBDATE=Y4MD-");
			if(($this->connection=@$function("@".$this->host,$user,$password))<=0)
				return($this->SetIFXError("Connect","Could not connect to the Informix server"));
			$this->connected_host=$this->host;
			$this->connected_user=$user;
			$this->connected_password=$password;
			$this->opened_persistent=$persistent;
			$in_transaction=0;
			$new_connection=1;
		}
		else
		{
			$new_connection=0;
			$in_transaction=!$this->auto_commit;
		}
		if(strcmp($this->connected_database_name,$database_name))
		{
			if(($in_transaction
			&& !$this->DoQuery("ROLLBACK"))
			|| !$this->DoQuery("DATABASE $database_name"))
			{
				$this->SetIFXError("Connect","Could not set the Informix database");
				ifx_Close($this->connection);
				$this->connection=0;
				$this->connected_database_name="";
				$this->affected_rows=-1;
				return(0);
			}
			$new_connection=1;
			$this->connected_database_name=$database_name;
		}
		if($new_connection
		&& !$this->auto_commit
		&& strcmp($this->options["Logging"],"ANSI")
		&& !$this->DoQuery("BEGIN"))
		{
			ifx_Close($this->connection);
			$this->connection=0;
			$this->connected_database_name="";
			$this->affected_rows=-1;
			return(0);
		}
		$this->connected_host=$this->host;
		$this->connected_database_name=$database_name;
		$this->connected_user=$user;
		$this->connected_password=$password;
		$this->opened_persistent=$persistent;
		return(1);
	}

	Function Close()
	{
		if($this->connection!=0)
		{
			if(!$this->auto_commit)
				$this->DoQuery("ROLLBACK");
			ifx_Close($this->connection);
			$this->connection=0;
			$this->connected_database_name="";
			$this->affected_rows=-1;
		}
	}

	Function DoQuery($query,$first=0,$limit=0,$free_non_select_result=1,$prepared_query=0)
	{
		ifx_nullformat(1);
		ifx_blobinfile_mode(0);
		$query=ltrim($query);
		$query_string=strtolower(strtok($query," \t\n\r"));
		if(($select=!strcmp($query_string,"select"))
		&& $limit>0)
		{
			$query=$query_string." FIRST ".strval($first+$limit)." ".strtok("");
			$cursor_type=($first ? IFX_SCROLL : 0);
		}
		else
			$cursor_type=0;
		if($prepared_query
		&& IsSet($this->query_parameters[$prepared_query])
		&& count($this->query_parameters[$prepared_query]))
			$result=($cursor_type ? @ifx_query($query,$this->connection,$cursor_type,$this->query_parameters[$prepared_query]) : @ifx_query($query,$this->connection,$this->query_parameters[$prepared_query]));
		else
			$result=($cursor_type ? @ifx_query($query,$this->connection,$cursor_type) : @ifx_query($query,$this->connection));
		if($result)
		{
			$result_value=intval($result);
			switch($query_string)
			{
				case "select":
					$this->current_row[$result_value]=-1;
					if($limit>0)
						$this->limits[$result_value]=array($first,$limit,0);
					$this->highest_fetched_row[$result_value]=-1;
					break;
				case "commit":
				case "rollback":
				case "begin":
				case "database":
					ifx_free_result($result);
					break;
				default:
					$this->affected_rows=ifx_affected_rows($result);
					if($free_non_select_result)
						ifx_free_result($result);
					break;
			}
		}
		else
			$this->SetIFXError("Do query","Could not query Informix database");
		return($result);
	}

	Function Query($query,$free_non_select_result=1,$prepared_query=0)
	{
		$this->Debug("Query: $query");
		$first=$this->first_selected_row;
		$limit=$this->selected_row_limit;
		$this->first_selected_row=$this->selected_row_limit=0;
		if(!strcmp($this->database_name,""))
			return($this->SetError("Query","it was not specified a valid database name to query"));
		if(!$this->Connect(0))
			return(0);
		if(($result=$this->DoQuery($query,$first,$limit,$free_non_select_result,$prepared_query))
		&& $free_non_select_result
		&& $this->auto_commit
		&& IsSet($this->options["Logging"])
		&& !strcmp($this->options["Logging"],"ANSI")
		&& strcmp(strtolower(strtok(ltrim($query)," \t\r\n")),"select")
		&& !$this->DoQuery("COMMIT"))
		{
			$this->FreeResult($result);
			$result=0;
		}
		return($result);
	}

	Function ExecutePreparedQuery($prepared_query,$query)
	{
		return($this->Query($query,1,$prepared_query));
	}

	Function SkipFirstRows($result)
	{
		$result_value=intval($result);
		$first=$this->limits[$result_value][0];
		if($this->limits[$result_value][2]<$first)
		{
			if(GetType($this->row_buffer[$result_value]=ifx_fetch_row($result,$first+1))!="array")
			{
				$this->limits[$result_value][2]=$first;
				return($this->SetError("Skip first rows","could not skip a query result row"));
			}
			$this->limits[$result_value][2]=$first;
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
			if(GetType($this->results[$result_value][$this->current_row[$result_value]+1]=ifx_fetch_row($result))!="array")
			{
				$this->rows[$result_value]=$this->current_row[$result_value]+1;
				Unset($this->results[$result_value][$this->current_row[$result_value]+1]);
				return($this->SetError("Fetch row","could not fetch the query result row"));
			}
		}
		return(1);
	}

	Function GetColumnNames($result)
	{
		$result_value=intval($result);
		if(!IsSet($this->highest_fetched_row[$result_value]))
			return($this->SetError("Get column names","it was specified an inexisting result set"));
		if(!IsSet($this->columns[$result_value]))
		{
			$this->columns[$result_value]=$this->column_names[$result_value]=array();
			$columns=ifx_num_fields($result);
			$types=ifx_fieldtypes($result);
			for(Reset($types),$column=0;$column<$columns;$column++,Next($types))
			{
				$field_name=Key($types);
				$this->column_names[$result_value][$column]=$field_name;
				$this->columns[$result_value][$field_name]=$column;
			}
		}
		return(1);
	}

	Function NumberOfColumns($result)
	{
		if(!IsSet($this->highest_fetched_row[$result]))
		{
			$this->SetError("Number of columns","it was specified an inexisting result set");
			return(-1);
		}
		return(ifx_num_fields($result));
	}

	Function GetColumn($result,$field)
	{
		$result_value=intval($result);
		if(!$this->GetColumnNames($result))
			return("");
		if(GetType($field)=="integer")
		{
			if(($column=$field)<0
			|| $column>=count($this->columns[$result_value]))
			{
				$this->SetError("Get column","attempted to fetch an query result column out of range");
				return("");
			}
			return($this->column_names[$result_value][$column]);
		}
		else
		{
			if(!IsSet($this->columns[$result_value][$field]))
			{
				$this->SetError("Get column","attempted to fetch an unknown query result column");
				return("");
			}
			return($field);
		}
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
		if(GetType($this->row_buffer[$result_value]=ifx_fetch_row($result))=="array")
			return(0);
		Unset($this->row_buffer[$result_value]);
		$this->rows[$result_value]=$this->current_row[$result_value]+1;
		return(1);
	}

	Function FetchResult($result,$row,$field)
	{
		$result_value=intval($result);
		if(!strcmp($column=$this->GetColumn($result,$field),"")
		|| !$this->FetchRow($result,$row))
			return("");
		if(!IsSet($this->results[$result_value][$row][$column]))
			return("");
		$this->highest_fetched_row[$result_value]=max($this->highest_fetched_row[$result_value],$row);
		return($this->results[$result_value][$row][$column]);
	}

	Function FetchResultArray($result,&$array,$row)
	{
		$result_value=intval($result);
		if(!$this->FetchRow($result,$row)
		|| (!IsSet($this->columns[$result_value])
		&& !$this->GetColumnNames($result)))
			return(0);
		for($array=array(),Reset($this->results[$result_value][$row]);$field=Key($this->results[$result_value][$row]);Next($this->results[$result_value][$row]))
			$array[$this->columns[$result_value][$field]]=$this->results[$result_value][$row][$field];
		$this->highest_fetched_row[$result_value]=max($this->highest_fetched_row[$result_value],$row);
		return($this->ConvertResultRow($result,$array));
	}

	Function RetrieveLOB($lob)
	{
		if(!IsSet($this->lobs[$lob]))
			return($this->SetError("Retrieve LOB","it was not specified a valid lob"));
		if(!IsSet($this->lobs[$lob]["Value"]))
		{
			$this->lobs[$lob]["Value"]=$this->FetchResult($this->lobs[$lob]["Result"],$this->lobs[$lob]["Row"],$this->lobs[$lob]["Field"]);
			if(!($this->lobs[$lob]["Data"]=ifx_get_blob($this->lobs[$lob]["Value"])))
			{
				Unset($this->lobs[$lob]["Value"]);
				return($this->SetIFXError("Retrieve LOB","Could not get a blob contents"));
			}
			$this->lobs[$lob]["Position"]=0;
		}
		return(1);
	}

	Function EndOfResultLOB($lob)
	{
		if(!$this->RetrieveLOB($lob))
			return(0);
		return($this->lobs[$lob]["Position"]>=strlen($this->lobs[$lob]["Data"]));
	}

	Function ReadResultLOB($lob,&$data,$length)
	{
		if(!$this->RetrieveLOB($lob))
			return(-1);
		$length=min($length,strlen($this->lobs[$lob]["Data"])-$this->lobs[$lob]["Position"]);
		$data=substr($this->lobs[$lob]["Data"],$this->lobs[$lob]["Position"],$length);
		$this->lobs[$lob]["Position"]+=$length;
		return($length);
	}

	Function DestroyResultLOB($lob)
	{
		if(IsSet($this->lobs[$lob]))
		{
			if(IsSet($this->lobs[$lob]["Value"]))
			{
				Unset($this->lobs[$lob]["Data"]);
				ifx_free_blob($this->lobs[$lob]["Value"]);
			}
			$this->lobs[$lob]="";
		}
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
				$value=(strcmp($value,"t") ? 0 : 1);
				return(1);
			case METABASE_TYPE_DECIMAL:
				return(1);
			case METABASE_TYPE_FLOAT:
				$value=doubleval($value);
				return(1);
			case METABASE_TYPE_DATE:
			case METABASE_TYPE_TIME:
			case METABASE_TYPE_TIMESTAMP:
				return(1);
			default:
				return($this->BaseConvertResult($value,$type));
		}
	}

	Function ResultIsNull($result,$row,$field)
	{
		$result_value=intval($result);
		if(!strcmp($column=$this->GetColumn($result,$field),"")
		|| !$this->FetchRow($result,$row))
			return(0);
		$this->highest_fetched_row[$result_value]=max($this->highest_fetched_row[$result_value],$row);
		return(!strcmp($this->results[$result_value][$row][$column],"NULL"));
	}

	Function NumberOfRows($result)
	{
		$result_value=intval($result);
		if(!IsSet($this->current_row[$result_value]))
			return($this->SetError("Number of rows","attemped to obtain the number of rows contained in an unknown query result"));
		if(!IsSet($this->rows[$result_value]))
		{
			if(!$this->GetColumnNames($result))
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
				for(;($limit==0 || $this->current_row[$result_value]+1<$limit) && GetType($this->results[$result_value][$this->current_row[$result_value]+1]=ifx_fetch_row($result))=="array";$this->current_row[$result_value]++);
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
		UnSet($this->column_names[$result_value]);
		UnSet($this->rows[$result_value]);
		UnSet($this->result_types[$result]);
		return(ifx_free_result($result));
	}

	Function GetLOBFieldValue($prepared_query,$parameter,$lob,&$value,$binary)
	{
		if(!$this->Connect(0))
			return(0);
		for($blob_data="";!MetabaseEndOfLOB($lob);)
		{
			if(MetabaseReadLOB($lob,$data,$this->lob_buffer_length)<0)
				return($this->SetError("Get LOB field value",MetabaseLOBError($lob)));
			$blob_data.=$data;
		}
		if(!($blob=ifx_create_blob($binary ? 0 : 1,0,$blob_data)))
			return($this->SetIFXError("Get LOB field value","Could not create a blob"));
		if(!IsSet($this->query_parameters[$prepared_query]))
			$this->query_parameters[$prepared_query]=$this->query_parameter_values[$prepared_query]=array();
		$query_parameter=count($this->query_parameters[$prepared_query]);
		$this->query_parameter_values[$prepared_query][$parameter]=$query_parameter;
		$this->query_parameters[$prepared_query][$query_parameter]=$blob;
		$value="?";
		return(1);
	}

	Function FreeLOBValue($prepared_query,$lob,&$value)
	{
		$query_parameter=$this->query_parameter_values[$prepared_query][$lob];
		ifx_free_blob($this->query_parameters[$prepared_query][$query_parameter]);
		Unset($this->query_parameters[$prepared_query][$query_parameter]);
		Unset($this->query_parameter_values[$prepared_query][$lob]);
		if(count($this->query_parameter_values[$prepared_query])==0)
		{
			Unset($this->query_parameters[$prepared_query]);
			Unset($this->query_parameter_values[$prepared_query]);
		}
		Unset($value);
	}

	Function GetCLOBFieldValue($prepared_query,$parameter,$clob,&$value)
	{
		return($this->GetLOBFieldValue($prepared_query,$parameter,$clob,$value,0));
	}

	Function FreeCLOBValue($prepared_query,$clob,&$value,$success)
	{
		$this->FreeLOBValue($prepared_query,$clob,$value);
	}

	Function GetBLOBFieldValue($prepared_query,$parameter,$blob,&$value)
	{
		return($this->GetLOBFieldValue($prepared_query,$parameter,$blob,$value,1));
	}

	Function FreeBLOBValue($prepared_query,$blob,&$value,$success)
	{
		$this->FreeLOBValue($prepared_query,$blob,$value);
	}

	Function GetBooleanFieldValue($value)
	{
		return(!strcmp($value,"NULL") ? "NULL" : ($value ? "'t'" : "'f'"));
	}

	Function GetFloatFieldValue($value)
	{
		return(!strcmp($value,"NULL") ? "NULL" : "$value");
	}

	Function GetTextFieldTypeDeclaration($name,&$field)
	{
		return((IsSet($field["length"]) ? "$name VARCHAR(".$field["length"].")" : "$name LVARCHAR").(IsSet($field["default"]) ? " DEFAULT '".$field["default"]."'" : "").(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetCLOBFieldTypeDeclaration($name,&$field)
	{
		return("$name TEXT".(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetBLOBFieldTypeDeclaration($name,&$field)
	{
		return("$name BYTE".(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetBooleanFieldTypeDeclaration($name,&$field)
	{
		return("$name BOOLEAN".(IsSet($field["default"]) ? " DEFAULT ".$this->GetBooleanFieldValue($field["default"]) : "").(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetIntegerFieldTypeDeclaration($name,&$field)
	{
		if(IsSet($field["unsigned"]))
			$this->warning="unsigned integer field \"$name\" is being declared as signed integer";
		return("$name ".((IsSet($this->options["Use8ByteIntegers"]) && $this->options["Use8ByteIntegers"]) ?  "INT8" : "INT").(IsSet($field["default"]) ? " DEFAULT ".$field["default"] : "").(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetFloatFieldTypeDeclaration($name,&$field)
	{
		return("$name FLOAT".(IsSet($field["default"]) ? " DEFAULT ".$this->GetFloatFieldValue($field["default"]) : "").(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetDecimalFieldTypeDeclaration($name,&$field)
	{
		return("$name DECIMAL(32,".$this->decimal_places.")".(IsSet($field["default"]) ? " DEFAULT ".$this->GetDecimalFieldValue($field["default"]) : "").(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetDateFieldTypeDeclaration($name,&$field)
	{
		return($name." DATE".(IsSet($field["default"]) ? " DEFAULT '".$field["default"]."'" : "").(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetTimestampFieldTypeDeclaration($name,&$field)
	{
		return($name." DATETIME YEAR TO SECOND".(IsSet($field["default"]) ? " DEFAULT '".$field["default"]."'" : "").(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetTimeFieldTypeDeclaration($name,&$field)
	{
		return($name." DATETIME HOUR TO SECOND".(IsSet($field["default"]) ? " DEFAULT '".$field["default"]."'" : "").(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function AutoCommitTransactions($auto_commit)
	{
		$this->Debug("AutoCommit: ".($auto_commit ? "On" : "Off"));
		if(((!$this->auto_commit)==(!$auto_commit)))
			return(1);
		if($this->connection)
		{
			if(!IsSet($this->options["Logging"]))
				return($this->SetError("Auto-commit transactions","transactions are not supported on databases without logging"));
			if(($auto_commit 
			|| strcmp($this->options["Logging"],"ANSI"))
			&& !$this->Query($auto_commit ? "COMMIT" : "BEGIN"))
				return(0);
		}
		$this->auto_commit=$auto_commit;
		return($this->RegisterTransactionShutdown($auto_commit));
	}

	Function CommitTransaction()
	{
 		$this->Debug("Commit Transaction");
		if($this->auto_commit)
			return($this->SetError("Commit transaction","transaction changes are being auto commited"));
		if(!IsSet($this->options["Logging"]))
			return($this->SetError("Commit transaction","transactions are not supported on databases without logging"));
		return($this->Query("COMMIT") && (!strcmp($this->options["Logging"],"ANSI") || $this->Query("BEGIN")));
	}

	Function RollbackTransaction()
	{
 		$this->Debug("Rollback Transaction");
		if($this->auto_commit)
			return($this->SetError("Rollback transaction","transactions can not be rolled back when changes are auto commited"));
		if(!IsSet($this->options["Logging"]))
			return($this->SetError("Rollback transaction","transactions are not supported on databases without logging"));
		return($this->Query("ROLLBACK") && (!strcmp($this->options["Logging"],"ANSI") || $this->Query("BEGIN")));
	}

	Function GetSequenceNextValue($name,&$value)
	{
		if(!($result=$this->DoQuery("INSERT INTO _sequence_$name (sequence) VALUES (0)",0,0,0)))
			return(0);
		if(IsSet($this->options["Use8ByteIntegers"])
		&& $this->options["Use8ByteIntegers"])
		{
			ifx_free_result($result);
			if(!($result=$this->DoQuery("SELECT dbinfo('serial8') FROM _sequence_$name")))
				return(0);
			$value=intval($this->FetchResult($result,0,0));
			$this->FreeResult($result);
			if($this->auto_commit
			&& IsSet($this->options["Logging"])
			&& !strcmp($this->options["Logging"],"ANSI")
			&& !$this->DoQuery("COMMIT"))
				return(0);
		}
		else
		{
			$sqlca=ifx_getsqlca($result);
			$value=$sqlca["sqlerrd1"];
			ifx_free_result($result);
		}
		if(!$this->Query("DELETE FROM _sequence_$name WHERE sequence<$value"))
			$this->warning="could delete previous sequence table values";
		return(1);
	}

	Function Setup()
	{
		$this->supported["Indexes"]=
		$this->supported["IndexSorting"]=
		$this->supported["SummaryFunctions"]=
		$this->supported["OrderByText"]=
		$this->supported["AffectedRows"]=
		$this->supported["Sequences"]=
		$this->supported["SelectRowRanges"]=
		$this->supported["LOBs"]=
		$this->supported["Replace"]=
			1;
		if(IsSet($this->options["Logging"]))
		{
			switch($this->options["Logging"])
			{
				case "Buffered":
				case "Unbuffered":
				case "ANSI":
					break;
				default:
					return($this->options["Logging"]." is not a support logging type");
			}
			$this->supported["Transactions"]=1;
		}
		return("");
	}
};

}
?>