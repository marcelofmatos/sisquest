<?php
/*
 * metabase_lob.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/metabase/metabase_lob.php,v 1.6 2002/10/15 23:30:34 mlemos Exp $
 *
 */

$metabase_lobs=array();

class metabase_lob_class
{
	var $error="";
	var $database=0;
	var $lob;
	var $data="";
	var $position=0;

	Function Create(&$arguments)
	{
		if(IsSet($arguments["Data"]))
			$this->data=$arguments["Data"];
		return(1);
	}

	Function Destroy()
	{
		$this->data="";
	}

	Function EndOfLOB()
	{
		return($this->position>=strlen($this->data));
	}

	Function ReadLOB(&$data,$length)
	{
		$length=min($length,strlen($this->data)-$this->position);
		$data=substr($this->data,$this->position,$length);
		$this->position+=$length;
		return($length);
	}
};

class metabase_result_lob_class extends metabase_lob_class
{
	var $result_lob=0;

	Function Create(&$arguments)
	{
		if(!IsSet($arguments["ResultLOB"]))
		{
			$this->error="it was not specified a result LOB identifier";
			return(0);
		}
		$this->result_lob=$arguments["ResultLOB"];
		return(1);
	}

	Function Destroy()
	{
		MetabaseDestroyResultLOB($this->database,$this->result_lob);
	}

	Function EndOfLOB()
	{
		return(MetabaseEndOfResultLOB($this->database,$this->result_lob));
	}

	Function ReadLOB(&$data,$length)
	{
		if(($read_length=MetabaseReadResultLOB($this->database,$this->result_lob,$data,$length))<0)
			$this->error=MetabaseError($this->database);
		return($read_length);
	}
};

class metabase_input_file_lob_class extends metabase_lob_class
{
	var $file=0;
	var $opened_file=0;

	Function Create(&$arguments)
	{
		if(IsSet($arguments["File"]))
		{
			if(intval($arguments["File"])==0)
			{
				$this->error="it was specified an invalid input file identifier";
				return(0);
			}
			$this->file=$arguments["File"];
		}
		else
		{
			if(IsSet($arguments["FileName"]))
			{
				if((!$this->file=fopen($arguments["FileName"],"rb")))
				{
					$this->error="could not open specified input file (\"".$arguments["FileName"]."\")";
					return(0);
				}
				$this->opened_file=1;
			}
			else
			{
				$this->error="it was not specified the input file";
				return(0);
			}
		}		
		return(1);
	}

	Function Destroy()
	{
		if($this->opened_file)
		{
			fclose($this->file);
			$this->file=0;
			$this->opened_file=0;
		}
	}

	Function EndOfLOB()
	{
		return(feof($this->file));
	}

	Function ReadLOB(&$data,$length)
	{
		if(GetType($data=@fread($this->file,$length))!="string")
		{
			$this->error="could not read from the input file";
			return(-1);
		}
		return(strlen($data));
	}
};

class metabase_output_file_lob_class extends metabase_lob_class
{
	var $file=0;
	var $opened_file=0;
	var $input_lob=0;
	var $opened_lob=0;
	var $buffer_length=8000;

