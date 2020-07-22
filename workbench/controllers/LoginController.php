<?php

class LoginController {

    private $errors;
    private $loginType;
    private $username;
    private $userRemembered;
    private $subdomain;
    private $apiVersion;
    private $startUrl;
    private $oauthEnabled;
    private $oauthRequired;
    private $termsRequired;
    private $termsFile;

    function __construct() {
        $this->errors = array();
        if (isset($_REQUEST['error'])) {
            $this->addError($_REQUEST['error'] .
                            (isset($_REQUEST['error_description'])
                                ? (": " . $_REQUEST['error_description'])
                                : ""));
        }

        $this->loginType = isset($_REQUEST['loginType'])
                             ? $_REQUEST['loginType']
                             : WorkbenchConfig::get()->value("defaultLoginType");

        $this->username = isset($_REQUEST['un'])
                             ? $_REQUEST['un']
                             : (isset($_COOKIE['user'])
                                 ? $_COOKIE['user']
                                 : "");

        $this->userRemembered = isset($_COOKIE['user']);

        $this->subdomain = isset($_REQUEST['inst'])
                             ? $_REQUEST['inst']
                             : WorkbenchConfig::get()->value("defaultInstance");

        $this->apiVersion = isset($_REQUEST['api'])
                              ? $_REQUEST['api']
                              : WorkbenchConfig::get()->value("defaultApiVersion");

        $this->startUrl = isset($_REQUEST['startUrl'])
                              ? $_REQUEST['startUrl']
                              : "select.php";

        $this->oauthEnabled = false;
        foreach (WorkbenchConfig::get()->value('oauthConfigs') as $host => $hostInfo) {
            if (!empty($hostInfo["label"]) && !empty($hostInfo["key"]) && !empty($hostInfo["secret"])) {
                $this->oauthEnabled = true;
                break;
            }
        }

        $this->oauthRequired = WorkbenchConfig::get()->value("oauthRequired");
        if ($this->oauthRequired) {
            $this->loginType = "oauth";
        }

        if ($this->oauthRequired && !$this->oauthEnabled) {
            throw new Exception("OAuth is required, but not enabled.");
        }

        $this->termsFile = WorkbenchConfig::get()->value("termsFile");
        if (!empty($this->termsFile)) {
            if (!is_file($this->termsFile)) {
                $termsHttpHeaders = get_headers($this->termsFile);
                if ($termsHttpHeaders === FALSE || strpos($termsHttpHeaders[0], "200 OK") === FALSE) {
                    throw new Exception("Could not find Terms of Service.");
                }
            }

            $this->termsRequired = true;
        }
    }

    public function processRequest() {

        if ((isset($_POST['signed_request']))){
            $this->processSignedRequest($_POST['signed_request']);
            return;
        }

        if (isset($_REQUEST["code"])) {
            if (!isset($_REQUEST['state'])) {
                throw new Exception("Invalid OAuth State");
            }

            $state = json_decode($_REQUEST['state']);

            if (WorkbenchConfig::get()->value("loginCsrfEnabled")) {
                $_REQUEST['CSRF_TOKEN'] = $state->csrfToken;
                validateCsrfToken();
            }

            $this->oauthProcessLogin($_REQUEST["code"], $state->host, $state->apiVersion, $state->startUrl);
            return;
        }

        if (WorkbenchConfig::get()->value("loginCsrfEnabled")) {
            if (!validateCsrfToken(false)) {
                $this->addError('This login method is not supported.');
                return;
            }
        }

        if ($this->termsRequired && !isset($_POST['termsAccepted'])) {
            $this->addError("You must agree to terms of service.");
            return;
        }

        if (isset($_REQUEST['loginType']) && $_REQUEST['loginType'] == "oauth") {
            if (!isset($_POST["oauth_host"]) || !isset($_POST["api"])) {
                throw new Exception("Invalid parameters for Oauth login");
            }

            $state = json_encode(array(
                "host" => $_POST["oauth_host"],
                "apiVersion" => $_POST["oauth_apiVersion"],
                "csrfToken" => getCsrfToken(),
                "startUrl" => $this->startUrl
            ));

            $this->oauthRedirect($_POST["oauth_host"], $state);
        } else {
            $pw   = isset($_REQUEST['pw'])  ? $_REQUEST['pw']  : null;
            $sid  = isset($_REQUEST['sid']) ? $_REQUEST['sid'] : null;
            $serverUrl = $this->buildServerUrl();

            // special-cases for UI vs API logins
            if (isset($_POST['uiLogin'])) {
                $this->processRememberUserCookie();
            } else {
                $_REQUEST['autoLogin'] = 1;
            }

            $this->processLogin($this->username, $pw, $serverUrl, $sid, $this->startUrl);
        }
    }

