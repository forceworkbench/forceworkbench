<?php
require_once 'session.php';
require_once 'shared.php';

if (isset($_SESSION['resultsWithData'])) {
    $csvFile = fopen('php://output','w') or die("Error opening php://output");
    $csvFilename = $_GET['action'] . "_results" . date('YmdHis') . ".csv";
    header("Content-Type: application/csv");
    header("Content-Disposition: attachment; filename=$csvFilename");

    foreach ($_SESSION['resultsWithData'] as $row) {
        fputcsv($csvFile, $row);
    }

    fclose($csvFile) or die("Error closing php://output");
} else {
    displayError("No results found to download.\n\n".
        "Results are not saved after navigating away from results page. ".
        "Ensure that another instance of Workbench is not running in a separate window or tab.", true, true);
}

?>
