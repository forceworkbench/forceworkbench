<?php
require_once 'ConnectionConfiguration.php';
require_once 'PartnerConnectionProvider.php';
require_once 'MetadataConnectionProvider.php';
require_once 'ApexConnectionProvider.php';
require_once 'AsyncBulkConnectionProvider.php';
require_once 'RestDataConnectionProvider.php';
require_once 'SfdcVersionsProvider.php';
require_once 'UserInfoProvider.php';
require_once 'DescribeGlobalProvider.php';
require_once 'DescribeSObjectsProvider.php';

class WorkbenchContext {
    // namespace for $_SESSION instance of $this and keyed values in $_REQUEST
    const INSTANCE = "WORKBENCH_CONTEXT";

    // request keys
    const CACHE = "CACHE";
    const ALL_CONNECTIONS = 'ALL_CONNECTIONS';
    const HAS_DEFAULT_OBJECT_CHANGED = "HAS_DEFAULT_OBJECT_CHANGED";
    const REQUEST_START_TIME = "REQUEST_START_TIME";

    // cache keys
    const PARTNER = "PARTNER";
    const METADATA = "METADATA";
    const ASYNC_BULK = "ASYNC_BULK";
    const APEX = "APEX";
    const REST_DATA = "REST_DATA";
    const SFDC_VERSIONS = "SFDC_VERSIONS";
    const USER_INFO = "USER_INFO";
    const DESCRIBE_GLOBAL = "DESCRIBE_GLOBAL";
    const DESCRIBE_SOBJECTS = "DESCRIBE_SOBJECTS";


    // session-based instance fields
    private $connConfig;
    private $cache;
    private $defaultObject;
    private $sfdcUiSidLikelySet;
    private $agreedToTerms;

    /**
     * @static
     * @return bool true if Workbench Context is established
     */
    static function isEstablished() {
        return isset($_SESSION[self::INSTANCE]);
    }

    /**
     * Establishes a new Workbench Context.
     *
     * @static
     * @param ConnectionConfiguration $connConfig
     * @return void
     */
    static function establish(ConnectionConfiguration $connConfig) {
        if (self::isEstablished()) {
            throw new Exception("Workbench session already established. Call get() or release() instead.");
        }

        $_SESSION[self::INSTANCE] = new WorkbenchContext($connConfig);
    }

    /**
     * Gets the current Workbench Context
     *
     * @static
     * @return WorkbenchContext
     */
    static function get() {
        if (isset($_SESSION[self::INSTANCE])) {
            return $_SESSION[self::INSTANCE];
        }

        throw new Exception("Workbench Context not yet established");
    }

    private function __construct(ConnectionConfiguration $connConfig) {
        if ($connConfig->getHost() == null) {
            throw new Exception("Host must be set to establish Workbench Context.");
        }

        if ($connConfig->getApiVersion() == null) {
            throw new Exception("API Version must be set to establish Workbench Context.");
        }

        $this->connConfig = $connConfig;
        $this->initializeCache();
        $this->defaultObject = false;
        $this->defaultObjectChanged = false;
        $this->sfdcUiSidLikelySet = false;
        $this->agreedToTerms = false;
    }

    function login($username, $password, $orgId, $portalId) {
        if ($orgId != null || $portalId != null) {
            $this->getPartnerConnection()->setLoginScopeHeader(new LoginScopeHeader($orgId, $portalId));
        }

        $loginResult = $this->getPartnerConnection()->login($username, $password);
        $this->connConfig->applyLoginResult($loginResult);
        unset($_REQUEST[self::INSTANCE][self::ALL_CONNECTIONS]);
    }

    function release() {
        unset($_SESSION[self::INSTANCE]);
        unset($_REQUEST[self::INSTANCE]);
    }

    function getConnConfig() {
        return $this->connConfig;
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
        unset($_REQUEST[self::INSTANCE][self::ALL_CONNECTIONS]);
    }

    /**
     * @param  $minVersion
     * @return bool
     */
    function isApiVersionAtLeast($minVersion) {
        return $this->getApiVersion() >= $minVersion;
    }

    function getApiVersion() {
        return $this->connConfig->getApiVersion();
    }

    function isSecure() {
        return $this->connConfig->isSecure();
    }

