<?php
require_once "shared.php";
require_once "context/WorkbenchContext.php";
require_once "util/PhpReverseProxy.php";
skipCsrfValidation();
require_once "session.php";

if (!WorkbenchContext::isEstablished()) {
    httpError("401 Unauthorized", "CometD Proxy only available if Workbench Context has been established.");
    exit;
}

// dereference session-based vars so we can close the session before entering the proxy
// this will allow concurrent long requests on the same session to work better
$host = WorkbenchContext::get()->getHost();
$apiVersion = WorkbenchContext::get()->getApiVersion();
$forceSSL = WorkbenchContext::get()->isSecure();
$sessionId = WorkbenchContext::get()->getSessionId();
session_write_close();

$proxy = new PhpReverseProxy();
$proxy->headers[] = "Authorization: OAuth $sessionId";
$proxy->host = $host;
$proxy->forceSSL = $forceSSL;
$proxy->forward_path = "/cometd/$apiVersion";
$proxy->cookie_whitelist = array("sfdc-stream", "BAYEUX_BROWSER");
$proxy->proxy_settings = getProxySettings();
$proxy->is_forward_path_static = true;
$proxy->connect();
$proxy->output();
?>
