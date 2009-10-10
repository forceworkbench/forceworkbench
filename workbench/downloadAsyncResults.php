<?php
require_once ('session.php');
require_once ('shared.php');
require_once ('restclient/AsyncApiClient.php');


if(isset($_GET['jobId']) && isset($_GET['batchId'])){
	try{
		$asyncConnection = new AsyncApiConnection($_SESSION['location'], $_SESSION['sessionId']);
		$jobInfo = $asyncConnection->getJobInfo($_GET['jobId']);
		$asyncResults = $asyncConnection->getBatchResults($_GET['jobId'], $_GET['batchId']);
	} catch (Exception $e){
		include_once('header.php');
		show_error($e);
		include_once('footer.php');
		exit;
	}
	
	if(strpos($asyncResults, "<exceptionCode>")){
		$asyncError = new SimpleXMLElement($asyncResults);
		include_once('header.php');
		show_error($asyncError->exceptionCode . ": " . $asyncError->exceptionMessage);
		include_once('footer.php');	
		exit;
	} else if ($asyncResults == "") {
		include_once('header.php');
		show_error("No results found. Confirm job or batch has not expired.");
		include_once('footer.php');
		exit;
	} else {
		$csv_filename = "bulk" . ucwords($jobInfo->getOpertion()) . "Results_" . $_GET['jobId'] . "_" . $_GET['batchId'] . ".csv";
		header("Content-Type: application/csv");
		header("Content-Disposition: attachment; filename=$csv_filename");
		print $asyncResults;
	}
} else {
	include_once('header.php');
	show_error("Both 'jobId' and 'batchId' parameters must be specified");
	include_once('footer.php');
	exit;
}


?>
