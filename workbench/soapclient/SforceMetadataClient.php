<?php
require_once ('SforceMetaObject.php');

class SforceMetadataClient {
  public $sforce;
  protected $sessionId;
  protected $location;

  protected $namespace = 'http://soap.sforce.com/2006/04/metadata';

  public function __construct($wsdl, $loginResult, $sforceConn) {

    $soapClientArray = null;
    if (phpversion() > '5.1.2') {
      $soapClientArray = array (
      'encoding' => 'utf-8',
      'trace' => 1,
      'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
      'sessionId' => $loginResult->sessionId
      );
    } else {
      $soapClientArray = array (
      'encoding' => 'utf-8',
      'trace' => 1,
      'sessionId' => $loginResult->sessionId
      );
    }
    $this->sforce = new SoapClient($wsdl,$soapClientArray);
    //$this->sforce->__setSoapHeaders($header_array);


    $sessionVar = array(
      'sessionId' => new SoapVar($loginResult->sessionId, XSD_STRING)
    );

    $headerBody = new SoapVar($sessionVar, SOAP_ENC_OBJECT);

    $session_header = new SoapHeader($this->namespace, 'SessionHeader', $headerBody, false);

    $header_array = array (
    $session_header
    );

    $this->sforce->__setSoapHeaders($header_array);

    $this->sforce->__setLocation($loginResult->metadataServerUrl);
    return $this->sforce;
  }

  /**
   * Specifies the session ID returned from the login server after a successful
   * login.
   */
  protected function _setLoginHeader($loginResult) {
    $this->sessionId = $loginResult->sessionId;
    $this->setSessionHeader($this->sessionId);
    $serverURL = $loginResult->serverUrl;
    $this->setEndPoint($serverURL);
  }

  /**
   * Set the endpoint.
   *
   * @param string $location   Location
   */
  public function setEndpoint($location) {
    $this->location = $location;
    $this->sforce->__setLocation($location);
  }

  /**
   * Set the Session ID
   *
   * @param string $sessionId   Session ID
   */
  public function setSessionHeader($sessionId) {
    $this->sforce->__setSoapHeaders(NULL);
    $session_header = new SoapHeader($this->namespace, 'SessionHeader', array (
    'sessionId' => $sessionId
    ));
    $this->sessionId = $sessionId;
    $header_array = array (
    $session_header
    );
    $this->_setClientId($header_array);
    $this->sforce->__setSoapHeaders($header_array);
  }

  public function create($obj) {
    $encodedObj->metadata = new SoapVar($obj, SOAP_ENC_OBJECT, 'CustomObject', $this->namespace);
     
    return $this->sforce->create($encodedObj);
  }
  
  public function delete($obj) {
    $encodedObj->metadata = new SoapVar($obj, SOAP_ENC_OBJECT, 'CustomObject', $this->namespace);
     
    return $this->sforce->delete($encodedObj);
  }  
  
  public function checkStatus($ids) {
    return $this->sforce->checkStatus($ids);
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
