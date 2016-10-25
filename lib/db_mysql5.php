<?php

  class db_mysql5{
      var $conexao;
      var $sql;
      var $num_rows;
      var $query;
      var $host;
      var $usuario;
      var $senha;
      var $banco;
      function db_mysql5($banco,$host='localhost',$usuario='root',$senha=''){
          $this->host = $host;
          $this->usuario = $usuario;
          $this->senha = $senha;
          $this->banco = $banco;
      }
      function conecta(){
          $this->conexao = mysql_connect($this->host,$this->usuario,$this->senha) OR die('Erro ao conectar com o banco de dados');
          mysql_select_db($this->banco) OR die('Erro ao selecionar o banco "'.$this->banco.'"');
          
      }
      function query($sql,$debug=false){
        $this->num_rows = 0;
        $this->sql = $sql;
        $this->query = mysql_query($this->sql,$this->conexao);
	if(preg_match('/^SELECT/',$this->sql)) $this->num_rows=mysql_num_rows($this->query);
        $error = mysql_error();
        $errorno = mysql_errno();
        if($debug && $errorno) print("<p><b>Erro(nº $errorno - $error) ao executar Comando SQL</b>: {$this->sql}</p><p><b>Mensagens do MySQL</b>:".mysql_error()."</p>"); 
        return $this->query;
      }
      function fetch_array(){
          return mysql_fetch_array($this->query);
      }
      function num_rows(){
          return mysql_num_rows($this->query);
      }
      function desconecta(){
          if($this->query && !preg_match('/^DELETE/',$this->sql)) @mysql_free_result($this->query);
          if($this->conexao) mysql_close($this->conexao);
      }
      
      function erro() {
          return mysql_error();
      }
      
      function begin() {
          $this->query("START TRANSACTION");
      }
      
      function commit() {
          return $this->query("COMMIT");
      }
      
      function rollback() {
          return $this->query("ROLLBACK");
      }
  }
?>
