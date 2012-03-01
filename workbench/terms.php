<?php
require_once 'session.php';
require_once 'shared.php';
require_once 'header.php';
echo file_get_contents(getConfig("termsFile"));
require_once 'footer.php';
?>
