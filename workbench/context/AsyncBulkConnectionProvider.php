<?php
require_once "context/AbstractConnectionProvider.php";
require_once "bulkclient/BulkApiClient.php";

class AsyncBulkConnectionProvider extends AbstractConnectionProvider {
    function establish(ConnectionConfiguration $connConfig) {
        $asyncConnection = new BulkApiClient($this->buildEndpoint($connConfig), $connConfig->getSessionId());
        $asyncConnection->setCompressionEnabled(WorkbenchConfig::get()->value("enableGzip"));
        $asyncConnection->setIncludeSessionCookie(WorkbenchConfig::get()->value("includeSessionCookie"));
        $asyncConnection->setUserAgent(getWorkbenchUserAgent());
        $asyncConnection->setExternalLogReference($_SESSION['restDebugLog']); //TODO: maybe replace w/ its own log?? //TODO: move into ctx
        $asyncConnection->setLoggingEnabled(WorkbenchConfig::get()->value("debug"));
        $asyncConnection->setProxySettings(getProxySettings());

        return $asyncConnection;
    }


    protected function getEndpointType() {
        return "async";
    }
}

?>
