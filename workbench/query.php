<?php

require_once 'soxl/QueryObjects.php';
require_once 'session.php';
require_once 'shared.php';
require_once 'async/QueryFutureTask.php';

$MIGRATION_MESSAGE = "Visual Studio Code now includes <a href=\"https://developer.salesforce.com/tools/vscode/en/soql/writing\">SOQL code completion</a>. <a href=\"https://developer.salesforce.com/tools/vscode/en/getting-started/install\">Try it today!</a>";

//clear all saved queries in cookies
// TODO: remove after next version
$persistedSavedQueryRequestsKey = "PSQR@";
if (isset($_COOKIE[$persistedSavedQueryRequestsKey])) {
    setcookie($persistedSavedQueryRequestsKey, null, time() - 3600);
}

$defaultSettings['numFilters'] = 1;

if (isset($_POST['justUpdate']) && $_POST['justUpdate'] == true) {
    $queryRequest = new QueryRequest($defaultSettings);
    $queryRequest->setObject($_POST['QB_object_sel']);
} else if (isset($_POST['querySubmit'])) {
    $queryRequest = new QueryRequest($_REQUEST);
} else if(isset($_SESSION['lastQueryRequest'])) {
    $queryRequest = $_SESSION['lastQueryRequest'];
} else {
    $queryRequest = new QueryRequest($defaultSettings);
    $queryRequest->setObject(WorkbenchContext::get()->getDefaultObject());
}

if (isset($_GET['qrjb'])) {
    if ($queryRequestJsonString = base64_decode($_REQUEST['qrjb'], true)) {
        if ($queryRequestJson = json_decode($queryRequestJsonString, true)) {
            $queryRequest = new QueryRequest($queryRequestJson);
            $_POST['querySubmit'] = 'Query'; //simulate the user clicking 'Query' to run immediately
        } else {
            displayErrorBeforeForm('Could not parse query request');
        }
    } else {
        displayErrorBeforeForm('Could not decode query request');
    }
}

$_SESSION['lastQueryRequest'] = $queryRequest;

//Main form logic: When the user first enters the page, display form defaulted to
//show the query results with default object selected on a previous page, otherwise
// just display the blank form. When the user selects the SCREEN or CSV options, the
//query is processed by the correct function
if (isset($_POST['queryMore']) && isset($_POST['queryLocator'])) {
    require_once 'header.php';
    displayQueryForm($queryRequest);
    $queryRequest->setQueryAction('QueryMore');
    $asyncJob = new QueryFutureTask($queryRequest, $_POST['queryLocator']);
    echo $asyncJob->perform();
    include_once 'footer.php';
} else if (isset($_POST['querySubmit']) && $_POST['querySubmit']=='Query' && $queryRequest->getSoqlQuery() != null && ($queryRequest->getExportTo() == 'screen' || $queryRequest->getExportTo() == 'matrix')) {
    require_once 'header.php';
    displayQueryForm($queryRequest);
    if ($queryRequest->getExportTo() == 'matrix' && ($queryRequest->getMatrixCols() == "" || $queryRequest->getMatrixRows() == "")) {
        displayWarning("Both column and row must be specified for Matrix view.", false, true);
        return;
    }
    echo "<p><a name='qr'>&nbsp;</a></p>";

    echo updateUrlScript($queryRequest);
    $asyncJob = new QueryFutureTask($queryRequest);
    echo $asyncJob->enqueueOrPerform();
    include_once 'footer.php';
} else if (isset($_POST['querySubmit']) && $_POST['querySubmit']=='Query' && $queryRequest->getSoqlQuery() != null && strpos($queryRequest->getExportTo(), 'async_') === 0) {
    try {
        queryAsync($queryRequest);
    } catch (Exception $e) {
        require_once 'header.php';
        displayQueryForm($queryRequest);
        throw $e;
    }
} else if (isset($_POST['querySubmit']) && $_POST['querySubmit']=='Query' && $queryRequest->getSoqlQuery() != null && $queryRequest->getExportTo() == 'csv') {
    if (stripos($_POST['soql_query'], "count()") == false) {
        $task = new QueryFutureTask($queryRequest);
        $records = $task->query($queryRequest->getSoqlQuery(),$queryRequest->getQueryAction(),null,true);
        $task->exportQueryAsCsv($records,$queryRequest->getExportTo());
    } else {
        require_once 'header.php';
        displayQueryForm($queryRequest);
        print "</form>"; //could include inside because if IE page loading bug
        print "<p>&nbsp;</p>";
        displayError("count() is not supported for CSV. View as List or choose fields and try again.");
        include_once 'footer.php';
    }
} else {
    require_once 'header.php';
    if ($queryRequest->getExportTo() == null) $queryRequest->setExportTo('screen');
    $queryRequest->setQueryAction('Query');
    displayQueryForm($queryRequest);
    print "</form>"; //could include inside because if IE page loading bug
    include_once 'footer.php';
}

