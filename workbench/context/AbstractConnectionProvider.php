<?php
require_once 'context/ConnectionConfiguration.php';

abstract class AbstractConnectionProvider {

    public abstract function establish(ConnectionConfiguration $connConfig);

    protected abstract function getEndpointType();

    protected function buildEndpoint(ConnectionConfiguration $connConfig) {
        return "http" . ($connConfig->isSecure() ? "s" : "") . "://" .
               $connConfig->getHost() .
               "/services/" . $this->getEndpointType() . "/" .
               $connConfig->getApiVersion();
    }

    // TODO: add getMinSupportedVersion() ?
    // TODO: add AbstractRestConnectionProvider ??
}

?>
