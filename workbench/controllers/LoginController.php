<?php

class LoginController {

    private $errors;
    private $loginType;
    private $username;
    private $userRemembered;
    private $subdomain;
    private $apiVersion;
    private $startUrl;

    function __construct() {
        $this->errors = array();

        $this->loginType = isset($_REQUEST['loginType'])
                             ? $_REQUEST['loginType']
                             : ((getConfig("defaultLoginType") == 'Advanced')
                                 ? "adv"
                                 : "std");

        $this->username = isset($_REQUEST['un'])
                             ? $_REQUEST['un']
                             : (isset($_COOKIE['user'])
                                 ? $_COOKIE['user']
                                 : "");

        $this->userRemembered = isset($_COOKIE['user']);

        $this->subdomain = isset($_REQUEST['inst'])
                             ? $_REQUEST['inst']
                             : getConfig("defaultInstance");

        $this->apiVersion = isset($_REQUEST['api'])
                              ? $_REQUEST['api']
                              : getConfig("defaultApiVersion");

        $this->startUrl = isset($_REQUEST['startUrl'])
                              ? $_REQUEST['startUrl']
                              : "select.php";
    }

    public function processRequest() {
        if (getConfig("loginCsrfEnabled")) {
            if (!validateCsrfToken(false)) {
                $this->addError('This login method is not supported.');
                return;
            }
        }

        if (isset($_POST["oauth_Login"]) || isset($_GET["code"])) {
            $this->processLoginOAuth();
            return;
        }

        $pw   = isset($_REQUEST['pw'])  ? $_REQUEST['pw']  : null;
        $sid  = isset($_REQUEST['sid']) ? $_REQUEST['sid'] : null;
        $serverUrl = $this->buildServerUrl();

        // special-cases for UI vs API logins
        if (isset($_POST['uiLogin'])) {
            $this->processRemeberUserCookie();
        } else {
            $_REQUEST['autoLogin'] = 1;
        }

        $this->processLogin($this->username, $pw, $serverUrl, $sid, $this->startUrl);
    }

