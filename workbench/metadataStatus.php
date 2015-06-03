<?php
require_once 'session.php';
require_once 'shared.php';

if (!WorkbenchContext::get()->isApiVersionAtLeast(10.0)) {
    displayError("Metadata API not supported prior to version 10.0", true, true);
    exit;
}

if (isset($_GET['asyncProcessId'])) {
    $asyncProcessId = htmlspecialchars($_GET['asyncProcessId']);
}

if (isset($asyncProcessId) && isset($_GET['downloadZip'])) {
    if (!isset($_SESSION['retrievedZips'][$asyncProcessId])) {
        displayError("No zip file found for async process id '$asyncProcessId'. Note, retrieve results are deleted after first download or navigating away from this page.", true, true);
    }

    header("Content-Type: application/zip");
    header("Content-Disposition: attachment; filename=retrieve_$asyncProcessId.zip");
    print $_SESSION['retrievedZips'][$asyncProcessId];
    unset($_SESSION['retrievedZips'][$asyncProcessId]);
    exit;
}

$isDeployOperation = false;
$isRetrieveOperation = false;

if (isset($_GET['op'])) {
    $operation = htmlspecialchars($_GET['op']);
    $isDeployOperation = ($operation == 'D');
    $isRetrieveOperation = ($operation == 'R');
}

if (!isset($asyncProcessId) || (!$isDeployOperation && !$isRetrieveOperation)) {
    $asyncProcessId = '';
    require_once 'header.php';
    print "<p/>";
    displayInfo("Specify process details",false,false);
    print     "<p/>" .
            "<form action='' method='GET'>" .
            "<table><tr><td>AsyncProcessId:</td>" .
            "<td><input type='text' name='asyncProcessId'" . "value='" . $asyncProcessId . "'/></td></tr>" .
            "<tr><td>Async Operation:</td>" .  
            "<td><input type='radio' name='op' value='D'" .  ($isDeployOperation ? "checked='checked'":"") . ">Deploy <input type='radio' name='op' value='R'" .  ($isRetrieveOperation ? "checked='checked'":"") . ">Retrieve</td></tr>" .
            "<tr><td>&nbsp;</td><td>&nbsp;</td></tr>".
            "<tr><td><input type='submit' value='Get Status'/></td><td>&nbsp;</td></tr></table>".
            "</form>";
    include_once 'footer.php';
    exit;
}

require_once 'header.php';
print "<p class='instructions'>A Metadata API operation has been performed, which requires asynchronous processing as resources are available. " .
      "Refresh this page periodically to view the latest status. Results will be available once processing is complete.</p><p/>";