    private function processSignedRequest($signedRequest) {
        $sep = strpos($signedRequest, '.');
        $encodedSig = substr($signedRequest, 0, $sep);
        $encodedEnv = substr($signedRequest, $sep + 1);

        $req = json_decode(base64_decode($encodedEnv));

        $clientId = $req->client->clientId;
        $oauthConfig = $this->findOAuthConfigByClientId($clientId);
        $secret = $oauthConfig['secret'];

        $calcedSig = base64_encode(hash_hmac("sha256", $encodedEnv, $secret, true));
        if ($calcedSig != $encodedSig) {
            throw new WorkbenchAuthenticationException("Signed request authentication failed");
        }

        $this->processLogin(null, null, $req->client->instanceUrl . $req->context->links->partnerUrl, $req->client->oauthToken, "select.php");
    }

    private function findOAuthConfigByClientId($clientId) {
        foreach (WorkbenchConfig::get()->value("oauthConfigs") as $oauthConfig) {
            if (isset($oauthConfig['key']) && $oauthConfig['key'] == $clientId) {
                return $oauthConfig;
            }
        }

        throw new Exception("Unknown OAuth Client ID");
    }

    public function processRememberUserCookie() {
        if (isset($_POST['rememberUser']) && $_POST['rememberUser'] == 'on') {
            setcookie('user', $this->username, time() + 60 * 60 * 24 * 7);
            $this->userRemembered = true;
        } else {
            setcookie('user', NULL, time() - 3600);
            $this->userRemembered = false;
        }
    }

    private function buildServerUrl() {
        // constructed on client side for UI logins -- take as is
        if (isset($_REQUEST['serverUrl'])) {
            return $_REQUEST['serverUrl'];
        }

        // API clients can provide just prefix or pieces of serverUrl, so build here
        $serverUrl = "";

        if (isset($_REQUEST['serverUrlPrefix'])) {
            $serverUrl .= $_REQUEST['serverUrlPrefix'];
        } else {
            $serverUrl .= "http";

            if (WorkbenchConfig::get()->value("useHTTPS") && !stristr(WorkbenchConfig::get()->value("defaultInstance"), 'localhost')) {
                $serverUrl .= "s";
            }

            $serverUrl .= "://" . $this->subdomain . ".salesforce.com";

            if (isset($_REQUEST['port'])) {
                $serverUrl .= ":" . $_REQUEST['port'];
            }
        }

        $serverUrl .= "/services/Soap/u/" . $this->apiVersion;
        return $serverUrl;
    }

    private function isAllowedHost($serverUrl) {
        $domainAllowlist = array(
            'salesforce\.com',
            'vpod\.t\.force\.com',
            'cloudforce\.com'
        );
        foreach ($domainAllowlist as $w) {
            if (preg_match('/^https?\:\/\/[\w\.\-_]+\.' . $w . '/', $serverUrl)) {
                return true;
            }
        }
        return false;
    }