//Show the main SOQL query form with default query or last submitted query and export action (screen or CSV)

function displayQueryForm($queryRequest) {

    registerShortcut("Ctrl+Alt+W",
        "addFilterRow(document.getElementById('numFilters').value++);".
            "toggleFieldDisabled();");

    if ($queryRequest->getObject()) {;
        $describeSObjectResult = WorkbenchContext::get()->describeSObjects($queryRequest->getObject());

        $fieldValuesToLabels = array();
        foreach ($describeSObjectResult->fields as $field) {
            $fieldValuesToLabels[$field->name] = $field->name;
        }
    } else {
        displayInfo('First choose an object to use the SOQL builder wizard.');
    }

    print "<script type='text/javascript'>\n";
        print "var field_type_array = new Array();\n";
        if (isset($describeSObjectResult)) {
            foreach ($describeSObjectResult->fields as $fields => $field) {
                print " field_type_array[\"$field->name\"]=[\"$field->type\"];\n";
            }
        }

        $ops = getComparisonOperators();

        print "var compOper_array = new Array();\n";
        foreach ($ops as $opValue => $opLabel) {
            print " compOper_array[\"$opValue\"]=[\"$opLabel\"];\n";
        }
    print "</script>\n";
    print "<script src='" . getPathToStaticResource('/script/query.js') . "' type='text/javascript'></script>\n";

    print "<form method='POST' id='query_form' name='query_form' action='query.php'>\n";
    print getCsrfFormTag();
    print "<input type='hidden' name='justUpdate' value='0' />";
    print "<input type='hidden' id='numFilters' name='numFilters' value='" . count($queryRequest->getFilters()) ."' />";
    print "<p class='instructions'>Choose the object, fields, and criteria to build a SOQL query below:</p>\n";
    print "<table border='0' style='width: 100%;'>\n";
    print "<tr><td valign='top' width='1'>Object:";

    printObjectSelection($queryRequest->getObject(), 'QB_object_sel', "16", "onChange='updateObject();'", "queryable");

    print "<p/>Fields:<select id='QB_field_sel' name='QB_field_sel[]' multiple='mutliple' size='4' style='width: 16em;' onChange='buildQuery();'>\n";
    if (isset($describeSObjectResult)) {

        print   " <option value='count()'";
        if ($queryRequest->getFields() != null) { //check to make sure something is selected; otherwise warnings will display
            foreach ($queryRequest->getFields() as $selectedField) {
                if ('count()' == $selectedField) print " selected='selected' ";
            }
        }
        print ">count()</option>\n";

        //print ">$field->name</option>\n";
        foreach ($describeSObjectResult->fields as $fields => $field) {
            print   " <option value='$field->name'";
            if ($queryRequest->getFields() != null) { //check to make sure something is selected; otherwise warnings will display
                foreach ($queryRequest->getFields() as $selectedField) {
                    if ($field->name == $selectedField) print " selected='selected' ";
                }
            }
            print ">$field->name</option>\n";
        }
    }
    print "</select></td>\n";
    print "<td valign='top'>";

    print "<table border='0' align='right' style='width:100%'>\n";
    print "<tr><td valign='top' colspan=2>View as:<br/>" .
        "<label><input type='radio' id='export_action_screen' name='export_action' value='screen' ";
    if ($queryRequest->getExportTo() == 'screen') print "checked='true'";
    print " onClick='toggleMatrixSortSelectors(true);'>List</label>&nbsp;";

    print "<label><input type='radio' id='export_action_matrix' name='export_action' value='matrix' ";
    if ($queryRequest->getExportTo() == 'matrix') print "checked='true'";
    print " onClick='toggleMatrixSortSelectors(true);'>Matrix</label>";

    if (WorkbenchConfig::get()->value("allowQueryCsvExport")) {
        print "<label><input type='radio' id='export_action_csv' name='export_action' value='csv' ";
        if ($queryRequest->getExportTo() == 'csv') print "checked='true'";
        print " onClick='toggleMatrixSortSelectors(true);'>CSV</label>&nbsp;";
    }

    print "<label><input type='radio' id='export_action_async_csv' name='export_action' value='async_CSV' ";
    if ($queryRequest->getExportTo() == 'async_CSV') print "checked='true'";
    print " onClick='toggleMatrixSortSelectors(true);'>Bulk CSV</label>&nbsp;";

    print "<label><input type='radio' id='export_action_async_xml' name='export_action' value='async_XML' ";
    if ($queryRequest->getExportTo() == 'async_XML') print "checked='true'";
    print " onClick='toggleMatrixSortSelectors(true);'>Bulk XML</label>&nbsp;";


    print "<td valign='top' colspan=2>Deleted and archived records:<br/>" .
        "<label><input type='radio' name='query_action' value='Query' ";
    if ($queryRequest->getQueryAction() == 'Query') print "checked='true'";
    print " >Exclude</label>&nbsp;";

    print "<label><input type='radio' name='query_action' value='QueryAll' ";
    if ($queryRequest->getQueryAction() == 'QueryAll') print "checked='true'";
    print " >Include</label></td></tr></table>\n";


    print "<table id='QB_right_sub_table' border='0' align='right' style='width:100%'>\n";

    print "<tr id='matrix_selection_headers' style='display: none;'><td><br/>Columns:</td> <td><br/>Rows:</td> <td>&nbsp;</td></tr>\n";
    print "<tr id='matrix_selection_row' style='display: none;'><td><select id='matrix_cols' name='matrix_cols' style='width: 15em;' onChange='toggleFieldDisabled();buildQuery();' onkeyup='toggleFieldDisabled();buildQuery();'>";
    if(isset($fieldValuesToLabels)) printSelectOptions(array_merge(array(""=>""),$fieldValuesToLabels), $queryRequest->getMatrixCols());
    print "</select></td> <td><select id='matrix_rows' name='matrix_rows' style='width: 15em;' onChange='toggleFieldDisabled();buildQuery();' onkeyup='toggleFieldDisabled();buildQuery();'>";
    if(isset($fieldValuesToLabels)) printSelectOptions(array_merge(array(""=>""),$fieldValuesToLabels), $queryRequest->getMatrixRows());
    print "</select></td> <td><img onmouseover=\"Tip('Matrix view groups records into columns and rows of common field values.')\" align='absmiddle' src='" . getPathToStaticResource('/images/help16.png') . "'/></td></tr>\n";

    print "<tr id='sort_selection_headers'><td colspan='2'><br/>Sort results by:</td> <td><br/>Max Records:</td></tr>\n";
    print "<tr id='sort_selection_row'>";
    print "<td colspan='2'><select id='QB_orderby_field' name='QB_orderby_field' style='width: 16em;' onChange='buildQuery();'>\n";
    print "<option value=''></option>\n";
    if (isset($describeSObjectResult)) {
        foreach ($describeSObjectResult->fields as $fields => $field) {
            print   " <option value='$field->name'";
            if ($queryRequest->getOrderByField() != null && $field->name == $queryRequest->getOrderByField()) print " selected='selected' ";
            print ">$field->name</option>\n";
        }
    }
    print "</select>\n";

    $qBOrderbySortOptions = array(
        'ASC' => 'A to Z',
        'DESC' => 'Z to A'
    );

    print "<select id='QB_orderby_sort' name='QB_orderby_sort' style='width: 6em;' onChange='buildQuery();' onkeyup='buildQuery();'>\n";
    foreach ($qBOrderbySortOptions as $opKey => $op) {
        print "<option value='$opKey'";
        if (isset($_POST['QB_orderby_sort']) && $opKey == $_POST['QB_orderby_sort']) print " selected='selected' ";
        print ">$op</option>\n";
    }
    print "</select>\n";

    $qBNullsOptions = array(
        'FIRST' => 'Nulls First',
        'LAST' => 'Nulls Last'
    );
    print "<select id='QB_nulls' name='QB_nulls' style='width: 10em;' onChange='buildQuery();' onkeyup='buildQuery();'>\n";
    foreach ($qBNullsOptions as $opKey => $op) {
        print "<option value='$opKey'";
        if ($queryRequest->getOrderByNulls() != null && $opKey == $queryRequest->getOrderByNulls()) print " selected='selected' ";
        print ">$op</option>\n";
    }
    print "</select></td>\n";

    print "<td><input type='text' id='QB_limit_txt' size='10' name='QB_limit_txt' value='" . htmlspecialchars($queryRequest->getLimit() != null ? $queryRequest->getLimit() : null,ENT_QUOTES) . "' onkeyup='buildQuery();' /></td>\n";

    print "</tr>\n";

    print "</table>\n";
    print "</td></tr>\n";

    $filterRowNum = 0;
    foreach ($queryRequest->getFilters() as $filter) {
        print "<script>addFilterRow(" .
            $filterRowNum++ . ", " .
            "\"" . $filter->getField()     . "\", " .
            "\"" . $filter->getCompOper()  . "\", " .
            "\"" . htmlspecialchars($filter->getValue(), ENT_QUOTES)     . "\"" .
            ");</script>";
    }


    print "<tr><td valign='top' colspan=5><br/>Enter or modify a SOQL query below:\n" .
        "<br/><textarea id='soql_query_textarea' type='text' name='soql_query' rows='" . WorkbenchConfig::get()->value("textareaRows") . "' style='width: 99%; overflow: auto; font-family: monospace, courier;'>" . htmlspecialchars($queryRequest->getSoqlQuery(),ENT_QUOTES) . "</textarea>\n" .
        "</td></tr>\n";


    print "<tr><td colspan=1><input type='submit' name='querySubmit' class='disableWhileAsyncLoading' value='Query' onclick='return parentChildRelationshipQueryBlocker();' /></td>";

    print "<td colspan=4 align='right'>";
    print "&nbsp;&nbsp;" .
        "<img onmouseover=\"Tip('Where did saved queries go? They have been replaced with bookmarkable and shareable queries! Just run a query and bookmark the URL to save or copy and paste to share.')\" align='absmiddle' src='" . getPathToStaticResource('/images/help16.png') . "'/>";
    print "</td></tr></table><p/>\n";

    print "<script>toggleFieldDisabled();toggleMatrixSortSelectors(false);</script>";
}

