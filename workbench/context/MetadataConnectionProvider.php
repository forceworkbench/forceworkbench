<?php
require_once "context/AbstractSoapConnectionProvider.php";
require_once 'soapclient/SforceMetadataClient.php';

class MetadataConnectionProvider extends AbstractSoapConnectionProvider {
    function establish(ConnectionConfiguration $connConfig) {
        return new SforceMetadataClient($connConfig->getSessionId(),
                                        $this->buildEndpoint($connConfig),
                                        $this->buildWsdlPath($connConfig));
    }

    function getWsdlType() {
        return "metadata";
    }

    function getEndpointType() {
        return "Soap/m";
    }

    function getMinWsdlVersion() {
        return 19.0; //TODO: does this need to be a string?
    }
}

?>
