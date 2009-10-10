<?php
require_once ('session.php');
require_once ('shared.php');
require_once ('header.php');
require_once ('restclient/AsyncApiClient.php');

print "<h2>" . getMyTitle() . "</h2>";

if(!isset($_GET['jobId']) || $_GET['jobId'] == ""){
	show_error("Parameter 'jobId' must be specified.",false,false);
	print 	"<p/>" . 
			"<form action='$_SERVER[PHP_SELF]' method='GET'>" . 
			"Job Id: <input type='text' name='jobId'/> &nbsp;" .  
			"<input type='submit' value='Get Status'".
			"</form>";
	include_once('footer.php');
	exit;
} else {
	$_GET['jobId'] = htmlspecialchars($_GET['jobId']);
}

try {	
	$asyncConnection = new AsyncApiConnection($_SESSION['location'], $_SESSION['sessionId']);
	$jobInfo = $asyncConnection->getJobInfo($_GET['jobId']);
	if($jobInfo->getExceptionCode() != "") throw new Exception($jobInfo->getExceptionCode() . ": " . $jobInfo->getExceptionMessage());
	
	$batchInfos = $asyncConnection->getBatchInfos($_GET['jobId']);	
} catch (Exception $e) {
	show_error($e->getMessage(), false, true);
}

print "<p>Records have been uploaded to Salesforce via the Bulk API and are processed asynchronously as resources are available. " . 
	  "Bookmark and periodically view this page to view the latest status. Results can be downloaded when batches are complete.</p><p/>";

print "<table width='100%'>";
print "<tr>" . 
		"<td align='left'><h3 style='color: #0046ad'>Job: " . addLinksToUiForIds($jobInfo->getId()) . "</h3></td>" .
		"<td align='right'><input type='button' onclick='window.location.href=window.location.href;' value='Refresh'/></td>" .
       "</tr>";
print "</table>";

print "<table width='100%' cellpadding='5' style='border-style:solid; border-width: 1px; border-collapse:collapse; border-color: #bbb;'>";
print "<tr>" . 
		"<td class='dataLabel'>Status</td><td class='dataValue'>" . $jobInfo->getState() . "</td>" .
		"<td class='dataLabel'>Records Processed</td><td class='dataValue'>" . $jobInfo->getNumberRecordsProcessed() . "</td>" .
        "<td class='dataLabel'>Batches Queued</td><td class='dataValue'>" . $jobInfo->getNumberBatchesQueued() . "</td>" .
       "</tr>";
print "<tr>" . 
		"<td class='dataLabel'>Object</td><td class='dataValue'>" . $jobInfo->getObject() . "</td>" .
		"<td class='dataLabel'>Concurrency Mode</td><td class='dataValue'>" . $jobInfo->getConcurrencyMode() . "</td>" .
        "<td class='dataLabel'>Batches In Progress</td><td class='dataValue'>" . $jobInfo->getNumberBatchesInProgress() . "</td>" .
       "</tr>";
print "<tr>" . 
		"<td class='dataLabel'>Operation</td><td class='dataValue'>" . ucwords($jobInfo->getOpertion()). "</td>" .
		"<td class='dataLabel'>Content Type</td><td class='dataValue'>" . $jobInfo->getContentType() . "</td>" .
        "<td class='dataLabel'>Batches Completed</td><td class='dataValue'>" . $jobInfo->getNumberBatchesCompleted() . "</td>" .
       "</tr>";
print "<tr>" . 
		"<td class='dataLabel'>External Id</td><td class='dataValue'>" . $jobInfo->getExternalIdFieldName(). "</td>" .
		"<td class='dataLabel'>API Version</td><td class='dataValue'>" . $jobInfo->getApiVersion() . "</td>" .	
        "<td class='dataLabel'>Batches Failed</td><td class='dataValue'>" . $jobInfo->getNumberBatchesFailed() . "</td>" .
       "</tr>";
print "<tr>" . 
		"<td class='dataLabel'>Created</td><td class='dataValue'>" . simpleFormattedTime($jobInfo->getCreatedDate()) . "</td>" .
        "<td class='dataLabel'>Last Modified</td><td class='dataValue'>" . simpleFormattedTime($jobInfo->getSystemModstamp()) . "</td>" .
        "<td class='dataLabel'>Retries</td><td class='dataValue'>" . $jobInfo->getNumberRetries() . "</td>" .
       "</tr>";
print "</table>";
print "<p>&nbsp;</p>";


if(count($batchInfos) > 0){
	print "<h3 style='color: #0046ad'>Batches</h3>";
	
	print "<table cellpadding='4' width='100%' style='border-style:solid; border-width: 1px; border-collapse:collapse; border-color: #bbb;'>";
		print "<tr>" . 
				"<th>&nbsp;</th>" .		
				"<th>Id</th>" .
				"<th>Status</th>" .
		        "<th>Processed</th>" .
				"<th>Created</th>" .
				"<th>Last Modifed</th>" .	
		       "</tr>";
	foreach($batchInfos as $batchInfo){		
		print "<tr>";
		
		if($batchInfo->getState() == "Completed"){
			print "<td class='dataValue'>" .
				   "<a href='downloadAsyncResults.php?jobId=" . $jobInfo->getId() . "&batchId=" . $batchInfo->getId() . "'>" . 
				   "<img src='images/downloadIcon.gif' border='0' onmouseover=\"Tip('Download Batch Results')\"/>" . 
				   "</a></td>";
		} else {
			print "<td class='dataValue'>&nbsp;</td>";
		}
		
		$recLabel = $batchInfo->getNumberRecordsProcessed() == "1" ? " record" : " records";
		
		print	"<td class='dataValue'>" . $batchInfo->getId() . "</td>" .
				"<td class='dataValue'>" . $batchInfo->getState() . "</td>" .
				"<td class='dataValue'>" . $batchInfo->getNumberRecordsProcessed() . $recLabel . "</td>" .
				"<td class='dataValue'>" . simpleFormattedTime($batchInfo->getCreatedDate()) . "</td>" .
				"<td class='dataValue'>" . simpleFormattedTime($batchInfo->getSystemModstamp()) . "</td>";
		
		print "</tr>";
	}
	print "</table>";
}



include_once ('footer.php');

?>
