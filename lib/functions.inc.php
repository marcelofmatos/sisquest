<?php
  function DisplayArray( $aArray ){
        //mostra todos os valores de uma matriz

        if( is_array($aArray)  && ( count($aArray) > 0 ) ){
        //abre tabela
        print "<table border='1'>";
        //mostra o cabeçalho
        print "<tr><th>Chave</th><th>Valor</th></tr>";
        //mostra each par Chave/Valor da matriz
        foreach ( $aArray as $aKey => $aValue ){
                print "<tr><td>$aKey";

                if ( is_array($aValue) ) {
                // se o valor corrente é matriz
                // faz com q seja mostrada seu valor
                        print " (array)</td><td>";
                        DisplayArray($aValue);
                        print "</td>";
                } else {
                        if ( empty($aValue) ){
                                print "</td><td><i>vazio</i></td>";
                        } else {
                                print "</td><td>".htmlspecialchars($aValue)."</td>";
                        }

                }

                print "</tr>";
        }                                                                                                                                  
        print "</table>";
        } else {
                print "<i>matriz vazia ou inválida</i><br>";
        }

}
                                                                                                                                          
  function ArrayToString( $aArray , $excKeys=''){
    //mostra todos os valores de uma matriz em uma string
   
    if( is_array($aArray)  && ( count($aArray) > 0 ) ){
    //mostra each par Chave/Valor da matriz
        foreach ( $aArray as $aKey => $aValue ){
            if( !preg_match("^($excKeys)$",$aKey) ){
                if ( is_array($aValue) ) {
                // se o valor corrente é matriz
                // faz com q seja extraído seu valor
                    if(count($aValue) > 0){
                        #$strarr .= "$aKey{\r\n";
                        $strarr .= ArrayToString($aValue,$excKeys);
                        #$strarr .= "}\r\n";
                    }
                } else {
                    if ( !empty($aValue) ){
                            $strarr .= "$aKey=". htmlspecialchars($aValue) ."\r\n";
                    }
                }
            }
        }
    }
    return $strarr;
}

    function StringToArray( $txt, $asArray = true ) {
        if (is_string( $txt )) {
            $lines = explode( "\n", $txt );
        } else if (is_array( $txt )) {
            $lines = $txt;
        } else {
            $lines = array();
        }
        $obj = array();

        $sec_name = '';
        $unparsed = 0;
        if (!$lines) {
            return $obj;
        }
        foreach ($lines as $line) {
            // ignore comments
            if ($line && $line[0] == ';') {
                continue;
            }
            $line = trim( $line );

            if ($line == '') {
                continue;
            }
            if ($line && $line[0] == '[' && $line[strlen($line) - 1] == ']') {
                $sec_name = substr( $line, 1, strlen($line) - 2 );
                $obj[$sec_name] = array();
            } else {
                if ($pos = strpos( $line, '=' )) {
                    $property = trim( substr( $line, 0, $pos ) );

                    if (substr($property, 0, 1) == '"' && substr($property, -1) == '"') {
                        $property = stripcslashes(substr($property,1,count($property) - 2));
                    }
                    $value = trim( substr( $line, $pos + 1 ) );
                    if ($value == 'false') {
                        $value = false;
                    }
                    if ($value == 'true') {
                        $value = true;
                    }
                    if (substr( $value, 0, 1 ) == '"' && substr( $value, -1 ) == '"') {
                        $value = stripcslashes( substr( $value, 1, count( $value ) - 2 ) );
                    }

                /*#if ($process_sections) {
                        $value = str_replace( '\n', "\n", $value );
                        if ($sec_name != '') {
                            if ($asArray) {
                                $obj[$sec_name][$property] = $value;
                            } else {
                                $obj->$sec_name->$property = $value;
                            }
                        } else {
                            if ($asArray) {
                                $obj[$property] = $value;
                            } else {
                                $obj->$property = $value;
                            }
                        }
                    } else {
                    */
                        $value = str_replace( '\n', "\n", $value );
                        if (preg_match('/^[0-9]+$/',$value)) {
                            $value = intval($value);
                        }
                        if ($asArray) {
                            $obj[$property] = $value;
                        } else {
                            $obj->$property = $value;
                        }
                    #}
                /*} else {
                    if ($line && trim($line[0]) == ';') {
                        continue;
                    }
                    
                    if ($process_sections) {
                        $property = '__invalid' . $unparsed++ . '__';
                        if ($process_sections) {
                            if ($sec_name != '') {
                                if ($asArray) {
                                    $obj[$sec_name][$property] = trim($line);
                                } else {
                                    $obj->$sec_name->$property = trim($line);
                                }
                            } else {
                                if ($asArray) {
                                    $obj[$property] = trim($line);
                                } else {
                                    $obj->$property = trim($line);
                                }
                            }
                        } else {
                            if ($asArray) {
                                $obj[$property] = trim($line);
                            } else {
                                $obj->$property = trim($line);
                            }
                        }
                    }
                    */
                }
            }
        }
        return $obj;
    }

