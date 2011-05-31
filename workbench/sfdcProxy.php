<?php
require_once "context/WorkbenchContext.php";
require_once "streaming/PhpReverseProxy.php";

//require_once "session.php";
//
//if (!WorkbenchContext::isEstablished()) {
//    header('HTTP/1.0 401 Unauthorized');
//    echo "SFDC Proxy only available if Workbench Context has been established.";
//    exit;
//}
//
//$host = WorkbenchContext::get()->getHost();
//$sessionId = WorkbenchContext::get()->getSessionId();
//$_COOKIE['sid'] = $sessionId;
//session_write_close();

$proxy = new PhpReverseProxy();
//$proxy->host = $host;
$proxy->host = "localhost";
$proxy->port = "8080";
$proxy->forward_path = "/dojo-jetty7-primer";


$proxy->connect();
$proxy->output();
?>
