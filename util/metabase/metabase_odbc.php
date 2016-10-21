<?php
if(!defined("METABASE_ODBC_INCLUDED"))
{
	define("METABASE_ODBC_INCLUDED",1);

/*
 * metabase_odbc.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/metabase/metabase_odbc.php,v 1.31 2004/07/27 06:26:03 mlemos Exp $
 *
 */

define("METABASE_ODBC_BIT_TYPE",-7);
define("METABASE_ODBC_BIGINT_TYPE",-5);
define("METABASE_ODBC_LONGVARBINARY_TYPE",-4);
define("METABASE_ODBC_VARBINARY_TYPE",-3);
define("METABASE_ODBC_LONGVARCHAR_TYPE",-1);
define("METABASE_ODBC_NUMERIC_TYPE",2);
define("METABASE_ODBC_DECIMAL_TYPE",3);
define("METABASE_ODBC_INTEGER_TYPE",4);
define("METABASE_ODBC_FLOAT_TYPE",6);
define("METABASE_ODBC_REAL_TYPE",7);
define("METABASE_ODBC_DOUBLE_TYPE",8);
define("METABASE_ODBC_DATE_TYPE",9);
define("METABASE_ODBC_TIME_TYPE",10);
define("METABASE_ODBC_TIMESTAMP_TYPE",11);
define("METABASE_ODBC_VARCHAR_TYPE",12);
define("METABASE_ODBC_TYPE_DATE_TYPE",91);
define("METABASE_ODBC_TYPE_TIME_TYPE",92);
define("METABASE_ODBC_TYPE_TIMESTAMP_TYPE",93);

class metabase_odbc_class extends metabase_database_class
{
	var $dba_access=0;
	var $connection=0;
	var $types_result=0;
	var $connected_user;
	var $connected_password;
	var $selected_database="";
	var $opened_persistent="";

	var $current_row=array();
	var $limits=array();
	var $highest_fetched_row=array();
	var $supported_types=array();
	var $results=array();
	var $rows=array();
	var $row_buffer=array();
	var $columns=array();
	var $query_parameters=array();
	var $query_parameter_values=array();
	var $decimal_factor=1.0;
	var $decimal_scale=-1;
	var $size_field="COLUMN_SIZE";
	var $support_defaults=1;
	var $support_decimal_scale=1;
	var $get_type_info=1;
	var $escape_quotes="'";
	var $blob_declaration="";
	var $clob_declaration="";
	var $php_version=0;
	var $manager_included_constant="METABASE_MANAGER_ODBC_INCLUDED";
	var $manager_include="manager_odbc.php";
	var $manager_class_name="metabase_manager_odbc_class";

	Function SetODBCError($scope,$error,&$php_error)
	{
		return($this->SetError($scope,$error.((function_exists("odbc_error") && function_exists("odbc_errormsg")) ? ": ".odbc_error()." ".odbc_errormsg() : (IsSet($php_error) ? ": ".$php_error : ""))));
	}

	Function FetchInto($result,$row,&$array)
	{
		if($this->php_version>=4002000)
			return(odbc_fetch_into($result,$array,$row));
		elseif($this->php_version>=4000005)
			return(odbc_fetch_into($result,$row,$array));
		else
		{
			eval("\$success=odbc_fetch_into(\$result,\$row,&\$array);");
			return($success);
		}
	}

	Function Connect()
	{
		if($this->dba_access)
		{
			$dsn=(IsSet($this->options["DBADSN"]) ? $this->options["DBADSN"] : "");
			$user=(IsSet($this->options["DBAUser"]) ? $this->options["DBAUser"] : "");
			$password=(IsSet($this->options["DBAPassword"]) ? $this->options["DBAPassword"] : "");
			$persistent=0;
		}
		else
		{
			$dsn=$this->database_name;
			$user=$this->user;
			$password=$this->password;
			$persistent=$this->persistent;
		}
		if($this->connection!=0)
		{
			if(!strcmp($this->selected_database,$dsn)
			&& !strcmp($this->connected_user,$user)
			&& !strcmp($this->connected_password,$password)
			&& $this->opened_persistent==$persistent)
				return(1);
			$this->Close();
		}
		$function=($persistent ? "odbc_pconnect" : "odbc_connect");
		if(!function_exists($function))
			return($this->SetError("Connect","ODBC support is not available in this PHP configuration"));
		if(($this->connection=@$function($dsn,$user,$password))<=0)
			return($this->SetODBCError("Connect","Could not connect to ODBC server",$php_errormsg));
		$this->supported_types=array();
		$this->type_field_names=array();
		$this->type_index=array();
		$this->type_property_names=array();
		if($this->get_type_info
		&& function_exists("odbc_gettypeinfo"))
		{
			if(!($this->types_result=odbc_gettypeinfo($this->connection)))
			{
				$this->SetODBCError("Connect","Could not obtain the information of supported ODBC data types",$php_errormsg);
				$this->Close();
				return(0);
			}
			$properties=odbc_num_fields($this->types_result);
			for($property=1;$property<=$properties;$property++)
			{
				$property_name=odbc_field_name($this->types_result,$property);
				$this->type_property_names[$property_name]=$property-1;
				switch($property)
				{
					case 3:
						$this->size_field=$property_name;
						break;
					case 12:
						$auto_increment_field=$property_name;
						break;
				}
			}
			for($type_index=$index=1;$this->FetchInto($this->types_result,$index,$this->supported_types[$type_index]);$type_index++,$index=$type_index)
			{
				$type=intval($this->supported_types[$type_index][$this->type_property_names["DATA_TYPE"]]);
				if(strcmp($this->supported_types[$type_index][$this->type_property_names[$auto_increment_field]],"1"))
					$this->type_index[$type]=$type_index;
			}
			Unset($this->supported_types[$type_index]);
			if(IsSet($this->options["FreeTypesResult"]))
				odbc_free_result($this->types_result);
			$this->types_result=0;
		}
		if(!$this->auto_commit
		&& !@odbc_autocommit($this->connection,0))
		{
			$this->SetODBCError("Connect","Could not disable transaction auto-commit mode",$php_errormsg);
			$this->Close();
			return(0);
		}
		$this->selected_database=$dsn;
		$this->connected_user=$user;
		$this->connected_password=$password;
		$this->opened_persistent=$persistent;
		return(1);
	}

	Function Close()
	{
		if($this->connection!=0)
		{
			if($this->types_result)
			{
				@odbc_free_result($this->types_result);
				$this->types_result=0;
			}
			@odbc_close($this->connection);
			$this->connection=0;
			$this->affected_rows=-1;
			$this->decimal_scale=-1;
		}
	}

	Function GetColumnNames($result,&$column_names)
	{
		$result_value=intval($result);
		if(!IsSet($this->highest_fetched_row[$result_value]))
			return($this->SetError("Get column names","it was specified an inexisting result set"));
		if(!IsSet($this->columns[$result_value]))
		{
			$this->columns[$result_value]=array();
			$columns=odbc_num_fields($result);
			for($column=0;$column<$columns;$column++)
				$this->columns[$result_value][strtolower(odbc_field_name($result,$column+1))]=$column;
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
		return(odbc_num_fields($result));
	}

	Function DoQuery($query,$first=0,$limit=0,$prepared_query=0)
	{
		if($prepared_query
		&& IsSet($this->query_parameters[$prepared_query])
		&& count($this->query_parameters[$prepared_query]))
		{
			if(($result=@odbc_prepare($this->connection,$query)))
			{
				if(!@odbc_execute($result,$this->query_parameters[$prepared_query]))
				{
					$this->SetODBCError("Do query","Could not execute a ODBC database prepared query \"$query\"",$php_errormsg);
					odbc_free_result($result);
					return(0);
				}
			}
			else
			{
				$this->SetODBCError("Do query","Could not execute a ODBC database prepared query \"$query\"",$php_errormsg);
				return(0);
			}
		}
		else
			$result=@odbc_exec($this->connection,$query);
		if($result)
		{
			$this->current_row[$result]=-1;
			if(!strcmp(strtolower(strtok(ltrim($query)," \t\n\r")),"select"))
			{
				$result_value=intval($result);
				$this->current_row[$result_value]=-1;
				if($limit>0)
					$this->limits[$result_value]=array($first,$limit,0);
				$this->highest_fetched_row[$result_value]=-1;
			}
			else
			{
				$this->affected_rows=odbc_num_rows($result);
				odbc_free_result($result);
			}
		}
		else
			$this->SetODBCError("Do query","Could not execute a ODBC database query \"$query\"",$php_errormsg);
		return($result);
	}

	Function Query($query)
	{
		$first=$this->first_selected_row;
		$limit=$this->selected_row_limit;
		$this->first_selected_row=$this->selected_row_limit=0;
		if(!$this->Connect())
			return(0);
		return($this->DoQuery($query,$first,$limit));
	}

	Function ExecutePreparedQuery($prepared_query,$query)
	{
		$first=$this->first_selected_row;
		$limit=$this->selected_row_limit;
		$this->first_selected_row=$this->selected_row_limit=0;
		if(!$this->Connect())
			return(0);
		return($this->DoQuery($query,$first,$limit,$prepared_query));
	}

	Function SkipFirstRows($result)
	{
		$result_value=intval($result);
		$first=$this->limits[$result_value][0];
		for(;$this->limits[$result_value][2]<$first;$this->limits[$result_value][2]++)
		{
			if(!odbc_fetch_row($result))
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
			$first=(IsSet($this->limits[$result_value]) ? $this->limits[$result_value][0] : 0);
			$fetch_row=$this->current_row[$result_value]+2+$first;
			if(!$this->FetchInto($result,$fetch_row,$this->results[$result_value][$this->current_row[$result_value]+1]))
			{
				Unset($this->results[$result_value][$this->current_row[$result_value]+1]);
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
				$this->SetError("","attempted to fetch an unknown query result column");
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
			$this->SetError("","attempted to check the end of an unknown result");
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
		$first=(IsSet($this->limits[$result_value]) ? $this->limits[$result_value][0] : 0);
		$row=$this->current_row[$result_value]+2+$first;
		if($this->FetchInto($result,$row,$this->row_buffer[$result_value]))
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

	Function FetchCLOBResult($result,$row,$field)
	{
		return($this->FetchLOBResult($result,$row,$field));
	}

	Function FetchBLOBResult($result,$row,$field)
	{
		return($this->FetchLOBResult($result,$row,$field));
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

	Function ConvertResult(&$value,$type)
	{
		switch($type)
		{
			case METABASE_TYPE_BOOLEAN:
				$value=(strcmp($value,"1") ? 0 : 1);
				return(1);
			case METABASE_TYPE_DECIMAL:
				if($this->decimal_scale<0)
					$this->GetTypeDeclaration(METABASE_ODBC_DECIMAL_TYPE,$this->support_decimal_scale ? $this->decimal_places : 0);
				if($this->decimal_scale<$this->decimal_places)
					$value=sprintf("%.".$this->decimal_places."f",doubleval($value)/$this->decimal_factor);
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

	Function NumberOfRows($result)
	{
		$result_value=intval($result);
		if(!IsSet($this->current_row[$result_value]))
			return($this->SetError("Number of rows","attemped to obtain the number of rows contained in an unknown query result"));
		if(!IsSet($this->rows[$result_value]))
		{
			if(($rows=odbc_num_rows($result))>=0)
			{
				if(IsSet($this->limits[$result_value]))
				{
					if($rows>$this->limits[$result_value][0])
						$rows-=$this->limits[$result_value][0];
					$rows=min($this->limits[$result_value][1],$rows);
				}
				return($rows);
			}
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
				$first=(IsSet($this->limits[$result_value]) ? $this->limits[$result_value][0] : 0);
				for($row=$this->current_row[$result_value]+2+$first,$fetched=1;($limit==0 || $this->current_row[$result_value]+1<$limit) && ($fetched=$this->FetchInto($result,$row,$this->results[$result_value][$this->current_row[$result_value]+1]));$this->current_row[$result_value]++,$row=$this->current_row[$result_value]+2+$first);
			}
			if(!$fetched)
				Unset($this->results[$result_value][$this->current_row[$result_value]+1]);
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
		UnSet($this->result_types[$result_value]);
		return(odbc_free_result($result));
	}

	Function GetTypeDeclaration($type,$size=0)
	{
		$current_dba_access=$this->dba_access;
		$this->dba_access=0;
		$declaration="";
		$scale=0;
		if($this->Connect())
		{
			if(IsSet($this->type_index[$type]))
			{
				$type_index=$this->type_index[$type];
				$declaration=$this->supported_types[$type_index][$this->type_property_names["TYPE_NAME"]];
				if($size)
				{
					switch($type)
					{
						case METABASE_ODBC_VARCHAR_TYPE:
							$declaration.="($size)";
							break;
						case METABASE_ODBC_DECIMAL_TYPE:
						case METABASE_ODBC_NUMERIC_TYPE:
							if(IsSet($this->type_property_names["MAXIMUM_SCALE"])
							&& strcmp($this->supported_types[$type_index][$this->type_property_names["MAXIMUM_SCALE"]],""))
							{
								$scale=intval($this->supported_types[$type_index][$this->type_property_names["MAXIMUM_SCALE"]]);
								if($scale>0)
								{
									if($scale>$size)
										$scale=$size;
									$precision=intval($this->supported_types[$type_index][$this->type_property_names[$this->size_field]]);
									$declaration.="($precision,$scale)";
								}
							}
							break;
					}
				}
			}
			else
			{
				switch($type)
				{
					case METABASE_ODBC_DECIMAL_TYPE:
						if(!strcmp($declaration=$this->GetTypeDeclaration(METABASE_ODBC_NUMERIC_TYPE,$size),"")
						&& !strcmp($declaration=$this->GetTypeDeclaration(METABASE_ODBC_BIGINT_TYPE),""))
							$declaration=$this->GetTypeDeclaration(METABASE_ODBC_INTEGER_TYPE);
						break;
					case METABASE_ODBC_LONGVARCHAR_TYPE:						
						$declaration=$this->GetTypeDeclaration(METABASE_ODBC_VARCHAR_TYPE,$size);
						break;
					case METABASE_ODBC_DOUBLE_TYPE:
						if(!strcmp($declaration=$this->GetTypeDeclaration(METABASE_ODBC_FLOAT_TYPE),""))
							$declaration=$this->GetTypeDeclaration(METABASE_ODBC_REAL_TYPE);
						break;
					case METABASE_ODBC_DATE_TYPE:
						if(!strcmp($declaration=$this->GetTypeDeclaration(METABASE_ODBC_TYPE_DATE_TYPE),""))
							$declaration=$this->GetTypeDeclaration(METABASE_ODBC_TIMESTAMP_TYPE,$size);
						break;
					case METABASE_ODBC_TIME_TYPE:
						if(!strcmp($declaration=$this->GetTypeDeclaration(METABASE_ODBC_TYPE_TIME_TYPE),""))
							$declaration=$this->GetTypeDeclaration(METABASE_ODBC_TIMESTAMP_TYPE,$size);
						break;
					case METABASE_ODBC_TIMESTAMP_TYPE:
						$declaration=$this->GetTypeDeclaration(METABASE_ODBC_TYPE_TIMESTAMP_TYPE,$size);
						break;
				}
			}
		}
		if(!strcmp($declaration,""))
		{
			switch($type)
			{
				case METABASE_ODBC_VARCHAR_TYPE:
				case METABASE_ODBC_LONGVARCHAR_TYPE:
					$declaration="VARCHAR($size)";
					break;
				case METABASE_ODBC_BIT_TYPE:
					$declaration="CHAR(1)";
					break;
				case METABASE_ODBC_INTEGER_TYPE:
				case METABASE_ODBC_DECIMAL_TYPE:
					$declaration="INT";
					break;
				case METABASE_ODBC_DATE_TYPE:
					$declaration="VARCHAR(10)";
					break;
				case METABASE_ODBC_TIME_TYPE:
					$declaration="VARCHAR(8)";
					break;
				case METABASE_ODBC_TIMESTAMP_TYPE:
					$declaration="VARCHAR(19)";
					break;
				case METABASE_ODBC_DOUBLE_TYPE:
					$declaration="FLOAT";
					break;
			}
		}
		if($type==METABASE_ODBC_DECIMAL_TYPE)
		{
			$this->decimal_scale=$scale;
			$this->decimal_factor=($scale<$this->decimal_places ? pow(10.0,$this->decimal_places-$scale) : 1.0);
		}
		$this->dba_access=$current_dba_access;
		return($declaration);
	}

	Function GetTextFieldTypeDeclaration($name,&$field)
	{
		if(IsSet($field["length"]))
			$declaration=$this->GetTypeDeclaration(METABASE_ODBC_VARCHAR_TYPE,$field["length"]);
		else
			$declaration=$this->GetTypeDeclaration(METABASE_ODBC_LONGVARCHAR_TYPE,(IsSet($this->options["DefaultTextFieldLength"]) ? $this->options["DefaultTextFieldLength"] : 255));
		if(IsSet($field["default"]))
		{
			if($this->support_defaults)
				$declaration.=" DEFAULT ".$this->GetTextFieldValue($field["default"]);
			else
				$this->warning="this ODBC data source does not support field default values";
		}
		return("$name $declaration".(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetCLOBFieldTypeDeclaration($name,&$field)
	{
		return("$name ".$this->clob_declaration.(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetBLOBFieldTypeDeclaration($name,&$field)
	{
		return("$name ".$this->blob_declaration.(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetBooleanFieldTypeDeclaration($name,&$field)
	{
		$declaration=$this->GetTypeDeclaration(METABASE_ODBC_BIT_TYPE);
		if(IsSet($field["default"]))
		{
			if($this->support_defaults)
				$declaration.=" DEFAULT ".$this->GetBooleanFieldValue($field["default"]);
			else
				$this->warning="this ODBC data source does not support field default values";
		}
		return("$name $declaration".(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetIntegerFieldTypeDeclaration($name,&$field)
	{
		$declaration=$this->GetTypeDeclaration(METABASE_ODBC_INTEGER_TYPE);
		if(IsSet($field["unsigned"]))
			$this->warning="unsigned integer field \"$name\" is being declared as signed integer";
		if(IsSet($field["default"]))
		{
			if($this->support_defaults)
				$declaration.=" DEFAULT ".$field["default"];
			else
				$this->warning="this ODBC data source does not support field default values";
		}
		return("$name $declaration".(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetDateFieldTypeDeclaration($name,&$field)
	{
		$declaration=$this->GetTypeDeclaration(METABASE_ODBC_DATE_TYPE);
		if(IsSet($field["default"]))
		{
			if($this->support_defaults)
				$declaration.=" DEFAULT ".$this->GetDateFieldValue($field["default"]);
			else
				$this->warning="this ODBC data source does not support field default values";
		}
		return("$name $declaration".(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetTimestampFieldTypeDeclaration($name,&$field)
	{
		$declaration=$this->GetTypeDeclaration(METABASE_ODBC_TIMESTAMP_TYPE);
		if(IsSet($field["default"]))
		{
			if($this->support_defaults)
				$declaration.=" DEFAULT ".$this->GetTimestampFieldValue($field["default"]);
			else
				$this->warning="this ODBC data source does not support field default values";
		}
		return("$name $declaration".(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetTimeFieldTypeDeclaration($name,&$field)
	{
		$declaration=$this->GetTypeDeclaration(METABASE_ODBC_TIME_TYPE);
		if(IsSet($field["default"]))
		{
			if($this->support_defaults)
				$declaration.=" DEFAULT ".$this->GetTimeFieldValue($field["default"]);
			else
				$this->warning="this ODBC data source does not support field default values";
		}
		return("$name $declaration".(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetFloatFieldTypeDeclaration($name,&$field)
	{
		$declaration=$this->GetTypeDeclaration(METABASE_ODBC_DOUBLE_TYPE);
		if(IsSet($field["default"]))
		{
			if($this->support_defaults)
				$declaration.=" DEFAULT ".$this->GetFloatFieldValue($field["default"]);
			else
				$this->warning="this ODBC data source does not support field default values";
		}
		return("$name $declaration".(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetDecimalFieldTypeDeclaration($name,&$field)
	{
		$declaration=$this->GetTypeDeclaration(METABASE_ODBC_DECIMAL_TYPE,$this->support_decimal_scale ? $this->decimal_places : 0);
		if(IsSet($field["default"]))
		{
			if($this->support_defaults)
				$declaration.=" DEFAULT ".$this->GetDecimalFieldValue($field["default"]);
			else
				$this->warning="this ODBC data source does not support field default values";
		}
		return("$name $declaration".(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetLOBFieldValue($prepared_query,$parameter,$lob,&$value)
	{
		if(!IsSet($this->query_parameters[$prepared_query]))
			$this->query_parameters[$prepared_query]=$this->query_parameter_values[$prepared_query]=array();
		$query_parameter=count($this->query_parameters[$prepared_query]);
		$this->query_parameter_values[$prepared_query][$parameter]=$query_parameter;
		for($this->query_parameters[$prepared_query][$query_parameter]="";!MetabaseEndOfLOB($lob);)
		{
			if(MetabaseReadLOB($lob,$data,$this->lob_buffer_length)<0)
			{
				$this->FreeLOBValue($prepared_query,$lob,$value,0);
				return($this->SetError("Get LOB field value",MetabaseLOBError($clob)));
			}
			$this->query_parameters[$prepared_query][$query_parameter].=$data;
		}
		$value="?";
		return(1);			
	}

	Function FreeLOBValue($prepared_query,$lob,&$value,$success)
	{
		Unset($this->query_parameters[$prepared_query][$this->query_parameter_values[$prepared_query][$lob]]);
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
		return($this->GetLOBFieldValue($prepared_query,$parameter,$clob,$value));
	}

	Function FreeCLOBValue($prepared_query,$clob,&$value,$success)
	{
		return($this->FreeLOBValue($prepared_query,$clob,$value,$success));
	}

	Function GetBLOBFieldValue($prepared_query,$parameter,$blob,&$value)
	{
		return($this->GetLOBFieldValue($prepared_query,$parameter,$blob,$value));
	}

	Function FreeBLOBValue($prepared_query,$blob,&$value,$success)
	{
		return($this->FreeLOBValue($prepared_query,$blob,$value,$success));
	}

	Function GetTextFieldValue($value)
	{
		$this->EscapeText($value);
		if(IsSet($this->options["EscapeBackslashes"])
		&& $this->options["EscapeBackslashes"])
			return("'".str_replace("\\","\\\\",$value)."'");
		else
			return("'".$value."'");
	}

	Function GetDecimalFieldValue($value)
	{
		if($this->decimal_scale<0)
			$this->GetTypeDeclaration(METABASE_ODBC_DECIMAL_TYPE,$this->decimal_places);
		return(!strcmp($value,"NULL") ? "NULL" : ($this->decimal_scale<$this->decimal_places ? ($this->decimal_scale>0 ? sprintf("%.".$this->decimal_scale."f",doubleval($value)*$this->decimal_factor) : strval(round(doubleval($value)*$this->decimal_factor))) : strval($value)));
	}

	Function GetBooleanFieldValue($value)
	{
		return(!strcmp($value,"NULL") ? "NULL" : ($value ? "1" : "0"));
	}

	Function GetFloatFieldValue($value)
	{
		return(!strcmp($value,"NULL") ? "NULL" : "$value");
	}

	Function AutoCommitTransactions($auto_commit)
	{
		if(((!$this->auto_commit)==(!$auto_commit)))
			return(1);
		if(!IsSet($this->supported["Transactions"]))
			return($this->SetError("Auto-commit transactions","transactions are not supported"));
		if($this->connection
		&& !@odbc_autocommit($this->connection,$auto_commit))
			return($this->SetODBCError("Auto-commit transactions","Could not set transaction auto-commit mode to $auto_commit",$php_errormsg));
		$this->auto_commit=$auto_commit;
		return(1);
	}

	Function CommitTransaction()
	{
		if($this->auto_commit)
			return($this->SetError("Commit transaction","transaction changes are being auto commited"));
		if(!odbc_commit($this->connection))
			return($this->SetODBCError("Commit transaction","Could not commit the current transaction",$php_errormsg));
		return(1);
	}

	Function RollbackTransaction()
	{
		if($this->auto_commit)
			return($this->SetError("Rollback transaction","transactions can not be rolled back when changes are auto commited"));
		if(!@odbc_rollback($this->connection))
			return($this->SetODBCError("Rollback transaction","Could not rollback the current transaction",$php_errormsg));
		return(1);
	}

	Function SetupODBCLOBs()
	{
		$current_dba_access=$this->dba_access;
		$current_auto_commit=$this->auto_commit;
		$this->dba_access=1;
		$success=$this->Connect();
		$this->dba_access=$current_dba_access;
		$this->auto_commit=$current_auto_commit;
		if(!$success)
			return($this->Error());
		if(IsSet($this->type_index[$binary=METABASE_ODBC_LONGVARBINARY_TYPE])
		|| IsSet($this->type_index[$binary=METABASE_ODBC_VARBINARY_TYPE]))
		{
			$type=$this->type_property_names["TYPE_NAME"];
			$this->blob_declaration=$this->supported_types[$this->type_index[$binary]][$type];
			if(IsSet($this->type_index[$text=METABASE_ODBC_LONGVARCHAR_TYPE])
			|| IsSet($this->type_index[$text=METABASE_ODBC_VARCHAR_TYPE]))
				$this->clob_declaration=$this->supported_types[$this->type_index[$text]][$type];
			else
				$this->clob_declaration=$this->blob_declaration;
			$this->supported["LOBs"]=1;
		}
		else
			$this->blob_declaration=$this->clob_declaration="";
		return(1);		
	}

	Function SetupODBC()
	{
		return($this->SetupODBCLOBs());
	}

	Function Setup()
	{
		$version=explode(".",function_exists("phpversion") ? phpversion() : "3.0.7");
		$this->php_version=$version[0]*1000000+$version[1]*1000+$version[2];
		$this->supported["AffectedRows"]=
		$this->supported["SelectRowRanges"]=
			1;
		if(IsSet($this->options["UseIndexes"])
		&& $this->options["UseIndexes"])
			$this->supported["Indexes"]=1;
		if(IsSet($this->options["UseTransactions"])
		&& $this->options["UseTransactions"])
			$this->supported["Transactions"]=$this->supported["Replace"]=1;
		$this->support_defaults=(!IsSet($this->options["UseDefaultValues"]) || $this->options["UseDefaultValues"]);
		$this->support_decimal_scale=(!IsSet($this->options["UseDecimalScale"]) || $this->options["UseDecimalScale"]);
		if(!$this->SetupODBC())
		{
			$this->Close();
			return($this->Error());
		}
		return("");
	}

};

}
?>