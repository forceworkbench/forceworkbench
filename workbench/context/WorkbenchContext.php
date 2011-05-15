<?php
require_once 'ConnectionConfiguration.php';
require_once 'PartnerConnectionProvider.php';
require_once 'MetadataConnectionProvider.php';
require_once 'ApexConnectionProvider.php';
require_once 'AsyncBulkConnectionProvider.php';
require_once 'RestDataConnectionProvider.php';

class WorkbenchContext {
    const INSTANCE    = 'WORKBENCH_CONTEXT';
    const CONNECTIONS = "connections";
    const PARTNER     = "partner";
    const METADATA    = "metadata";
    const ASYNC_BULK  = "async_bulk";
    const APEX        = "apex";
    const REST_DATA   = "rest_data";

    private static $connectionProviders;

    private $connConfig;

    private function __construct(ConnectionConfiguration $connConfig) {
        if ($connConfig->getHost() == null) {
            throw new Exception("Host must set to establish Workbench Context.");
        }

        if ($connConfig->getApiVersion() == null) {
            throw new Exception("API Version must set to establish Workbench Context.");
        }

        $this->connConfig = $connConfig;
    }

    /**
     * @static
     * @return WorkbenchContext
     */
    static function get() {
        if (isset($_SESSION[self::INSTANCE])) {
            return $_SESSION[self::INSTANCE];
        }

        throw new Exception("Workbench Context not yet established");
    }

    /**
     * @static
     * @return bool
     */
    static function isEstablished() {
        return isset($_SESSION[self::INSTANCE]);
    }

    static function establish(ConnectionConfiguration $connConfig) {
        if (self::isEstablished()) {
            throw new Exception("Workbench session already established. Call get() or release() instead.");
        }

        $_SESSION[self::INSTANCE] = new WorkbenchContext($connConfig);
    }

    function login($username, $password, $orgId, $portalId) {
        if ($orgId != null || $portalId != null) {
            $this->getPartnerConnection()->setLoginScopeHeader(new LoginScopeHeader($orgId, $portalId));
        }

        $loginResult = $this->getPartnerConnection()->login($username, $password);
        $this->connConfig->applyLoginResult($loginResult);
    }

    function release() {
        unset($_SESSION[self::INSTANCE]);
        unset($_REQUEST[self::INSTANCE]);
    }

    function isLoggedIn() {
        return $this->connConfig->getSessionId() != null;
    }

    function getSessionId() {
        return $this->connConfig->getSessionId();
    }

    function getHost() {
        return $this->connConfig->getHost();
    }

    function setApiVersion($apiVersion) {
        $this->connConfig->setApiVersion($apiVersion);
    }

    function getApiVersion() {
        return $this->connConfig->getApiVersion();
    }

    function isSecure() {
        return $this->connConfig->isSecure();
    }

    /**
     * @return SforcePartnerClient
     */
    function getPartnerConnection() {
        return $this->getConnection(self::PARTNER);
    }

    /**
     * @return SforceMetadataClient
     */
    function getMetadataConnection() {
        return $this->getConnection(self::METADATA);
    }

    /**
     * @return SforceApexClient
     */
    function getApexConnection() {
        return $this->getConnection(self::APEX);
    }

    /**
     * @return BulkApiClient
     */
    function getAsyncBulkConnection() {
        return $this->getConnection(self::ASYNC_BULK);
    }

    /**
     * @return RestApiClient
     */
    function getRestDataConnection() {
        return $this->getConnection(self::REST_DATA);
    }

    private function getConnection($type) {
        // connections can't be serialized in $_SESSION, so use $_REQUEST
        if (isset($_REQUEST[self::INSTANCE][self::CONNECTIONS][$type])) {
            return $_REQUEST[self::INSTANCE][self::CONNECTIONS][$type];
        }

        // lazily register static connection providers
        if (!isset(self::$connectionProviders)) {
            self::$connectionProviders[self::PARTNER]    = new PartnerConnectionProvider();
            self::$connectionProviders[self::METADATA]   = new MetadataConnectionProvider();
            self::$connectionProviders[self::APEX]       = new ApexConnectionProvider();
            self::$connectionProviders[self::ASYNC_BULK] = new AsyncBulkConnectionProvider();
            self::$connectionProviders[self::REST_DATA]  = new RestDataConnectionProvider();
        }

        // find the requested connection provider
        $provider = self::$connectionProviders[$type];
        if ($provider == null) {
            throw new Exception("Unknown connection type: " . $type);
        }

        // establish and cache the connection
        $_REQUEST[self::INSTANCE][self::CONNECTIONS][$type] = $provider->establish($this->connConfig);;

        // call ourselves to pull from cache
        return $this->getConnection($type);
    }

}

?>
