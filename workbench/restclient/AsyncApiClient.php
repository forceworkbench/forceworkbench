<?php

class AsyncApiConnection {
	protected $endpoint;
	protected $sessionId;
	protected $userAgent;
	
	private $logs;
	private $loggingEnabled = false;
	
	public function __construct($partnerEndpoint, $sessionId, $userAgent = "WorkbenchAsyncApiClient/2.5.17"){
		$this->endpoint = $this->convertEndpointFromPartner($partnerEndpoint);
		$this->sessionId = $sessionId;
		$this->userAgent = $userAgent;
	}
	
	function convertEndpointFromPartner($partnerEndpoint){
	
		if(preg_match('!/(\d{1,2})\.(\d)!',$partnerEndpoint,$apiVersionMatches) && $apiVersionMatches[1] < 17){
			throw new Exception("Bulk API operations only supported in API 17.0 and higher.");
		}
		
		$count = 1;
		$endpoint = str_replace("Soap/u", "async", $partnerEndpoint, $count);

		//strip off org id hint from end, if present.
		if(strpos($endpoint, "00D")){
			$endpoint = substr($endpoint, 0, strripos($endpoint, "/"));
		}
		
		return $endpoint;
	}
	
	public function createJob(JobInfo $job){
		return new JobInfo($this->post($this->endpoint . "/job", "application/xml", $job->asXml()));
	}
	
	public function updateJob(JobInfo $job){
		return new JobInfo($this->post($this->endpoint . "/job/" . $job->getId(), "application/xml", $job->asXml()));
	}

	public function updateJobState($jobId, $state){
		$job = new JobInfo();
		$job->setId($jobId);
		$job->setState($state);		
		return $this->updateJob($job);
	}
	
	public function getJobInfo($jobId){
		return new JobInfo($this->get($this->endpoint . "/job/" . $jobId));
	}
	
	public function createBatch(JobInfo $job, $data){	
		if($job->getContentType() == "CSV"){
			$contentType = "text/csv";
		} else if ($job->getContentType() == "XML"){
			$contentType = "application/xml";
		}
		
		return new BatchInfo($this->post($this->endpoint . "/job/" . $job->getId() . "/batch", $contentType, $data));		
	}
	
	public function getBatchInfo($jobId, $batchId){
		return new BatchInfo($this->get($this->endpoint . "/job/" . $jobId . "/batch/" . $batchId));
	}
	
	public function getBatchInfos($jobId){
		$batchInfos = array();

		$batchInfoList = new SimpleXMLElement($this->get($this->endpoint . "/job/" . $jobId . "/batch"));		
		foreach($batchInfoList as $batchInfoListItem){
			$batchInfos["$batchInfoListItem->id"] = new BatchInfo($batchInfoListItem->asXml());
		}
		
		return $batchInfos;
	}
	
	public function getBatchResults($jobId, $batchId){
		return $this->get($this->endpoint . "/job/" . $jobId . "/batch/" . $batchId . "/result");
	}
	
	private function http($isPost, $url, $contentType, $data){		
		$this->log("INITIALIZING cURL \n" . print_r(curl_version(), true));
		
		$ch = curl_init();
		
		$httpHeaders = array(
			"X-SFDC-Session: " . $this->sessionId,
			"Accept: application/xml",
			"User-Agent: " . $this->userAgent,
			"Expect:"
		);
		if(isset($contentType)){
			$httpHeaders[] = "Content-Type: $contentType; charset=UTF-8";
		} 

		
		if($isPost) curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
		if($isPost) curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //TODO: use ca-bundle instead
		if($_SESSION['config']['enableGzip']) curl_setopt($ch, CURLOPT_ENCODING, "gzip");  //TODO: add  outbound compression support

		$this->log("REQUEST \n POST: $isPost \n URL: $url \n HTTP HEADERS: \n" . print_r($httpHeaders, true) . " DATA:\n " . htmlentities($data)); 
		
		$chResponse = curl_exec($ch);
		$this->log("RESPONSE \n" . htmlentities($chResponse)); 
		
		if(curl_error($ch) != null){
			$this->log("ERROR \n" . htmlentities(curl_error($ch))); 
			throw new Exception(curl_error($ch));
		}
		
		curl_close($ch);
		
		return $chResponse;
	}
	
	private function get($url){
		return $this->http(false, $url, null, null);
	}
	
