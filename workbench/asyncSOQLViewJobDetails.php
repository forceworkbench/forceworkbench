<!-- Receives and processes ajax requests from AsyncSOQL View Jobs functionality (through async_viewjobs.js) to view specific job details, to cancel running jobs and to view query results of submitted jobs. For each of these requets, it obtains raw result from rest explorer, formats it and sends it back to be displayed in Async View Jobs tab-->

<?php

require_once 'soxl/QueryObjects.php';
require_once 'session.php';
require_once 'shared.php';
require_once 'async/QueryFutureTask.php';

// for REST calls
require_once 'controllers/RestExplorerController.php';
require_once 'async/RestExplorerFutureTask.php';

set_exception_handler('handleAllExceptionsNoHeaders');
?>

<link
    rel="stylesheet" type="text/css"
    href="<?php echo getPathToStaticResource('/style/restexplorer.css'); ?>" />
<script
    type="text/javascript"
    src="<?php echo getPathToStaticResource('/script/restexplorer.js'); ?>"></script>

<?php

$action = $_REQUEST["action"];

if ($action == 'display') {
    $jobId = $_REQUEST["id"];
    displayJobDetails($jobId);
    
} else if ($action == 'cancel') {
    $jobId = $_REQUEST["id"];
    $cancelResponse = cancelJob($jobId);  
    if ($cancelResponse != null) {
        echo "<p>".$cancelResponse."</p>";
    } else {
        displayError("Cancel operation not successful");
    }   
} else if ($action == 'queryMore') {
    generateMore();
} else {
    displayError("Not a valid request.");
}


function displayJobDetails($jobId) {
    $c = new RestExplorerController();
    $c->getInstanceForAsyncSOQL($jobId,'GET');
    $f = new RestExplorerFutureTask($c);
    $f->returnUnformattedResult(true);
    $viewJobsResult = $f->enqueueOrPerform();
    if (isset($viewJobsResult)) {
        printResult($viewJobsResult,$jobId);
    }
}

function printResult($viewJobsResult,$jobId) {
    $viewJobsResultInst = $viewJobsResult->instResponse;
    $rawResponse = $viewJobsResult->rawResponse;
    $viewJobsResultInst = json_decode($viewJobsResultInst);
    $setCancel = false;
    $statusComplete = false;
    $jobIdValue = null;
    if (json_last_error() == JSON_ERROR_NONE) {
        echo "<table id='results' class='list'  style='table-layout:fixed;width:100%;'>";
        foreach ($viewJobsResultInst as $k => $v) {
            if ($k =='targetValueMap') {
                $jobIdValue = getJobIdValue($v);
            }
            echo "<tr><td style='font-weight:bold;width:24%;'>".$k."</td>";
            if (is_string($v)){
                if ($k =='status') {
                    fillStatusCell($v, $jobId);
                    if(strcmp($v,'Complete')==0) {
                         $statusComplete = true;
                    } else if(strcmp($v,'Running')==0) {
                         $setCancel = true;
                    }  
                } else {
                     echo "<td>".$v."</td></tr>";
                }
            } else {
                echo "<td style='word-wrap: break-word'>".json_encode($v)."</td></tr>";
            }
        }
        echo "</table>";
    }         
    if (isset($rawResponse)){
        printRawResponse($rawResponse);           
    }
    if ($setCancel==false) {
        print "<input type='hidden' id='disable_cancel' name='disable_cancel' value='true' />";
    } else {
        print "<input type='hidden' id='disable_cancel' name='disable_cancel' value='false' />";
    }
    if ($jobIdValue != null && $statusComplete == true) {
        print "<a id='viewResultsToggler' href='javascript:toggleTable();' style='float: right;'>Show Target Object SOQL results</a><br/><br/>";
        generateSoql($viewJobsResultInst, $jobIdValue, $jobId);
    }
}

function printRawResponse($rawResponse) {
    echo "<br/><a id='codeViewPortTogglerForDetails' href='javascript:toggleCodeViewPortForDetails();'>Show Raw Response</a>";
    echo "<div id='codeViewPortContainerForDetails' style='display:none'>";
    echo "<strong>Raw Response</strong>";
    echo "<p id='codeViewPortForDetails'>".htmlspecialchars($rawResponse->header)."<br/>".htmlspecialchars($rawResponse->body)."</p>";
    echo "</div>";   
}

function cancelJob($jobId) {
    $c = new RestExplorerController();
    $c->getInstanceForAsyncSOQL($jobId,'DELETE');
    $f = new RestExplorerFutureTask($c);
    $f->returnUnformattedResult(true);
    $cancelJobsResult = $f->enqueueOrPerform();
    $cancelRawResponse = null;
    if (isset($cancelJobsResult)) {
        $cancelRawResponse = $cancelJobsResult->rawResponse;
    }
    return $cancelRawResponse;
}

function generateSoql($viewJobsResultInst, $jobIdValue, $jobId){
    $targetObject;
    $targetFields = array();
    foreach ($viewJobsResultInst as $k => $v) {
        if ($k=='targetObject') {
            $targetObject = $v;
        }
        else if ($k=='targetFieldMap') {
            $targetFieldMap = $v;
            foreach ($targetFieldMap as $tk => $tv) {
                array_push($targetFields, $tv);
            }
        }
    }
    $query = "SELECT";
    foreach ($targetFields as $field) {
        $query = $query." ".$field.",";
    }
    $query = rtrim($query, ",");

    $trimmedJobId = substr($jobId, 0, 15); 
    $query = $query." FROM ".$targetObject." WHERE ".$jobIdValue." = '".$trimmedJobId."'"." OR ".$jobIdValue." = '".$jobId."'";


    $source = array(
    "QB_object_sel" => $targetObject,
    "soql_query" => $query,
    );
    $queryRequest = new QueryRequest($source);
    $_SESSION['queryRequest'] = $queryRequest;
    $ob = $queryRequest->getObject();

    $asyncJob = new QueryFutureTask($queryRequest);
    $results = $asyncJob->enqueueOrPerform();
    print "<div id='soql_results' class='hidden'>".$results."</div>";
}

function generateMore() {
    $queryRequest = $_SESSION['queryRequest'];
    $queryLocator = $_REQUEST["queryLocator"];
    $ob = $queryRequest->getObject();
    $queryRequest->setQueryAction('QueryMore');
    $asyncJob = new QueryFutureTask($queryRequest, $queryLocator);
    echo $asyncJob->perform();
}

function getJobIdValue($targetValueMap) {
    foreach ($targetValueMap as $tk => $tv) {
        if (($tk == '$JOB_ID') || ($tk == 'AsyncJob-$JOB_ID')) {
            return $tv;
        }
    }
    return null;
}

?>
