<?php
require_once 'session.php';
require_once 'shared.php';

const UNSUPPORTED_API_VERSION = 'UNSUPPORTED_API_VERSION';

if (isset($_REQUEST['switchApiVersionTo'])) {
    $previousVersion = getApiVersion();
    clearSessionCache(); //todo: move to ctx
    WorkbenchContext::get()->setApiVersion($_REQUEST['switchApiVersionTo']);
    try {
        WorkbenchContext::get()->getPartnerConnection()->getServerTimestamp();
    } catch (Exception $e) {
        if (stripos($e->getMessage(), UNSUPPORTED_API_VERSION) > -1) {
            
            header("Location: $_SERVER[PHP_SELF]?switchApiVersionTo=" . $previousVersion . "&" . UNSUPPORTED_API_VERSION);
        } else {
            throw $e;
        }
    }

}

require_once 'header.php';
?>
<p />
<p class='instructions'>Below is information regarding the current user session:</p>
<div style='float: right;'>
    <form name="changeApiVersionForm" action="<?php $_SERVER['PHP_SELF'] ?>">
    Change API Version: <?php
    print "<select  method='POST' name='switchApiVersionTo' onChange='document.changeApiVersionForm.submit();'>";
    foreach ($GLOBALS['API_VERSIONS'] as $v) {
        print "<option value='$v'";
        if (getApiVersion() == $v) print " selected=\"selected\"";
        print ">" . $v . "</option>";
    }
    print "</select>";
    ?></form>
</div>

<?php

if (isset($_REQUEST[UNSUPPORTED_API_VERSION])) {
    print "<div style='margin-top: 3em;'>";
    displayError("Selected API version is not supported by this Salesforce organization. Automatically reverted to prior version.",false,false);
    print "<p/>";
} else {
    print "<div>";
}

$sessionInfo = array();
$sessionInfo['Connection'] = array(
    'API Version' => getApiVersion(),
    'Client Id' => isset($_SESSION['tempClientId']) ? $_SESSION['tempClientId'] : getConfig('callOptions_client'), 
    'Endpoint' => WorkbenchContext::get()->getPartnerConnection()->getLocation(),
    'Session Id' => WorkbenchContext::get()->getPartnerConnection()->getSessionId(),
);

$errors = array();

try {
    foreach (WorkbenchContext::get()->getPartnerConnection()->getUserInfo() as $uiKey => $uiValue) {
        if (stripos($uiKey,'org') !== 0) {
            $sessionInfo['User'][$uiKey] = $uiValue;
        } else {
            $sessionInfo['Organization'][$uiKey] = $uiValue;
        }
    }
} catch (Exception $e) {
    $errors[] = "Partner API Error: " . $e->getMessage();
}

if (apiVersionIsAtLeast(10.0)) {
    try {
        foreach (WorkbenchContext::get()->getMetadataConnection()->describeMetadata(getApiVersion()) as $resultsKey => $resultsValue) {
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

printTree("sessionInfoTree", $sessionInfo, false, null, true, true);

print "</div>";
require_once 'footer.php';
?>