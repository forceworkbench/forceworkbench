<?php
require_once 'session.php';
require_once 'shared.php';

if (!isset($_GET['startUrl'])) {
    throw new Exception("startUrl param not set");
}

preg_match("@((https?://)((.*)-api)?(.*))/services@", WorkbenchContext::get()->getPartnerConnection()->getLocation(), $sfdcApiHost);

// [1] => https://na4-api.salesforce.com
// [2] => https://
// [3] => na4-api
// [4] => na4
// [5] => .salesforce.com

if ($sfdcApiHost[3] != null) {
    //special cases
    switch ($sfdcApiHost[4]) {
        case "na0": $sfdcApiHost[4] = "ssl"; break;
        case "ap0": $sfdcApiHost[4] = "ap"; break;
        case "eu0": $sfdcApiHost[4] = "emea"; break;
    }
    $sfdcUiHost = $sfdcApiHost[2] . $sfdcApiHost[4] . $sfdcApiHost[5];
} else {
    $sfdcUiHost = $sfdcApiHost[1];
}

if (WorkbenchConfig::get()->value("useSfdcFrontdoor") == 'ALWAYS' || (WorkbenchConfig::get()->value("useSfdcFrontdoor") == 'AUTO' && !WorkbenchContext::get()->isUiSessionLikelySet())) {
    WorkbenchContext::get()->setIsUiSessionLikelySet(true);
    $jumpUrl = "$sfdcUiHost/secur/frontdoor.jsp?sid=". WorkbenchContext::get()->getSessionId() . "&retURL=%2F" . $_GET['startUrl'];
} else {
    $jumpUrl = "$sfdcUiHost/" . $_GET['startUrl'];
}

header("Location: " . $jumpUrl);
?>