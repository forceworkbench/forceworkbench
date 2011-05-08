<?php
require_once 'session/ConnectionConfiguration.php';

abstract class AbstractConnectionProvider {
    abstract function establish(ConnectionConfiguration $connConfig);

    abstract function getEndpointType();

    abstract function getWsdlType();

    function buildEndpoint(ConnectionConfiguration $connConfig) {
        return "http" . ($connConfig->isSecure() ? "s" : "") . "://" .
               $connConfig->getHost() .
               "/services/" . $this->getEndpointType() . "/" .
               $connConfig->getApiVersion();
    }

    function buildWsdlPath(ConnectionConfiguration $connConfig) {
        return 'soapclient/sforce.' .
               str_replace(".", "", $connConfig->getApiVersion()) .
               "." . $this->getWsdlType() . ".wsdl";
    }
}

?>
