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
	} catch (exception $e){
		header('Location: login.php');
  		exit;
	}
}
?>
