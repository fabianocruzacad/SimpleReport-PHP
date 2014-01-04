<?php

class Relatorio {

	public $file;
	public $diretorio;	
	public $arquivo;
	public $ext;
	public $empresa;
	public $nomeRelatorio;
	public $head;
	public $consulta;
	public $mode;
	public $topo = array();
	public $nPag;
	public $col;
	public $formato;
	public $posicoesFormato;
	public $numRegFolha;
	public $inicioRodape;
	public $contLinha;

	function __construct($formato = "RETRATO", $diretorio="",$arquivo="",$ext="", $mode="w", $empresa="", $nomeRelatorio = "", $consulta = NULL){
		$this->empresa 			= $empresa;
		$this->nomeRelatorio 	= $nomeRelatorio;
		$this->diretorio 		= $diretorio;
		$this->arquivo 			= $arquivo;
		$this->ext 				= $ext;
		$this->mode 			= $mode;
		$this->consulta 		= $consulta;
		$this->formato			= $formato;
		if($formato=="RETRATO"){
			$this->posicoesFormato 	= 80;
			$this->numRegFolha		= 60;
		} elseif($formato=="PAISAGEM"){
			$this->posicoesFormato 	= 113;
			$this->numRegFolha		= 41;
		}

	}
	
	function abreArq(){
		$this->file = fopen($this->diretorio.$this->arquivo.".".$this->ext, $this->mode);
	}
	
	function fechaArq(){
		fclose($this->file);
	}
	
	function escreve($text){
		fwrite($this->file,utf8_decode($text));
	}
	
	function le($cont){
		fread($this->file,$cont);	
	}
	
	function quebra(){
		$this->contLinha += 1;
//		echo $this->numRegFolha."<br>";
//		echo $this->contLinha."<br>";
//		if($this->contLinha>$this->numRegFolha){ $this->head($this->nPag); $this->contLinha = 0; }
		return chr(13).chr(10);	
	}
	
	function addCampo($posicao, $campo, $label, $tamanho, $formato = "TEXT"){
		$array = array("posicao"=>$posicao, "campo"=>$campo, "label"=>$label, "tamanho"=>$tamanho, "formato"=>$formato);
		array_push($this->topo, $array);
		empty($array);
	}
	
	function formataCampo($text, $format, $caixa){
		switch($format){
			case "DATE": return date("d/m/Y", strtotime($text));
			break;	
			case "TEXT": return $text;
			break;
			case "MILHAR": return $this->alinhaDireita(number_format($text,0,"","."), $caixa);
			break;
			case "DECIMAL": return $this->alinhaDireita(number_format($text,2,",","."), $caixa);
			break;
		}
	}
	
	function retiraUltimaLetra($text){
		$qL = strlen($text) - 2;
		return substr($text,0,$qL);
	}

	function alinhaDireita($text, $caixa){
		$qL = strlen($text);
		
		//validacao
		if($caixa<$qL){
			return $text;
		}

		$qEspaco = $caixa - $qL;
		
		$i= 1;
		while($i <= $qEspaco){
			$textRetorno .= chr(32);
			$i++;
		}

		$textRetorno .= $text;

		return $textRetorno;
	}
		
	function head($nPagAtual){
						
		#monta o cabeçalho do documento
		$iLinha1 = 11; //desconta os caracteres do numerador de paginas
		while($iLinha1<$this->posicoesFormato - 2){
			$this->head .= "-";
			$iLinha1++;
		}
		$this->head .= " Página: $nPagAtual".$this->quebra();
		//--------------------------------------------------------------------------------//
		$totalLetras = strlen($this->empresa) + strlen($this->nomeRelatorio);
		$quantEspacosLinha2 = $this->posicoesFormato - $totalLetras;
		$this->head .= $this->empresa;
		$iLinha2 = -1;
		while($iLinha2<$quantEspacosLinha2){
			$this->head .= " ";
			$iLinha2++;
		}
		$this->head .= $this->nomeRelatorio.$this->quebra();
		//--------------------------------------------------------------------------------//
		$iLinha3 = 21;
		while($iLinha3<$this->posicoesFormato){
			$this->head .= "-";
			$iLinha3++;
		}
		$this->head .= " ".date("d/m/Y H:m:s").$this->quebra();
		$this->head .= $this->quebra();
		
		#imprime cabecalho
		$this->escreve($this->head);

		#zera variaveis
		$this->head = "";
	}
	
	function montaRelatorio(){
		$this->abreArq($this->mode);
		$this->imprimeTabela();
		$this->fechaArq();
	}
	
	function retiraAcento($texto){
		$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç", "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
		$array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c", "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
		return str_replace( $array1, $array2, $texto );
	} 


	function imprimeLabels(){
		
		#monta os labels das colunas de acordo com os campos informados
		array_multisort($this->topo, SORT_ASC);
		$totalPosicoes = 0;
		foreach($this->topo as $r){
			$i = 0;
			$tab = "";
			$numChars = strlen($this->retiraAcento($r['label']));
			$numTabNecessario = $r['tamanho']-$numChars + 2;
			while($i<$numTabNecessario){
				$tab .= " ";
				$i++;
				$totalPosicoes++;
			}			
			$this->col .= $r['label'].$tab;
			$totalPosicoes += $numChars;
			
			
		}
		if($totalPosicoes<$this->posicoesFormato){	$totalPosicoes = $this->posicoesFormato; } 
		$i = 0;
		while($i<($totalPosicoes-1)){
			$underline .= "-";
			$i++;
		}

		$this->col .= $this->quebra().$underline;
		
		#imprime labels
		$this->escreve($this->col);
		$this->escreve($this->quebra());
		

		#zera variaveis
		$this->col  = "";
		$underline = "";
		
	}
	
