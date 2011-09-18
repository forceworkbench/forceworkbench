<?php
require_once "context/AbstractConnectionProvider.php";
require_once "bulkclient/BulkApiClient.php";

class AsyncBulkConnectionProvider extends AbstractConnectionProvider {
    function establish(ConnectionConfiguration $connConfig) {
        $asyncConnection = new BulkApiClient($this->buildEndpoint($connConfig), $connConfig->getSessionId());
        $asyncConnection->setCompressionEnabled(getConfig("enableGzip"));
        $asyncConnection->setUserAgent(getWorkbenchUserAgent());
        $asyncConnection->setExternalLogReference($_SESSION['restDebugLog']); //TODO: maybe replace w/ its own log?? //TODO: move into ctx
        $asyncConnection->setLoggingEnabled(getConfig("debug"));
        $asyncConnection->setProxySettings(getProxySettings());

        return $asyncConnection;
    }


    protected function getEndpointType() {
        return "async";
    }
}

?>
