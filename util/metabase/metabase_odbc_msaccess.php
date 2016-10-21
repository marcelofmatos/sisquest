<?php

if(!defined("METABASE_ODBC_MSACCESS_INCLUDED"))
{
	define("METABASE_ODBC_MSACCESS_INCLUDED",1);

/*
 * metabase_odbc_msaccess.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/metabase/metabase_odbc_msaccess.php,v 1.3 2002/12/30 14:20:47 mlemos Exp $
 *
 */

class metabase_odbc_msaccess_class extends metabase_odbc_class
{
	var $get_type_info=0;
	var $manager_sub_included_constant="METABASE_MANAGER_ODBC_MSACCESS_INCLUDED";
	var $manager_sub_include="manager_odbc_msaccess.php";
	var $manager_class_name="metabase_manager_odbc_msaccess_class";

	// If UseDecimalScale=1 - use INT for DECIMAL, otherwise use CURRENCY
	// TODO: issue a warning if decimal_places>4 and UseDecimalScale=0
	Function GetTypeDeclaration($type,$size=0)
	{
		$current_dba_access=$this->dba_access;
		$this->dba_access=0;
		$declaration="";
		$scale=0;
		if(!strcmp($declaration,""))
		{
			switch($type)
			{
				case METABASE_ODBC_VARCHAR_TYPE:
					$declaration="VARCHAR";
					break;
				case METABASE_ODBC_LONGVARCHAR_TYPE:
					$declaration="MEMO";
					break;
				case METABASE_ODBC_BIT_TYPE:
					$declaration="BIT";
					break;
				case METABASE_ODBC_INTEGER_TYPE:
					$declaration="INT";
					break;
				case METABASE_ODBC_DECIMAL_TYPE:
					if($this->support_decimal_scale)
						$declaration="INT";
					else
						$declaration="CURRENCY";
					break;
				case METABASE_ODBC_DATE_TYPE:
					$declaration="DATETIME";
					break;
				case METABASE_ODBC_TIME_TYPE:
					$declaration="DATETIME";
					break;
				case METABASE_ODBC_TIMESTAMP_TYPE:
					$declaration="DATETIME";
					break;
				case METABASE_ODBC_DOUBLE_TYPE:
					$declaration="FLOAT";
					break;
			}
		}
		if($size)
		{
			switch($type)
			{
				case METABASE_ODBC_VARCHAR_TYPE:
					$declaration.="($size)";
					break;
			}
		}
		if($type==METABASE_ODBC_DECIMAL_TYPE)
		{
			if($this->support_decimal_scale)
			{
				$this->decimal_scale=0;
				$this->decimal_factor=($scale<$this->decimal_places ? pow(10.0,$this->decimal_places-$scale) : 1.0);
			}
		}
		$this->dba_access=$current_dba_access;
		return($declaration);
	}

	// Use VARCHAR as the default text type, and LONGCHAR (Memo) only if size>255
	Function GetTextFieldTypeDeclaration($name,&$field)
	{
		if(!IsSet($field["length"]))
			$field["length"]=(IsSet($this->options["DefaultTextFieldLength"]) ? $this->options["DefaultTextFieldLength"] : 255);
		if($field["length"] > 255)
			$declaration=$this->GetTypeDeclaration(METABASE_ODBC_LONGVARCHAR_TYPE);
		else
			$declaration=$this->GetTypeDeclaration(METABASE_ODBC_VARCHAR_TYPE,$field["length"]);
		if(IsSet($field["default"]))
		{
			if($this->support_defaults)
				$declaration.=" DEFAULT ".$this->GetTextFieldValue($field["default"]);
			else
				$this->warning="this ODBC data source does not support field default values";
		}
		return("$name $declaration".(IsSet($field["notnull"]) ? " NOT NULL" : ""));
	}

	// If using CURRENCY, adjust the value because Access always returns 4 decimal places
	// TODO: Consider the consequences of switching between tables or DBs that use the Decimal Scale
	Function GetDecimalFieldValue($value)
	{
		if($this->support_decimal_scale)
		{
			if($this->decimal_scale<0)
				$this->GetTypeDeclaration(METABASE_ODBC_DECIMAL_TYPE,$this->decimal_places);
			return(!strcmp($value,"NULL") ? "NULL" : ($this->decimal_scale<$this->decimal_places ? ($this->decimal_scale>0 ? sprintf("%.".$this->decimal_scale."f",doubleval($value)*$this->decimal_factor) : strval(round(doubleval($value)*$this->decimal_factor))) : strval($value)));
		} else {
			return(!strcmp($value,"NULL") ? "NULL" : number_format(doubleval($value),$this->decimal_places,'.',''));
		}
	}

	// Similar change as above for DECIMAL. Also take only what we need from a DATETIME field
	Function ConvertResult(&$value,$type)
	{
		switch($type)
		{
			case METABASE_TYPE_BOOLEAN:
				$value=(strcmp($value,"1") ? 0 : 1);
				return(1);
			case METABASE_TYPE_DECIMAL:
				if($this->support_decimal_scale)
				{
					if($this->decimal_scale<0)
						$this->GetTypeDeclaration(METABASE_ODBC_DECIMAL_TYPE,$this->support_decimal_scale ? $this->decimal_places : 0);
					if($this->decimal_scale<$this->decimal_places)
						$value=sprintf("%.".$this->decimal_places."f",doubleval($value)/$this->decimal_factor);
				} else {
					$value=number_format(doubleval($value),$this->decimal_places,'.','');
				}
				return(1);
			case METABASE_TYPE_FLOAT:
				$value=doubleval($value);
				return(1);
			case METABASE_TYPE_DATE:
				$value=substr($value,0,10);
				return(1);     
			case METABASE_TYPE_TIME:
				$value=substr($value,11,8);
				return(1);     
			case METABASE_TYPE_TIMESTAMP:
				return(1);
			default:
				return($this->BaseConvertResult($value,$type));
		}
	}

	Function GetCLOBFieldValue($prepared_query,$parameter,$clob,&$value)
	{
		for($value="'";!MetabaseEndOfLOB($clob);)
		{
			if(MetabaseReadLOB($clob,$data,$this->lob_buffer_length)<0)
			{
				$value="";
				return($this->SetError("Get CLOB field value",MetabaseLOBError($clob)));
			}
			$this->EscapeText($data);
			$value.=$data;
		}
		$value.="'";
		return(1);			
	}

	Function FreeCLOBValue($prepared_query,$clob,&$value,$success)
	{
		Unset($value);
	}

	Function GetBLOBFieldValue($prepared_query,$parameter,$blob,&$value)
	{
		for($value="0x";!MetabaseEndOfLOB($blob);)
		{
			if(!MetabaseReadLOB($blob,$data,$this->lob_buffer_length))
			{
				$value="";
				return($this->SetError("Get BLOB field value",MetabaseLOBError($blob)));
			}
			$value.=Bin2Hex($data);
		}
		return(1);			
	}

	Function FreeBLOBValue($prepared_query,$blob,&$value,$success)
	{
		Unset($value);
	}

	Function GetSequenceNextValue($name,&$value)
	{
		if(!($this->Query("INSERT INTO _sequence_$name(foo) VALUES(NULL)"))
		|| !($result=$this->Query("SELECT @@IDENTITY FROM _sequence_$name")))
			return(0);
		$value=intval($this->FetchResult($result,0,0));
		$this->FreeResult($result);
		if(!$this->Query("DELETE FROM _sequence_$name WHERE sequence<$value"))
			$this->warning="could not delete previous sequence table values";
		return(1);
	}

	Function SetupODBC()
	{
		if(!IsSet($this->options["UseIndexes"])
		|| $this->options["UseIndexes"])
			$this->supported["Indexes"]=1;
		if(!IsSet($this->options["UseTransactions"])
		|| $this->options["UseTransactions"])
			$this->supported["Transactions"]=$this->supported["Replace"]=1;
		$this->support_defaults=(IsSet($this->options["UseDefaultValues"]) && $this->options["UseDefaultValues"]);
		$this->supported["Sequences"]=
		$this->supported["GetSequenceCurrentValue"]=1;
		$this->blob_declaration=(IsSet($this->options["BLOBType"]) ? $this->options["BLOBType"] : "IMAGE");
		if(strlen($this->blob_declaration)>0)
		{
			$this->clob_declaration=(IsSet($this->options["CLOBType"]) ? $this->options["CLOBType"] : "MEMO");
			if(strlen($this->clob_declaration)==0)
				$this->clob_declaration=$this->blob_declaration;
			$this->supported["LOBs"]=1;
			return(1);
		}
		$get_type_info=$this->get_type_info;
		$this->get_type_info=1;
		$success=$this->SetupODBCLOBs();
		$this->get_type_info=$get_type_info;
		return($success);
	}
};
}
?>