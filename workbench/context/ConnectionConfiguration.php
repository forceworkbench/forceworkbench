<?php
 
class ConnectionConfiguration {
    private $sessionId;
    private $isSecure;
    private $host;
    private $apiVersion;
    private $overriddenClientId;

    function __construct($sessionId, $isSecure, $host, $apiVersion, $overriddenClientId) {
        $this->sessionId = crypto_serialize($sessionId);
        $this->isSecure = $isSecure;
        $this->host = $host;
        $this->setApiVersion($apiVersion);
        $this->overriddenClientId = $overriddenClientId;
    }

    function getSessionId() {
        return crypto_unserialize($this->sessionId);
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

    function getClientId() {
        return isset($this->overriddenClientId) ? $this->overriddenClientId : WorkbenchConfig::get()->value("callOptions_client");
    }

    function applyLoginResult($loginResult) {
        $this->host = parse_url($loginResult->serverUrl, PHP_URL_HOST);
        $port = parse_url($loginResult->serverUrl, PHP_URL_PORT);
        $this->host .= !empty($port) ? ":$port" : "";

        $this->sessionId = crypto_serialize($loginResult->sessionId);
    }

    static function fromUrl($serviceUrl, $sessionId, $clientId) {
        if (preg_match("!http(s?)://(.*)/services/Soap/[uc]/(\d{1,2}\.\d)!", $serviceUrl, $serviceUrlMatches) == 0) {
            throw new WorkbenchHandledException("Invalid Service URL format. Must be formatted as either Partner or Enterprise URL.\n" . $serviceUrl);
        }

        return new ConnectionConfiguration(
                    $sessionId,
                    $serviceUrlMatches[1] == "s", // using HTTPS
                    $serviceUrlMatches[2],        // host
                    $serviceUrlMatches[3],        // API Version
                    $clientId);
    }
}

?>
