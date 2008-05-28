<?php
require_once ('session.php');
require_once ('shared.php');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<meta http-equiv="Content-Language" content="UTF-8" />
<meta http-equiv="Content-Type" content="text/xhtml; charset=UTF-8" />
<link rel="stylesheet" href="style/master.css" type="text/css" />
<title>Workbench CSV Preview</title>
</head>
<body>

<?php
csv_array_show($_SESSION[csv_array]);
include_once ('footer.php');
?>
