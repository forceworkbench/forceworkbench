<?php
require_once "context/AbstractSoapConnectionProvider.php";
require_once 'soapclient/SforceApexClient.php';

class ApexConnectionProvider extends AbstractSoapConnectionProvider {
    function establish(ConnectionConfiguration $connConfig) {
        return new SforceApexClient($connConfig->getSessionId(),
                                    $this->buildEndpoint($connConfig),
                                    $this->buildWsdlPath($connConfig));
    }

    function getWsdlType() {
        return "apex";
    }

    function getEndpointType() {
        return "Soap/s";
    }

    function getMinWsdlVersion() {
        return 14.0; //TODO: does this need to be a string?
    }
}

?>
