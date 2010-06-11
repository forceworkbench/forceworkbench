<?php
require_once ('session.php');
require_once ('shared.php');
require_once ('header.php');
require_once ('restclient/BulkApiClient.php');

print "<p/>";
if(!isset($_GET['jobId']) || $_GET['jobId'] == ""){
	show_error("Parameter 'jobId' must be specified.",false,false);
	print 	"<p/>" . 
			"<form action='$_SERVER[PHP_SELF]' method='GET'>" . 
			"Job Id: <input type='text' name='jobId'/> &nbsp;" .  
			"<input type='submit' value='Get Status'".
			"</form>";
	include_once('footer.php');
	exit;
} 

$_GET['jobId'] = htmlspecialchars(trim($_GET['jobId']));

try {	
	$asyncConnection = getAsyncApiConnection();
	$jobInfo = $asyncConnection->getJobInfo($_GET['jobId']);	
	$batchInfos = $asyncConnection->getBatchInfos($_GET['jobId']);	
} catch (Exception $e) {	
	show_error($e->getMessage(), false, false);
	if(stripos($e->getMessage(), 'InvalidVersion') > -1){
		print "<p/><em>Quick Fix: <a href='sessionInfo.php' target='_blank'>Change API Version</a></em>";
		include_once ('footer.php');
		exit;
	}
}

print "<p class='instructions'>Records have been uploaded to Salesforce via the Bulk API and are processed asynchronously as resources are available. " . 
	  "Bookmark and periodically view this page to view the latest status. Results can be downloaded when batches are complete.</p><p/>";

print "<table width='100%'>";
print "<tr>" . 
		"<td align='left'><h3>Job: " . addLinksToUiForIds($jobInfo->getId()) . "</h3></td>" .
		"<td align='right'><input type='button' onclick='window.location.href=window.location.href;' value='Refresh'/></td>" .
       "</tr>";
print "</table>";

if($jobInfo->getStateMessage() != ""){
	show_info($jobInfo->getStateMessage());
	print "<p/>";
}

print "<table width='100%' cellpadding='5' class='lightlyBoxed'>";
print "<tr>" . 
		"<td class='dataLabel'>Status</td><td class='dataValue'>" . $jobInfo->getState() . "</td>" .
		"<td class='dataLabel'>Records Processed</td><td class='dataValue'>" . $jobInfo->getNumberRecordsProcessed() . "</td>" .
        "<td class='dataLabel'>Batches Queued</td><td class='dataValue'>" . $jobInfo->getNumberBatchesQueued() . "</td>" .
       "</tr>";
print "<tr>" . 
		"<td class='dataLabel'>Object</td><td class='dataValue'>" . $jobInfo->getObject() . "</td>" .
		(apiVersionIsAtLeast(19.0) ? 
			"<td class='dataLabel'>Records Failed</td><td class='dataValue'>" . $jobInfo->getNumberRecordsFailed() . "</td>" : 
			"<td class='dataLabel'>Content Type</td><td class='dataValue'>" . $jobInfo->getContentType() . "</td>"
		) .
        "<td class='dataLabel'>Batches In Progress</td><td class='dataValue'>" . $jobInfo->getNumberBatchesInProgress() . "</td>" .
       "</tr>";
print "<tr>" . 
		"<td class='dataLabel'>Operation</td><td class='dataValue'>" . ucwords($jobInfo->getOpertion()). "</td>" .
		"<td class='dataLabel'>Concurrency Mode</td><td class='dataValue'>" . $jobInfo->getConcurrencyMode() . "</td>" .
		"<td class='dataLabel'>Batches Completed</td><td class='dataValue'>" . $jobInfo->getNumberBatchesCompleted() . "</td>" .
       "</tr>";
print "<tr>" . 
		"<td class='dataLabel'>External Id</td><td class='dataValue'>" . $jobInfo->getExternalIdFieldName(). "</td>" .
		"<td class='dataLabel'>API Version</td><td class='dataValue'>" . $jobInfo->getApiVersion() . "</td>" .	
        "<td class='dataLabel'>Batches Failed</td><td class='dataValue'>" . $jobInfo->getNumberBatchesFailed() . "</td>" .
       "</tr>";
