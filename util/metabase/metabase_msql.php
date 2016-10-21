<?php
if(!defined("METABASE_MSQL_INCLUDED"))
{
	define("METABASE_MSQL_INCLUDED",1);

/*
 * metabase_msql.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/metabase/metabase_msql.php,v 1.40 2004/07/27 06:26:03 mlemos Exp $
 *
 */
 
class metabase_msql_class extends metabase_database_class
{
	var $connection=0;
	var $connected_host;
	var $selected_database="";
	var $decimal_factor=1.0;
	var $highest_fetched_row=array();
	var $columns=array();
	var $limits=array();
	var $escape_quotes="\\";
	var $manager_class_name="metabase_manager_msql_class";
	var $manager_include="manager_msql.php";
	var $manager_included_constant="METABASE_MANAGER_MSQL_INCLUDED";

	Function Connect()
	{
		if($this->connection!=0)
		{
			if(!strcmp($this->connected_host,$this->host)
			&& $this->opened_persistent==$this->persistent)
				return(1);
			msql_Close($this->connection);
			$this->connection=0;
			$this->affected_rows=-1;
		}
		$function=($this->persistent ? "msql_pconnect" : "msql_connect");
		if(!function_exists($function))
			return($this->SetError("Connect","mSQL support is not available in this PHP configuration"));
		if(!strcmp($this->host,""))
			$this->connection=$function();
		else
			$this->connection=$function($this->host);
		if($this->connection==0)
			return($this->SetError("Connect",msql_error()));
		$this->connected_host=$this->host;
		$this->opened_persistent=$this->persistent;
		return(1);
	}

	Function Close()
	{
		if($this->connection!=0)
		{
			msql_Close($this->connection);
			$this->connection=0;
			$this->affected_rows=-1;
		}
	}

	Function SelectDatabase()
	{
		if(!strcmp($this->database_name,""))
			return($this->SetError("Select database","It was not specified a valid database name to select"));
		$last_connection=$this->connection;
		if(!$this->Connect())
			return(0);
		if($last_connection==$this->connection
		&& strcmp($this->selected_database,"")
		&& !strcmp($this->selected_database,$this->database_name))
			return(1);
		if(!msql_select_db($this->database_name,$this->connection))
			return($this->SetError("Select database",msql_error()));
		$this->selected_database=$this->database_name;
		return(1);
	}

	Function Query($query)
	{
		$this->Debug("Query: $query");
		$first=$this->first_selected_row;
		$limit=$this->selected_row_limit;
		$this->first_selected_row=$this->selected_row_limit=0;
		if(!$this->SelectDatabase())
			return(0);
		if(($result=@msql_query($query,$this->connection)))
		{
			if(!strcmp(strtolower(strtok(ltrim($query)," \t\n\r")),"select"))
			{
				if($limit>0)
					$this->limits[$result]=array($first,$limit);
				$this->highest_fetched_row[$result]=-1;
			}
			$this->affected_rows=msql_affected_rows($result);
		}
		else
			return($this->SetError("Query",msql_error()));
		return($result);
	}

	Function EndOfResult($result)
	{
		if(!IsSet($this->highest_fetched_row[$result]))
		{
			$this->SetError("End of result","attempted to check the end of an unknown result");
			return(-1);
		}
		return($this->highest_fetched_row[$result]>=$this->NumberOfRows($result)-1);
	}

	Function FetchResult($result,$row,$field)
	{
		if(IsSet($this->limits[$result]))
		{
			if($row>$this->limits[$result][1])
			{
				$this->warning="attempted to retrieve a row beyhond the result limit";
				return("");
			}
			$actual_row=$row+$this->limits[$result][0];
		}
		else
			$actual_row=$row;
		$this->highest_fetched_row[$result]=max($this->highest_fetched_row[$result],$row);
		return(msql_result($result,$actual_row,$field));
	}

	Function FetchResultArray($result,&$array,$row)
	{
		if(IsSet($this->limits[$result]))
		{
			if($row>$this->limits[$result][1])
				return($this->SetError("Fetch result array","attempted to retrieve a row beyhond the result limit"));
			$actual_row=$row+$this->limits[$result][0];
		}
		else
			$actual_row=$row;
		if(!msql_data_seek($result,$row)
		|| !($array=msql_fetch_row($result)))
			return($this->SetError("Fetch result array",msql_error()));
		$this->highest_fetched_row[$result]=max($this->highest_fetched_row[$result],$row);
		return($this->ConvertResultRow($result,$array));
	}

	Function ResultIsNull($result,$row,$field)
	{
		return(!strcmp($this->FetchResult($result,$row,$field),""));
	}

	Function NumberOfRows($result)
	{
		$rows=msql_num_rows($result);
		if(IsSet($this->limits[$result]))
		{
			if(($rows-=$this->limits[$result][0])<0)
				$rows=0;
			else
			{
				if($rows>$this->limits[$result][1])
					$rows=$this->limits[$result][1];
			}
		}
		return($rows);
	}

	Function FreeResult($result)
	{
		UnSet($this->highest_fetched_row[$result]);
		UnSet($this->columns[$result]);
		UnSet($this->limits[$result]);
		UnSet($this->result_types[$result]);
		return(msql_free_result($result));
	}

	Function GetIntegerFieldTypeDeclaration($name,&$field)
	{
		if(IsSet($field["default"]))
			$this->warning="mSQL does not support field default values";
		return("$name ".(IsSet($field["unsigned"]) ? "UINT" : "INT").(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetTextFieldTypeDeclaration($name,&$field)
	{
		if(IsSet($field["default"]))
			$this->warning="mSQL does not support field default values";
		return((IsSet($field["length"]) ? "$name CHAR (".$field["length"].")" : "$name TEXT (".(IsSet($this->options["DefaultTextFieldLength"]) ? $this->options["DefaultTextFieldLength"] : 255).")").(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetBooleanFieldTypeDeclaration($name,&$field)
	{
		if(IsSet($field["default"]))
			$this->warning="mSQL does not support field default values";
		return("$name CHAR (1)".(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetDateFieldTypeDeclaration($name,&$field)
	{
		if(IsSet($field["default"]))
			$this->warning="mSQL does not support field default values";
		return("$name DATE".(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetTimestampFieldTypeDeclaration($name,&$field)
	{
		if(IsSet($field["default"]))
			$this->warning="mSQL does not support field default values";
		return("$name UINT".(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetTimeFieldTypeDeclaration($name,&$field)
	{
		if(IsSet($field["default"]))
			$this->warning="mSQL does not support field default values";
		return($name." TIME".(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetDateFieldValue($value)
	{
		$date_value="";
		if(!strcmp($value,"NULL"))
			return("NULL");
		if(!ereg("^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$",$value,$date))
		{
			$this->warning="could not scan DATE value \"$value\"";
			return("");
		}
		$months=array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
		return("'".$date[3]."-".$months[intval($date[2])-1]."-".$date[1]."'");
	}

	Function GetTimestampFieldValue($value)
	{
		$date_value="";
		if(!strcmp($value,"NULL"))
			return("NULL");
		if(!ereg("^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$",$value,$date))
		{
			$this->warning="could not scan TIMESTAMP value \"$value\"";
			return("");
		}
		return(strval(mktime($date[4],$date[5],$date[6],$date[2],$date[3],$date[1])));
	}

	Function GetFloatFieldTypeDeclaration($name,&$field)
	{
		if(IsSet($field["default"]))
			$this->warning="mSQL does not support field default values";
		return("$name REAL ".(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetFloatFieldValue($value)
	{
		return(!strcmp($value,"NULL") ? "NULL" : "$value");
	}

	Function GetDecimalFieldTypeDeclaration($name,&$field)
	{
		if(IsSet($field["default"]))
			$this->warning="mSQL does not support field default values";
		return("$name INT".(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	Function GetDecimalFieldValue($value)
	{
		return(!strcmp($value,"NULL") ? "NULL" : strval(round($value*$this->decimal_factor)));
	}

	Function GetColumnNames($result,&$column_names)
	{
		if(!IsSet($this->highest_fetched_row[$result]))
			return($this->SetError("Get column names","it was specified an inexisting result set"));
		if(!IsSet($this->columns[$result]))
		{
			$this->columns[$result]=array();
			$columns=msql_num_fields($result);
			for($column=0;$column<$columns;$column++)
				$this->columns[$result][strtolower(msql_fieldname($result,$column))]=$column;
		}
		$column_names=$this->columns[$result];
		return(1);
	}

	Function NumberOfColumns($result)
	{
		if(!IsSet($this->highest_fetched_row[$result]))
		{
			$this->SetError("Number of columns","it was specified an inexisting result set");
			return(-1);
		}
		return(msql_num_fields($result));
	}

	Function ConvertResult(&$value,$type)
	{
		switch($type)
		{
			case METABASE_TYPE_BOOLEAN:
				$value=(strcmp($value,"Y") ? 0 : 1);
				return(1);
			case METABASE_TYPE_DECIMAL:
				$value=sprintf("%.".$this->decimal_places."f",doubleval($value)/$this->decimal_factor);
				return(1);
			case METABASE_TYPE_FLOAT:
				$value=doubleval($value);
				return(1);
			case METABASE_TYPE_DATE:
				$months=array("Jan"=>"01","Feb"=>"02","Mar"=>"03","Apr"=>"04","May"=>"05","Jun"=>"06","Jul"=>"07","Aug"=>"08","Sep"=>"09","Oct"=>"10","Nov"=>"11","Dec"=>"12");
				if(!ereg("^([0-9]{1,2})-([a-zA-Z]{3})-([0-9]{4})$",$value,$date)
				|| !IsSet($months[$date[2]]))
					return($this->SetError("ConvertResult","could not scan DATE value \"$value\""));
				$value=$date[3]."-".$months[$date[2]]."-".$date[1];
				return(1);
			case METABASE_TYPE_TIME:
				return(1);
			case METABASE_TYPE_TIMESTAMP:
				$value=strftime("%Y-%m-%d %H:%M:%S",$value);
				return(1);
			default:
				return($this->BaseConvertResult($value,$type));
		}
	}

	Function GetSequenceNextValue($name,&$value)
	{
		$this->first_selected_row=$this->selected_row_limit=0;
		if(!($result=$this->Query("SELECT _seq FROM sequence_$name")))
			return(0);
		if($this->NumberOfRows($result)==0)
		{
			$this->FreeResult($result);
			return($this->SetError("Get sequence next value","could not find value in sequence table"));
		}
		$value=intval($this->FetchResult($result,0,"_seq"));
		$this->FreeResult($result);
		return(1);
	}

	Function Setup()
	{
		$this->supported["Sequences"]=
		$this->supported["Indexes"]=
		$this->supported["AffectedRows"]=
		$this->supported["SelectRowRanges"]=
			1;
		$this->decimal_factor=pow(10.0,$this->decimal_places);
		return("");
	}
};
 
}
?>