<?php
require_once "context/WorkbenchContext.php";
require_once "util/PhpReverseProxy.php";
require_once "shared.php";
skipCsrfValidation();

class CometdProxy
{
    private $host;
    private $apiVersion;
    private $forceSSL;
    private $sessionId;
    private $proxy;

    function __construct()
    {
        $this->host = WorkbenchContext::get()->getHost();
        $this->apiVersion = WorkbenchContext::get()->getApiVersion();
        $this->forceSSL = WorkbenchContext::get()->isSecure();
        $this->sessionId = WorkbenchContext::get()->getSessionId();
        session_write_close();

        $this->proxy = $this->newProxy();
        $this->startProxy();
    }

    private function newProxy()
    {
        $forwardPath = "/cometd/$this->apiVersion";
        $proxy = new PhpReverseProxy();
        $proxy->headers[] = "Authorization: OAuth $this->sessionId";
        $proxy->host = $this->host;
        $proxy->forceSSL = $this->forceSSL;
        $proxy->forward_path = $forwardPath;
        $proxy->cookie_allowlist = array("sfdc-stream", "BAYEUX_BROWSER");
        $proxy->proxy_settings = getProxySettings();
        $proxy->is_forward_path_static = true;
        return $proxy;
    }

    private function startProxy()
    {
        $this->proxy->connect();
        $this->proxy->output();
    }

    static function checkPreconditions($cometdReplay = false)
    {
        if (!WorkbenchContext::isEstablished()) {
            httpError("401 Unauthorized", "CometD Proxy only available if Workbench Context has been established.");
            exit;
        }
    }
}

?>
