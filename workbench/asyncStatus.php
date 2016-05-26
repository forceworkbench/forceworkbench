<?php
require_once 'session.php';
require_once 'shared.php';
require_once 'header.php';

print "<p/>";
if (!isset($_GET['jobId']) || $_GET['jobId'] == "") {
    displayInfo("Parameter 'jobId' must be specified.",false,false);
    print     "<p/>" .
            "<form action='' method='GET'>" .
            "Job Id: <input type='text' name='jobId'/> &nbsp;" .  
            "<input type='submit' value='Get Status'/>".
            "</form>";
    include_once 'footer.php';
    exit;
}

$_GET['jobId'] = htmlspecialchars(trim($_GET['jobId']));

try {
    $asyncConnection = WorkbenchContext::get()->getAsyncBulkConnection();
    $jobInfo = $asyncConnection->getJobInfo($_GET['jobId']);
    $batchInfos = $asyncConnection->getBatchInfos($_GET['jobId']);
} catch (Exception $e) {
    displayError($e->getMessage(), false, false);
    if (stripos($e->getMessage(), 'InvalidVersion') > -1) {
        print "<p/><em>Quick Fix: <a href='sessionInfo.php' target='_blank'>Change API Version</a></em>";
    }
    include_once 'footer.php';
    exit;
}

print "<p class='instructions'>A job has been uploaded to Salesforce via the Bulk API and is being processed asynchronously as resources are available. " .
      "Refresh this page periodically to view the latest status. Results can be downloaded when batches are complete.</p><p/>";

foreach ($batchInfos as $batchInfo) {
    if ($batchInfo->getState() == "Queued" || $batchInfo->getState() == "InProgress") {
        printAsyncRefreshBlock();
        break;
    }
}

print "<h3>Job: " . $jobInfo->getId() . "</h3>";

if ($jobInfo->getStateMessage() != "") {
    displayInfo($jobInfo->getStateMessage());
    print "<p/>";
}

$timeOnlyFormat = "h:i:s A";

print "<table width='100%' cellpadding='5' class='lightlyBoxed'>";

print "<tr>" .
        "<td class='dataLabel'>Status</td><td class='dataValue'>" . htmlspecialchars($jobInfo->getState()) . "</td>" .
        "<td class='dataLabel'>Records Processed</td><td class='dataValue'>" . htmlspecialchars($jobInfo->getNumberRecordsProcessed()) . "</td>" .
        "<td class='dataLabel'>Batches Queued</td><td class='dataValue'>" . htmlspecialchars($jobInfo->getNumberBatchesQueued()) . "</td>" .
        "</tr>";

print "<tr>" .
      "<td class='dataLabel'>Object</td><td class='dataValue'>" . htmlspecialchars($jobInfo->getObject()) . "</td>" .
      (WorkbenchContext::get()->isApiVersionAtLeast(19.0)
          ? "<td class='dataLabel'>Records Failed</td><td class='dataValue'>" . htmlspecialchars($jobInfo->getNumberRecordsFailed()) . "</td>"
          : "<td class='dataLabel'>Content Type</td><td class='dataValue'>" . htmlspecialchars($jobInfo->getContentType()) . "</td>"
      ) .
      "<td class='dataLabel'>Batches In Progress</td><td class='dataValue'>" . htmlspecialchars($jobInfo->getNumberBatchesInProgress()) . "</td>" .
     "</tr>";

print "<tr>" .
      "<td class='dataLabel'>Operation</td><td class='dataValue'>" . ucwords(htmlspecialchars($jobInfo->getOpertion())) . "</td>" .
      "<td class='dataLabel'>Concurrency Mode</td><td class='dataValue'>" . htmlspecialchars($jobInfo->getConcurrencyMode()) . "</td>" .
      "<td class='dataLabel'>Batches Completed</td><td class='dataValue'>" . htmlspecialchars($jobInfo->getNumberBatchesCompleted()) . "</td>" .
      "</tr>";

print "<tr>" .
      "<td class='dataLabel'>External Id</td><td class='dataValue'>" . htmlspecialchars($jobInfo->getExternalIdFieldName()) . "</td>" .
      "<td class='dataLabel'>API Version</td><td class='dataValue'>" . htmlspecialchars($jobInfo->getApiVersion()) . "</td>" .
      "<td class='dataLabel'>Batches Failed</td><td class='dataValue'>" . htmlspecialchars($jobInfo->getNumberBatchesFailed()) . "</td>" .
      "</tr>";

if (WorkbenchContext::get()->isApiVersionAtLeast(19.0)) {
    print "<tr>" .
          "<td class='dataLabel'>API Processing</td><td class='dataValue'>" . htmlspecialchars($jobInfo->getApiActiveProcessingTime()) . " ms</td>" .
          "<td class='dataLabel'>Apex Processing</td><td class='dataValue'>" . htmlspecialchars($jobInfo->getApexProcessingTime()) . " ms</td>" .
          "<td class='dataLabel'>Total Processing</td><td class='dataValue'>" . htmlspecialchars($jobInfo->getTotalProcessingTime()) . " ms</td>" .
          "</tr>";
}

