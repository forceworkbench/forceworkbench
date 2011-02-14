<?php
require_once 'session.php';
require_once 'shared.php';

if (!isset($_GET['startUrl'])) {
    throw new Exception("startUrl param not set");
}

preg_match("@(https?://.*)/services@", $_SESSION['location'], $instUIDomain);

if (getConfig("useSfdcFrontdoor") == 'ALWAYS' || (getConfig("useSfdcFrontdoor") == 'AUTO' && !$_SESSION['sfdcUiSidLikelySet'])) {
    $_SESSION['sfdcUiSidLikelySet'] = true;
    $jumpUrl = "$instUIDomain[1]/secur/frontdoor.jsp?sid=". $_SESSION['sessionId'] . "&retURL=%2F" . $_GET['startUrl'];
} else {
    $jumpUrl = "$instUIDomain[1]/" . $_GET['startUrl'];
}

header("Location: " . $jumpUrl);
?>