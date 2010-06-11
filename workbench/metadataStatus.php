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
	  "Bookmark and periodically view this page to view the latest status. Results will be available once processing is complete.</p><p/>";

print "<input type='button' onclick='window.location.href=window.location.href;' value='Refresh' style='float:right;'/>";


require_once ('soapclient/SforceMetadataClient.php');
global $metadataConnection;
try {
	$asyncResults = $metadataConnection->checkStatus($asyncProcessId);
	
	$orderedAsyncResults = array("id"=>null,"done"=>null,"stateDetailLastModifiedDate"=>null,"state"=>null);
	foreach($asyncResults as $resultName => $resultValue) {
		$orderedAsyncResults[$resultName] = $resultValue;
	}
	
	print "<h3>Status</h3>";
	print "<table class='lightlyBoxed' cellpadding='5' width='100%'>\n";
	$rowNum = 0;
	foreach($orderedAsyncResults as $resultName => $resultValue) {
		if(++$rowNum % 2) {
			print "<tr>";
			printStatusCell($resultName, $resultValue);
		} else {
			printStatusCell($resultName, $resultValue);
			print "</td></tr>\n";
		}
	}
	if($rowNum % 2) {
		print "<td></td><td></td></tr>";
	}
	print "</table>\n";
	
	if($asyncResults->done) {
		print "<p>&nbsp;</p><h3>Results</h3>";
		$results = $metadataConnection->checkDeployStatus($asyncProcessId, $debugInfo);
		
		$processedResults = processResults($results);
		
		printTree("metadataStatusResultsTree", $processedResults, true);
		
		if(isset($debugInfo["DebuggingInfo"]->debugLog)){
			print "<p>&nbsp;</p><h3>Debug Logs</h3>";
			print("<pre>" . addLinksToUiForIds(htmlspecialchars($debugInfo["DebuggingInfo"]->debugLog,ENT_QUOTES,'UTF-8')) . '</pre>');
		} 
	}
} catch (Exception $e) {
	show_error($e->getMessage(), false, true);
}

include_once('footer.php');
exit;

function printStatusCell($resultName, $resultValue) {
	print "<td style='text-align: right; padding-right: 2em;'>" . unCamelCase($resultName) . "</td><td style='font-weight: bold;'>";
	if(is_bool($resultValue)) {
		print $resultValue ? "true" : "false";
	} else {
		print $resultValue;
	}
	print "</td>";
}

function processResults($raw) {
	foreach(array(true, false) as $scalarProcessing){
		foreach($raw as $rawKey => $rawValue) {
			if(is_array($rawValue) || is_object($rawValue)) {
				if($scalarProcessing) continue;
				
				if (isset($rawValue->name)) {
					$processed[$rawValue->name] = processResults($rawValue);
				} else if(isset($rawValue->fullName)) {
					$processed[$rawValue->fullName] = processResults($rawValue);
				} else if(isset($rawValue->column) && isset($rawValue->line)) {
					$processed[$rawValue->column . ":" . $rawValue->line] = processResults($rawValue);
					krsort($processed);
				} else {
					$processed[$rawKey] = processResults($rawValue);
				}
			} else {
				$processed[$rawKey] = $rawValue;
			}
		}
	}
	return $processed;
}
?>