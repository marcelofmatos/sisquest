<?php
if(!defined("METABASE_MANAGER_SQLITE_INCLUDED"))
{
	define("METABASE_MANAGER_SQLITE_INCLUDED",1);

/*
 *	manager_sqlite.php
 *
 *	@(#) $Header: /home/mlemos/cvsroot/metabase/manager_sqlite.php,v 1.8 2005/11/18 20:57:18 mlemos Exp $
 *	@author	Jeroen Derks <jeroen@derks.it>
 *
 *	Adapted for PHP Extensions by
 *	John Walton admin@ryefc.com
 *
 *	Added inherited function to suppress PRIMARY KEY declaration for Autoincrement Field
 *
 *
 */

class metabase_manager_sqlite_class extends metabase_manager_database_class
{
// 5/11/05 J. Walton
// Updated CreateDatabase function to test for file permissions on server
// Set default SQLite permissions
	Function CreateDatabase(&$db,$name)
	{
		if(!function_exists("sqlite_open"))
			return($db->SetError("Connect","SQLite support is not available in this PHP configuration"));
		$database_file=$db->GetDatabaseFile($name);
		if(@file_exists($database_file))
			return($db->SetError("Create database","database already exists"));
		@touch($database_file);
		if(!@file_exists($database_file))
			return($db->SetError("Create database","Unable to create new database. Permission denied"));
		$mode=(IsSet($db->options["AccessMode"]) ? (strcmp($db->options["AccessMode"][0],"0") ? intval($db->options["AccessMode"]) : octdec($db->options["AccessMode"])) : 0640);
		@chmod($database_file, $mode);
		if(!is_readable($database_file))
		{
			@unlink($database_file);
			return($db->SetError("Create database","Unable to open database for Reading. Permission denied"));
		}
		if(!is_writable($database_file))
		{
			@unlink($database_file);
			return($db->SetError("Create database","Unable to open database for Writing. Permission denied"));
		}
		$handle=@sqlite_open($database_file, $mode);
		if(!$handle)
		{
			@unlink($database_file);
			return($db->SetError("Create database",IsSet($php_errormsg) ? $php_errormsg : "could not create the database file"));
		}
		sqlite_close($handle);
		return(1);
	}

	Function DropDatabase(&$db,$name)
	{
		$database_file=$db->GetDatabaseFile($name);
		if (!@file_exists($database_file))
			return($db->SetError("Drop database","database does not exist"));
		$success=@unlink($database_file);
		if(!$success)
			return($db->SetError("Drop database",IsSet($php_errormsg) ? $php_errormsg : "could not remove the database file"));
		return(1);
	}

	Function AlterTable(&$db,$name,&$changes,$check)
	{
		$sql=array();
		if(IsSet($changes["name"]))
		{
			$new_name=$changes["name"];
			$sql[]="ALTER TABLE ".$name." RENAME TO ".$new_name;
		}
		else
			$new_name=$name;
		if(IsSet($changes["AddedFields"]))
		{
			$fields=$changes["AddedFields"];
			for($field=0,Reset($fields);$field<count($fields);Next($fields),$field++)
			{
				$field_name=Key($fields);
				$definition=$fields[$field_name];
				if(!$this->GetField($db,$definition,$field_name,$declaration))
					return(0);
				$sql[]="ALTER TABLE ".$new_name." ADD COLUMN ".$declaration;
			}
		}
		$v=explode(".", $version=sqlite_libversion());
		$version_number=$v[0]*1000000+$v[1]*1000+$v[2];
		for($change=0,Reset($changes);$change<count($changes);Next($changes),$change++)
		{
			switch(Key($changes))
			{
				case "name":
					if($version_number<3001000)
						return($db->SetError("Alter table","table renaming is only supported in SQLite 3.1.0 and your version is ".$version));
					break;
				case "AddedFields":
					if($version_number<3001000)
						return($db->SetError("Alter table","table column adding is only supported in SQLite 3.2.0 and your version is ".$version));
					break;
				case "AutoIncrement":
				case "PrimaryKey":
				case "SQL":
					break;
				case "RenamedFields":
				case "RemovedFields":
				case "ChangedFields":
				case "AddedPrimaryKey":
				case "RemovedPrimaryKey":
				case "ChangedPrimaryKey":
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

	Function ListTables(&$db,&$tables)
	{
		if(!$db->QueryColumn("SELECT name FROM sqlite_master WHERE type='table' AND sql NOT NULL ORDER BY name",$table_names))
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
		if(!($result=$db->Query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$table'")))
			return(0);
		if(!$db->GetColumnNames($result,$columns))
		{
			$db->FreeResult($result);
			return(0);
		}
		if(!IsSet($columns["sql"]))
		{
			$db->FreeResult($result);
			return($db->SetError("List table fields","show columns does not return the table creation sql"));
		}

		$sql=$db->FetchResult($result,0,0);
		if(!$this->GetTableColumnNames($db,$sql,$fields))
			return(0);
		$db->FreeResult($result);
		return(1);
	}

	Function GetTableColumnNames(&$db,$sql,&$column_names)
	{
		if(!$this->GetTableColumns($db,$sql,$columns))
			return(0);
		$count=count($columns);
		if($count==0)
			return($db->SetError("Get table column names","table did not return any columns"));
		$column_names=array();
		for($i=0;$i<$count;++$i)
			$column_names[]=$columns[$i]["name"];
		return(1);
	}

	Function GetTableColumns(&$db,$sql,&$columns)
	{
		$start_pos=strpos($sql,"(");
		$end_pos=strrpos($sql,")");
		$column_def=substr($sql,$start_pos+1,$end_pos-$start_pos-1);
		$column_sql=split(",",$column_def);
		$columns=array();
		$count=count($column_sql);
		if($count==0)
			return($db->SetError("Get table columns","unexpected empty table column definition list"));
		for($i=0,$j=0;$i<$count;++$i)
		{
			if(!preg_match('/^([^ ]+) (CHAR|VARCHAR|VARCHAR2|TEXT|INT|INTEGER|BIGINT|DOUBLE|FLOAT|DATETIME|DATE|TIME|LONGTEXT|LONGBLOB)( PRIMARY)?( \(([1-9][0-9]*)(,([1-9][0-9]*))?\))?( DEFAULT (\'[^\']*\'|[^ ]+))?( NOT NULL)?$/i',$column_sql[$i],$matches))
				return($db->SetError("Get table columns","unexpected table column SQL definition"));
			$columns[$j]["name"]=$matches[1];
			$columns[$j]["type"]=strtolower($matches[2]);
			if(IsSet($matches[5])
			&& strlen($matches[5]))
				$columns[$j]["length"]=$matches[5];
			if(IsSet($matches[7])
			&& strlen($matches[7]))
				$columns[$j]["decimal"]=$matches[7];
			if(IsSet($matches[9])
			&& strlen($matches[9]))
			{
				$default=$matches[9];
				if(strlen($default)
				&& $default[0]=="'")
					$default=str_replace("''","'",substr($default,1,strlen($default)-2));
				$columns[$j]["default"]=$default;
			}
			if(IsSet($matches[10])
			&& strlen($matches[10]))
				$columns[$j]["notnull"]=1;
			++$j;
		}
		return(1);
	}

	Function GetTableFieldDefinition(&$db,$table,$field,&$definition)
	{
		$field_name=strtolower($field);
		if(!($result=$db->Query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$table'")))
			return(0);
		if(!$db->GetColumnNames($result,$columns))
		{
			$db->FreeResult($result);
			return(0);
		}
		if(!IsSet($columns["sql"]))
		{
			$db->FreeResult($result);
			return($db->SetError("Get table field definition","show columns does not return the table creation sql"));
		}

		$sql=$db->FetchResult($result,0,0);
		if(!$this->GetTableColumns($db,$sql,$columns))
			return(0);
		$count=count($columns);
		for($i=0;$i<$count;++$i)
		{
			if($field_name==$columns[$i]["name"])
			{
				$db_type=$columns[$i]["type"];
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
						if(IsSet($columns[$i]["length"]) && $columns[$i]["length"]=="1")
							$type[1]="boolean";
						break;

					case "tinytext":
					case "mediumtext":
					case "longtext":
					case "text":
					case "char":
					case "varchar":
					case "varchar2":
						$type[0]="text";
						if(IsSet($columns[$i]["length"]) && $columns[$i]["length"]=="1")
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
						$type[0]="text";
						break;

					case "year":
						$type[0]="integer";
						$type[1]="date";
						break;

					default:
						return($db->SetError("Get table field definition","unknown database attribute type"));
				}

				for($definition=array(),$datatype=0;$datatype<count($type);$datatype++)
				{
					$definition[$datatype]=array(
						"type"=>$type[$datatype]
					);
					if(IsSet($columns[$i]["notnull"]))
						$definition[$datatype]["notnull"]=1;
					if(IsSet($columns[$i]["default"]))
						$definition[$datatype]["default"]=$columns[$i]["default"];
					if(IsSet($columns[$i]["length"]))
						$definition[$datatype]["length"]=$columns[$i]["length"];
				}
				$db->FreeResult($result);
				return(1);
			}
		}
		$db->FreeResult($result);
		return($db->SetError("Get table field definition","it was not specified an existing table column"));
	}

	Function ListTableIndexes(&$db,$table,&$indexes)
	{
		if(!($result=$db->Query("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='$table' AND sql NOT NULL ORDER BY name")))
			return(0);
		if($db->NumberOfRows($result)==0)
		{
			$db->FreeResult($result);
			return(1);
		}
		if(!$db->GetColumnNames($result,$columns))
		{
			$db->FreeResult($result);
			return(0);
		}
		if(!IsSet($columns["name"]))
		{
			$db->FreeResult($result);
			return($db->SetError("List table indexes","show index does not return the index name"));
		}

		$name_column=$columns["name"];
		for($found=$indexes=array(),$index=0;!$db->EndOfResult($result);$index++)
		{
			$index_name=$db->FetchResult($result,$index,$name_column);
			if(!IsSet($found[$index_name]))
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
		if($index_name=="PRIMARY")
			return($db->SetError("Get table index definition","PRIMARY is an hidden index"));
		if(!($result=$db->Query("SELECT sql FROM sqlite_master WHERE type='index' AND name='$index' AND tbl_name='$table' AND sql NOT NULL ORDER BY name")))
			return(0);
		if(!$db->GetColumnNames($result,$columns))
		{
			$db->FreeResult($result);
			return(0);
		}
		if(!IsSet($columns[$column="sql"]))
		{
			$db->FreeResult($result);
			return($db->SetError("Get table index definition","show index does not return the table creation sql"));
		}

		$sql=strtolower($db->FetchResult($result,0,0));
		$unique=strstr($sql," unique ");
		$key_name=$index;
		$start_pos=strpos($sql,"(");
		$end_pos=strrpos($sql,")");
		$column_names=substr($sql,$start_pos+1,$end_pos-$start_pos-1);
		$column_names=split(",",$column_names);

		$definition=array();
		if($unique)
			$definition["unique"]=1;
		$count=count($column_names);
		for($i=0;$i<$count;++$i)
		{
			$column_name=strtok($column_names[$i]," ");
			$collation=strtok(" ");
			$definition["FIELDS"][$column_name]=array();
			if(!empty($collation))
				$definition["FIELDS"][$column_name]["sorting"]=($collation=="ASC" ? "ascending" : "descending");
		}

		$db->FreeResult($result);
		return(IsSet($definition["FIELDS"]) ? 1 : $db->SetError("Get table index definition","it was not specified an existing table index"));
	}

	Function ListSequences(&$db,&$sequences)
	{
		if(!$db->QueryColumn("SELECT name FROM sqlite_master WHERE type='table' AND sql NOT NULL ORDER BY name",$table_names))
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
		if(!$db->QueryColumn("SELECT name FROM sqlite_master WHERE type='table' AND name='$db->sequence_prefix$sequence' AND sql NOT NULL ORDER BY name",$table_names))
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
		if(!$db->Query("CREATE TABLE ".$db->sequence_prefix.$name." (sequence INTEGER PRIMARY KEY DEFAULT 0 NOT NULL)"))
			return(0);
		if($db->Query("INSERT INTO ".$db->sequence_prefix.$name." (sequence) VALUES (".($start-1).")"))
			return(1);
		$error=$db->Error();
		if(!$db->Query("DROP TABLE ".$db->sequence_prefix.$name))
			$db->warning="could not drop inconsistent sequence table";
		return($db->SetError("Create sequence",$error));
	}

	Function DropSequence(&$db,$name)
	{
		return($db->Query("DROP TABLE ".$db->sequence_prefix.$name));
	}

	Function GetSequenceCurrentValue(&$db,$name,&$value)
	{
		if(!($result=$db->Query("SELECT MAX(sequence) FROM ".$db->sequence_prefix.$name)))
			return(0);
		if(!($db->FetchResultField($result,$value)))
			$value=0;
		return(1);
	}

	Function CreateIndex(&$db,$table,$name,&$definition)
	{
		$query="CREATE ".(IsSet($definition["unique"]) ? "UNIQUE" : "")." INDEX $name ON $table (";
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
		return($db->Query("DROP INDEX $name"));
	}
};
}
?>