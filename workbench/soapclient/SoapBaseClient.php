<?php
abstract class SoapBaseClient {
    public $sforce;
    protected $sessionId;
    protected $location;

    public function __construct($LogCategory = null, $LogCategoryLevel = null) {

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

        $this->sforce = new SoapClient($this->getWsdl(), $soapClientArray);

        //start to set headers
        $headerArray = array();

        //set session header
        $sessionVar = array('sessionId' => new SoapVar($_SESSION['sessionId'], XSD_STRING));
        $headerBody = new SoapVar($sessionVar, SOAP_ENC_OBJECT);
        $headerArray[] = new SoapHeader($this->getNamespace(), 'SessionHeader', $headerBody, false);

        //set debugging header
        if (isset($LogCategory) && isset($LogCategoryLevel)) {
            $logInfoComp = array(
                    'category' => new SoapVar($LogCategory, XSD_STRING),
                    'level' => new SoapVar($LogCategoryLevel, XSD_STRING)
            );
    
            $logInfoVar = array(
                    'categories' => new SoapVar($logInfoComp, SOAP_ENC_OBJECT)
            );
    
            $debugBody = new SoapVar($logInfoVar, SOAP_ENC_OBJECT);
    
            $headerArray[] = new SoapHeader($this->getNamespace(), 'DebuggingHeader', $debugBody, false);
        }
        
        //set call options header    
        if (isset($_SESSION['config']['callOptions_client'])) {
            $clientBody = array('client' => new SoapVar($_SESSION['config']['callOptions_client'], XSD_STRING));
            $callOptionsHeader = new SoapHeader($this->getNamespace(), 'CallOptions', $clientBody, false);
            $headerArray[] = $callOptionsHeader;
        } 

        //set allowFieldTruncationHeader header    
        if (isset($_SESSION['config']['allowFieldTruncationHeader_allowFieldTruncation'])) {
            $allowFieldTruncationBody = array('allowFieldTruncation' => new SoapVar($_SESSION['config']['allowFieldTruncationHeader_allowFieldTruncation'], XSD_BOOLEAN));
            $allowFieldTruncationHeader = new SoapHeader($this->getNamespace(), 'AllowFieldTruncationHeader', $allowFieldTruncationBody, false);
            $headerArray[] = $allowFieldTruncationHeader;
        } 
        $this->sforce->__setSoapHeaders($headerArray);
        $this->sforce->__setLocation($this->getServerUrl());

        return $this->sforce;
    }

    abstract protected function getNamespace();
    
    abstract protected function getServerUrl();

    abstract protected function getWsdl();
    
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