function MudaData($data){
    
    if($data=='') return null;
    
    # 2000-02-01 => 01/02/2000
    if( ereg('^([0-9]{4})-([0-1][0-9])-([0-3][0-9])$',$data,$resultado) ){
        return $resultado[3] . "/" . $resultado[2] . "/" . $resultado[1];
    
    # 2000-02-01 10:22:34 => 01/02/2000 10:22   
    }else if( ereg('^([0-9]{4})-([0-1][0-9])-([0-3][0-9]) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9])$',$data,$resultado) ){
        return $resultado[3] . "/" . $resultado[2] . "/" . $resultado[1] ." ". $resultado[4] .":". $resultado[5];
    
    # 01/02/2000 => 2000-02-01   
    }else if( ereg('^([0-3][0-9])/([0-1][0-9])/([0-9]{4})$',$data,$resultado) ){
        return $resultado[1] . "-" . $resultado[2] . "-" . $resultado[3];
    
    # 01/02/2000 10:22 => 2000-02-01 10:22:34   
    }else if( ereg('^([0-3][0-9])/([0-1][0-9])/([0-9]{4}) ([0-2][0-9]):([0-5][0-9])$',$data,$resultado) ){
        return $resultado[1] . "-" . $resultado[2] . "-" . $resultado[3] ." ". $resultado[4] .":". $resultado[5].":00";

    # 01/02/2000 10:22:34 => 2000-02-01 10:22:34   
    }else if( ereg('^([0-3][0-9])/([0-1][0-9])/([0-9]{4}) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9])$',$data,$resultado) ){
        return $resultado[1] . "-" . $resultado[2] . "-" . $resultado[3] ." ". $resultado[4] .":". $resultado[5].":00";
        
    }else die('Data <b>'.$data.'</b> não pôde ser convertida<br><br><a href="javascript:history.back()">Voltar</a>');
}


function getIdCampo($id){
    return 'campo_'.$id;
}
function getNomeCampo($pergunta,$nome){
    return 'resposta_'.$pergunta.'_'.$nome;
}

### Funcoes para gerar o resultado dos graficos
function formatPercentArrayMap($aVal){
	global $total;
	return formatPercent($aVal,$total)."%";
}

function formatPercent($aVal,$total){
	return number_format( ($aVal / $total * 100) , 2); # formata o número em 0.00
}

function listarOpcoes(&$perg){
    global $conexao, $larguralegenda, $ordenar;
        unset($perg['opcoes']);

        $sql  = "SELECT DISTINCT c.nome,d.valor,c.rotulo,c.tipo,c.idcampo,";
        $sql .= " (SELECT count(*) FROM dados WHERE idcampo = d.idcampo AND valor LIKE d.valor) as contador ";
        $sql .= " FROM campos c LEFT JOIN dados d ON d.idcampo=c.idcampo";
        $sql .= " WHERE c.idpergunta IN (".$perg['idpergunta'].") ORDER BY contador ". ( ($ordenar==2) ? "DESC" : "ASC") .", c.rotulo, d.valor ";

		$conexao->query($sql);
        while($rowOpcoes = $conexao->fetch_array()){
            if($rowOpcoes['tipo']=='text' || $rowOpcoes['tipo']=='textarea') {
                $rowOpcoes['rotulo'] = $rowOpcoes['valor'];
            } else if(!empty($rowOpcoes['rotulo'])){
                # retirar observações no meio do texto da opção
                $rowOpcoes['rotulo'] = preg_replace('/\(.+\)/','',$rowOpcoes['rotulo']);
                # retirar sinais no final do texto
                $rowOpcoes['rotulo'] = trim(preg_replace('/[:\.!?;]$/','',$rowOpcoes['rotulo']));
            }
            $perg['opcoes'][] = $rowOpcoes;
			
			# valor para largura da legenda
			$larguralegenda = max( $larguralegenda, strlen($rowOpcoes['rotulo']) );
		
        }  
}
?>
