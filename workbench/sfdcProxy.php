<?php
require_once "context/WorkbenchContext.php";
require_once "cometd-resourses/PhpReverseProxy.php";
require_once "session.php";

$host = WorkbenchContext::get()->getHost();
$sessionId = WorkbenchContext::get()->getSessionId();
session_write_close();

$proxy = new PhpReverseProxy();
$proxy->host = $host;
$_COOKIE['sid'] = $sessionId;
$proxy->connect();
$proxy->output();
?>