    public function processRemeberUserCookie() {
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

            if (getConfig("useHTTPS") && !stristr(getConfig("defaultInstance"), 'localhost')) {
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

    private function processLogin($username, $password, $serverUrl, $sessionId, $actionJump) {
        if ($username && $password && $sessionId) {
            $this->addError('Provide only username and password OR session id, but not all three.');
            return;
        }

        //block connections to localhost
        if (stripos($serverUrl,'localhost')) {
            if (isset($GLOBALS['internal']['localhostLoginError'])) {
                $this->addError($GLOBALS['internal']['localhostLoginError']);
            } else {
                $this->addError("Must not connect to 'localhost'");
            }

            return;
        }

        if (WorkbenchContext::isEstablished()) {
            WorkbenchContext::get()->release();
        }

        // TODO: clean up this hackiness due to in-progress context refactoring...
        $savedConfig = $_SESSION['config'];
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['config'] = $savedConfig;

        $overriddenClientId = isset($_REQUEST["clientId"]) ? $_REQUEST["clientId"] : null;
        if ($username && $password && !$sessionId) {
            $orgId = isset($_REQUEST["orgId"]) ? $_REQUEST["orgId"] : getConfig("loginScopeHeader_organizationId");
            $portalId = isset($_REQUEST["portalId"]) ? $_REQUEST["portalId"] : getConfig("loginScopeHeader_portalId");

            WorkbenchContext::establish(ConnectionConfiguration::fromUrl($serverUrl, null, $overriddenClientId));
            try {
                WorkbenchContext::get()->login($username, $password, $orgId, $portalId);
            } catch (Exception $e) {
                WorkbenchContext::get()->release();
                $this->addError($e->getMessage());
                return;
            }
        } else if ($sessionId && $serverUrl && !($username && $password)) {
            if (stristr($serverUrl,'login') || stristr($serverUrl,'www') || stristr($serverUrl,'test') || stristr($serverUrl,'prerellogin')) {
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

        if (isset($_REQUEST['autoLogin'])) {
            $actionJump .= (strpos($actionJump, "?") > -1 ? "&" :  "?") . "autoLogin=1";
            if (isset($_REQUEST['skipVC'])) $actionJump .= "&skipVC=1";
            if (isset($_GET['clientId'])) $_SESSION['tempClientId'] = $_GET['clientId'];
        }

        header("Location: $actionJump");
    }

    private function processLoginOauth() {
        $redirectUri =
            "http" . ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? "s" : "") . "://" .
            $_SERVER['HTTP_HOST'] .
            str_replace('\\', '/', dirname(htmlspecialchars($_SERVER['PHP_SELF']))) .
			(strlen(dirname(htmlspecialchars($_SERVER['PHP_SELF']))) == 1 ? "" : "/") .
            "login.php";

        $auth_url = "https://" . $this->subdomain . ".salesforce.com" .
                    "/services/oauth2/authorize?response_type=code&display=popup&client_id=" .
                    getConfig("oauth2ConsumerKey") . "&redirect_uri=" . urlencode($redirectUri);

        if (isset($_POST["oauth_Login"])) {
            header('Location: ' . $auth_url);
        } else if (isset($_GET['code'])) {
//            if(isset($_SESSION['server'])) $server = $_SESSION['server'];

            $token_url =  "https://" . $this->subdomain . ".salesforce.com" . "/services/oauth2/token";

            $code = $_GET['code'];

            $params = "code=" . $code
                . "&grant_type=authorization_code"
                . "&client_id=" . getConfig("oauth2ConsumerKey")
                . "&client_secret=" . getConfig("oauth2ConsumerSecret")
                . "&redirect_uri=" . urlencode($redirectUri);

            $curl = curl_init($token_url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);                                //TODO: use ca-bundle instead

            $json_response = curl_exec($curl);

            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($status != 200 ) {
                die("Error: call to token URL $token_url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
            }

            curl_close($curl);

            $response = json_decode($json_response, true);

            $access_token = $response['access_token'];
            $instance_url = $response['instance_url'];

            if (!isset($access_token) || $access_token == "") {
                die("Error - access token missing from response!");
            }

            if (!isset($instance_url) || $instance_url == "") {
                die("Error - instance URL missing from response!");
            }

            $this->processLogin(null, null, $instance_url . "/services/Soap/u/22.0", $access_token, "select.php");
        } else {
            throw new Exception("Unknown OAuth state.");
        }
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

    public function getSubdomainSelectOptions() {
        $subdomains = array();
        foreach ($GLOBALS['config']['defaultInstance']['valuesToLabels'] as $subdomain => $info) {
            $subdomains[$subdomain] = $info[0];
        }
        return $subdomains;
    }

    public function getApiVersionSelectOptions() {
        return $GLOBALS['config']['defaultApiVersion']['valuesToLabels'];
    }

    public function getStartUrlSelectOptions() {
        $urls = array();
        $urls["select.php"] = "";
        foreach ($GLOBALS["MENUS"] as $pages) {
            foreach ($pages as $href => $page) {
                if ($page->onMenuSelect) $urls[$href] = $page->title;
            }
        }
        return $urls;
    }

    public function getServerIdMap() {
        $serverIdMap = array();
        foreach ($GLOBALS['config']['defaultInstance']['valuesToLabels'] as $subdomain => $info) {
            if (isset($info[1]) && $info[1] != "") {
                $serverIdMap[$info[1]] = $subdomain;
            }
        }
        return $serverIdMap;
    }

    public function getJsConfig() {
        $jsConfig = array();
        $jsConfig['useHTTPS'] = (bool) getConfig('useHTTPS');
        $jsConfig['customServerUrl'] = isset($_REQUEST['serverUrl']) ? $_REQUEST['serverUrl'] : "";
        $jsConfig['serverIdMap'] = $this->getServerIdMap();

        return json_encode($jsConfig);
    }

    private function addError($error) {
        array_push($this->errors, $error);
    }
}
?>
