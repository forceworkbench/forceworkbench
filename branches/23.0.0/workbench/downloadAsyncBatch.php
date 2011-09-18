<?php
require_once 'session.php';
require_once 'shared.php';

try {
    if (!isset($_GET['jobId']) || !isset($_GET['batchId']) || !isset($_GET['op'])) {
        throw new Exception("'jobId', 'batchId', and 'op' parameters must be specified");
    } else if (!in_array($_GET['op'], array('request', 'result'))) {
        throw new Exception("Invalid operation specified");
    }

    $asyncConnection = WorkbenchContext::get()->getAsyncBulkConnection();
    $jobInfo = $asyncConnection->getJobInfo($_GET['jobId']);

    $fileContext = fopen('php://output','w') or die("Error opening php://output");

    if ($_GET['op'] == 'request' && $jobInfo->getOpertion() == "query") {
        $fileExt = "txt";
        header("Content-Type: text/plain");
    } else if ($_GET['op'] == 'request' && stristr($jobInfo->getContentType(), "ZIP")) {
        $fileExt = "zip";
        header("Content-Type: application/zip");
    } else if (stristr($jobInfo->getContentType(), "CSV")) {
        $fileExt = "csv";
        header("Content-Type: application/csv");
    } else if (stristr($jobInfo->getContentType(), "XML")) {
        $fileExt = "xml";
        header("Content-Type: application/xml");
    } else {
        throw new Exception("Unknown content type");
    }

    $filename = "bulk" . ucwords($jobInfo->getOpertion()) .
                "_" . $_GET['op'] .
                "_" . $_GET['jobId'] .
                "_" . $_GET['batchId'] .
                (isset($_GET['resultId']) && $_GET['resultId'] != null ? ("_" . $_GET['resultId']) : "") .
                "." . $fileExt;
    
    header("Content-Disposition: attachment; filename=$filename");

    if ($_GET['op'] == 'result') {
        if (isset($_GET['resultId'])) {
            $asyncConnection->getBatchResult($_GET['jobId'], $_GET['batchId'], $_GET['resultId'], $fileContext);
        } else {
            $asyncConnection->getBatchResults($_GET['jobId'], $_GET['batchId'], $fileContext);
        }
    } else if ($_GET['op'] == 'request') {
        $asyncConnection->getBatchRequest($_GET['jobId'], $_GET['batchId'], $fileContext);
    }

    fclose($fileContext) or die("Error closing php://output");
} catch (Exception $e) {
    displayError($e->getMessage(), true, true);
    exit;
}
?>
