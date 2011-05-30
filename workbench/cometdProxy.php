<?php
require_once "context/WorkbenchContext.php";
require_once "cometd-resourses/PhpReverseProxy.php";
require_once "session.php";

$proxy = new PhpReverseProxy();
$proxy->host = WorkbenchContext::get()->getHost();
$_COOKIE['sid'] = WorkbenchContext::get()->getSessionId();
$proxy->connect();
$proxy->output();
?>