function queryAsync($queryRequest) {
    if ($queryRequest->getQueryAction() == "QueryAll") {
        throw new WorkbenchHandledException("Including deleted and archived records not supported by Bulk Queries.");
    }

    $asyncConnection = WorkbenchContext::get()->getAsyncBulkConnection();

    $job = new JobInfo();

    // try to find actual object in FROM clause in case it is different from object set in form
    preg_match("/FROM\s(\w+)/i", $queryRequest->getSoqlQuery(), $fromMatches);
    // if we can't find it, go ahead and use the object from the form.
    // it's probably a malformed query anyway, but let SFDC error on it instead of Workbench
    $job->setObject(isset($fromMatches[1]) ? $fromMatches[1] : $queryRequest->getObject());

    $job->setOpertion("query");
    $job->setContentType(substr($queryRequest->getExportTo(), strlen("async_")));
    $job->setConcurrencyMode(WorkbenchConfig::get()->value("asyncConcurrencyMode"));

    try {
        $job = $asyncConnection->createJob($job);
    } catch (Exception $e) {
        if ((strpos($e->getMessage(), 'Unable to find object') > -1) || (strpos($e->getMessage(), 'InvalidEntity') > -1)) {
            throw new WorkbenchHandledException($e->getMessage());
        } else {
            throw $e;
        }
    }

    $asyncConnection->createBatch($job, $queryRequest->getSoqlQuery());
    $job = $asyncConnection->updateJobState($job->getId(), "Closed");


    header("Location: asyncStatus.php?jobId=" . $job->getId());
}

function displayErrorBeforeForm($msg) {
    include_once("header.php");
    print "<p>";
    displayError($msg);
    print "</p>";
}

function updateUrlScript($queryRequest) {
    return "<script type='text/javascript'>window.history.replaceState({}, document.title, '" . qrjb($queryRequest) . "');</script>";
}

function qrjb($queryRequest) {
    return  basename($_SERVER['SCRIPT_NAME']) . '?qrjb=' . urlencode(base64_encode($queryRequest->toJson())) .
        (WorkbenchConfig::get()->value("autoJumpToResults") ? '#qr' : '');
}

?>