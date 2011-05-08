<?php
require_once 'shared.php';

set_exception_handler('handleAllExceptions');

if (!isset($GLOBALS['requestTimeStart'])) {
    $GLOBALS['requestTimeStart'] = microtime(true);
}

session_start();

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
        //setup SOAP client
        if (getConfig('mockClients')) {
            require_once 'soapclient/SforceMockPartnerClient.php';
        }
        require_once 'soapclient/SforcePartnerClient.php';
        require_once 'soapclient/SforceHeaderOptions.php';
        require_once 'soapclient/SforceMetadataClient.php';

        $location = $_SESSION['location'];
        $sessionId = $_SESSION['sessionId'];
        $wsdl = $_SESSION['wsdl'];
        $partnerConnection = (getConfig('mockClients') ? new SforceMockPartnerClient() : new SforcePartnerClient());
        $sforceSoapClient = $partnerConnection->createConnection($wsdl);
        $partnerConnection->setEndpoint($location);
        $partnerConnection->setSessionHeader($sessionId);

        $metadataConnection = new SforceMetadataClient();

        //setting default object to remove notices through functions
        if (!isset($_SESSION['default_object'])) {
            $_SESSION['default_object'] = null;
        }

        //Has the user selected a default object on? If so,
        //pass them to the session
        if (isset($_REQUEST['default_object'])) {
            $_REQUEST['default_object_changed'] = $_SESSION['default_object'] != $_REQUEST['default_object'];
            $_SESSION['default_object'] = $_REQUEST['default_object'];
        }

        if (isset($_SESSION['tempClientId'])) {
            $partnerConnection->setCallOptions(new CallOptions($_SESSION['tempClientId'], getConfig('callOptions_defaultNamespace')));
        } else if (getConfig('callOptions_client') || getConfig('callOptions_defaultNamespace')) {
            $partnerConnection->setCallOptions(new CallOptions(getConfig('callOptions_client'), getConfig('callOptions_defaultNamespace')));
        }

        if (getConfig('assignmentRuleHeader_assignmentRuleId') || getConfig('assignmentRuleHeader_useDefaultRule')) {
            $partnerConnection->setAssignmentRuleHeader(
                new AssignmentRuleHeader(
                    getConfig('assignmentRuleHeader_assignmentRuleId'), 
                    getConfig('assignmentRuleHeader_useDefaultRule')
                )
            );
        }

        if (getConfig('mruHeader_updateMru')) {
            $partnerConnection->setMruHeader(new MruHeader(getConfig('mruHeader_updateMru')));
        }

        if (getConfig('queryOptions_batchSize')) {
            $partnerConnection->setQueryOptions(new QueryOptions(getConfig('queryOptions_batchSize')));
        }

        if (getConfig('emailHeader_triggerAutoResponseEmail') || 
            getConfig('emailHeader_triggerOtherEmail') || 
            getConfig('emailHeader_triggertriggerUserEmail')) {
            
            $partnerConnection->setEmailHeader(new EmailHeader(
                    getConfig('emailHeader_triggerAutoResponseEmail'), 
                    getConfig('emailHeader_triggerOtherEmail'), 
                    getConfig('emailHeader_triggertriggerUserEmail')
                )
            );
        }

        if (getConfig('UserTerritoryDeleteHeader_transferToUserId')) {
            $partnerConnection->setUserTerritoryDeleteHeader(
                new UserTerritoryDeleteHeader(getConfig('UserTerritoryDeleteHeader_transferToUserId')));
        }

        if (getConfig('allowFieldTruncationHeader_allowFieldTruncation')) {
            $partnerConnection->setAllowFieldTruncationHeader(
                new AllowFieldTruncationHeader(getConfig('allowFieldTruncationHeader_allowFieldTruncation')));
        }

        if (getConfig('allOrNoneHeader_allOrNone')) {
            $partnerConnection->setAllOrNoneHeader(
			    new AllOrNoneHeader(getConfig('allOrNoneHeader_allOrNone')));
        }
    
        if (getConfig('disableFeedTrackingHeader_disableFeedTracking')) {
            $partnerConnection->setDisableFeedTrackingHeader(
			    new DisableFeedTrackingHeader(getConfig('disableFeedTrackingHeader_disableFeedTracking')));
        }

        if (getConfig('localOptions_language')) {
            $partnerConnection->setLocaleOptions(
			    new LocaleOptions(getConfig('localOptions_language')));
        }
    
        if (getConfig('packageVersionHeader_include') && 
            getConfig('packageVersion_namespace') &&
            getConfig('packageVersion_majorNumber') &&
            getConfig('packageVersion_minorNumber')) {
            $partnerConnection->setPackageVersionHeader(
                getConfig("packageVersion_namespace"), 
                getConfig("packageVersion_majorNumber"), 
                getConfig("packageVersion_minorNumber")
		    );
        }
        
        if (!isset($_SESSION['getUserInfo']) || !getConfig('cacheGetUserInfo')) {
            $_SESSION['getUserInfo'] = $partnerConnection->getUserInfo();
        } else if (isset($_SESSION['lastRequestTime'])) {
            $idleTime = microtime(true) - $_SESSION['lastRequestTime'];
            if ($idleTime > (getConfig("sessionIdleMinutes") * 60)) {
                // ping SFDC to check if session is still alive
                $partnerConnection->getServerTimestamp();
            }
        }
        $_SESSION['lastRequestTime'] = microtime(true);

    } catch (exception $e) {
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
