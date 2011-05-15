<?php
require_once 'ConnectionConfiguration.php';
require_once 'PartnerConnectionProvider.php';
require_once 'MetadataConnectionProvider.php';
require_once 'ApexConnectionProvider.php';
require_once 'AsyncBulkConnectionProvider.php';
require_once 'RestDataConnectionProvider.php';
require_once 'UserInfoProvider.php';

class WorkbenchContext {
    const INSTANCE    = "WORKBENCH_CONTEXT";
    const CACHE       = "CACHE";
    const PARTNER     = "PARTNER";
    const METADATA    = "METADATA";
    const ASYNC_BULK  = "ASYNC_BULK";
    const APEX        = "APEX";
    const REST_DATA   = "REST_DATA";
    const USER_INFO   = "USER_INFO";

    private static $cacheKeys;

    private $connConfig;
    private $cache;

    private function __construct(ConnectionConfiguration $connConfig) {
        if ($connConfig->getHost() == null) {
            throw new Exception("Host must set to establish Workbench Context.");
        }

        if ($connConfig->getApiVersion() == null) {
            throw new Exception("API Version must set to establish Workbench Context.");
        }

        $this->connConfig = $connConfig;
        $this->cache = array();
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

    function clearCache() {
        if (!isset(self::$cacheKeys)) {
            foreach (self::$cacheKeys as $cacheKey => $provider) {
                $cacheLocation =& $this->resolveCacheLocation($provider->getCacheBaseLocation(), $cacheKey);
                unset($cacheLocation);
            }
        }
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

    function getUserInfo() {
        return $this->getCacheableValue(self::USER_INFO);
    }

    private function getConnection($type) {
        return $this->getCacheableValue($type, $this->connConfig);
    }

    private function &getCacheableValue($cacheKey, $args=null) {
        // lazily register static cache keys
        if (!isset(self::$cacheKeys)) {
            self::$cacheKeys[self::PARTNER]    = new PartnerConnectionProvider();
            self::$cacheKeys[self::METADATA]   = new MetadataConnectionProvider();
            self::$cacheKeys[self::APEX]       = new ApexConnectionProvider();
            self::$cacheKeys[self::ASYNC_BULK] = new AsyncBulkConnectionProvider();
            self::$cacheKeys[self::REST_DATA]  = new RestDataConnectionProvider();
            self::$cacheKeys[self::USER_INFO]  = new UserInfoProvider();
        }

        // find the provider for the requested cache key
        $provider = self::$cacheKeys[$cacheKey];
        if ($provider == null) {
            throw new Exception("Unknown cache key: " . $cacheKey);
        }

        $cachedLocation =& $this->resolveCacheLocation($provider->isSerializable(), $cacheKey);
        if (isset($cachedLocation)) {
            return $cachedLocation;
        } else {
            /** @noinspection PhpUnusedLocalVariableInspection */
            $cachedLocation =& $provider->load($args);
        }

        return $this->getCacheableValue($cacheKey, $args);
    }

    private function &resolveCacheLocation($isSerializable, $cacheKey) {
        if ($isSerializable) {
            return $this->cache[$cacheKey];
        } else {
            return $_REQUEST[self::INSTANCE][self::CACHE][$cacheKey];
        }
    }
}

?>