	function imprimeTabela(){

		//verifica o numero de registros e calcula o numero de paginas
		$cont = mysql_num_rows($this->consulta);
		$this->nPag = number_format(($cont / $this->numRegFolha),0);
		$nPagAtual = 1;
		//$this->head($nPagAtual);  
		
		$iLinhaAux = $this->numRegFolha;
		while($r = mysql_fetch_array($this->consulta)){
			$totalColunas = count($r)/2;
			if($iLinhaAux==$this->numRegFolha){
				$iLinhaAux = 0;
				$this->nPag += 1;
				$this->head($nPagAtual);  
				$this->imprimeLabels();	
				$nPagAtual++;		
			}
			$iLinhaAux++;
			$iColuna = 0;
			while($iColuna<$totalColunas){			
				$i = 0;
				$tab = "";
				$campo = "";
				if(strpos($this->topo[$iColuna]['campo'], "*")){
					$matrizColuna = explode("*", $this->topo[$iColuna]['campo']);
					foreach ($matrizColuna as $coluna) {
						if($r[$coluna]!="NULL" and $r[$coluna] != ""){
							$campo .= substr(trim($r[$coluna]),0,$this->topo[$iColuna]['tamanho']);							
						}
					}
					$campo = $this->formataCampo($campo, $this->topo[$iColuna]['formato'], $this->topo[$iColuna]['tamanho']);
					$numChars = strlen(utf8_decode($campo));
					$numTabNecessario = $this->topo[$iColuna]['tamanho']-$numChars + 2;
					while($i<$numTabNecessario){
						$tab .= " ";
						$i++;
						$totalPosicoes++;
					}

					$totalPosicoes += $numChars;								
					$this->escreve($campo.$tab);
					$tab = "";
					$campo = "";
				} elseif(strpos($this->topo[$iColuna]['campo'], ",")){
					$matrizColuna = explode(",", $this->topo[$iColuna]['campo']);
					foreach ($matrizColuna as $coluna) {
						if($r[$coluna]!="NULL" and $r[$coluna] != ""){
							$campo .= substr(trim($r[$coluna]).", ",0,$this->topo[$iColuna]['tamanho']);							
						}
					}
					$campo = $this->formataCampo($this->retiraUltimaLetra($campo), $this->topo[$iColuna]['formato'], $this->topo[$iColuna]['tamanho']);
					$numChars = strlen(utf8_decode($campo));
					$numTabNecessario = $this->topo[$iColuna]['tamanho']-$numChars + 2;
					while($i<$numTabNecessario){
						$tab .= " ";
						$i++;
						$totalPosicoes++;
					}

					$totalPosicoes += $numChars;								
					$this->escreve($campo.$tab);
					$tab = "";
					$campo = "";
				} elseif(strpos($this->topo[$iColuna]['campo'], "-")){
					$matrizColuna = explode("-", $this->topo[$iColuna]['campo']);
					foreach ($matrizColuna as $coluna) {
						if($r[$coluna]!="NULL" and $r[$coluna] != ""){
							$campo .= substr(trim($r[$coluna])."- ",0,$this->topo[$iColuna]['tamanho']);							
						}
					}
					$campo = $this->formataCampo($this->retiraUltimaLetra($campo), $this->topo[$iColuna]['formato'], $this->topo[$iColuna]['tamanho']);
					$numChars = strlen(utf8_decode($campo));
					$numTabNecessario = $this->topo[$iColuna]['tamanho']-$numChars + 2;
					while($i<$numTabNecessario){
						$tab .= " ";
						$i++;
						$totalPosicoes++;
					}

					$totalPosicoes += $numChars;								
					$this->escreve($campo.$tab);
					$tab = "";
					$campo = "";
				} else {
					$campo = substr(trim($r[$this->topo[$iColuna]['campo']]),0,$this->topo[$iColuna]['tamanho']);
					$campo = $this->formataCampo($campo, $this->topo[$iColuna]['formato'], $this->topo[$iColuna]['tamanho']);
					$numChars = strlen(utf8_decode($campo));

					$numTabNecessario = $this->topo[$iColuna]['tamanho']-$numChars + 2;
					while($i<$numTabNecessario){
						$tab .= " ";
						$i++;
						$totalPosicoes++;
					}
					$totalPosicoes += $numChars;								
					$this->escreve($campo.$tab);
					$tab = "";
					$campo = "";
				}


				//verifica o groupby
//				if($this->topo[$iColuna]['groupBy']==true){
//					$valorCorrenteGB = $campo;
//					
//					if($valorAnteriorGB != $valorCorrenteGB){
//						$this->escreve($this->quebra().$this->quebra().$this->quebra()."       ".$this->topo[$iColuna]['label'].":      ".$valorCorrenteGB.$this->quebra().$this->quebra());
//					}
//					$valorAnteriorGB = $valorCorrenteGB;
//				}
				$iColuna++;
			}
			$this->escreve($this->quebra());
			$iLinha++;
		}		
	}
}

?>
