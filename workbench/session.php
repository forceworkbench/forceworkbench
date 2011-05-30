<?php
require_once 'shared.php';
require_once 'context/WorkbenchContext.php';

set_exception_handler('handleAllExceptions');

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
    //only process non-headers
    if (!isset($configValue['isHeader'])) {
        //check if the setting is NOT overrideable and if so clear the cookie
        //this is done to clear previously set cookeies
        if (!$configValue['overrideable'] && isset($_COOKIE[$configKey])) {
            setcookie($configKey,NULL,time()-3600);
        }

        //check if user has cookies that override defaults
        if (isset($_COOKIE[$configKey])) {
            $_SESSION['config'][$configKey] = $_COOKIE[$configKey];
        } else {
            $_SESSION['config'][$configKey] = $configValue['default'];
        }
    }
}

if ($config["callOptions_client"]["default"] == "WORKBENCH_DEFAULT" && !isset($_COOKIE["callOptions_client"])) {
    $_SESSION['config']['callOptions_client'] = getWorkbenchUserAgent();
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
    throw new Exception("This page is not accessible in read-only mode");
}


if (isLoggedIn()) {
    try {
        // todo: should this be in the ctx?
        if (isset($_SESSION['lastRequestTime'])) {
            $idleTime = microtime(true) - $_SESSION['lastRequestTime'];
            if ($idleTime > (getConfig("sessionIdleMinutes") * 60)) {
                // ping SFDC to check if session is still alive
                WorkbenchContext::get()->getPartnerConnection()->getServerTimestamp();
            }
        }
        $_SESSION['lastRequestTime'] = microtime(true);

    } catch (exception $e) {
        WorkbenchContext::get()->release();
        session_unset();
        session_destroy();
        try { include_once 'header.php'; } catch (exception $e) {}

        if (strpos($e->getMessage(), "INVALID_SESSION_ID") === 0) {
            displayError("Your Salesforce session is invalid or has expired. Please login again.", false, false);
        } else {
            displayError("Fatal error connecting to Salesforce. Please login again.\n\nERROR: " . $e->getMessage(), false, false);
        }

        print "<script type='text/javascript'>setTimeout(\"location.href = 'login.php';\",3000);</script>";
        include_once 'footer.php';
        exit;
    }
}
?>
