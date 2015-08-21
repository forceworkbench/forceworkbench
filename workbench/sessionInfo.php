<?php
require_once 'session.php';
require_once 'shared.php';

if (isset($_REQUEST['switchApiVersionTo'])) {
    $previousVersion = WorkbenchContext::get()->getApiVersion();
    WorkbenchContext::get()->clearCache();
    WorkbenchContext::get()->setApiVersion($_REQUEST['switchApiVersionTo']);
    unset($_SESSION['restExplorerController']);
    try {
        WorkbenchContext::get()->getPartnerConnection()->getServerTimestamp();
    } catch (Exception $e) {
        if (stripos($e->getMessage(), 'UNSUPPORTED_API_VERSION') > -1) {
            WorkbenchContext::get()->setApiVersion($previousVersion);
            WorkbenchContext::get()->getPartnerConnection()->getServerTimestamp();
            $unsupportedVersionError = "Selected API version is not supported by this Salesforce organization. Automatically reverted to prior version.";
        } else {
            throw $e;
        }
    }


    if (WorkbenchContext::get()->getDefaultObject()) {
        if (!in_array(WorkbenchContext::get()->getDefaultObject(), WorkbenchContext::get()->describeGlobal()->types)) {
            WorkbenchContext::get()->setDefaultObject(null);
        }
    }
}

require_once 'header.php';

if (isset($unsupportedVersionError)) {
    displayError($unsupportedVersionError);
    print "<p/>";
} else if (isset($cacheCleared)) {
    displayInfo("Cache Cleared Successfully");
    print "<p/>";
}
?>

<div>
<p class='instructions'>Below is information regarding the current user session:</p>
<div style='float: right;'>
    <form name="changeApiVersionForm" action="" method="POST">
    Change API Version: <?php
    print getCsrfFormTag();
    print "<select  method='POST' name='switchApiVersionTo' onChange='document.changeApiVersionForm.submit();'>";
    foreach ($GLOBALS['API_VERSIONS'] as $v) {
        print "<option value='$v'";
        if (WorkbenchContext::get()->getApiVersion() == $v) print " selected=\"selected\"";
        print ">" . $v . "</option>";
    }
    print "</select>";
    ?></form>
</div>

<?php

$sessionInfo = array();
$sessionInfo['Connection'] = array(
    'API Version' => WorkbenchContext::get()->getApiVersion(),
    'Client Id' => isset($_SESSION['tempClientId']) ? $_SESSION['tempClientId'] : WorkbenchConfig::get()->value('callOptions_client'),
    'Endpoint' => WorkbenchContext::get()->getPartnerConnection()->getLocation(),
    'Session Id' => WorkbenchContext::get()->getPartnerConnection()->getSessionId(),
);

$errors = array();

try {
    foreach (WorkbenchContext::get()->getUserInfo() as $uiKey => $uiValue) {
        if (stripos($uiKey,'org') !== 0) {
            $sessionInfo['User'][$uiKey] = $uiValue;
        } else {
            $sessionInfo['Organization'][$uiKey] = $uiValue;
        }
    }
} catch (Exception $e) {
    $errors[] = "Partner API Error: " . $e->getMessage();
}

if (WorkbenchContext::get()->isApiVersionAtLeast(10.0)) {
    try {
        foreach (WorkbenchContext::get()->getMetadataConnection()->describeMetadata(WorkbenchContext::get()->getApiVersion()) as $resultsKey => $resultsValue) {
            if ($resultsKey != 'metadataObjects' && !is_array($resultsValue)) {
                $sessionInfo['Metadata'][$resultsKey] = $resultsValue;
            }
        }
    } catch (Exception $e) {
        $sessionInfo['Metadata']['Error'] = $e->getMessage();
    }
}

if (count($errors) > 0) {
    print "<p>&nbsp;</p>";
    displayError($errors);
    print "</p>";
}

$tree = new ExpandableTree("sessionInfoTree", $sessionInfo);
$tree->setContainsDates(true);
$tree->setContainsIds(true);
$tree->setAdditionalMenus(ExpandableTree::getClearCacheMenu());
$tree->printTree();

print "</div>";
require_once 'footer.php';
?>

