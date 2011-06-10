<?php
require_once "context/AbstractConnectionProvider.php";

abstract class AbstractSoapConnectionProvider extends AbstractConnectionProvider {

    protected function buildEndpoint(ConnectionConfiguration $connConfig) {
        return parent::buildEndpoint($connConfig) .
               ($connConfig->getSessionId() != null ? ("/" . substr($connConfig->getSessionId(), 0, 15)) : "");
    }

    protected abstract function getWsdlType();

    protected function buildWsdlPath(ConnectionConfiguration $connConfig) {
        return "soapclient/sforce." .
               str_replace(".", "", max($this->getMinWsdlVersion(), $connConfig->getApiVersion())) .
               "." . $this->getWsdlType() . ".wsdl";
    }

    /**
     * Lowest consecutive WSDL version supported.
     *
     * @return string **Must return number as a string!**
     */
    protected function getMinWsdlVersion() {
        return "0";
    }
}

?>