    private function processLogin($username, $password, $serverUrl, $sessionId, $actionJump) {
        if ($username && $password && $sessionId) {
            $this->addError('Provide only username and password OR session id, but not all three.');
            return;
        }

        //block connections to non-sfdc domains
        if (!$this->isAllowedHost($serverUrl)) {
            $this->addError("Host must be a Salesforce domain");
            return;
        }

        if (WorkbenchContext::isEstablished()) {
            // cache clearing shouldn't be needed since we're releasing on the next line,
            // but doing it just in case someone puts a cache key outside the WbCtx scope
            WorkbenchContext::get()->clearCache();
            WorkbenchContext::get()->release();
        }

        // TODO: clean up this hackiness due to in-progress context refactoring...
        $savedOauthConfig = isset($_SESSION['oauth']) ? $_SESSION['oauth'] : null;
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id();
        $_SESSION['oauth'] = $savedOauthConfig;

        $overriddenClientId = isset($_REQUEST["clientId"]) ? $_REQUEST["clientId"] : null;
        if ($username && $password && !$sessionId) {
             if ($this->oauthRequired) {
                throw new WorkbenchHandledException("OAuth login is required");
            }

            $orgId = isset($_REQUEST["orgId"]) ? $_REQUEST["orgId"] : WorkbenchConfig::get()->value("loginScopeHeader_organizationId");
            $portalId = isset($_REQUEST["portalId"]) ? $_REQUEST["portalId"] : WorkbenchConfig::get()->value("loginScopeHeader_portalId");

            WorkbenchContext::establish(ConnectionConfiguration::fromUrl($serverUrl, null, $overriddenClientId));
            try {
                WorkbenchContext::get()->login($username, $password, $orgId, $portalId);
            } catch (Exception $e) {
                WorkbenchContext::get()->release();
                $this->addError($e->getMessage());
                return;
            }
        } else if ($sessionId && $serverUrl && !($username && $password)) {
            $serverUrlHost = parse_url($serverUrl, PHP_URL_HOST);
            $loginHosts = array("login.salesforce.com", "test.salesforce.com", "prerellogin.pre.salesforce.com");
            if (in_array($serverUrlHost, $loginHosts)) {
                $this->addError('Must not connect to login server (www, login, test, or prerellogin) if providing a session id. ' .
                               'Choose your specific Salesforce instance on the QuickSelect menu when using a session id; ' .
                               'otherwise, provide a username and password and choose the appropriate a login server.');
                return;
            }

            WorkbenchContext::establish(ConnectionConfiguration::fromUrl($serverUrl, $sessionId, $overriddenClientId));
            WorkbenchContext::get()->setIsUiSessionLikelySet(true);
        } else {
            $this->addError('Invalid login parameters.');
            return;
        }

        // todo: put in WbCtx?
        if (stripos(WorkbenchContext::get()->getHost(),'localhost')) {
            if (isset($GLOBALS['internal']['localhostLoginRedirectError'])) {
                $this->addError($GLOBALS['internal']['localhostLoginRedirectError']);
            } else {
                $this->addError("Must not connect to 'localhost'");
            }

            return;
        }

        if (isset($_POST['termsAccepted'])) {
            WorkbenchContext::get()->agreeToTerms();
        }

        // test the connection and prime the UserInfo cache
        // exceptions will be caught by top-level handler
        $userInfo = WorkbenchContext::get()->getUserInfo();

        // do org id allowlist/blocklisting
        $orgId15 = substr($userInfo->organizationId,0,15);
        $orgIdAllowList = array_map('trim',explode(",",WorkbenchConfig::get()->value("orgIdAllowList")));
        $orgIdBlockList = array_map('trim',explode(",",WorkbenchConfig::get()->value("orgIdBlockList")));
        $isAllowed = true;
        foreach ($orgIdAllowList as $allowedOrgId) {
            if ($allowedOrgId === "") {
                continue;
            } else if ($orgId15 === substr($allowedOrgId,0,15)) {
                $isAllowed = true;
                break;
            } else {
                // there is something on the Allowlist that's not us
                // disallow and keep looking until we find our org id
                $isAllowed = false;
            }
        }

        foreach ($orgIdBlockList as $disallowedOrgId) {
            if ($orgId15 ===  substr($disallowedOrgId,0,15)) {
                $isAllowed = false;
                break;
            }
        }

        if (!$isAllowed) {
            throw new WorkbenchAuthenticationException("Requests for organization $orgId15 are not allowed");
        }


        if (isset($_REQUEST['autoLogin'])) {
            $actionJump .= (strpos($actionJump, "?") > -1 ? "&" :  "?") . "autoLogin=1";
            if (isset($_REQUEST['skipVC'])) $actionJump .= "&skipVC=1";
            if (isset($_GET['clientId'])) $_SESSION['tempClientId'] = $_GET['clientId'];
        }

        header("Location: $actionJump");
    }

    private function oauthRedirect($hostName, $state) {
        if (!$this->oauthEnabled) {
            throw new Exception("OAuth not enabled");
        }

        $oauthConfigs = WorkbenchConfig::get()->value("oauthConfigs");
        $authUrl = "https://" . $hostName .
                    "/services/oauth2/authorize?response_type=code&display=popup".
                    "&client_id=" . urlencode($oauthConfigs[$hostName]["key"]) .
                    "&redirect_uri=" . urlencode($this->oauthBuildRedirectUrl()) .
                    "&state=" . urlencode($state);

        header('Location: ' . $authUrl);
    }

    private function oauthProcessLogin($code, $hostName, $apiVersion, $startUrl) {
        if (!$this->oauthEnabled) {
            throw new Exception("OAuth not enabled");
        }

        // we set this again below to the real value returned,
        // but in case it fails prior, need to set for logout iframe hack
        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            $_SESSION['oauth']['serverUrlPrefix'] = "https://" . parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        }

        $oauthConfigs = WorkbenchConfig::get()->value("oauthConfigs");

        $tokenUrl =  "https://" . $hostName . "/services/oauth2/token";

        if (!isset($oauthConfigs[$hostName]['key']) || !isset($oauthConfigs[$hostName]['secret'])) {
            throw new Exception("Misconfigured OAuth Host");
        }

        $params = "code=" . $code
                  . "&grant_type=authorization_code"
                  . "&client_id=" . $oauthConfigs[$hostName]['key']
                  . "&client_secret=" . $oauthConfigs[$hostName]['secret']
                  . "&redirect_uri=" . urlencode($this->oauthBuildRedirectUrl());

