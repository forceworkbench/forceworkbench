<?php
require_once 'context/ConnectionConfiguration.php';

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
               str_replace(".", "", max($this->getMinWsdlVersion(), $connConfig->getApiVersion())) .
               "." . $this->getWsdlType() . ".wsdl";
    }

    function getMinWsdlVersion() {
        return 0;
    }
}

?>
