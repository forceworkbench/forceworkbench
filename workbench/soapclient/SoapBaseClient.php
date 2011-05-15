<?php
abstract class SoapBaseClient {
    public $sforce;
    protected $sessionId;
    protected $location;

    public function __construct($sessionId, $clientId, $endpoint, $wsdlPath) {

        $_SERVER['HTTP_USER_AGENT'] = getWorkbenchUserAgent();

        $soapClientArray = array();
        $soapClientArray['trace'] = 1;
        $soapClientArray['encoding'] = 'utf-8';

        //set compression settings
        if (getConfig("enableGzip") && phpversion() > '5.1.2') {
            $soapClientArray['compression'] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 1;
        }

        //set proxy settings
        if (getConfig("proxyEnabled") == true) {
            $proxySettings = array();
            $proxySettings['proxy_host'] = getConfig("proxyHost");
            $proxySettings['proxy_port'] = (int)getConfig("proxyPort"); // Use an integer, not a string
            $proxySettings['proxy_login'] = getConfig("proxyUsername");
            $proxySettings['proxy_password'] = getConfig("proxyPassword");

            $soapClientArray = array_merge($soapClientArray, $proxySettings);
        }

        $this->sforce = new SoapClient($wsdlPath, $soapClientArray);

        //start to set headers
        $headerArray = array();

        //set session header
        $sessionVar = array('sessionId' => new SoapVar($sessionId, XSD_STRING));
        $headerBody = new SoapVar($sessionVar, SOAP_ENC_OBJECT);
        $headerArray[] = new SoapHeader($this->getNamespace(), 'SessionHeader', $headerBody, false);

        //set call options header
        if ($clientId != null) {
            $clientBody = array('client' => new SoapVar(getConfig("callOptions_client"), XSD_STRING));
            $callOptionsHeader = new SoapHeader($this->getNamespace(), 'CallOptions', $clientBody, false);
            $headerArray[] = $callOptionsHeader;
        }

        //set allowFieldTruncationHeader header
        if (getConfig("allowFieldTruncationHeader_allowFieldTruncation")) {
            $allowFieldTruncationBody = array('allowFieldTruncation' => new SoapVar(getConfig("allowFieldTruncationHeader_allowFieldTruncation"), XSD_BOOLEAN));
            $allowFieldTruncationHeader = new SoapHeader($this->getNamespace(), 'AllowFieldTruncationHeader', $allowFieldTruncationBody, false);
            $headerArray[] = $allowFieldTruncationHeader;
        }

        $this->sforce->__setSoapHeaders($headerArray);
        $this->sforce->__setLocation($endpoint);

        return $this->sforce;
    }

    abstract protected function getNamespace();

    public function setDebugLevels($logCategory, $logCategoryLevel) {
        $logInfoComp = array(
                'category' => new SoapVar($logCategory, XSD_STRING),
                'level' => new SoapVar($logCategoryLevel, XSD_STRING)
        );

        $logInfoVar = array(
                'categories' => new SoapVar($logInfoComp, SOAP_ENC_OBJECT)
        );

        $debugBody = new SoapVar($logInfoVar, SOAP_ENC_OBJECT);

        $this->sforce->__default_headers[] = new SoapHeader($this->getNamespace(), 'DebuggingHeader', $debugBody, false);
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