    // TODO: if this becomes too many things, should we make the context observable?
    function beginRequestHook() {
        if (isset($_REQUEST[self::INSTANCE][self::REQUEST_START_TIME])) {
            throw new Exception("beginRequestHook() should not be called more than once per request");
        }
        $_REQUEST[self::INSTANCE][self::REQUEST_START_TIME] = microtime(true);

        if (isset($_REQUEST['default_object'])) {
            $this->setDefaultObject($_REQUEST['default_object']);
        }
    }

    function isRequestStartTimeSet() {
        return isset($_REQUEST[self::INSTANCE][self::REQUEST_START_TIME]);
    }

    function getRequestProcessingTime() {
        return microtime(true) - $_REQUEST[self::INSTANCE][self::REQUEST_START_TIME];
    }

    function setDefaultObject($defaultObject) {
        if ($defaultObject != $this->defaultObject) {
            $_REQUEST[self::INSTANCE][self::HAS_DEFAULT_OBJECT_CHANGED] = true;
        }

        return $this->defaultObject = $defaultObject;
    }

    function getDefaultObject() {
        return $this->defaultObject;
    }

    function hasDefaultObjectChanged() {
        return isset($_REQUEST[self::INSTANCE][self::HAS_DEFAULT_OBJECT_CHANGED])
               && $_REQUEST[self::INSTANCE][self::HAS_DEFAULT_OBJECT_CHANGED];
    }

    function getObjectTypeByKeyPrefixOrId($keyPrefixOrId) {
        $keyPrefix = substr($keyPrefixOrId, 0, 3);
        $describeGlobal = $this->describeGlobal();
        return isset($describeGlobal->byKeyPrefix[$keyPrefix])
                ? $describeGlobal->byKeyPrefix[$keyPrefix]
                : null;
    }

    function setIsUiSessionLikelySet($sfdcUiSidLikelySet) {
        $this->sfdcUiSidLikelySet = $sfdcUiSidLikelySet;
    }

    function agreeToTerms() {
        $this->agreedToTerms = true;
    }

    function hasAgreedToTerms() {
        return $this->agreedToTerms;
    }

    /**
     * @return bool
     */
    function isUiSessionLikelySet() {
        return $this->sfdcUiSidLikelySet;
    }

    // CACHING & CONNECTIONS

    private function initializeCache() {
        $this->cache[self::PARTNER] = new PartnerConnectionProvider(self::PARTNER);
        $this->cache[self::METADATA] = new MetadataConnectionProvider(self::METADATA);
        $this->cache[self::APEX] = new ApexConnectionProvider(self::APEX);
        $this->cache[self::ASYNC_BULK] = new AsyncBulkConnectionProvider(self::ASYNC_BULK);
        $this->cache[self::REST_DATA] = new RestDataConnectionProvider(self::REST_DATA);
        $this->cache[self::USER_INFO] = new UserInfoProvider(self::USER_INFO);
        $this->cache[self::SFDC_VERSIONS] = new CurrentAppVersionProvider(self::SFDC_VERSIONS);
        $this->cache[self::DESCRIBE_GLOBAL] = new DescribeGlobalProvider(self::DESCRIBE_GLOBAL);
        $this->cache[self::DESCRIBE_SOBJECTS] = new DescribeSObjectsProvider(self::DESCRIBE_SOBJECTS);
    }

    function clearCache() {
        $this->initializeCache();
    }

    private function &getCacheableValue($cacheKey, $args = null) {
        return $this->cache[$cacheKey]->get($args);
    }

    /**
     * @param  $type
     * @return AbstractConnectionProvider
     */
    private function getConnection($type) {
        return $this->getCacheableValue($type, $this->connConfig);
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

    function getSfdcVersions() {
        return $this->getCacheableValue(self::SFDC_VERSIONS);
    }

    function getCurrentSfdcVersion() {
        return end($this->getCacheableValue(self::SFDC_VERSIONS));
    }

    function getUserInfo() {
        return $this->getCacheableValue(self::USER_INFO);
    }

    function describeGlobal() {
        return $this->getCacheableValue(self::DESCRIBE_GLOBAL);
    }

    function describeSObjects($sObjectTypes) {
        return $this->getCacheableValue(self::DESCRIBE_SOBJECTS, $sObjectTypes);
    }
}

?>