	private function post($url, $contentType, $data){
		return $this->http(true, $url, $contentType, $data);
	}
	
	
	//LOGGING FUNCTIONS
	
	public function isLoggingEnabled(){
		return $this->loggingEnabled;
	}
	
	public function setLoggingEnabled($loggingEnabled){
		$this->loggingEnabled = $loggingEnabled;
	}
	
	protected function log($txt){
		if($this->loggingEnabled){ 
			$this->logs .= $txt .= "\n\n";
		}
		return $txt;
	}
	
	public function setExternalLogReference(&$extLogs){
		$this->logs = &$extLogs;
	}
	
	public function getLogs(){
		return $this->logs;
	}
	
	public function clearLogs(){
		$this->logs = null;
	}
}

class JobInfo {	
	private $xml;
	
	public function __construct($xml = null){
		if($xml != null) {
			$this->xml = new SimpleXMLElement($xml);
		} else {
			$this->xml = new SimpleXMLElement("<jobInfo xmlns=\"http://www.force.com/2009/06/asyncapi/dataload\"/>");
			
			//setting writeable fields in their required sequence; otherwise, API can't parse correctly
			//if any of them are still empty after  setting values, we unset them before converting to XML
			$this->xml->id = "";
			$this->xml->operation = "";
			$this->xml->object = "";
			$this->xml->state = "";
			$this->xml->externalIdFieldName = "";
			$this->xml->concurrencyMode = "";
			$this->xml->contentType = "";
			$this->xml->assignmentRuleId = "";
		}
	}
	
	public function asXml(){
		//removing empty fields to allow API to parse correctly
		//two loops are needed to not cause errors
		$emptyFields = array();
		foreach($this->xml as $field=>$value){
			if($value == ""){
				$emptyFields[] = $field;
			}
		}
		foreach($emptyFields as $field){
			unset($this->xml->$field);
		}
		
		return $this->xml->asXML();
	}
	
	//SETTERS
	public function setId($id){
		$this->xml->id = $id;
	}
	
	public function setOpertion($operation){
		$this->xml->operation = $operation;
	}
	
	public function setObject($object){
		$this->xml->object = $object;
	}

	public function setExternalIdFieldName($externalIdFieldName){
		$this->xml->externalIdFieldName = $externalIdFieldName;
	}
	
	public function setAssignmentRuleId($assignmentRuleId){
		$this->xml->assignmentRuleId = $assignmentRuleId;
	}	
	
	public function setState($state){
		$this->xml->state = $state;
	}
	
	public function setConcurrencyMode($concurrencyMode){
		$this->xml->concurrencyMode = $concurrencyMode;
	}
	
	public function setContentType($contentType){
		$this->xml->contentType = $contentType;
	}
	
	//GETTERS
	public function getId(){
		return $this->xml->id;
	}

	public function getOpertion(){
		return $this->xml->operation;
	}

	public function getObject(){
		return $this->xml->object;
	}
	
	public function getExternalIdFieldName(){
		return $this->xml->externalIdFieldName;
	}

	public function getCreatedById(){
		return $this->xml->createdById;
	}

	public function getCreatedDate(){
		return $this->xml->createdDate;
	}

	public function getSystemModstamp(){
		return $this->xml->systemModstamp;
	}

	public function getState(){
		return $this->xml->state;
	}

	public function getStateMessage(){
		return $this->xml->stateMessage;
	}
	
	public function getConcurrencyMode(){
		return $this->xml->concurrencyMode;
	}
	
	public function getContentType(){
		return $this->xml->contentType;
	}

	public function getNumberBatchesQueued(){
		return $this->xml->numberBatchesQueued;
	}

	public function getNumberBatchesInProgress(){
		return $this->xml->numberBatchesInProgress;
	}

	public function getNumberBatchesCompleted(){
		return $this->xml->numberBatchesCompleted;
	}
	
	public function getNumberBatchesFailed(){
		return $this->xml->numberBatchesFailed;
	}
	
	public function getNumberBatchesTotal(){
		return $this->xml->numberBatchesTotal;
	}
	
	public function getNumberRecordsProcessed(){
		return $this->xml->numberRecordsProcessed;
	}
	
	public function getNumberRetries(){
		return $this->xml->numberRetries;
	}
	
	public function getApiVersion(){
		return $this->xml->apiVersion;
	}
	
	public function getExceptionCode(){
		return $this->xml->exceptionCode;
	}
	
	public function getExceptionMessage(){
		return $this->xml->exceptionMessage;
	}
}

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