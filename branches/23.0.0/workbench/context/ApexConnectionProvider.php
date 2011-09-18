<?php
require_once "context/AbstractSoapConnectionProvider.php";
require_once 'soapclient/SforceApexClient.php';

class ApexConnectionProvider extends AbstractSoapConnectionProvider {
    function establish(ConnectionConfiguration $connConfig) {
        return new SforceApexClient($connConfig->getSessionId(),
                                    $connConfig->getClientId(),
                                    $this->buildEndpoint($connConfig),
                                    $this->buildWsdlPath($connConfig));
    }

    protected function getWsdlType() {
        return "apex";
    }

    protected function getEndpointType() {
        return "Soap/s";
    }

    protected function getMinWsdlVersion() {
        return "14.0";
    }
}

?>
