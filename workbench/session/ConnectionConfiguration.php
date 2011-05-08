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
        $this->apiVersion = floor($apiVersion) == $apiVersion ? $apiVersion . ".0" : $apiVersion;
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

    function getApiVersion() {
        return $this->apiVersion;
    }
}

?>
