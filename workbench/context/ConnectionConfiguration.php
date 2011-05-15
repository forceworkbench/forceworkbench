<?php
 
class ConnectionConfiguration {
    private $sessionId;
    private $isSecure;
    private $host;
    private $apiVersion;

    function __construct($sessionId, $isSecure, $host, $apiVersion) {
        $this->sessionId = $sessionId;
        $this->isSecure = $isSecure;
        $this->host = $host;
        $this->setApiVersion($apiVersion);
    }

    function getSessionId() {
        return $this->sessionId;
    }

    function isSecure() {
        return $this->isSecure;
    }

    function getHost() {
        return $this->host;
    }

    function setApiVersion($apiVersion) {
        $this->apiVersion = floor($apiVersion) == $apiVersion ? floor($apiVersion) . ".0" : $apiVersion;
    }

    function getApiVersion() {
        return $this->apiVersion;
    }

    static function fromUrl($serviceUrl, $sessionId) {
        if (preg_match("!http(s?)://(.*)/services/Soap/u/(\d{1,2}\.\d)!", $serviceUrl, $serviceUrlMatches) == 0) {
            throw new Exception("Invalid Service URL format: " . $serviceUrl);
        }

        return new ConnectionConfiguration(
                    $sessionId,
                    $serviceUrlMatches[1] == "s", // using HTTPS
                    $serviceUrlMatches[2],        // host
                    $serviceUrlMatches[3]);       // API Version
    }
}

?>
