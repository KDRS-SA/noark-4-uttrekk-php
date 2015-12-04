<?php

require_once 'models/UtvMedl.php';
require_once 'utility/Utility.php';
require_once 'models/Noark4Base.php';

class UtvMedlDAO extends Noark4Base {
	
	public function __construct ($srcBase, $uttrekksBase, $SRC_TABLE_NAME, $logger) {
                parent::__construct (Constants::getXMLFilename('UTVMEDL'), $srcBase, $uttrekksBase, $SRC_TABLE_NAME, $logger);
		$this->selectQuery = "select PNID, UTVID, FUNKFRA, FUNKTIL, FUNKSJON, NR, REPPARTI, VARAPERSON, ADRID, MERKNAD from " . $SRC_TABLE_NAME . "";
	} 

	// NB!!! MERKNAD does not make it to through to te XML file because it's always empty and causes and "error" in arkn4. If you have merknad, switch it on again in
	// noarDatabasestrutut.php
	
	function processTable () {
		$this->srcBase->createAndExecuteQuery ($this->selectQuery);
		
		while (($result = $this->srcBase->getQueryResult ($this->selectQuery))) {
				$utvMedl= new UtvMedl();
				$utvMedl->UM_PNID = $result['PNID'];
				$utvMedl->UM_UTVID = $result['UTVID'];
				$utvMedl->UM_FRADATO = Utility::fixDateFormat($result['FUNKFRA']);

				if (isset($result['FUNKTIL']) == false) {
					$this->logger->log($this->XMLfilename, "UM_PNID (" . $result['PNID'] . ") is mising UM_TILDATO. This is fine accodring to DTD but arkn4 seems to require it. Setting UM_TILDATO to " . Utility::fixDateFormat(Constants::DATE_AUTO_END), Constants::LOG_WARNING);
					$this->warningIssued = true;
					$utvMedl->UM_TILDATO = Utility::fixDateFormat(Constants::DATE_AUTO_END);

				} else {

					$utvMedl->UM_TILDATO = Utility::fixDateFormat($result['FUNKTIL']);
				}


				$utvMedl->UM_FUNK = $result['FUNKSJON'];
				$utvMedl->UM_RANGERING = '0';
				$this->logger->log($this->XMLfilename, "UM.RANGERING has no value, setting to 0 for UM_PNID (" . $utvMedl->UM_PNID . ")", Constants::LOG_WARNING);
				$this->warningIssued = true;
				$utvMedl->UM_SORT = $result['NR'];
				
				$this->logger->log($this->XMLfilename, "Non mandatory field UM.REPRES is not linked on ADRID for UTVID( " . $utvMedl->UM_UTVID ."), PNID (" . $utvMedl->UM_PNID . "). Value from ESA is " . $result['REPPARTI'], Constants::LOG_WARNING);
				$utvMedl->UM_MERKNAD = $result['MERKNAD'];
				$utvMedl->UM_VARAFOR = $result['VARAPERSON'];

				if (is_null($result['VARAPERSON']) == true) {

					$queryGetPNID = "select PNID from dgjhutvmedlem where adrid = '" . $result['ADRID']. "' AND utvid = '" . $utvMedl->UM_UTVID ."'";
					$this->srcBase->createAndExecuteQuery ($queryGetPNID);
					$pnidResult = $this->srcBase->getQueryResult ($queryGetPNID);
					$utvMedl->UM_VARAFOR = $pnidResult['PNID'];
 					$this->srcBase->endQuery($queryGetPNID);
				}
				
				
				$this->writeToDestination($utvMedl);
		}
		$this->srcBase->endQuery($this->selectQuery);
	}
	
	function writeToDestination($data) {
		
		//$sqlInsertStatement = "INSERT INTO UTVMEDL (UM_PNID, UM_UTVID, UM_FRADATO, UM_TILDATO, UM_FUNK, UM_RANGERING, UM_SORT, UM_VARAFOR, UM_MERKNAD) VALUES (";			
		$sqlInsertStatement = "INSERT INTO UTVMEDL (UM_PNID, UM_UTVID, UM_FRADATO, UM_TILDATO, UM_FUNK, UM_RANGERING, UM_SORT, UM_VARAFOR) VALUES (";

		$sqlInsertStatement .= "'" . $data->UM_PNID . "', ";
		$sqlInsertStatement .= "'" . $data->UM_UTVID . "', ";
		$sqlInsertStatement .= "'" . $data->UM_FRADATO . "', ";
		$sqlInsertStatement .= "'" . $data->UM_TILDATO . "', ";
		$sqlInsertStatement .= "'" . $data->UM_FUNK . "', ";
		$sqlInsertStatement .= "'" . $data->UM_RANGERING . "', ";
		$sqlInsertStatement .= "'" . $data->UM_SORT . "', ";		
		$sqlInsertStatement .= "'" . $data->UM_VARAFOR . "'";
//		$sqlInsertStatement .= "'" . $data->UM_MERKNAD. "'";
	
		$sqlInsertStatement .= ");";
		
		$this->uttrekksBase->executeStatement($sqlInsertStatement);
    }


  function createXML($extractor) {    
    	$sqlQuery = "SELECT * FROM UTVMEDL";
    	$mapping = array ('idColumn' => 'um_pnid', 
				'rootTag' => 'UTVMEDLEM.TAB',	
			    		'rowTag' => 'UTVMEDLEM',
  						'encoder' => 'utf8_decode',
  						'elements' => array(
							'UM.UTVID' => 'um_utvid',
							'UM.PNID' => 'um_pnid',
							'UM.RANGERING' => 'um_rangering',
    							'UM.VARAFOR' => 'um_varafor',
							'UM.FUNK' => 'um_funk',
							'UM.FRADATO' => 'um_fradato',
							'UM.TILDATO' => 'um_tildato',
							'UM.SORT' => 'um_sort'
//							'UM.REPRES' => 'um_repres',
//    							'UM.MERKNAD' => 'um_merknad'
  							)
 
						) ;
		
    	$extractor->extract($sqlQuery, $mapping, $this->XMLfilename, "file");
	
    }
 }