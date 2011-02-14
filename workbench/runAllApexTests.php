<?php
require_once 'session.php';
require_once 'shared.php';
header("Location: " . getJumpToSfdcUrlPrefix() . 'setup%2Fbuild%2FrunAllApexTests.apexp');
?>