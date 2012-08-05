<?php
require_once 'session.php';
require_once 'shared.php';
require_once 'put.php';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Language" content="UTF-8" />
        <meta http-equiv="Content-Type" content="text/xhtml; charset=UTF-8" />
        <link rel="stylesheet" href="<?php echo getPathToStaticResource('/style/master.css'); ?>" type="text/css" />
        <link rel="Shortcut Icon" type="image/png" href="<?php echo getPathToStaticResource('/images/bluecube-16x16.png'); ?>" />
        <title>Workbench - CSV Preview</title>
    </head>
<body>

<?php
if (isset($_SESSION['csv_array'])) {
    displayCsvArray($_SESSION['csv_array']);
} else {
    displayError("No CSV has been uploaded, or it is no longer active");
}

include_once 'footer.php';
?>