<?php
require_once "context/WorkbenchContext.php";
require_once "util/PhpReverseProxy.php";
require_once "session.php";

if (!WorkbenchContext::isEstablished()) {
    httpError("401 Unauthorized", "CometD Proxy only available if Workbench Context has been established.");
    exit;
}

$host = WorkbenchContext::get()->getHost();
$sessionId = WorkbenchContext::get()->getSessionId();
$_COOKIE['sid'] = $sessionId;
session_write_close();

$proxy = new PhpReverseProxy();
$proxy->host = $host;
$proxy->forward_path = "/cometd";
$proxy->proxy_settings = getProxySettings();
$proxy->is_forward_path_static = true;
$proxy->connect();
$proxy->output();
?>