        $curl = curl_init($tokenUrl);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);  //TODO: use ca-bundle instead
        curl_setopt($curl, CURLOPT_SSLVERSION, 6);

        $proxySettings = getProxySettings();
        if ($proxySettings != null) {
            curl_setopt($curl, CURLOPT_PROXY, $proxySettings["proxy_host"]);
            curl_setopt($curl, CURLOPT_PROXYPORT, $proxySettings["proxy_port"]);
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxySettings["proxy_username"] . ":" . $proxySettings["proxy_password"]);
        }

        try {
            $json_response = curl_exec($curl);

            if (curl_error($curl) != null) {
                // not printing exception because it could contain the secret
                throw new Exception("Unknown OAuth Error");
            }

            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $response = json_decode($json_response, true);

            curl_close($curl);
        } catch (Exception $e) {
            throw new WorkbenchAuthenticationException("OAuth authentication failed connect to: " . $tokenUrl);
        }

        if (isset($response["error"]) && isset($response["error_description"])) {
            throw new WorkbenchAuthenticationException($response["error"] . ": " . $response["error_description"]);
        } else if ($status != 200) {
            throw new WorkbenchAuthenticationException("Unknown OAuth Error. Status Code: $status");
        }

        $accessToken = $response['access_token'];
        $serverUrlPrefix = $response['instance_url'];
        $_SESSION['oauth']['serverUrlPrefix'] = $serverUrlPrefix;

        if (empty($accessToken)) {
            throw new Exception("OAuth response missing access token");
        }

        if (empty($serverUrlPrefix)) {
            throw new Exception("OAuth response missing instance name");
        }

        $_POST['termsAccepted'] = 1; // re-apply terms acceptance on oauth redirect

        $this->processLogin(null, null, $serverUrlPrefix . "/services/Soap/u/" . $apiVersion, $accessToken, $startUrl);
    }

    private function oauthBuildRedirectUrl() {
        return "http" . (usingSslFromUserToWorkbench() ? "s" : "") ."://" .
                WorkbenchConfig::get()->valueOrElse("oauthRedirectHost", $_SERVER['HTTP_HOST']) .
                str_replace('\\', '/', dirname(htmlspecialchars($_SERVER['PHP_SELF']))) .
                (strlen(dirname(htmlspecialchars($_SERVER['PHP_SELF']))) == 1 ? "" : "/") .
                basename($_SERVER['SCRIPT_NAME']);
    }

    public function isOAuthEnabled() {
        return $this->oauthEnabled;
    }

    public function isOAuthRequired() {
        return $this->oauthRequired;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getLoginType() {
        return $this->loginType;
    }

    public function getUsername() {
        return $this->username;
    }

    public function isUserRemembered() {
        return $this->userRemembered;
    }

    public function getSubdomain() {
        return $this->subdomain;
    }

    public function getApiVersion() {
        return $this->apiVersion;
    }

    public function getStartUrl() {
        return $this->startUrl;
    }

    public function getTermsFile() {
        return $this->termsFile;
    }

    public function getSubdomainSelectOptions() {
        $subdomains = array();
        foreach (WorkbenchConfig::get()->valuesToLabels("defaultInstance") as $subdomain => $info) {
            $subdomains[$subdomain] = $info[0];
        }
        return $subdomains;
    }

    public function getOauthHostSelectOptions() {
        $hosts = array();
        foreach (WorkbenchConfig::get()->value('oauthConfigs') as $host => $hostInfo) {
            if (empty($hostInfo["label"]) || empty($hostInfo["key"]) || empty($hostInfo["secret"])) {
                continue;
            }

            $hosts[$host] = $hostInfo["label"];
        }

        if (array_key_exists("login.salesforce.com", $hosts)) {
            $loginHost = $hosts["login.salesforce.com"];
            unset($hosts["login.salesforce.com"]);
            $hosts = array_unshift_assoc($hosts, "login.salesforce.com", $loginHost);
        }

        return $hosts;
    }

    public function getApiVersionSelectOptions() {
        return WorkbenchConfig::get()->valuesToLabels("defaultApiVersion");
    }

    public function getServerIdMap() {
        $serverIdMap = array();
        foreach (WorkbenchConfig::get()->valuesToLabels("defaultInstance") as $subdomain => $info) {
            if (isset($info[1]) && $info[1] != "") {
                $serverId = $info[1];
                if (strlen($serverId) == 1) {
                    $serverId .= "0";
                }
                $serverIdMap[$serverId] = $subdomain;
            }
        }
        return $serverIdMap;
    }

    public function getJsConfig() {
        $jsConfig = array();
        $jsConfig['useHTTPS'] = (bool) WorkbenchConfig::get()->value('useHTTPS');
        $jsConfig['customServerUrl'] = isset($_REQUEST['serverUrl']) ? $_REQUEST['serverUrl'] : "";
        $jsConfig['serverIdMap'] = $this->getServerIdMap();

        return json_encode($jsConfig);
    }

    private function addError($error) {
        array_push($this->errors, $error);
    }
}
?>