	Function Create(&$arguments)
	{
		global $metabase_lobs;

		if(IsSet($arguments["BufferLength"]))
		{
			if($arguments["BufferLength"]<=0)
			{
				$this->error="it was specified an invalid buffer length";
				return(0);
			}
			$this->buffer_length=$arguments["BufferLength"];
		}
		if(IsSet($arguments["File"]))
		{
			if(intval($arguments["File"])==0)
			{
				$this->error="it was specified an invalid output file identifier";
				return(0);
			}
			$this->file=$arguments["File"];
		}
		else
		{
			if(IsSet($arguments["FileName"]))
			{
				if((!$this->file=fopen($arguments["FileName"],"wb")))
				{
					$this->error="could not open specified output file (\"".$arguments["FileName"]."\")";
					return(0);
				}
				$this->opened_file=1;
			}
			else
			{
				$this->error="it was not specified the output file";
				return(0);
			}
		}		
		if(IsSet($arguments["LOB"]))
		{
			if(!IsSet($metabase_lobs[$arguments["LOB"]]))
			{
				$this->Destroy();
				$this->error="it was specified an invalid input large object identifier";
				return(0);
			}
			$this->input_lob=$arguments["LOB"];
		}
		else
		{
			if($this->database
			&& IsSet($arguments["Result"])
			&& IsSet($arguments["Row"])
			&& IsSet($arguments["Field"])
			&& IsSet($arguments["Binary"]))
			{
				if($arguments["Binary"])
					$this->input_lob=MetabaseFetchBLOBResult($this->database,$arguments["Result"],$arguments["Row"],$arguments["Field"]);
				else
					$this->input_lob=MetabaseFetchCLOBResult($this->database,$arguments["Result"],$arguments["Row"],$arguments["Field"]);
				if($this->input_lob==0)
				{
					$this->Destroy();
					$this->error="could not fetch the input result large object";
					return(0);
				}
				$this->opened_lob=1;
			}
			else
			{
				$this->Destroy();
				$this->error="it was not specified the input large object identifier";
				return(0);
			}
		}		
		return(1);
	}

	Function Destroy()
	{
		if($this->opened_file)
		{
			fclose($this->file);
			$this->opened_file=0;
			$this->file=0;
		}
		if($this->opened_lob)
		{
			MetabaseDestroyLOB($this->input_lob);
			$this->input_lob=0;
			$this->opened_lob=0;
		}
	}

	Function EndOfLOB()
	{
		return(MetabaseEndOfLOB($this->input_lob));
	}

	Function ReadLOB(&$data,$length)
	{
		$buffer_length=($length==0 ? $this->buffer_length : min($this->buffer_length,$length));
		for($written=0;!MetabaseEndOfLOB($this->input_lob) && ($length==0 || $written<$buffer_length);$written+=$read)
		{
			if(MetabaseReadLOB($this->input_lob,$buffer,$buffer_length)==-1)
			{
				$this->error=MetabaseLOBError($this->input_lob);
				return(-1);
			}
			$read=strlen($buffer);
			if(@fwrite($this->file,$buffer,$read)!=$read)
			{
				$this->error="could not write to the output file";
				return(-1);
			}
		}
		return($written);
	}
};

Function MetabaseCreateLOB(&$arguments,&$lob)
{
	global $metabase_lobs;

	$lob=count($metabase_lobs)+1;
	$class="metabase_lob_class";
	if(IsSet($arguments["Type"]))
	{
		switch($arguments["Type"])
		{
			case "resultlob":
				$class="metabase_result_lob_class";
				break;
			case "inputfile":
				$class="metabase_input_file_lob_class";
				break;
			case "outputfile":
				$class="metabase_output_file_lob_class";
				break;
			case "data":
				break;
			default:
				if(IsSet($arguments["Error"]))
					$arguments["Error"]=$arguments["Type"]." is not a valid type of large object";
				return(0);
		}
	}
	else
	{
		if(IsSet($arguments["Class"]))
			$class=$arguments["Class"];
	}
	$metabase_lobs[$lob]=new $class;
	$metabase_lobs[$lob]->lob=$lob;
	if(IsSet($arguments["Database"]))
		$metabase_lobs[$lob]->database=$arguments["Database"];
	if($metabase_lobs[$lob]->Create($arguments))
		return(1);
	if(IsSet($arguments["Error"]))
		$arguments["Error"]=$metabase_lobs[$lob]->error;
	MetabaseDestroyLOB($lob);
	return(0);
}

Function MetabaseDestroyLOB($lob)
{
	global $metabase_lobs;

	$metabase_lobs[$lob]->Destroy();
	Unset($metabase_lobs[$lob]);
	$metabase_lobs[$lob]="";
}

Function MetabaseEndOfLOB($lob)
{
	global $metabase_lobs;

	return($metabase_lobs[$lob]->EndOfLOB());
}

Function MetabaseReadLOB($lob,&$data,$length)
{
	global $metabase_lobs;

	return($metabase_lobs[$lob]->ReadLOB($data,$length));
}

Function MetabaseLOBError($lob)
{
	global $metabase_lobs;

	return($metabase_lobs[$lob]->error);
}

?>