<?php
require_once 'session.php';
require_once 'shared.php';

if (!WorkbenchContext::get()->isApiVersionAtLeast(10.0)) {
    displayError("Metadata API not supported prior to version 10.0", true, true);
    exit;
}

if (!isset($_GET['asyncProcessId'])) {
    require_once 'header.php';
    print "<p/>";
    displayInfo("Parameter 'asyncProcessId' must be specified.",false,false);
    print     "<p/>" .
            "<form action='' method='GET'>" .
            "Async Process Id: <input type='text' name='asyncProcessId'/> &nbsp;" .  
            "<input type='submit' value='Get Status'/>".
            "</form>";
    include_once 'footer.php';
    exit;
}

$asyncProcessId = htmlspecialchars($_GET['asyncProcessId']);

if (isset($_GET['downloadZip'])) {
    if (!isset($_SESSION['retrievedZips'][$asyncProcessId])) {
        displayError("No zip file found for async process id '$asyncProcessId'. Note, retrieve results are deleted after first download or navigating away from this page.", true, true);
    }

    header("Content-Type: application/zip");
    header("Content-Disposition: attachment; filename=retrieve_$asyncProcessId.zip");
    print $_SESSION['retrievedZips'][$asyncProcessId];
    unset($_SESSION['retrievedZips'][$asyncProcessId]);
    exit;
}

require_once 'header.php';
print "<p class='instructions'>A Metadata API operation has been performed, which requires asynchronous processing as resources are available. " .
      "Refresh this page periodically to view the latest status. Results will be available once processing is complete.</p><p/>";

