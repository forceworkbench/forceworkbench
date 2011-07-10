<?php
require_once 'shared.php';
require_once 'context/WorkbenchContext.php';

set_exception_handler('handleAllExceptions');

// PATH_INFO can include malicious scripts and never used purposely in Workbench.
if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] != "") {
    httpError("400 Bad Request", "Path info trailing script name in URI not allowed.");
}

ini_set("session.cookie_httponly", "1");
session_start();

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

//load default config values and then any custom overrides.
require_once 'config.php';
if(is_file('configOverrides.php')) require_once 'configOverrides.php';

foreach ($config as $configKey => $configValue) {
    // skip headers
    if (isset($configValue['isHeader'])) {
        continue;
    }

    // does the user have an override?
    else if (isset($_COOKIE[$configKey])) {
        // override the session value with that of the cookie
        if ($configValue['overrideable']) {
            $_SESSION['config'][$configKey] = $_COOKIE[$configKey];
        }
        // remove the override if not actually overridable and set to default
        else {
            setcookie($configKey,NULL,time()-3600);
            $_SESSION['config'][$configKey] = $configValue['default'];
        }
    }
    // otherwise, just use the default
    else {
        $_SESSION['config'][$configKey] = $configValue['default'];
    }
}

if ($config["callOptions_client"]["default"] == "WORKBENCH_DEFAULT" && !isset($_COOKIE["callOptions_client"])) {
    $_SESSION['config']['callOptions_client'] = getWorkbenchUserAgent();
}

// log for every request. needs to be after configs load
workbenchLog(LOG_INFO, "U");

if (getConfig("requireSSL") && !usingSSL()) {
    if (WorkbenchContext::isEstablished()) {
        WorkbenchContext::get()->release();
    }

    httpError("403.4 SSL Required", "Connection to Workbench and Salesforce required"); //TODO: what do we want to do here?
}

//kick user back to login page for any page that requires a session and one isn't established
$myPage = getMyPage();
if (!isLoggedIn() && $myPage->requiresSfdcSession) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

if (!$myPage->isReadOnly && isReadOnlyMode()) {
    throw new WorkbenchHandledException("This page is not accessible in read-only mode");
}

if (WorkbenchContext::isEstablished() && !$myPage->isReadOnly  && $_SERVER['REQUEST_METHOD'] == 'POST') {
    validateCsrfToken();
}

if (isLoggedIn()) {
    // todo: should this be in the ctx?
    if (isset($_SESSION['lastRequestTime'])) {
        $idleTime = microtime(true) - $_SESSION['lastRequestTime'];
        if ($idleTime > (getConfig("sessionIdleMinutes") * 60)) {
            // ping SFDC to check if session is still alive
            WorkbenchContext::get()->getPartnerConnection()->getServerTimestamp();
        }
    }
    $_SESSION['lastRequestTime'] = microtime(true);
}
?>
