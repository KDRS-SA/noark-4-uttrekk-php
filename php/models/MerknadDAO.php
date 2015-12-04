<?php

require_once 'models/Merknad.php';
require_once 'models/Noark4Base.php';

class MerknadDAO extends Noark4Base {
	
	public function __construct ($srcBase, $uttrekksBase, $SRC_TABLE_NAME, $logger) {
                parent::__construct (Constants::getXMLFilename('MERKNAD'), $srcBase, $uttrekksBase, $SRC_TABLE_NAME, $logger);
		$this->selectQuery = "select REGISTER, NOKKEL, KOMNR, ITYPE, UNNTOFF, GRUPPE, GRUPPEID, KOMMENTAR, SBHID, PVGAV, ENDRDATO from " . $SRC_TABLE_NAME . ""; 
	} 
	
	function processTable () {
		$this->srcBase->createAndExecuteQuery ($this->selectQuery);
		
		while (($result = $this->srcBase->getQueryResult ($this->selectQuery))) {
				$merknad = new Merknad();
				$merknad->ME_ID = $result['NOKKEL'] . $result['KOMNR'];

				if (!strcmp($result['REGISTER'], "S")) {
					$merknad->ME_SAID = $result['NOKKEL'];	
				}
				else if (!strcmp($result['REGISTER'], "J")) {
					$merknad->ME_JPID = $result['NOKKEL'];	
				}
				else if (!strcmp($result['REGISTER'], "D")) {
					$merknad->ME_DOKID = $result['NOKKEL'];	
				}
				else {
				 //die ("Unknown REGISTER value (" . $result['REGISTER'] . ") in " . $SRC_TABLE_NAME);
				}

				$merknad->ME_RNR = $result['KOMNR'];
				$merknad->ME_ITYPE = $result['ITYPE'];
				$merknad->ME_TGKODE = $result['UNNTOFF'];
				$merknad->ME_TGGRUPPE = $result['GRUPPEID'];
				$merknad->ME_TEKST = $result['KOMMENTAR'];
				$merknad->ME_REGAV = $result['SBHID'];
		
				if (isset($result['PVGAV']) == true) {
					$merknad->ME_PVGAV = $result['PVGAV'];
				}
				else {
					$merknad->ME_PVGAV = '0'; // Not something in ESA, so this should be OK
					$this->logger->log($this->XMLfilename, "MERKNAD ME.ID (" . $merknad->ME_ID . ") has null for  ME_PVGAV. Setting it to 0", Constants::LOG_WARNING);
					$this->warningIssued = true;
				}
				$merknad->ME_REGDATO = Utility::fixDateFormat($result['ENDRDATO']);
				
				$this->writeToDestination($merknad);
		}
		$this->srcBase->endQuery($this->selectQuery);
	}
	


	function processMerknadFromSakOrJP($register, $nokkel, $untoff, $gruppeId, $merknadTekst, $sbhId) {
		$merknad = new Merknad();
		$merknad->ME_ID = $nokkel . "0";

		if (!strcmp($register, "S")) {
			$merknad->ME_SAID = $nokkel;	
		}
		else if (!strcmp($register, "J")) {
			$merknad->ME_JPID = $nokkel;	
		}		
		else {
			die ("Unknown REGISTER value (" . $register. ") from SAK/JP" . $register);
		}

		$merknad->ME_RNR = "0";
		$merknad->ME_ITYPE = "MS"; // Hardcoded from observing values in INFOTYPE
		$merknad->ME_TGKODE = $untoff;
		$merknad->ME_TGGRUPPE = $gruppeId;
		$merknad->ME_TEKST = $merknadTekst;
		$merknad->ME_REGAV = $sbhId;
		$merknad->ME_PVGAV = '0'; // Not something in ESA, so this should be OK
		//$merknad->ME_REGDATO = Utility::fixDateFormat($regDato);

		$this->writeToDestination($merknad);
	}

	function writeToDestination($data) {
		
		$sqlInsertStatement = "INSERT INTO MERKNAD (ME_ID, ME_SAID, ME_JPID, ME_DOKID, ME_RNR, ME_ITYPE, ME_TGKODE, ME_TGGRUPPE, ME_TEKST, ME_REGAV, ME_PVGAV, ME_REGDATO) VALUES (";
	
		$sqlInsertStatement .= "'" . $data->ME_ID . "', ";						
		$sqlInsertStatement .= "'" . $data->ME_SAID . "', ";
		$sqlInsertStatement .= "'" . $data->ME_JPID . "', ";
		$sqlInsertStatement .= "'" . $data->ME_DOKID . "', ";
		$sqlInsertStatement .= "'" . $data->ME_RNR. "', ";						
		$sqlInsertStatement .= "'" . $data->ME_ITYPE . "', ";
		$sqlInsertStatement .= "'" . $data->ME_TGKODE . "', ";
		$sqlInsertStatement .= "'" . $data->ME_TGGRUPPE . "', ";						
		$sqlInsertStatement .= "'" . mysql_real_escape_string($data->ME_TEKST) . "', ";
		$sqlInsertStatement .= "'" . $data->ME_REGAV . "', ";
		$sqlInsertStatement .= "'" . $data->ME_PVGAV . "', ";								
		$sqlInsertStatement .= "'" . $data->ME_REGDATO . "'";			
	
		$sqlInsertStatement.= ");";
		
		$this->uttrekksBase->executeStatement($sqlInsertStatement);

    }

  function createXML($extractor) {    
    	$sqlQuery = "SELECT * FROM MERKNAD";
    	$mapping = array ('idColumn' => 'me_id', 
  				'rootTag' => 'MERKNAD.TAB',	
			    		'rowTag' => 'MERKNAD',
  						'encoder' => 'utf8_decode',
  						'elements' => array(
							'ME.ID' => 'me_id',
							'ME.SAID' => 'me_said',
							'ME.JPID' => 'me_jpid',
							'ME.RNR' => 'me_rnr',
							'ME.ITYPE' => 'me_itype',
    							'ME.TGKODE' => 'me_tgkode',
							'ME.TGGRUPPE' => 'me_tggruppe',
							'ME.REGDATO' => 'me_regdato',
							'ME.REGAV' => 'me_regav',
							'ME.PVGAV' => 'me_pvgav',
							'ME.TEKST' => 'me_tekst'
  							) 
						) ;
		
    	$extractor->extract($sqlQuery, $mapping, $this->XMLfilename, "file");
    	
    }    
 }
			