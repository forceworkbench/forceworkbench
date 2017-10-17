<!-- Receives ajax request from Async SOQL Submit Job functionality (through async_submitjobs.js) constructs the Async Query, and submits it to Rest Explorer; receives the HTTP response, formats and displays it in the Submit Job tab -->

<?php
require_once 'session.php';
require_once 'shared.php';
require_once 'controllers/RestExplorerController.php';
require_once 'async/RestExplorerFutureTask.php';

set_exception_handler('handleAllExceptionsNoHeaders');

$sourceObject = $_REQUEST["sourceObject"];
$targetObject = $_REQUEST["targetObject"];
$sourceQuery = $_REQUEST["sourceQuery"];
$mappedfields = $_REQUEST["mappedfields"];
$valuefields = $_REQUEST["valuefields"];
$queryType = $_REQUEST["queryType"];
$targetExternalIdField = $_REQUEST["targetExternalIdField"];

//Constructing AsyncSOQL query
//For 39.0 and above the Async SOQL customers can access and use targetExternalIDFields while defining the query. 
if (WorkbenchContext::get()->isApiVersionAtLeast(39.0)) {
    if(!empty($targetExternalIdField)) {
        $req = '{"query": "'.$sourceQuery. '", "operation": "'.$queryType. '", "targetObject": "'.$targetObject. '", "targetExternalIdField": "'.$targetExternalIdField. '", "targetFieldMap": {' .$mappedfields. '}, "targetValueMap":{' .$valuefields. '}}'; 
    } else {
        $req = '{"query": "'.$sourceQuery. '", "operation": "'.$queryType. '", "targetObject": "'.$targetObject. '", "targetFieldMap": {' .$mappedfields. '}, "targetValueMap":{' .$valuefields. '}}'; 
    }
} else if (WorkbenchContext::get()->isApiVersionAtLeast(37.0)) { 
    $req = '{"query": "'. $sourceQuery . '","targetObject": "'. $targetObject . '","targetFieldMap": {' .$mappedfields. '},"targetValueMap":{' .$valuefields. '}}'; 
} else {
    $req = '{"query": "'. $sourceQuery . '","targetObject": "'. $targetObject . '","targetFieldMap": {' .$mappedfields. '}}'; 
}

// For POST method
$c = new RestExplorerController();
$c->getInstanceForAsyncSOQL(null,'POST');
$c->requestBody =  (get_magic_quotes_gpc()? stripslashes($req): $req);
echo '<script>console.log("submitJobResult C:")</script>';
echo '<script>console.log('. json_encode( $c ) .')</script>';
$f = new RestExplorerFutureTask($c);
$f->returnUnformattedResult(true);
echo '<script>console.log("F:")</script>';
echo '<script>console.log('. json_encode( $f ) .')</script>';
echo '<script>console.log("submitJobResult enqueueOrPerform")</script>';
$submitJobResult = $f->enqueueOrPerform();

if (isset($submitJobResult)) {
        $jobResultInst = $submitJobResult->instResponse;
        $rawResponse = $submitJobResult->rawResponse;
        $jobResultInst = json_decode($jobResultInst);
        if (isset($rawResponse) && (strpos($rawResponse->header, "400 Bad Request") > 0)) {
            $jobResultInst = $jobResultInst[0];
            print "<span style='color:Red;font-weight:bold'>ERROR</span>";
        }
        if (json_last_error() == JSON_ERROR_NONE) {
            echo "<table id='submitjob_results' class='list' style='table-layout:fixed;width:100%;'>";
            foreach ($jobResultInst as $k => $v) {
                echo "<tr><td style='font-weight:bold;width:25%;'>".$k."</td>";
                if (is_string($v)) {
                    if ($k=='status') {
                        fillStatusCell($v, $jobId);
                    } else {
                         echo "<td>".$v."</td></tr>";
                    }
                } else {
                    echo "<td style='word-wrap: break-word'>".json_encode($v)."</td></tr>";
                }
            }
            echo "</table>";
        }
        echo "<br/><a id='codeViewPortToggler' href='javascript:toggleCodeViewPort();'>Show Raw Response</a>";
        if (isset($rawResponse)){
            echo "<div id='codeViewPortContainer' style='display:none'>";
            echo "<strong>Raw Response</strong>";
            echo "<p id='codeViewPort'>".htmlspecialchars($rawResponse->header)."<br/>".htmlspecialchars($rawResponse->body)."</p>";
            echo "</div>";
        }
    }
?>
