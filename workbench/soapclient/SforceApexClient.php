<?php

class SforceApexClient {
  public $sforce;
  protected $sessionId;
  protected $location;

  protected $namespace = 'http://soap.sforce.com/2006/08/apex';

  public function __construct($wsdl, $apexServerUrl, $LogCategory, $LogCategoryLevel) {
	
  	$_SERVER['HTTP_USER_AGENT'] = 'Salesforce/PHPToolkit/1.0';

	$soapClientArray = array();
	$soapClientArray['trace'] = 1;
	$soapClientArray['encoding'] = 'utf-8';

	//set compression settings
		if ($_SESSION['config']['enableGzip'] && phpversion() > '5.1.2') {
			$soapClientArray['compression'] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 1;
		}

	//set proxy settings
		if ($_SESSION['config']['proxyEnabled'] == true) {
	  		$proxySettings = array();
	   		$proxySettings['proxy_host'] = $_SESSION['config']['proxyHost'];
		    $proxySettings['proxy_port'] = (int)$_SESSION['config']['proxyPort']; // Use an integer, not a string
	  	    $proxySettings['proxy_login'] = $_SESSION['config']['proxyUsername'];
	        $proxySettings['proxy_password'] = $_SESSION['config']['proxyPassword'];
	
	  	    $soapClientArray = array_merge($soapClientArray, $proxySettings);
		}
    
    $this->sforce = new SoapClient($wsdl,$soapClientArray);

    //start to set headers
    	$header_array = array();
    
    //set session header
	    $sessionVar = array('sessionId' => new SoapVar($_SESSION['sessionId'], XSD_STRING));
	    $headerBody = new SoapVar($sessionVar, SOAP_ENC_OBJECT);
	    $header_array[] = new SoapHeader($this->namespace, 'SessionHeader', $headerBody, false);
   
    //set debugging header
	    $logInfoComp = array(
	      'category' => new SoapVar($LogCategory, XSD_STRING),
	      'level' => new SoapVar($LogCategoryLevel, XSD_STRING)
	    );
	    
	    $logInfoVar = array(
	    	'categories' => new SoapVar($logInfoComp, SOAP_ENC_OBJECT)
	    );
	
	    $debugBody = new SoapVar($logInfoVar, SOAP_ENC_OBJECT);
	    
	    $header_array[] = new SoapHeader($this->namespace, 'DebuggingHeader', $debugBody, false);
 
    //set call options header    
	    if(isset($_SESSION['config']['callOptions_client'])){
	    	$clientBody = array('client' => new SoapVar($_SESSION['config']['callOptions_client'], XSD_STRING));
	    	$callOptions_header = new SoapHeader($this->namespace, 'CallOptions', $clientBody, false);
	    	$header_array[] = $callOptions_header;
	    } 
	    
    //set allowFieldTruncationHeader header    
	    if(isset($_SESSION['config']['allowFieldTruncationHeader_allowFieldTruncation'])){
	    	$allowFieldTruncationBody = array('allowFieldTruncation' => new SoapVar($_SESSION['config']['allowFieldTruncationHeader_allowFieldTruncation'], XSD_BOOLEAN));
	    	$allowFieldTruncationHeader = new SoapHeader($this->namespace, 'AllowFieldTruncationHeader', $allowFieldTruncationBody, false);
	    	$header_array[] = $allowFieldTruncationHeader;
	    } 
    $this->sforce->__setSoapHeaders($header_array);
    $this->sforce->__setLocation($apexServerUrl);

    return $this->sforce;
  }


  public function executeAnonymous($executeAnonymousBlock) {
  	$executeAnonymousRequest = new stdClass;
  	$executeAnonymousRequest->String = $executeAnonymousBlock;
  	$executeAnonymousResult = $this->sforce->__soapCall("executeAnonymous",array($executeAnonymousRequest),null,null,$outputHeaders);
  	
  	$executeAnonymousResultWithDebugLog = new stdClass;
  	$executeAnonymousResultWithDebugLog->executeAnonymousResult = $executeAnonymousResult->result;
  	foreach($outputHeaders as $outputHeader){
  		$executeAnonymousResultWithDebugLog->debugLog .= $outputHeader->debugLog;
  	}
  	return $executeAnonymousResultWithDebugLog;
  }
 

  public function getLastRequest() {
    return $this->sforce->__getLastRequest();
  }

  public function getLastRequestHeaders() {
    return $this->sforce->__getLastRequestHeaders();
  }

  public function getLastResponse() {
    return $this->sforce->__getLastResponse();
  }

  public function getLastResponseHeaders() {
    return $this->sforce->__getLastResponseHeaders();
  }


}


?>
