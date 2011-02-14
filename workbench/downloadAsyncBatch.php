<?php
require_once 'session.php';
require_once 'shared.php';
require_once 'bulkclient/BulkApiClient.php';

if (!isset($_GET['jobId']) || !isset($_GET['batchId']) || !isset($_GET['op'])) {
    displayError("'jobId', 'batchId', and 'op' parameters must be specified", true, true);
    exit;
}

try {
    $asyncConnection = getAsyncApiConnection();
    $jobInfo = $asyncConnection->getJobInfo($_GET['jobId']);
    if ($_GET['op'] == 'result') {
        if (isset($_GET['resultId'])) {
            $batchData = $asyncConnection->getBatchResult($_GET['jobId'], $_GET['batchId'], $_GET['resultId']);
        } else {
            $batchData = $asyncConnection->getBatchResults($_GET['jobId'], $_GET['batchId']);
        }
    } else if ($_GET['op'] == 'request') {
        if (!apiVersionIsAtLeast(19.0)) {
            displayError("Downloading batch requests only supported in API 19.0 and higher", true, true);
            exit;
        }

        $batchData = $asyncConnection->getBatchRequest($_GET['jobId'], $_GET['batchId']);
    } else {
        displayError("Invalid operation specified", true, true);
        exit;
    }
} catch (Exception $e) {
    displayError($e->getMessage(), true, true);
    exit;
}

if (strpos($batchData, "<exceptionCode>")) {
    $asyncError = new SimpleXMLElement($batchData);
    displayError($asyncError->exceptionCode . ": " . $asyncError->exceptionMessage, true, true);
    exit;
} else if ($batchData == "") {
    displayError("No results found. Confirm job or batch has not expired.", true, true);
    exit;
} else {
    if (stristr($jobInfo->getContentType(), "CSV")) {
        $fileExt = "csv";
        header("Content-Type: application/csv");
    } else if (stristr($jobInfo->getContentType(), "XML")) {
        $fileExt = "xml";
        header("Content-Type: application/xml");
    } else {
        throw new Exception("Unknown content type");
    }
    
    
    $csvFilename = "bulk" . ucwords($jobInfo->getOpertion()). "_" . $_GET['op'] . "_" . $_GET['jobId'] . "_" . $_GET['batchId'] . "." . $fileExt;
    header("Content-Disposition: attachment; filename=$csvFilename");
    print $batchData;
}
?>
