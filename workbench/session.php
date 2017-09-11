<?php
require_once 'config/constants.php';
require_once 'config/WorkbenchConfig.php';
require_once 'shared.php';
require_once 'context/WorkbenchContext.php';

set_exception_handler('handleAllExceptions');
set_error_handler('handleAllErrors');

if (!ini_get("date.timezone")) {
    date_default_timezone_set('UTC');
}

$logMeasures = array();

if (isset($_SERVER['HTTP_X_REQUEST_START'])) {
    $logMeasures["measure.request.wait"] = (round(microtime(true) * 1000) - $_SERVER['HTTP_X_REQUEST_START'])  . 'ms ';
}

if (isset($_SERVER['HTTP_X_REQUEST_ID'])) {
    header('X-Request-ID: ' . $_SERVER['HTTP_X_REQUEST_ID']);
}

$sessionStore = WorkbenchConfig::get()->value("sessionStore");
// If $sessionStore starts with redis://, convert to format for Redis extension and set as the session save handler
// IN:  redis://user:pass@host:port/
// OUT: tcp://host:port?auth=pass
if (strpos($sessionStore, "redis://") === 0) {
  $redisUrl = "tcp://" . parse_url($sessionStore, PHP_URL_HOST) . ":" . parse_url($sessionStore, PHP_URL_PORT);
  if (!is_array(parse_url($sessionStore, PHP_URL_PASS))) {
    $redisUrl .= "?auth=" . parse_url($sessionStore, PHP_URL_PASS);
  }
  ini_set("session.save_path", $redisUrl);
  ini_set("session.save_handler", "redis");
}

ini_set("session.cookie_httponly", "1");
session_start();

if (WorkbenchConfig::get()->value("redirectToHTTPS") && !usingSslFromUserToWorkbench()) {
    header("Location: " . "https://" . $_SERVER['HTTP_HOST']  . $_SERVER['REQUEST_URI']);
    exit;
}

workbenchLog(LOG_INFO, "U", $logMeasures);

if (WorkbenchContext::isEstablished()) {
    WorkbenchContext::get()->beginRequestHook();
}

//clear ResultsWithData and retrievedZips from session unless downloading them
if (isset($_SESSION['resultsWithData']) && basename($_SERVER['PHP_SELF']) != 'downloadResultsWithData.php') {
    unset($_SESSION['resultsWithData']);
}

if (isset($_SESSION['retrievedZips']) && basename($_SERVER['PHP_SELF']) != 'metadataStatus.php') {
    unset($_SESSION['retrievedZips']);
}

if (WorkbenchContext::isEstablished() && isset($_REQUEST['clearCache'])) {
    WorkbenchContext::get()->clearCache();
    $cacheCleared = true;
}

// PATH_INFO can include malicious scripts and never used purposely in Workbench.
if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] != "") {
    httpError("400 Bad Request", "Path info trailing script name in URI not allowed.");
}

if (WorkbenchConfig::get()->value("requireSSL") && !usingSslEndToEnd()) {
    if (WorkbenchContext::isEstablished()) {
        WorkbenchContext::get()->release();
    }

    httpError("403.4 SSL Required", "Secure connection to Workbench and Salesforce required"); //TODO: what do we want to do here?
}

//kick user back to login page for any page that requires a session and one isn't established
$myPage = getMyPage();
if (!isLoggedIn() && $myPage->requiresSfdcSession) {
    session_unset();
    session_destroy();
    header('Location: login.php?startUrl=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

if (!$myPage->isReadOnly && isReadOnlyMode()) {
    throw new WorkbenchHandledException("This page is not accessible in read-only mode");
}

if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    validateCsrfToken();
}

if (WorkbenchContext::isEstablished() && isset($_POST['termsAccepted'])) {
    WorkbenchContext::get()->agreeToTerms();
}

if (isLoggedIn()) {
    // todo: should this be in the ctx?
    if (!in_array(basename($_SERVER['PHP_SELF'], ".php"), array("login", "logout")) && isset($_SESSION['lastRequestTime'])) {
        $idleTime = microtime(true) - $_SESSION['lastRequestTime'];
        if ($idleTime > (WorkbenchConfig::get()->value("sessionIdleMinutes") * 60)) {
            // ping SFDC to check if session is still alive
            WorkbenchContext::get()->getPartnerConnection()->getServerTimestamp();
        }
    }
    $_SESSION['lastRequestTime'] = microtime(true);
}
?>