print "<tr>" .
        "<td class='dataLabel'>Created</td><td class='dataValue'>" . localizeDateTimes(htmlspecialchars($jobInfo->getCreatedDate()), $timeOnlyFormat) . "</td>" .
        "<td class='dataLabel'>Last Modified</td><td class='dataValue'>" . localizeDateTimes(htmlspecialchars($jobInfo->getSystemModstamp()) ,$timeOnlyFormat) . "</td>" .
        "<td class='dataLabel'>Retries</td><td class='dataValue'>" . htmlspecialchars($jobInfo->getNumberRetries()) . "</td>" .
       "</tr>";

print "</table>";

print "<p>&nbsp;</p>";

if (count($batchInfos) > 0) {
    print "<h3>Batches</h3>";

    print "<table cellpadding='4' width='100%' class='lightlyBoxed'>";
    print "<tr>" .
          "<th>&nbsp;</th>" .
          "<th>Id</th>" .
          "<th>Status</th>" .
          "<th>Processed</th>" .
          (WorkbenchContext::get()->isApiVersionAtLeast(19.0) ? "<th>Failed</th>" : "") .
          "<th>Created</th>" .
          "<th>Last Modified</th>" .    
         "</tr>";
    
    foreach ($batchInfos as $batchInfo) {
        print "<tr><td class='dataValue'>";
        if ($batchInfo->getState() == "Completed" || $batchInfo->getState() == "Failed") {
            $batchResultList = array(null); // default to an array of one null 
            if ($jobInfo->getOpertion() == 'query' && $batchInfo->getState() == "Completed") {
                $batchResultList = $asyncConnection->getBatchResultList($jobInfo->getId(), $batchInfo->getId());
            }
            foreach($batchResultList as $resultId) {
                print "<a href='downloadAsyncBatch.php?op=result&jobId=" . htmlspecialchars($jobInfo->getId()) . "&batchId=" . htmlspecialchars($batchInfo->getId()) . "&resultId=" . $resultId . "'>" .
                      "<img src='" . getPathToStaticResource('/images/downloadIcon' . htmlspecialchars($batchInfo->getState()) . '.gif') . "' border='0' onmouseover=\"Tip('Download " . htmlspecialchars($batchInfo->getState()) . " Batch Results')\"/>" .
                      "</a><br/>";
            }
        } else {
            print "&nbsp;";
        }
        print "</td>";

        $processingTimeDetails = "API Processing: " . htmlspecialchars($batchInfo->getApiActiveProcessingTime()) . " ms<br/>" .
                     "Apex Processing: "  .  htmlspecialchars($batchInfo->getApexProcessingTime()) .   " ms<br/>" .
                     "Total Processing: "  . htmlspecialchars($batchInfo->getTotalProcessingTime()) .  " ms<br/>";

        print "<td class='dataValue'>" .
              (WorkbenchContext::get()->isApiVersionAtLeast(19.0)
                  ? "<a href='downloadAsyncBatch.php?op=request&jobId=" . htmlspecialchars($jobInfo->getId()) . "&batchId=" . htmlspecialchars($batchInfo->getId()) .
                     "' onmouseover=\"Tip('Download Batch Request')\"/>" . htmlspecialchars($batchInfo->getId()) . "</a>"
                  : htmlspecialchars($batchInfo->getId())) .
              "</td>" . 
              "<td class='dataValue'>" . htmlspecialchars($batchInfo->getState()) . (($batchInfo->getStateMessage() != "") ? (": " . htmlspecialchars($batchInfo->getStateMessage())) : "") . "</td>" .
              (WorkbenchContext::get()->isApiVersionAtLeast(19.0)
                  ? "<td class='dataValue pseudoLink' style='cursor: default' onmouseover=\"Tip('$processingTimeDetails')\"/>"
                  : "<td class='dataValue'>") .
                     htmlspecialchars($batchInfo->getNumberRecordsProcessed()) . (htmlspecialchars($batchInfo->getNumberRecordsProcessed()) == "1" ? " record" : " records") .
              "</td>" .
              (WorkbenchContext::get()->isApiVersionAtLeast(19.0)
                  ? "<td class='dataValue'>" . htmlspecialchars($batchInfo->getNumberRecordsFailed()) .
                    ($batchInfo->getNumberRecordsFailed() == "1" 
                        ? " record" 
                        : " records") . "</td>" 
                  : "").
              "<td class='dataValue'>" . localizeDateTimes(htmlspecialchars($batchInfo->getCreatedDate()), $timeOnlyFormat) . "</td>" .
              "<td class='dataValue'>" . localizeDateTimes(htmlspecialchars($batchInfo->getSystemModstamp()), $timeOnlyFormat) . "</td>";

        print "</tr>";
    }
    
    print "</table>";
}

include_once 'footer.php';
?>