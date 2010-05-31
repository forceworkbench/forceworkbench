<?php
require_once('shared.php');

if(!isset($GLOBALS['requestTimeStart'])){
	$GLOBALS['requestTimeStart'] = microtime(true);
}

session_start();

//clear ResultsWithData from session unless downloading them
if(isset($_SESSION['resultsWithData']) && basename($_SERVER['PHP_SELF']) != 'downloadResultsWithData.php'){
	unset($_SESSION['resultsWithData']);
}

//load default config values and then any custom overrides.
require_once('config.php');
if(is_file('configOverrides.php')) require_once('configOverrides.php');

foreach($config as $configKey => $configValue){
	//only process non-headers
	if(!isset($configValue['isHeader'])){
		//check if the setting is NOT overrideable and if so clear the cookie
		//this is done to clear previously set cookeies
		if(!$configValue['overrideable']){
			setcookie($configKey,NULL,time()-3600);
		}

		//check if user has cookies that override defaults
		if(isset($_COOKIE[$configKey])){
			$_SESSION['config'][$configKey] = $_COOKIE[$configKey];
		} else if(isset($configValue['default'])) {
			$_SESSION['config'][$configKey] = $configValue['default'];
		}
	}
}

if($config["callOptions_client"]["default"] == "WORKBENCH_DEFAULT" && !isset($_COOKIE["callOptions_client"])){
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


if(isLoggedIn()){
	try{
		//setup SOAP client
		require_once ('soapclient/SforcePartnerClient.php');
		require_once ('soapclient/SforceHeaderOptions.php');
		$location = $_SESSION['location'];
		$sessionId = $_SESSION['sessionId'];
		$wsdl = $_SESSION['wsdl'];
		$mySforceConnection = new SforcePartnerClient();
		$sforceSoapClient = $mySforceConnection->createConnection($wsdl);
		$mySforceConnection->setEndpoint($location);
		$mySforceConnection->setSessionHeader($sessionId);
			
		$_SESSION['config']['currentApiVersion'] = getApiVersion();
			
		//setting default object to remove notices through functions
		if(!isset($_SESSION['default_object'])){
			$_SESSION['default_object'] = null;
		}
			
		//Has the user selected a default object on? If so,
		//pass them to the session
		if (isset($_REQUEST['default_object'])){
			$_REQUEST['default_object_changed'] = $_SESSION['default_object'] != $_REQUEST['default_object'];
			$_SESSION['default_object'] = $_REQUEST['default_object'];
		}
			
		$defaultNamespace = isset($_SESSION['config']['callOptions_defaultNamespace']) ? $_SESSION['config']['callOptions_defaultNamespace'] : null;
		if(isset($_SESSION['tempClientId'])){
			$header = new CallOptions($_SESSION['tempClientId'], $defaultNamespace);
			$mySforceConnection->setCallOptions($header);
		} else if($_SESSION['config']['callOptions_client'] || $defaultNamespace){
			$header = new CallOptions($_SESSION['config']['callOptions_client'], $defaultNamespace);
			$mySforceConnection->setCallOptions($header);
		}

		$assignmentRuleId = isset($_SESSION['config']['assignmentRuleHeader_assignmentRuleId']) ? $_SESSION['config']['assignmentRuleHeader_assignmentRuleId'] : null;
		if($assignmentRuleId || $_SESSION['config']['assignmentRuleHeader_useDefaultRule']){
			$header = new AssignmentRuleHeader($assignmentRuleId, $_SESSION['config']['assignmentRuleHeader_useDefaultRule']);
			$mySforceConnection->setAssignmentRuleHeader($header);
		}

		if($_SESSION['config']['mruHeader_updateMru']){
			$header = new MruHeader($_SESSION['config']['mruHeader_updateMru']);
			$mySforceConnection->setMruHeader($header);
		}

		if($_SESSION['config']['queryOptions_batchSize']){
			$header = new QueryOptions($_SESSION['config']['queryOptions_batchSize']);
			$mySforceConnection->setQueryOptions($header);
		}

		if($_SESSION['config']['emailHeader_triggerAutoResponseEmail'] || $_SESSION['config']['emailHeader_triggerOtherEmail'] || $_SESSION['config']['emailHeader_triggertriggerUserEmail']){
			$header = new EmailHeader($_SESSION['config']['emailHeader_triggerAutoResponseEmail'], $_SESSION['config']['emailHeader_triggerOtherEmail'], $_SESSION['config']['emailHeader_triggertriggerUserEmail']);
			$mySforceConnection->setEmailHeader($header);
		}

		if(isset($_SESSION['config']['UserTerritoryDeleteHeader_transferToUserId'])){
			$header = new UserTerritoryDeleteHeader($_SESSION['config']['UserTerritoryDeleteHeader_transferToUserId']);
			$mySforceConnection->setUserTerritoryDeleteHeader($header);
		}

		if($_SESSION['config']['allowFieldTruncationHeader_allowFieldTruncation']){
			$header = new AllowFieldTruncationHeader($_SESSION['config']['allowFieldTruncationHeader_allowFieldTruncation']);
			$mySforceConnection->setAllowFieldTruncationHeader($header);
		}

		if(!isset($_SESSION['getUserInfo']) || !$_SESSION['config']['cacheGetUserInfo']){
			$_SESSION['getUserInfo'] = $mySforceConnection->getUserInfo();
		}
		
	} catch (exception $e){
		session_unset();
		session_destroy();
		header('Location: login.php');
		exit;
	}
}
?>
