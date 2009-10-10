<?php
require_once ('session.php');
require_once ('shared.php');

if(isset($_SESSION['resultsWithData'])){
	$csv_file = fopen('php://output','w') or die("Error opening php://output");
	$csv_filename = $_GET['action'] . "_results" . date('YmdHis') . ".csv";
	header("Content-Type: application/csv");
	header("Content-Disposition: attachment; filename=$csv_filename");
	
	foreach ($_SESSION['resultsWithData'] as $row) {
		fputcsv($csv_file, $row);
	}
	
	fclose($csv_file) or die("Error closing php://output");
} else {
	show_error("No results found to download.\n\n".
		"Results are not saved after navigating away from results page. ".
		"Ensure that another instance of Workbench is not running in a separate window or tab.", true, true);
}

?>
