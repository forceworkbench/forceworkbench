<?php

require_once 'soxl/QueryObjects.php';
require_once 'session.php';
require_once 'shared.php';

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
    if (getConfig("savedQueriesAndSearchesPersistanceLevel") == 'USER') {
        $persistedSavedQueryRequestsKey .= WorkbenchContext::get()->getUserInfo()->userId . "@" . WorkbenchContext::get()->getUserInfo()->organizationId;
    } else if (getConfig("savedQueriesAndSearchesPersistanceLevel") == "ORG") {
        $persistedSavedQueryRequestsKey .= WorkbenchContext::get()->getUserInfo()->organizationId;
    } else if (getConfig("savedQueriesAndSearchesPersistanceLevel") == 'ALL') {
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
        if (getConfig("savedQueriesAndSearchesPersistanceLevel") != 'NONE' && !isset($_SESSION['savedQueryRequests']) && isset($_COOKIE[$persistedSavedQueryRequestsKey])) {
            $_SESSION['savedQueryRequests'] = unserialize($_COOKIE[$persistedSavedQueryRequestsKey]);
        }
    }

    //clear  all saved queries in scope if user requests
    if (isset($_POST['clearAllQr']) && $_POST['clearAllQr'] == 'Clear All') {
        $_SESSION['savedQueryRequests'] = null;
        if (getConfig("savedQueriesAndSearchesPersistanceLevel") != 'NONE') {
            setcookie($persistedSavedQueryRequestsKey,null,time()-3600);
        }
    }

    //save as named query
    if (isset($_POST['doSaveQr']) && $_POST['doSaveQr'] == 'Save' && isset($_REQUEST['saveQr']) && strlen($_REQUEST['saveQr']) > 0) {
        $_SESSION['savedQueryRequests'][htmlspecialchars($_REQUEST['saveQr'],ENT_QUOTES,'UTF-8')] = $lastQr;
        if (getConfig("savedQueriesAndSearchesPersistanceLevel") != 'NONE') {
            setcookie($persistedSavedQueryRequestsKey,serialize($_SESSION['savedQueryRequests']),time()+60*60*24*7);
        }
    }
}

