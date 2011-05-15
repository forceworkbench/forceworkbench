<?php
require_once "context/AbstractSoapConnectionProvider.php";
require_once 'soapclient/SforceMetadataClient.php';

class MetadataConnectionProvider extends AbstractSoapConnectionProvider {
    function establish(ConnectionConfiguration $connConfig) {
        return new SforceMetadataClient($connConfig->getSessionId(),
                                        $connConfig->getClientId(),
                                        $this->buildEndpoint($connConfig),
                                        $this->buildWsdlPath($connConfig));
    }

    protected function getWsdlType() {
        return "metadata";
    }

    protected function getEndpointType() {
        return "Soap/m";
    }

    protected function getMinWsdlVersion() {
        return "19.0";
    }
}

?>