require_once 'soapclient/SforceMetadataClient.php';
try {

    $deployOn29OrHigher = $isDeployOperation && WorkbenchContext::get()->isApiVersionAtLeast(29.0);
    $retrieveOn31OrHigher = $isRetrieveOperation && WorkbenchContext::get()->isApiVersionAtLeast(31.0);
    $retrieveOn34OrHigher = $isRetrieveOperation && WorkbenchContext::get()->isApiVersionAtLeast(34.0);

    if ($deployOn29OrHigher) {
        $asyncResults = WorkbenchContext::get()->getMetadataConnection()->checkDeployStatus($asyncProcessId, true, $debugInfo);
    } else if ($retrieveOn31OrHigher) {
        if ($retrieveOn34OrHigher) {
            $asyncResults = WorkbenchContext::get()->getMetadataConnection()->checkRetrieveStatus($asyncProcessId, true, $debugInfo);
        }
        else {
            $asyncResults = WorkbenchContext::get()->getMetadataConnection()->checkRetrieveStatus($asyncProcessId, NULL, $debugInfo);
        }
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
            "id" => null,
            "done" => null,
            "status" => null,
            "checkOnly" => null,
            "rollbackOnError" => null,
            "ignoreWarnings" => null,
            "numberComponentErrors" => null,
            "numberTestErrors" => null,
            "numberComponentsDeployed" => null,
            "numberTestsCompleted" => null,
            "numberComponentsTotal" => null,
            "numberTestsTotal" => null,
            "createdDate" => null,
            "startDate" => null,
            "lastModifiedDate" => null,
            "completedDate" => null
        );
    } else {
        $orderedAsyncResults = array(
            "id" => null,
            "done" => null,
            "state" => null
        );
    }

    if ($retrieveOn31OrHigher) {
        $orderedAsyncResults = array(
            "id" => $asyncResults->id,
            "done" => $asyncResults->done,
            "status" => $asyncResults->status
        );
    } else {
        foreach ($asyncResults as $resultName => $resultValue) {
            $orderedAsyncResults[$resultName] = $resultValue;
        }
    }

    print "<h3>Status</h3>";
    print "<table class='lightlyBoxed' cellpadding='5' width='100%'>\n";
    $rowNum = 0;
    foreach ($orderedAsyncResults as $resultName => $resultValue) {
        // Details and success flag will be displayed in results section, skip for now.
        if ($resultName == 'details' || $resultName == 'success') continue;
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

    if ($deployOn29OrHigher && !$asyncResults->done && $asyncResults->status == 'InProgress') {
        print "<p>&nbsp;</p><h3>Results <img src='" . getPathToStaticResource('/images/wait16trans.gif') . " align='absmiddle'/> </h3>";
        $hasInProgressDetailsToPrint = false;
        $results = array();

        if (isset($asyncResults->details)) {
            processDeployResultsForApiVersion29AndHigher($asyncResults);
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
    } else if ($asyncResults->done) {
        print "<p>&nbsp;</p><h3>Results</h3>";

        if ($deployOn29OrHigher) {
            processDeployResultsForApiVersion29AndHigher($asyncResults);

            $results_extra_params= array();
            $results_extra_params['success'] = $asyncResults->success;

            $results = $asyncResults->details;
            $results = (object) array_merge($results_extra_params, (array) $results);
        } else {
            if ($isDeployOperation) {
               $results = WorkbenchContext::get()->getMetadataConnection()->checkDeployStatus($asyncProcessId, false, $debugInfo);
               processDeployResultsForApiVersion28AndLower($results);
            }
            else {
                if ($retrieveOn31OrHigher) {
                    $results = $asyncResults;
                }
                else {
                    $results = WorkbenchContext::get()->getMetadataConnection()->checkRetrieveStatus($asyncProcessId, NULL, $debugInfo);
                }
            }
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

function processDeployResultsForApiVersion29AndHigher($deployResults) {
    if (!isset($deployResults->details)) return;

    if (isset($deployResults->details->componentFailures)) {
       if (!is_array($deployResults->details->componentFailures)) {
           $deployResults->details->componentFailures = array($deployResults->details->componentFailures);
       }
    }

    if (isset($deployResults->details->componentSuccesses)) {
       if (!is_array($deployResults->details->componentSuccesses)) {
           $deployResults->details->componentSuccesses = array($deployResults->details->componentSuccesses);
       }
    }

    if (isset($deployResults->details->runTestResult)) {
        $runTestResult = $deployResults->details->runTestResult;
        processRunTestResult($runTestResult);
    }
}

function processDeployResultsForApiVersion28AndLower($deployResults) {

    if (isset($deployResults->messages)) {
       if (!is_array($deployResults->messages)) {
           $deployResults->messages = array($deployResults->messages);
       }
    }

    if (isset($deployResults->runTestResult)) {
        $runTestResult = $deployResults->runTestResult;
        processRunTestResult($runTestResult);
    }
}

function processRunTestResult($runTestResult) {
    if (isset($runTestResult->failures)) {
        if (!is_array($runTestResult->failures)) {
            $runTestResult->failures = array($runTestResult->failures);
        }
    }
    if (isset($runTestResult->successes)) {
        if (!is_array($runTestResult->successes)) {
            $runTestResult->successes = array($runTestResult->successes);
        }
    }
}

?>