//Main form logic: When the user first enters the page, display form defaulted to
//show the query results with default object selected on a previous page, otherwise
// just display the blank form. When the user selects the SCREEN or CSV options, the
//query is processed by the correct function
if (isset($_POST['queryMore']) && isset($_SESSION['queryLocator'])) {
    require_once 'header.php';
    //    $queryRequest->setExportTo('screen');
    displayQueryForm($queryRequest);
    $queryTimeStart = microtime(true);
    $records = query(null,'QueryMore',$_SESSION['queryLocator']);
    $queryTimeEnd = microtime(true);
    $queryTimeElapsed = $queryTimeEnd - $queryTimeStart;
    displayQueryResults($records,$queryTimeElapsed,$queryRequest);
    include_once 'footer.php';
} else if (isset($_POST['querySubmit']) && $_POST['querySubmit']=='Query' && $queryRequest->getSoqlQuery() != null && ($queryRequest->getExportTo() == 'screen' || $queryRequest->getExportTo() == 'matrix')) {
    require_once 'header.php';
    displayQueryForm($queryRequest);
    if ($queryRequest->getExportTo() == 'matrix' && ($queryRequest->getMatrixCols() == "" || $queryRequest->getMatrixRows() == "")) {
        displayWarning("Both column and row must be specified for Matrix view.", false, true);
        return;
    }
    $queryTimeStart = microtime(true);
    $records = query($queryRequest->getSoqlQuery(),$queryRequest->getQueryAction());
    $queryTimeEnd = microtime(true);
    $queryTimeElapsed = $queryTimeEnd - $queryTimeStart;
    displayQueryResults($records,$queryTimeElapsed,$queryRequest);
    include_once 'footer.php';
} else if (isset($_POST['querySubmit']) && $_POST['querySubmit']=='Query' && $queryRequest->getSoqlQuery() != null && strpos($queryRequest->getExportTo(), 'async_') === 0) {
    queryAsync($queryRequest);
} else if (isset($_POST['querySubmit']) && $_POST['querySubmit']=='Query' && $queryRequest->getSoqlQuery() != null && $queryRequest->getExportTo() == 'csv') {
    if (!substr_count($_POST['soql_query'],"count()")) {
        $records = query($queryRequest->getSoqlQuery(),$queryRequest->getQueryAction(),null,true);
        exportQueryAsCsv($records,$queryRequest->getExportTo());
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
    newPlusCell.innerHTML = "<img id='filter_plus_button' src='" + WORKBENCH_STATIC_RESOURCES_PATH + "/images/plus_icon.jpg' onclick='addFilterRow(document.getElementById(\"numFilters\").value++);toggleFieldDisabled();' onmouseover='this.style.cursor=\"pointer\";'  style='padding-top: 4px;'/>";
    
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


        if (getConfig("autoJumpToResults")) {
            print "<form method='POST' id='query_form' name='query_form' action='$_SERVER[PHP_SELF]#qr'>\n";
        } else {
            print "<form method='POST' id='query_form' name='query_form' action='$_SERVER[PHP_SELF]'>\n";
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

        print "<label><input type='radio' id='export_action_csv' name='export_action' value='csv' ";
        if ($queryRequest->getExportTo() == 'csv') print "checked='true'";
        print " onClick='toggleMatrixSortSelectors(true);'>CSV</label>&nbsp;";

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
        print "</select></td> <td><img onmouseover=\"Tip('Matrix view groups records into columns and rows of common field values.')\" align='absmiddle' src='" . getStaticResourcesPath() ."/images/help16.png'/></td></tr>\n";

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

    print "<td><input type='text' id='QB_limit_txt' size='10' name='QB_limit_txt' value='" . htmlspecialchars($queryRequest->getLimit() != null ? $queryRequest->getLimit() : null,ENT_QUOTES,'UTF-8') . "' onkeyup='buildQuery();' /></td>\n";

    print "</tr>\n";

    print "</table>\n";
    print "</td></tr>\n";

    $filterRowNum = 0;
    foreach ($queryRequest->getFilters() as $filter) {
        print "<script>addFilterRow(" .
        $filterRowNum++ . ", " .
        "\"" . $filter->getField()     . "\", " . 
        "\"" . $filter->getCompOper()  . "\", " . 
        "\"" . htmlentities($filter->getValue(), ENT_QUOTES)     . "\"" .
        ");</script>";
    }


    print "<tr><td valign='top' colspan=5><br/>Enter or modify a SOQL query below:\n" .
        "<br/><textarea id='soql_query_textarea' type='text' name='soql_query' rows='" . getConfig("textareaRows") . "' style='width: 99%; overflow: auto; font-family: monospace, courier;'>" . htmlspecialchars($queryRequest->getSoqlQuery(),ENT_QUOTES,'UTF-8') . "</textarea>\n" .
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


    print "&nbsp;&nbsp;Save as: <input type='text' id='saveQr' name='saveQr' value='" . htmlspecialchars($queryRequest->getName(),ENT_QUOTES,'UTF-8') . "' style='width: 10em;'/>\n";

    print "<input type='submit' name='doSaveQr' value='Save' onclick='return doesQueryHaveName();' />\n";
    print "<input type='submit' name='clearAllQr' value='Clear All'/>\n";

    print "&nbsp;&nbsp;" .
          "<img onmouseover=\"Tip('Save a query with a name and run it at a later time during your session. Note, if a query is already saved with the same name, the previous one will be overwritten.')\" align='absmiddle' src='" . getStaticResourcesPath() ."/images/help16.png'/>";

    print "</td></tr></table><p/>\n";

    print "<script>toggleFieldDisabled();toggleMatrixSortSelectors(false);</script>";
}


function query($soqlQuery,$queryAction,$queryLocator = null,$suppressScreenOutput=false) {
    try {
        if ($queryAction == 'Query') $queryResponse = WorkbenchContext::get()->getPartnerConnection()->query($soqlQuery);
        if ($queryAction == 'QueryAll') $queryResponse = WorkbenchContext::get()->getPartnerConnection()->queryAll($soqlQuery);
        if ($queryAction == 'QueryMore' && isset($queryLocator)) $queryResponse = WorkbenchContext::get()->getPartnerConnection()->queryMore($queryLocator);

        if (substr_count($soqlQuery,"count()") && $suppressScreenOutput == false) {
            $countString = "Query would return " . $queryResponse->size . " record";
            $countString .= ($queryResponse->size == 1) ? "." : "s.";
            displayInfo($countString);
            $records = $queryResponse->size;
            include_once 'footer.php';
            exit;
        }

        if (isset($queryResponse->records)) {
            $records = $queryResponse->records;
        } else {
            $records = null;
        }

        $_SESSION['totalQuerySize'] = $queryResponse->size;

        if (!$queryResponse->done) {
            $_SESSION['queryLocator'] = $queryResponse->queryLocator;
        } else {
            $_SESSION['queryLocator'] = null;
        }

        //correction for documents and attachments with body. issue #176
        if ($queryResponse->size > 0 && !is_array($records)) {
            $records = array($records);
        }

        $memLimitBytes = toBytes(ini_get("memory_limit"));
        $memWarningThreshold = getConfig("memoryUsageWarningThreshold") / 100;
        while(($suppressScreenOutput || getConfig("autoRunQueryMore")) && !$queryResponse->done) {

            if ($memLimitBytes != 0 && (memory_get_usage() / $memLimitBytes > $memWarningThreshold)) {
                displayError("Workbench almost exhausted all its memory after only processing " . count($records) . " rows of data.
                When performing a large queries, it is recommended to export as Bulk CSV or Bulk XML.",
                $suppressScreenOutput, true);
                return; // bail out
            }

            $queryResponse = WorkbenchContext::get()->getPartnerConnection()->queryMore($queryResponse->queryLocator);

            if (!is_array($queryResponse->records)) {
                $queryResponse->records = array($queryResponse->records);
            }

            $records = array_merge($records, $queryResponse->records); //todo: do memory check here
        }

        return $records;

    } catch (Exception $e) {
        print "<p><a name='qr'>&nbsp;</a></p>";
        displayError($e->getMessage(),true,true);
    }
}

function getQueryResultHeaders($sobject, $tail="") {
    if (!isset($headerBufferArray)) {
        $headerBufferArray = array();
    }

    if (isset($sobject->Id)) {
        $headerBufferArray[] = $tail . "Id";
    }

    if (isset($sobject->fields)) {
        foreach ($sobject->fields->children() as $field) {
            $headerBufferArray[] = $tail . htmlspecialchars($field->getName(),ENT_QUOTES,'UTF-8');
        }
    }

    if (isset($sobject->sobjects)) {
        foreach ($sobject->sobjects as $sobjects) {
            $recurse = getQueryResultHeaders($sobjects, $tail . htmlspecialchars($sobjects->type,ENT_QUOTES,'UTF-8') . ".");
            $headerBufferArray = array_merge($headerBufferArray, $recurse);
        }
    }

    if (isset($sobject->queryResult)) {
        if(!is_array($sobject->queryResult)) $sobject->queryResult = array($sobject->queryResult);
        foreach ($sobject->queryResult as $qr) {
            $headerBufferArray[] = $qr->records[0]->type;
        }
    }

    return $headerBufferArray;
}

function getQueryResultRow($sobject, $escapeHtmlChars=true) {

    if (!isset($rowBuffer)) {
        $rowBuffer = array();
    }
     
    if (isset($sobject->Id)) {
        $rowBuffer[] = $sobject->Id;
    }

    if (isset($sobject->fields)) {
        foreach ($sobject->fields as $datum) {
            $rowBuffer[] = ($escapeHtmlChars ? htmlspecialchars($datum,ENT_QUOTES,'UTF-8') : $datum);
        }
    }

    if (isset($sobject->sobjects)) {
        foreach ($sobject->sobjects as $sobjects) {
            $rowBuffer = array_merge($rowBuffer, getQueryResultRow($sobjects,$escapeHtmlChars));
        }
    }

    if (isset($sobject->queryResult)) {
        $rowBuffer[] = $sobject->queryResult;
    }

    return localizeDateTimes($rowBuffer);
}

function createQueryResultsMatrix($records, $matrixCols, $matrixRows) {
    $matrix;
    $allColNames = array();
    $allRowNames = array();

    foreach ($records as $rawRecord) {
        $record = new SObject($rawRecord);

        $data = "";
        if (isset($record->Id)) $record->fields->Id = $record->Id;

        foreach ($record->fields as $fieldName => $fieldValue) {
            if ($fieldName == $matrixCols || $fieldName == $matrixRows) {
                continue;
            }

            $data .= "<em>$fieldName:</em>  " . htmlentities($fieldValue,ENT_QUOTES,'UTF-8') . "<br/>";
        }

        foreach ($record->fields as $rowName => $rowValue) {
            if ($rowName != $matrixRows) continue;
            foreach ($record->fields as $colName => $colValue) {
                if($colName != $matrixCols) continue;
                $allColNames["$colValue"] = $colValue;
                $allRowNames["$rowValue"] = $rowValue;
                $matrix["$rowValue"]["$colValue"][] = $data;
            }
        }
    }

    if (count($allColNames) == 0 || count($allRowNames) == 0) {
        displayWarning("No records match matrix column and row selections.", false, true);
        return;
    }

    $table =  "<table id='query_results_matrix' border='1' class='" . getTableClass() . "'>";

    $hw = false;
    foreach ($allRowNames as $rowName) {
        if (!$hw) {
            $table .= "<tr><td></td>";
            foreach ($allColNames as $colName) {
                $table .= "<th>$colName</th>";
            }
            $table .= "</tr>";
            $hw = true;
        }

        $table .= "<tr>";
        $table .= "<th>$rowName</th>";

        foreach ($allColNames as $colName) {

            $table .= "<td>";

            if (isset($matrix["$rowName"]["$colName"])) {
                foreach ($matrix["$rowName"]["$colName"] as $data) {
                    $table .= "<div class='matrixItem'" . ($data == "" ? "style='width: 0px;'" : "") . ">$data</div>";
                }
            }
             
            $table .= "</td>";
        }
        $table .= "</tr>";
    }

    $table .= "</table>";

    return localizeDateTimes($table);
}

function createQueryResultTable($records, $rowNum) {
    $table = "<table id='query_results' class='" . getTableClass() . "'>\n";

    //call shared recusive function above for header printing
    $table .= "<tr><th></th><th>";
    if ($records[0] instanceof SObject) {
        $table .= implode("</th><th>", getQueryResultHeaders($records[0]));
    } else {
        $table .= implode("</th><th>", getQueryResultHeaders(new SObject($records[0])));
    }
    $table .= "</th></tr>\n";


    //Print the remaining rows in the body
    foreach ($records as $record) {
        //call shared recusive function above for row printing
        $table .= "<tr><td>" . $rowNum++ . "</td><td>";

        if ($record instanceof SObject) {
            $row = getQueryResultRow($record);
        } else {
            $row = getQueryResultRow(new SObject($record));
        }


        for ($i = 0; $i < count($row); $i++) {
            if($row[$i] instanceof QueryResult && !is_array($row[$i])) $row[$i] = array($row[$i]);
            if (isset($row[$i][0]) && $row[$i][0] instanceof QueryResult) {
                foreach ($row[$i] as $qr) {
                    $table .= createQueryResultTable($qr->records, 1);
                    if($qr != end($row[$i])) $table .= "</td><td>";
                }
            } else {
                $table .= $row[$i];
            }

            if ($i+1 != count($row)) {
                $table .= "</td><td>";
            }
        }

        $table .= "</td></tr>\n";
    }

    $table .= "</table>";

    return $table;
}


//If the user selects to display the form on screen, they are routed to this function
function displayQueryResults($records, $queryTimeElapsed, QueryRequest $queryRequest) {

    //Check if records were returned
    if ($records) {
        if (getConfig("areTablesSortable")) {
            addFooterScript("<script type='text/javascript' src='" . getStaticResourcesPath() . "/script/sortable.js'></script>");
        }
        
        try {
            $rowNum = 0;
            print "<a name='qr'></a><div style='clear: both;'><br/><h2>Query Results</h2>\n";
            if (isset($_SESSION['queryLocator']) && !getConfig("autoRunQueryMore")) {
                preg_match("/-(\d+)/",$_SESSION['queryLocator'],$lastRecord);
                $rowNum = ($lastRecord[1] - count($records) + 1);
                print "<p>Returned records $rowNum - " . $lastRecord[1] . " of ";
            } else if (!getConfig("autoRunQueryMore")) {
                $rowNum = ($_SESSION['totalQuerySize'] - count($records) + 1);
                print "<p>Returned records $rowNum - " . $_SESSION['totalQuerySize'] . " of ";
            } else {
                $rowNum = 1;
                print "<p>Returned ";
            }
             
            print $_SESSION['totalQuerySize'] . " total record";
            if ($_SESSION['totalQuerySize'] !== 1) print 's';
            print " in ";
            printf ("%01.3f", $queryTimeElapsed);
            print " seconds:</p>\n";

            if (!getConfig("autoRunQueryMore") && $_SESSION['queryLocator']) {
                print "<p><input type='submit' name='queryMore' id='queryMoreButtonTop' value='More...' /></p>\n";
            }

            print addLinksToUiForIds($queryRequest->getExportTo() == 'matrix' ?
            createQueryResultsMatrix($records, $queryRequest->getMatrixCols(), $queryRequest->getMatrixRows()) :
            createQueryResultTable($records, $rowNum));

            if (!getConfig("autoRunQueryMore") && $_SESSION['queryLocator']) {
                print "<p><input type='submit' name='queryMore' id='queryMoreButtonBottom' value='More...' /></p>";
            }

            print    "</form></div>\n";
        } catch (Exception $e) {
            print "<p />";
            displayError($e->getMessage(), false, true);
        }
    } else {
        print "<p><a name='qr'>&nbsp;</a></p>";
        displayWarning("Sorry, no records returned.");
    }
    include_once 'footer.php';
}


//Export the above query to a CSV file
function exportQueryAsCsv($records,$queryAction) {
    if ($records) {
        try {
            $csvFile = fopen('php://output','w') or die("Error opening php://output");
            $csvFilename = "export" . date('YmdHis') . ".csv";
            header("Content-Type: application/csv");
            header("Content-Disposition: attachment; filename=$csvFilename");

            //Write first row to CSV and unset variable
            fputcsv($csvFile,getQueryResultHeaders(new SObject($records[0])));

            //Export remaining rows and write to CSV line-by-line
            foreach ($records as $record) {
                fputcsv($csvFile, getQueryResultRow(new SObject($record),false));
            }

            fclose($csvFile) or die("Error closing php://output");

        } catch (Exception $e) {
            require_once("header.php");
            displayQueryForm(new QueryRequest($_POST),'csv',$queryAction);
            print "<p />";
            displayError($e->getMessage(),false,true);
        }
    } else {
        require_once("header.php");
        displayQueryForm(new QueryRequest($_POST),'csv',$queryAction);
        print "<p />";
        displayWarning("No records returned for CSV output.",false,true);
    }
}

function queryAsync($queryRequest) {
    if ($queryRequest->getQueryAction() == "QueryAll") {
        throw new Exception("Including deleted and archived records not supported by Bulk Queries.");
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
    $job->setConcurrencyMode(getConfig("asyncConcurrencyMode"));

    $job = $asyncConnection->createJob($job);
    $asyncConnection->createBatch($job, $queryRequest->getSoqlQuery());
    $job = $asyncConnection->updateJobState($job->getId(), "Closed");


    header("Location: asyncStatus.php?jobId=" . $job->getId());
}

?>
