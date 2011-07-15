<?php
require_once 'session.php';
require_once 'shared.php';
require_once 'put.php';
$_REQUEST['sourceType'] = "singleRecord";
put('retrieve');
?>