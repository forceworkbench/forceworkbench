<?php
require_once 'session/ConnectionConfiguration.php';
require_once 'session/PartnerConnectionProvider.php';

class WorkbenchSession {
    const SESSION_INSTANCE = 'WORKBENCH_SESSION';

    const PARTNER     = "partner";
    const METADATA    = "metadata";
    const ASYNC_BULK  = "async_bulk";
    const APEX        = "apex";
    const REST        = "rest";

    private static $connectionProviders;

    private $connConfig;
    private $connections;


    private function __construct(ConnectionConfiguration $connConfig) {
        // register static connection providers
        if (!isset(self::$connectionProviders)) {
            self::$connectionProviders[self::PARTNER] = new PartnerConnectionProvider();
        }

        $this->connConfig = $connConfig;
        $this->connections = array();
    }

    static function get() {
        if (isset($_SESSION[self::SESSION_INSTANCE])) {
            return $_SESSION[self::SESSION_INSTANCE];
        }

        throw new Exception("Workbench session not yet established");
    }

    static function establish(ConnectionConfiguration $connConfig) {
        if (isset($_SESSION[self::SESSION_INSTANCE])) {
            throw new Exception("Workbench session already established. Call get() or release() instead.");
        }

        $_SESSION[self::SESSION_INSTANCE] = new WorkbenchSession($connConfig);
        return $_SESSION[self::SESSION_INSTANCE];
    }

    static function release() {
        unset($_SESSION[self::SESSION_INSTANCE]);
    }

    function getPartnerConnection() {
        return $this->getConnection(self::PARTNER);
    }

    function getSessionId() {
        return $this->connConfig->getSessionId();
    }


    function getApiVersion() {
        return $this->connConfig->getApiVersion();
    }

    private function getConnection($type) {
        if (isset($this->connections[$type])) {
            return $this->connections[$type];
        }

        $provider = self::$connectionProviders[$type];

        if ($provider == null) {
            throw new Exception("Unknown connection type: " . $type);
        }

        $this->connections[$type] = $provider->establish($this->connConfig);
        return $this->connections[$type];
    }

}

?>
