<?php
require_once ('session.php');
require_once ('shared.php');
require_once('header.php');
print "<p/>";
if(!apiVersionIsAtLeast(10.0)) {
	show_error("Metadata API not supported prior to version 10.0", false, true);
	exit;
}

if(!isset($_GET['asyncProcessId'])){
	show_error("Async Process Id must be specified.",false,false);
	print 	"<p/>" . 
			"<form action='$_SERVER[PHP_SELF]' method='GET'>" . 
			"Async Process Id: <input type='text' name='asyncProcessId'/> &nbsp;" .  
			"<input type='submit' value='Get Status'".
			"</form>";
	include_once('footer.php');
	exit;
} 

$asyncProcessId = $_GET['asyncProcessId'];

print "<p class='instructions'>A Metadata API operation has been performed, which requires asynchronous processing as resources are available. " . 
	  "Bookmark and periodically view this page to view the latest status.</p><p/>";

print "<input type='button' onclick='window.location.href=window.location.href;' value='Refresh' style='float:right;'/>";


require_once ('soapclient/SforceMetadataClient.php');
global $metadataConnection;
try {
	$asyncResults = $metadataConnection->checkStatus($asyncProcessId);
	
	print "<h3>Status</h3>";
	print "<table class='lightlyBoxed' style='padding: 3em;' cellpadding='6'>\n";
	foreach($asyncResults as $resultName => $resultValue) {
		print "<tr><td style='text-align: right; padding-right: 2em;'>" . unCamelCase($resultName) . "</td><td style='font-weight: bold;'>";
		if(is_bool($resultValue)) {
			print $resultValue ? "true" : "false";
		} else {
			print $resultValue;
		}
		print "</td></tr>\n";
	}
	print "</table>\n";
	
	if($asyncResults->done) {
		print "<p>&nbsp;</p><h3>Results</h3>";
		$results = $metadataConnection->checkDeployStatus($asyncProcessId);
		
		$processedResults = array();
		foreach(array(true, false) as $scalarProcessing){
			foreach($results as $resultKey => $resultValue) {
				if ($scalarProcessing && (is_array($resultValue) || is_object($resultValue))){
					continue;
				}
				
				if($resultKey == "messages") {
					foreach($resultValue as $message) {
						$processedResults["messages"][$message->fullName] = $message;
					}
				} else {
					$processedResults[$resultKey] = $resultValue;
				}
			}
		}
		
		printTree("metadataStatusResultsTree", $processedResults, true);
	}
} catch (Exception $e) {
	show_error($e->getMessage(), false, true);
}

include_once('footer.php');
exit;
?>