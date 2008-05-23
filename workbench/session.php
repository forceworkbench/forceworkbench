<?php
session_start();
if (!isset($_SESSION['sessionId'])) {
  header('Location: login.php');
  exit;
} else {
	try{
		require_once ('soapclient/SforcePartnerClient.php');
		require_once ('soapclient/SforceHeaderOptions.php');
		$location = $_SESSION['location'];
		$sessionId = $_SESSION['sessionId'];
		$wsdl = $_SESSION['wsdl'];
		$mySforceConnection = new SforcePartnerClient();
		$sforceSoapClient = $mySforceConnection->createConnection($wsdl);
		$mySforceConnection->setEndpoint($location);
		$mySforceConnection->setSessionHeader($sessionId);
		//Has the user selected a default object on? If so,
		//pass them to the session
		if ($_POST[default_object]){
			$_SESSION[default_object] = $_POST[default_object];
		}

		//load default config values
		require_once('config.php');

		foreach($config as $configKey => $configValue){
			//check if the setting is NOT overrideable and if so clear the cookie
			//this is done to clear previously set cookeies
			if(!$configValue['overrideable']){
				setcookie($configKey,NULL,time()-3600);
			}

			//check if user has cookies that override defaults
			if(isset($_COOKIE[$configKey])){
				$_SESSION['config'][$configKey] = $_COOKIE[$configKey];
			} else {
				$_SESSION['config'][$configKey] = $configValue['default'];
			}
		}

		require_once ('soapclient/SforceHeaderOptions.php');

		if($_SESSION['config']['callOptions_client'] || $_SESSION['config']['callOptions_defaultNamespace']){
			$header = new CallOptions($_SESSION['config']['callOptions_client'], $_SESSION['config']['callOptions_defaultNamespace']);
			$mySforceConnection->setCallOptions($header);
		}

		if($_SESSION['config']['assignmentRuleHeader_assignmentRuleId'] || $_SESSION['config']['assignmentRuleHeader_useDefaultRule']){
			$header = new AssignmentRuleHeader($_SESSION['config']['assignmentRuleHeader_assignmentRuleId'], $_SESSION['config']['assignmentRuleHeader_useDefaultRule']);
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

		if($_SESSION['config']['UserTerritoryDeleteHeader_transferToUserId']){
			$header = new UserTerritoryDeleteHeader($_SESSION['config']['UserTerritoryDeleteHeader_transferToUserId']);
			$mySforceConnection->setUserTerritoryDeleteHeader($header);
		}


	} catch (exception $e){
		header('Location: login.php');
  		exit;
	}
}
?>
