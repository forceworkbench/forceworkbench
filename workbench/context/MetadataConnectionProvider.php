<?php
require_once "context/AbstractConnectionProvider.php";
require_once 'soapclient/SforceMetadataClient.php';

class MeteadataConnectionProvider extends AbstractConnectionProvider {
    function establish(ConnectionConfiguration $connConfig) {
        $connection =  new SforceMetadataClient();
        return $connection;
    }

    function getWsdlType() {
        return "metadata";
    }

    function getEndpointType() {
        return "Soap/m";
    }
}

?>
