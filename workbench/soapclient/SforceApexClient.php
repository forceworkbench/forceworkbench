<?php

class SforceApexClient {
  public $sforce;
  protected $sessionId;
  protected $location;

  protected $namespace = 'http://soap.sforce.com/2006/08/apex';

  public function __construct($wsdl, $sessionId, $apexServerUrl, $LogCategory, $LogCategoryLevel) {
	
  	$_SERVER['HTTP_USER_AGENT'] = 'Salesforce/PHPToolkit/1.0';

	$soapClientArray = array();
	$soapClientArray['trace'] = 1;
	$soapClientArray['encoding'] = 'utf-8';

	if ($_SESSION['config']['enableGzip'] && phpversion() > '5.1.2') {
		$soapClientArray['compression'] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 1;
	}

	if ($_SESSION['config']['proxyEnabled'] == true) {
  		$proxySettings = array();
   		$proxySettings['proxy_host'] = $_SESSION['config']['proxyHost'];
	    $proxySettings['proxy_port'] = (int)$_SESSION['config']['proxyPort']; // Use an integer, not a string
  	    $proxySettings['proxy_login'] = $_SESSION['config']['proxyUsername'];
            $proxySettings['proxy_password'] = $_SESSION['config']['proxyPassword'];

  	    $soapClientArray = array_merge($soapClientArray, $proxySettings);
	}
    
    $this->sforce = new SoapClient($wsdl,$soapClientArray);

    $sessionVar = array(
      'sessionId' => new SoapVar($sessionId, XSD_STRING)
    );

    $headerBody = new SoapVar($sessionVar, SOAP_ENC_OBJECT);

    $session_header = new SoapHeader($this->namespace, 'SessionHeader', $headerBody, false);

    
    
    $logInfoComp = array(
      'category' => new SoapVar($LogCategory, XSD_STRING),
      'level' => new SoapVar($LogCategoryLevel, XSD_STRING)
    );
    
    $logInfoVar = array(
    	'categories' => new SoapVar($logInfoComp, SOAP_ENC_OBJECT)
    );

    $debugBody = new SoapVar($logInfoVar, SOAP_ENC_OBJECT);
    
    $debugging_header = new SoapHeader($this->namespace, 'DebuggingHeader', $debugBody, false);

    
    
    $header_array = array (
	    $session_header,
	    $debugging_header
    );

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