require_once 'soapclient/SforceMetadataClient.php';
try {

        //if they don't tell us the operation name, let's guess from the deploy-specific checkOnly flag (doesn't work for all api versions).
    $isDeployOperation = isset($_REQUEST['op'])
                    ? htmlspecialchars($_REQUEST['op'])
                    : (isset($asyncResults->checkOnly)
                        ? true 
                        : false);
    if ($isDeployOperation && WorkbenchContext::get()->isApiVersionAtLeast(29.0)) {
        $deployOn29OrHigher = true;  
    } else {
        $deployOn29OrHigher = false;  
    }
    if ($deployOn29OrHigher) {
        $asyncResults = WorkbenchContext::get()->getMetadataConnection()->checkDeployStatus($asyncProcessId, true);
    } else {
        $asyncResults = WorkbenchContext::get()->getMetadataConnection()->checkStatus($asyncProcessId);
    }

    if (!isset($asyncResults)) {
        displayError("No results returned for '$asyncProcessId'", false, true);
    }

    if (!$asyncResults->done) {
        printAsyncRefreshBlock();
    }

    if ($deployOn29OrHigher) {
        $orderedAsyncResults = array(
            "id"=>null, "done"=>null, 
            "status"=>null, "checkOnly"=>null, 
            "rollbackOnError"=>null, "ignoreWarnings"=>null,
            "numberComponentErrors"=>null, "numberComponentsDeployed"=>null,
            "numberComponentsTotal"=>null, "numberTestErrors"=>null, 
            "numberTestsCompleted"=>null, "numberTestsTotal"=>null,
            "createdDate"=>null, "startDate"=>null,
            "lastModifiedDate"=>null, "completedDate"=>null
        );
    } else {
        $orderedAsyncResults = array("id"=>null,"done"=>null,"state"=>null);
    }
    foreach ($asyncResults as $resultName => $resultValue) {
        $orderedAsyncResults[$resultName] = $resultValue;
    }

    print "<h3>Status</h3>";
    print "<table class='lightlyBoxed' cellpadding='5' width='100%'>\n";
    $rowNum = 0;
    foreach ($orderedAsyncResults as $resultName => $resultValue) {
        // Details will be displayed in results section, skip for now.
        if ($resultName == 'details') continue;
        if (++$rowNum % 2) {
            print "<tr>";
            printStatusCell($resultName, $resultValue);
        } else {
            printStatusCell($resultName, $resultValue);
            print "</td></tr>\n";
        }
    }
    if ($rowNum % 2) {
        print "<td width='25%'>&nbsp;</td><td width='25%'>&nbsp;</td></tr>";
    }
    print "</table>\n";

    if (deployOn29OrHigher && ! $asyncResults->done && $asyncResults->status == 'InProgress') {
        print "<p>&nbsp;</p><h3>Failures encountered so far</h3>";
        $hasInProgressDetailsToPrint = false;
        $results = array();

        if (isset($asyncResults->details)) {
            if (isset($asyncResults->details->componentFailures)) {
                $hasInProgressDetailsToPrint = true;
                $results['componentFailures'] = $asyncResults->details->componentFailures;
            }
            if (isset($asyncResults->details->runTestResult) && isset($asyncResults->details->runTestResult->failures) ) {
                $hasInProgressDetailsToPrint = true;
                $results['testFailures'] = $asyncResults->details->runTestResult->failures;
            }
        }
        if ($hasInProgressDetailsToPrint) {
	    $processedResult = ExpandableTree::processResults($results);

            $tree = new ExpandableTree("metadataInProgressDetailsTree", $processedResult);
	    $tree->setForceCollapse(false);
	    $tree->setContainsIds(true);
	    $tree->setContainsDates(true);
	    $tree->printTree();
        }
        else {
            print "<p>None</p>";
        }
    } else if ($asyncResults->done) {
        print "<p>&nbsp;</p><h3>Results</h3>";

        if ($deployOn29OrHigher) {
            $results = $asyncResults->details;
        } else {
            $results = $isDeployOperation
                    ? WorkbenchContext::get()->getMetadataConnection()->checkDeployStatus($asyncProcessId, false, $debugInfo)
                    : WorkbenchContext::get()->getMetadataConnection()->checkRetrieveStatus($asyncProcessId, $debugInfo);
        }

        $zipLink = null;
        if (isset($results->zipFile) || isset($results->retrieveResult->zipFile) ) {
            if (isset($results->zipFile)) {
                $_SESSION['retrievedZips'][$asyncResults->id] = $results->zipFile;
                unset($results->zipFile);
            } else if (isset($results->retrieveResult->zipFile)) {
                $_SESSION['retrievedZips'][$asyncResults->id] = $results->retrieveResult->zipFile;
                unset($results->retrieveResult->zipFile);
            }

            displayInfo("Retrieve result ZIP file is ready for download.");
            print "<p/>";

            $zipLink = " | <a id='zipLink' href='?asyncProcessId=$asyncResults->id&downloadZip' onclick='undownloadedZip=false;' style='text-decoration:none;'>" .
                       "<span style='text-decoration:underline;'>Download ZIP File</span> <img src='" . getPathToStaticResource('/images/downloadIconCompleted.gif') . "' border='0'/>" .
                       "</a></p>";
        }

        
        $processedResult = ExpandableTree::processResults($results);
        $tree = new ExpandableTree("metadataStatusResultsTree", $processedResult);
        $tree->setForceCollapse(true);
        $tree->setAdditionalMenus($zipLink);
        $tree->setContainsIds(true);
        $tree->setContainsDates(true);
        $tree->printTree();

        if (isset($debugInfo["DebuggingInfo"]->debugLog)) {
            print "<p>&nbsp;</p><h3>Debug Logs</h3>";
            print("<pre>" . addLinksToIds(htmlspecialchars($debugInfo["DebuggingInfo"]->debugLog,ENT_QUOTES)) . '</pre>');
        }

        // if metadata changes were deployed, clear the cache because describe results will probably be different
        if ($isDeployOperation) {
            WorkbenchContext::get()->clearCache();
        }
    }
} catch (Exception $e) {
    displayError($e->getMessage(), false, true);
}
?>
<script>
var undownloadedZip = false;
window.onbeforeunload = function() {
  if (undownloadedZip) {
    return 'There is a ZIP file awaiting download. It will be deleted if you refresh or navigate away from this page.';
  }
}

if (document.getElementById("zipLink") != null) {
    undownloadedZip = true;
}
</script>
<?php

include_once 'footer.php';

function printStatusCell($resultName, $resultValue) {
    print "<td style='text-align: right; padding-right: 2em; font-weight: bold;'>" . unCamelCase($resultName) . "</td><td>";
    if (is_bool($resultValue)) {
        print $resultValue ? "true" : "false";
    } else {
        print localizeDateTimes($resultValue);
    }
    print "</td>";
}
?>
