<?php

/**
 * PHP BULK API CLIENT 17.0
 * @author Ryan Brainard
 * 
 * BatchInfo.php
 * Represents a Force.com Bulk API 17.0 BatchInfo object. 
 * 
 * For reference, see:
 * http://www.salesforce.com/us/developer/docs/api_asynch/Content/asynch_api_reference_batchinfo.htm
 * 
 */

class BatchInfo {
	
	private $xml;
	
	public function __construct($xml){
		$this->xml = new SimpleXMLElement($xml);
	}

	//GETTERS
	public function getId(){
		return $this->xml->id;
	}

	public function getJobId(){
		return $this->xml->jobId;
	}
	
	public function getState(){
		return $this->xml->state;
	}

	public function getStateMessage(){
		return $this->xml->stateMessage;
	}
	
	public function getCreatedDate(){
		return $this->xml->createdDate;
	}
	
	public function getSystemModstamp(){
		return $this->xml->systemModstamp;
	}
	
	public function getNumberRecordsProcessed(){
		return $this->xml->numberRecordsProcessed;
	}
	
	public function getExceptionCode(){
		return $this->xml->exceptionCode;
	}
	
	public function getExceptionMessage(){
		return $this->xml->exceptionMessage;
	}	
}
?>