if(apiVersionIsAtLeast(19.0)){
	print "<tr>" . 
			"<td class='dataLabel'>API Processing</td><td class='dataValue'>" . $jobInfo->getApiActiveProcessingTime(). " ms</td>" .
			"<td class='dataLabel'>Apex Processing</td><td class='dataValue'>" . $jobInfo->getApexProcessingTime() . " ms</td>" .	
	        "<td class='dataLabel'>Total Processing</td><td class='dataValue'>" . $jobInfo->getTotalProcessingTime() . " ms</td>" .
	       "</tr>";
}
print "<tr>" . 
		"<td class='dataLabel'>Created</td><td class='dataValue'>" . simpleFormattedTime($jobInfo->getCreatedDate()) . "</td>" .
        "<td class='dataLabel'>Last Modified</td><td class='dataValue'>" . simpleFormattedTime($jobInfo->getSystemModstamp()) . "</td>" .
        "<td class='dataLabel'>Retries</td><td class='dataValue'>" . $jobInfo->getNumberRetries() . "</td>" .
       "</tr>";
print "</table>";
print "<p>&nbsp;</p>";

if(count($batchInfos) > 0){
	print "<h3>Batches</h3>";
	
	print "<table cellpadding='4' width='100%' class='lightlyBoxed'>";
		print "<tr>" . 
				"<th>&nbsp;</th>" .
				"<th>Id</th>" .
				"<th>Status</th>" .
		        "<th>Processed</th>" .
				(apiVersionIsAtLeast(19.0) ? "<th>Failed</th>" : "") .
				"<th>Created</th>" .
				"<th>Last Modified</th>" .	
		       "</tr>";
	foreach($batchInfos as $batchInfo){		
		print "<tr><td class='dataValue'>";
		if ($batchInfo->getState() == "Completed" || $batchInfo->getState() == "Failed"){
			print "<a href='downloadAsyncBatch.php?op=result&jobId=" . $jobInfo->getId() . "&batchId=" . $batchInfo->getId() . "'>" . 
				  "<img src='images/downloadIcon" . $batchInfo->getState() . ".gif' border='0' onmouseover=\"Tip('Download " . $batchInfo->getState() . " Batch Results')\"/>" . 
				  "</a>";
		} else {
			print "&nbsp;";
		}
		print "</td>";
		
		$processingTimeDetails = "API Processing: " . $batchInfo->getApiActiveProcessingTime() . " ms<br/>" .
								 "Apex Processing: "  .  $batchInfo->getApexProcessingTime() .   " ms<br/>" . 
								 "Total Processing: "  . $batchInfo->getTotalProcessingTime() .  " ms<br/>";
				
		print	"<td class='dataValue'>" . 
					(apiVersionIsAtLeast(19.0) ? 
						"<a href='downloadAsyncBatch.php?op=request&jobId=" . $jobInfo->getId() . "&batchId=" . $batchInfo->getId() . 
							"' onmouseover=\"Tip('Download Batch Request')\"/>" . $batchInfo->getId() . "</a>" :
					 	$batchInfo->getId()) . 
					 "</td>" . 
				"<td class='dataValue'>" . $batchInfo->getState() . (($batchInfo->getStateMessage() != "") ? (": " . $batchInfo->getStateMessage()) : "") . "</td>" .
				(apiVersionIsAtLeast(19.0) ? "<td class='dataValue pseudoLink' onmouseover=\"Tip('$processingTimeDetails')\"/>" : "<td class='dataValue'>") . 
						$batchInfo->getNumberRecordsProcessed() . ($batchInfo->getNumberRecordsProcessed() == "1" ? " record" : " records") . 
				"</td>" .
				(apiVersionIsAtLeast(19.0) ? "<td class='dataValue'>" . $batchInfo->getNumberRecordsFailed() . ($batchInfo->getNumberRecordsFailed() == "1" ? " record" : " records") . "</td>" : "").
				"<td class='dataValue'>" . simpleFormattedTime($batchInfo->getCreatedDate()) . "</td>" .
				"<td class='dataValue'>" . simpleFormattedTime($batchInfo->getSystemModstamp()) . "</td>";
		
		print "</tr>";
	}
	print "</table>";
}

include_once ('footer.php');

?>