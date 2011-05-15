<?php
require_once 'context/ConnectionConfiguration.php';
require_once 'context/CacheableValueProvider.php';

abstract class AbstractConnectionProvider implements CacheableValueProvider {

    function isSerializable() {
        return false;
    }

    function load($connConfig) {
        return $this->establish($connConfig);
    }

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
