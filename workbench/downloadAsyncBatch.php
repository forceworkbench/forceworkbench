<?php
require_once 'session.php';
require_once 'shared.php';
require_once 'restclient/BulkApiClient.php';

if (!isset($_GET['jobId']) || !isset($_GET['batchId']) || !isset($_GET['op'])) {
    displayError("'jobId', 'batchId', and 'op' parameters must be specified", true, true);
    exit;
}

try {
    $asyncConnection = getAsyncApiConnection();
    $jobInfo = $asyncConnection->getJobInfo($_GET['jobId']);
    if ($_GET['op'] == 'result') {
        $batchData = $asyncConnection->getBatchResults($_GET['jobId'], $_GET['batchId']);
    } elseif ($_GET['op'] == 'request') {
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
    $csvFilename = "bulk" . ucwords($jobInfo->getOpertion()). "_" . $_GET['op'] . "_" . $_GET['jobId'] . "_" . $_GET['batchId'] . ".csv";
    header("Content-Type: application/csv");
    header("Content-Disposition: attachment; filename=$csvFilename");
    print $batchData;
}
?>
