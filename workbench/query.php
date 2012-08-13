<?php

require_once 'soxl/QueryObjects.php';
require_once 'session.php';
require_once 'shared.php';
require_once 'async/QueryFutureTask.php';

$defaultSettings['numFilters'] = 1;
//clear the form if the user changes the object
if (isset($_POST['justUpdate']) && $_POST['justUpdate'] == true) {
    $queryRequest = new QueryRequest($defaultSettings);
    $queryRequest->setObject($_POST['QB_object_sel']);
} else {
    //create a new QueryRequest object to save named and/or last query
    $lastQr = new QueryRequest($_REQUEST);

    //save last query. always do this even if named.
    if ((isset($_POST['querySubmit']) && $_POST['querySubmit']=='Query') || (isset($_POST['doSaveQr']) && $_POST['doSaveQr'] == 'Save' )) {
        $_SESSION['lastQueryRequest'] = $lastQr;
    }

    $persistedSavedQueryRequestsKey = "PSQR@";
    if (WorkbenchConfig::get()->value("savedQueriesAndSearchesPersistanceLevel") == 'USER') {
        $persistedSavedQueryRequestsKey .= WorkbenchContext::get()->getUserInfo()->userId . "@" . WorkbenchContext::get()->getUserInfo()->organizationId;
    } else if (WorkbenchConfig::get()->value("savedQueriesAndSearchesPersistanceLevel") == "ORG") {
        $persistedSavedQueryRequestsKey .= WorkbenchContext::get()->getUserInfo()->organizationId;
    } else if (WorkbenchConfig::get()->value("savedQueriesAndSearchesPersistanceLevel") == 'ALL') {
        $persistedSavedQueryRequestsKey .= "ALL";
    }

    //populate queryRequest for this page view. first see if user wants to retreive a saved query,
    //then see if there was a last query, else just show a null query with default object.
    if (isset($_REQUEST['getQr']) && $_REQUEST['getQr'] != "" && isset($_SESSION['savedQueryRequests'][$_REQUEST['getQr']])) {
        $queryRequest = $_SESSION['savedQueryRequests'][$_REQUEST['getQr']];
        $_POST['querySubmit'] = 'Query'; //simulate the user clicking 'Query' to run immediately
    } else if (isset($_SESSION['lastQueryRequest'])) {
        $queryRequest = $_SESSION['lastQueryRequest'];
    } else {
        $queryRequest = new QueryRequest($defaultSettings);
        $queryRequest->setObject(WorkbenchContext::get()->getDefaultObject());
        if (WorkbenchConfig::get()->value("savedQueriesAndSearchesPersistanceLevel") != 'NONE' && !isset($_SESSION['savedQueryRequests']) && isset($_COOKIE[$persistedSavedQueryRequestsKey])) {
            $_SESSION['savedQueryRequests'] = unserialize($_COOKIE[$persistedSavedQueryRequestsKey]);
        }
    }

    //clear  all saved queries in scope if user requests
    if (isset($_POST['clearAllQr']) && $_POST['clearAllQr'] == 'Clear All') {
        $_SESSION['savedQueryRequests'] = null;
        if (WorkbenchConfig::get()->value("savedQueriesAndSearchesPersistanceLevel") != 'NONE') {
            setcookie($persistedSavedQueryRequestsKey,null,time()-3600);
        }
    }

    //save as named query
    if (isset($_POST['doSaveQr']) && $_POST['doSaveQr'] == 'Save' && isset($_REQUEST['saveQr']) && strlen($_REQUEST['saveQr']) > 0) {
        $_SESSION['savedQueryRequests'][htmlspecialchars($_REQUEST['saveQr'],ENT_QUOTES)] = $lastQr;
        if (WorkbenchConfig::get()->value("savedQueriesAndSearchesPersistanceLevel") != 'NONE') {
            setcookie($persistedSavedQueryRequestsKey,serialize($_SESSION['savedQueryRequests']),time()+60*60*24*7);
        }
    }
}

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

    $asyncJob = new QueryFutureTask($queryRequest);
    $asyncStartTime = time(); // TODO: remove timing
    if (WorkbenchConfig::get()->isConfigured("ENABLE_ASYNC_QUERY") && time() % 4) { // TODO: REMOVE FEATURE FLAG & A/B TESTING
        $future = $asyncJob->enqueue();
        echo "<a name='qr'>&nbsp;</a>";
        echo $future->ajax();
        workbenchLog(LOG_DEBUG, "QUERY_ELAPSED_TIME_ASYNC", time() - $asyncStartTime);
    } else {
        echo $asyncJob->perform();
        workbenchLog(LOG_DEBUG, "QUERY_ELAPSED_TIME_SYNC",  time() - $asyncStartTime);
    }

    include_once 'footer.php';
} else if (isset($_POST['querySubmit']) && $_POST['querySubmit']=='Query' && $queryRequest->getSoqlQuery() != null && strpos($queryRequest->getExportTo(), 'async_') === 0) {
    queryAsync($queryRequest);
} else if (isset($_POST['querySubmit']) && $_POST['querySubmit']=='Query' && $queryRequest->getSoqlQuery() != null && $queryRequest->getExportTo() == 'csv') {
    if (!substr_count($_POST['soql_query'],"count()")) {
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

    print "<script>\n";

    print "var field_type_array = new Array();\n";
    if (isset($describeSObjectResult)) {
        foreach ($describeSObjectResult->fields as $fields => $field) {
            print " field_type_array[\"$field->name\"]=[\"$field->type\"];\n";
        }
    }

    $ops = array(
        '=' => '=',
        '!=' => '&ne;',
        '<' => '&lt;',
        '<=' => '&le;',
        '>' => '&gt;',
        '>=' => '&ge;',
        'starts' => 'starts with',
        'ends' => 'ends with',
        'contains' => 'contains',
        'IN' => 'in',
        'NOT IN' => 'not in',
        'INCLUDES' => 'includes',
        'EXCLUDES' => 'excludes'
    );


    print "var compOper_array = new Array();\n";
    foreach ($ops as $opValue => $opLabel) {
        print " compOper_array[\"$opValue\"]=[\"$opLabel\"];\n";
    }

    print <<<QUERY_BUILDER_SCRIPT

function parentChildRelationshipQueryBlocker() {
    var soql = document.getElementById('soql_query_textarea').value.toUpperCase();
    
    if (soql.indexOf('(SELECT') != -1 && soql.indexOf('IN (SELECT') == -1 && document.getElementById('export_action_csv').checked) {
        return confirm ("Export of parent-to-child relationship queries to CSV are not yet supported by Workbench and may give unexpected results. Are you sure you wish to continue?");
    }
    
}

function doesQueryHaveName() {
    var saveQr = document.getElementById('saveQr');
    if (saveQr.value == null || saveQr.value.length == 0) {
        alert('Query must have a name to save.');
        return false;
    }    
    
    return true;
}


function toggleFieldDisabled() {
    var QB_field_sel = document.getElementById('QB_field_sel');

    if (document.getElementById('QB_object_sel').value) {
        QB_field_sel.disabled = false;
    } else {
        QB_field_sel.disabled = true;
    }


    var isFieldSelected = false;
    for (var i = 0; i < QB_field_sel.options.length; i++)
        if (QB_field_sel.options[i].selected)
            isFieldSelected = true;
            
    if (isFieldSelected || (document.getElementById('matrix_rows').value != '' && document.getElementById('matrix_cols').value != '')) {
            document.getElementById('QB_orderby_field').disabled = false;
            document.getElementById('QB_orderby_sort').disabled = false;
            document.getElementById('QB_nulls').disabled = false;
            document.getElementById('QB_limit_txt').disabled = false;
            
            document.getElementById('QB_filter_field_0').disabled = false;
            if (document.getElementById('QB_filter_field_0').value) {
                document.getElementById('QB_filter_value_0').disabled = false;
                document.getElementById('QB_filter_compOper_0').disabled = false;
            } else {
                document.getElementById('QB_filter_value_0').disabled = true;
                document.getElementById('QB_filter_compOper_0').disabled = true;
            }
    } else {
            document.getElementById('QB_filter_field_0').disabled = true;
            document.getElementById('QB_filter_compOper_0').disabled = true;
            document.getElementById('QB_filter_value_0').disabled = true;
            document.getElementById('QB_orderby_field').disabled = true;
            document.getElementById('QB_orderby_sort').disabled = true;
            document.getElementById('QB_nulls').disabled = true;
            document.getElementById('QB_limit_txt').disabled = true;
    }

    var allPreviousRowsUsed = true;
    for (var r = 1; r < document.getElementById('numFilters').value; r++) {
        var lastRow = r-1;
        var thisRow = r;
        
        if (isFieldSelected && allPreviousRowsUsed && document.getElementById('QB_filter_field_' + lastRow).value && document.getElementById('QB_filter_compOper_' + lastRow).value && document.getElementById('QB_filter_value_' + lastRow).value) {
            document.getElementById('QB_filter_field_' + thisRow).disabled = false;
            if (document.getElementById('QB_filter_field_' + thisRow).value) {
                document.getElementById('QB_filter_value_' + thisRow).disabled = false;
                document.getElementById('QB_filter_compOper_' + thisRow).disabled = false;
            } else {
                document.getElementById('QB_filter_value_' + thisRow).disabled = true;
                document.getElementById('QB_filter_compOper_' + thisRow).disabled = true;
            }
        } else {
            allPreviousRowsUsed = false;
            document.getElementById('QB_filter_field_' + thisRow).disabled = true;
            document.getElementById('QB_filter_compOper_' + thisRow).disabled = true;
            document.getElementById('QB_filter_value_' + thisRow).disabled = true;
        }
    }
}

function updateObject() {
  document.query_form.justUpdate.value = 1;
  document.query_form.submit();
}

function exportActionIs(type) {
    var exportActions = document.getElementById('query_form')['export_action'];
    for (var i = 0; i < exportActions.length; i++) {
        if (exportActions[i].checked && exportActions[i].value == type) {
            return true;
        }
    }
    return false;
}

function arrayContains(haystack, needle) {
    for (i in haystack) {
        if (haystack[i] == needle) {
            return true;
        }
    }
    
    return false;
}

function buildQuery() {
    toggleFieldDisabled();
    var QB_object_sel = document.getElementById('QB_object_sel').value;
    var QB_field_sel = document.getElementById('QB_field_sel');
    QB_fields_selected = new Array();
    for (var i = 0; i < QB_field_sel.options.length; i++) {
        if (QB_field_sel.options[i].selected) {
            QB_fields_selected.push(QB_field_sel.options[i].value);
        }
    }
    
    if (exportActionIs('matrix')) {
        var matrix_cols = document.getElementById('matrix_cols');
        var matrix_rows = document.getElementById('matrix_rows');
        
        if (matrix_cols.value != '' && matrix_rows.value != '') {
            if (!arrayContains(QB_fields_selected, matrix_cols.value)) QB_fields_selected.push(matrix_cols.value);
            if (!arrayContains(QB_fields_selected, matrix_rows.value)) QB_fields_selected.push(matrix_rows.value);
        }
    }

    var soql_select = '';
    if (QB_fields_selected.toString().indexOf('count()') != -1 && QB_fields_selected.length > 1) {
        alert('Warning: Choosing count() with other fields will result in a malformed query. Unselect either count() or the other fields to continue.');
    } else    if (QB_fields_selected.length > 0) {
        var soql_select = 'SELECT ' + QB_fields_selected + ' FROM ' + QB_object_sel;
    }

    soql_where = '';
    for (var f = 0; f < document.getElementById('numFilters').value; f++) {
    
        var QB_filter_field = document.getElementById('QB_filter_field_' + f).value;
        var QB_filter_compOper = document.getElementById('QB_filter_compOper_' + f).value;
        var QB_filter_value = document.getElementById('QB_filter_value_' + f).value;
        
        var soql_where_logicOper = '';
        if (f > 0) {
            soql_where_logicOper = ' AND ';
        }    
        
        if (QB_filter_field && QB_filter_compOper && QB_filter_value) {
            if (QB_filter_compOper == 'starts') {
                QB_filter_compOper = 'LIKE'
                QB_filter_value = QB_filter_value + '%';
            } else if (QB_filter_compOper == 'ends') {
                QB_filter_compOper = 'LIKE'
                QB_filter_value = '%' + QB_filter_value;
            } else if (QB_filter_compOper == 'contains') {
                QB_filter_compOper = 'LIKE'
                QB_filter_value = '%' + QB_filter_value + '%';
            }
            
            
            if (QB_filter_compOper == 'IN' || 
                QB_filter_compOper == 'NOT IN' ||
                QB_filter_compOper == 'INCLUDES' || 
                QB_filter_compOper == 'EXCLUDES') {
                    QB_filter_value_q = '(' + QB_filter_value + ')';
            } else if ((QB_filter_value == 'null') ||
                (field_type_array[QB_filter_field] == "datetime") ||
                (field_type_array[QB_filter_field] == "date") ||
                (field_type_array[QB_filter_field] == "currency") ||
                (field_type_array[QB_filter_field] == "percent") ||
                (field_type_array[QB_filter_field] == "double") ||
                (field_type_array[QB_filter_field] == "int") ||
                (field_type_array[QB_filter_field] == "boolean")) {
                    QB_filter_value_q = QB_filter_value;
            } else {
                QB_filter_value_q = '\'' + QB_filter_value + '\'';
            }

            soql_where += soql_where_logicOper + QB_filter_field + ' ' + QB_filter_compOper + ' ' + QB_filter_value_q;
        } else {
            break;
        }
    }
    soql_where = soql_where != '' ? ' WHERE ' + soql_where : '';

    var QB_orderby_field = document.getElementById('QB_orderby_field').value;
    var QB_orderby_sort = document.getElementById('QB_orderby_sort').value;
    var QB_nulls = document.getElementById('QB_nulls').value;
    if (QB_orderby_field) {
        var soql_orderby = ' ORDER BY ' + QB_orderby_field + ' ' + QB_orderby_sort;
        if (QB_nulls)
            soql_orderby = soql_orderby + ' NULLS ' + QB_nulls;
    } else
        var soql_orderby = '';


    var QB_limit_txt = document.getElementById('QB_limit_txt').value;
    if (QB_limit_txt)
        var soql_limit = ' LIMIT ' + QB_limit_txt;
    else
        var soql_limit = '';

    if (soql_select)
        document.getElementById('soql_query_textarea').value = soql_select + soql_where + soql_orderby + soql_limit ;

}


function addFilterRow(filterRowNum, defaultField, defaultCompOper, defaultValue) {
    //build the row inner html
    var row = filterRowNum == 0 ? "<br/>Filter results by:<br/>" : "" ;
    row +=     "<select id='QB_filter_field_" + filterRowNum + "' name='QB_filter_field_" + filterRowNum + "' style='width: 16em;' onChange='buildQuery();' onkeyup='buildQuery();'>" +
            "<option value=''></option>";
    
    for (var field in field_type_array) {
        row += "<option value='" + field + "'";
        if (defaultField == field) row += " selected='selected' ";
        row += "'>" + field + "</option>";
    }     
    
    row += "</select>&nbsp;" +
            "" +
            "<select id='QB_filter_compOper_" + filterRowNum + "' name='QB_filter_compOper_" + filterRowNum + "' style='width: 6em;' onChange='buildQuery();' onkeyup='buildQuery();'>";

    for (var opKey in compOper_array) {
        row += "<option value='" + opKey + "'";
        if (defaultCompOper == opKey) row += " selected='selected' ";
        row += ">" + compOper_array[opKey] + "</option>";
    } 
    
    defaultValue = defaultValue != null ? defaultValue : "";
    row +=  "</select>&nbsp;" +
            "<input type='text' id='QB_filter_value_" + filterRowNum + "' size='31' name='QB_filter_value_" + filterRowNum + "' value='" + defaultValue + "' onkeyup='buildQuery();' />";
            

    //add to the DOM
    var newFilterCell = document.createElement('td');
    newFilterCell.setAttribute('colSpan','4');
    newFilterCell.setAttribute('vAlign','top');
    newFilterCell.setAttribute('nowrap','true');
    newFilterCell.innerHTML = row;

    var newPlusCell = document.createElement('td');
    newPlusCell.setAttribute('id','filter_plus_cell_' + filterRowNum);
    newPlusCell.setAttribute('vAlign','bottom');
    newPlusCell.innerHTML = "<img id='filter_plus_button' src='" + getPathToStaticResource('/images/plus_icon.jpg') + "' onclick='addFilterRow(document.getElementById(\"numFilters\").value++);toggleFieldDisabled();' onmouseover='this.style.cursor=\"pointer\";'  style='padding-top: 4px;'/>";
    
    var newFilterRow = document.createElement('tr');
    newFilterRow.setAttribute('id','filter_row_' + filterRowNum);
    newFilterRow.appendChild(newFilterCell);
    newFilterRow.appendChild(newPlusCell);
    
    document.getElementById('QB_right_sub_table').getElementsByTagName("TBODY").item(0).appendChild(newFilterRow);
    
    if (filterRowNum > 0) {
        var filter_plus_button = document.getElementById('filter_plus_button');
        filter_plus_button.parentNode.removeChild(filter_plus_button);
    }
    
    //expand the field list so it looks right
    document.getElementById('QB_field_sel').size += 2;
}

function toggleMatrixSortSelectors(hasChanged) {
    if (exportActionIs('matrix')) {
        document.getElementById('matrix_selection_headers').style.display = '';
        document.getElementById('matrix_selection_row').style.display = '';
        document.getElementById('QB_field_sel').size += 4;
        
        if(hasChanged) buildQuery();
    } else if (document.getElementById('matrix_selection_headers').style.display == '') {
        document.getElementById('matrix_selection_headers').style.display = 'none';
        document.getElementById('matrix_selection_row').style.display = 'none';
        document.getElementById('QB_field_sel').size -= 4;
        
        if(hasChanged) buildQuery();
    }
    
    //don't do anything if moving from screen to csv
}

</script>
QUERY_BUILDER_SCRIPT;


    if (WorkbenchConfig::get()->value("autoJumpToResults")) {
        print "<form method='POST' id='query_form' name='query_form' action='#qr'>\n";
    } else {
        print "<form method='POST' id='query_form' name='query_form' action=''>\n";
    }
    print "<input type='hidden' name='justUpdate' value='0' />";
    print "<input type='hidden' id='numFilters' name='numFilters' value='" . count($queryRequest->getFilters()) ."' />";
    print "<p class='instructions'>Choose the object, fields, and critera to build a SOQL query below:</p>\n";
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


    print "<tr><td colspan=1><input type='submit' name='querySubmit' value='Query' onclick='return parentChildRelationshipQueryBlocker();' />\n" .
        "<input type='reset' value='Reset' />\n" .
        "</td>";

    //save and retrieve named queries
    print "<td colspan=4 align='right'>";

    print "&nbsp;Run: " .
        "<select name='getQr' style='width: 10em;' onChange='document.query_form.submit();'>" .
        "<option value='' selected='selected'></option>";
    if (isset($_SESSION['savedQueryRequests'])) {
        foreach ($_SESSION['savedQueryRequests'] as $qrName => $qr) {
            if($qrName != null) print "<option value='$qrName'>$qrName</option>";
        }
    }
    print "</select>";


    print "&nbsp;&nbsp;Save as: <input type='text' id='saveQr' name='saveQr' value='" . htmlspecialchars($queryRequest->getName(),ENT_QUOTES) . "' style='width: 10em;'/>\n";

    print "<input type='submit' name='doSaveQr' value='Save' onclick='return doesQueryHaveName();' />\n";
    print "<input type='submit' name='clearAllQr' value='Clear All' onclick='return confirm(\"Are you sure you would like to clear all saved queries?\");'/>\n";

    print "&nbsp;&nbsp;" .
        "<img onmouseover=\"Tip('Save a query with a name and run it at a later time during your session. Note, if a query is already saved with the same name, the previous one will be overwritten.')\" align='absmiddle' src='" . getPathToStaticResource('/images/help16.png') . "'/>";

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

    $job = $asyncConnection->createJob($job);
    $asyncConnection->createBatch($job, $queryRequest->getSoqlQuery());
    $job = $asyncConnection->updateJobState($job->getId(), "Closed");


    header("Location: asyncStatus.php?jobId=" . $job->getId());
}

?>