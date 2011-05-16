<?php
require_once 'soxl/SearchObjects.php';
require_once 'session.php';
require_once 'shared.php';

$lastSr = new SearchRequest($_REQUEST);


//save last search. always do this even if named.
if ((isset($_POST['searchSubmit']) && $_POST['searchSubmit']=='Search') || (isset($_POST['doSaveSr']) && $_POST['doSaveSr'] == 'Save' )) {
    $_SESSION['lastSearchRequest'] = $lastSr;
}

$persistedSavedSearchRequestsKey = "PSSR@";
if (getConfig("savedQueriesAndSearchesPersistanceLevel") == 'USER') {
    $persistedSavedSearchRequestsKey .= WorkbenchContext::get()->getUserInfo()->userId . "@" . WorkbenchContext::get()->getUserInfo()->organizationId;
} else if (getConfig("savedQueriesAndSearchesPersistanceLevel") == "ORG") {
    $persistedSavedSearchRequestsKey .= WorkbenchContext::get()->getUserInfo()->organizationId;
} else if (getConfig("savedQueriesAndSearchesPersistanceLevel") == 'ALL') {
    $persistedSavedSearchRequestsKey .= "ALL";
}

//populate searchRequest for this page view. first see if user wants to retreive a saved search,
//then see if there was a last search, else just show a null search with default object.
if (isset($_REQUEST['getSr']) && $_REQUEST['getSr'] != "" && isset($_SESSION['savedSearchRequests'][$_REQUEST['getSr']])) {
    $searchRequest = $_SESSION['savedSearchRequests'][$_REQUEST['getSr']];
    $_POST['searchSubmit'] = 'Search'; //simulate the user clicking 'search' to run immediately
} else if (isset($_SESSION['lastSearchRequest'])) {
    $searchRequest = $_SESSION['lastSearchRequest'];
} else {
    $defaultSettings['numReturningObjects'] = 1;
    $searchRequest = new SearchRequest($defaultSettings);
    if (getConfig("savedQueriesAndSearchesPersistanceLevel") != 'NONE' && !isset($_SESSION['savedSearchRequests']) && isset($_COOKIE[$persistedSavedSearchRequestsKey])) {
        $_SESSION['savedSearchRequests'] = unserialize($_COOKIE[$persistedSavedSearchRequestsKey]);
    }
}

//clear  all saved searches in scope if user requests
if (isset($_POST['clearAllSr']) && $_POST['clearAllSr'] == 'Clear All') {
    $_SESSION['savedSearchRequests'] = null;
    if (getConfig("savedQueriesAndSearchesPersistanceLevel") != 'NONE') {
        setcookie($persistedSavedSearchRequestsKey,null,time()-3600);
    }
}

//save as named search
if (isset($_POST['doSaveSr']) && $_POST['doSaveSr'] == 'Save' && isset($_REQUEST['saveSr']) && strlen($_REQUEST['saveSr']) > 0) {
    $_SESSION['savedSearchRequests'][htmlspecialchars($_REQUEST['saveSr'],ENT_QUOTES,'UTF-8')] = $lastSr;
    if (getConfig("savedQueriesAndSearchesPersistanceLevel") != 'NONE') {
        setcookie($persistedSavedSearchRequestsKey,serialize($_SESSION['savedSearchRequests']),time()+60*60*24*7);
    }
}


//Main form logic: When the user first enters the page, display form defaulted to
//show the search results with default object selected on a previous page, otherwise
// just display the blank form.
if (isset($_POST['searchSubmit']) && isset($searchRequest)) {
    require_once 'header.php';
    displaySearchForm($searchRequest);
    $searchTimeStart = microtime(true);
    $records = search($searchRequest);
    $searchTimeEnd = microtime(true);
    $searchTimeElapsed = $searchTimeEnd - $searchTimeStart;
    displaySearchResult($records,$searchTimeElapsed);
    include_once 'footer.php';
} else {
    require_once 'header.php';
    displaySearchForm($searchRequest);
    include_once 'footer.php';
}



//Show the main SOSL search form with default search or last submitted search and export action (screen or CSV)

function displaySearchForm($searchRequest) {
   registerShortcut("Ctrl+Alt+W",
                    "addReturningObjectRow(document.getElementById('numReturningObjects').value++);".
                    "toggleFieldDisabled();");

    print "<script>\n";

    print "var searchable_objects = new Array();\n";
    foreach (describeGlobal("searchable") as $obj) {
        print "searchable_objects[\"$obj\"]=\"$obj\";\n";
    }
    
    print <<<SEARCH_BUILDER_SCRIPT

function doesSearchHaveName() {
    var saveSr = document.getElementById('saveSr');
    if (saveSr.value == null || saveSr.value.length == 0) {
        alert('Search must have a name to save.');
        return false;
    }    
    
    return true;
}

function toggleFieldDisabled() {

    if (document.getElementById('SB_searchString').value) {
        document.getElementById('SB_limit').disabled = false;
        document.getElementById('SB_fieldTypeSelect').disabled = false;
        document.getElementById('SB_objSelect_0').disabled = false;
        if (document.getElementById('SB_objSelect_0').value) {
            document.getElementById('SB_objDetail_0').disabled = false;
        } else {
            document.getElementById('SB_objDetail_0').disabled = true;
        }
    } else {
        document.getElementById('SB_limit').disabled = true;
        document.getElementById('SB_fieldTypeSelect').disabled = true;
        document.getElementById('SB_objSelect_0').disabled = true;
        document.getElementById('SB_objDetail_0').disabled = true;
    }

    var allPreviousRowsUsed = true;
    for (var ro = 1; ro < document.getElementById('numReturningObjects').value; ro++) {
        var this_SB_objSelect = document.getElementById('SB_objSelect_' + ro);
        var this_SB_objDetail = document.getElementById('SB_objDetail_' + ro);
        
        var last_SB_objSelect = document.getElementById('SB_objSelect_' + (ro - 1));
        var last_SB_objDetail = document.getElementById('SB_objDetail_' + (ro - 1));
        
        if (allPreviousRowsUsed && last_SB_objSelect.value && last_SB_objDetail.value) {
            this_SB_objSelect.disabled = false;
            this_SB_objDetail.disabled = false;
            if (this_SB_objSelect.value) {
                this_SB_objDetail.disabled = false;
            } else {
                this_SB_objDetail.disabled = true;
            }
        } else {
            this_SB_objSelect.disabled = true;
            this_SB_objDetail.disabled = true;
            allPreviousRowsUsed = false;
        }
    }
}

function buildSearch() {
    toggleFieldDisabled();
    
    var searchString = 'FIND {' + document.getElementById('SB_searchString').value + '}';
    
    var fieldTypeSelect = '';
    if (document.getElementById('SB_fieldTypeSelect').value && !document.getElementById('SB_fieldTypeSelect').disabled) {
        fieldTypeSelect = ' IN ' + document.getElementById('SB_fieldTypeSelect').value;
    }
    
    var roString = '';
    for (var ro = 0; ro < document.getElementById('numReturningObjects').value; ro++) {
        var SB_objSelect = document.getElementById('SB_objSelect_' + ro);
        var SB_objDetail = document.getElementById('SB_objDetail_' + ro);
        
        if (SB_objSelect.value && !SB_objSelect.disabled) {
            roString += ro == 0 ? ' RETURNING ' : ', ';
            
            roString += SB_objSelect.value;

            if (SB_objDetail.value && !SB_objDetail.disabled) {
                roString += '(' + SB_objDetail.value + ')';
            }
        }
    }
    
    var limit = '';
    if (document.getElementById('SB_limit').value && !document.getElementById('SB_limit').disabled) {
        limit = ' LIMIT ' + document.getElementById('SB_limit').value;
    }


    if (searchString) {
        document.getElementById('sosl_search_textarea').value = searchString + fieldTypeSelect + roString + limit;
    }
}

function addReturningObjectRow(rowNum, defaultObject, defaultFields) {
    //build the row inner html
    var row = "";
    
    row +=     "<select id='SB_objSelect_" + rowNum + "' name='SB_objSelect_" + rowNum + "' style='width: 20em;' onChange='buildSearch();' onkeyup='buildSearch();'>" +
            "<option value=''></option>";
    
    for (var obj in searchable_objects) {
        row += "<option value='" + obj + "'";
        if (defaultObject == obj) row += " selected='selected' ";
        row += "'>" + obj + "</option>";
    }     
    
    defaultFields = defaultFields != null ? defaultFields : "";
    row +=  "</select>&nbsp;" +
            "<input type='text' id='SB_objDetail_" + rowNum + "' size='51' name='SB_objDetail_" + rowNum + "' value='" + defaultFields + "' onkeyup='buildSearch();' />";
            

    //add to the DOM
    var leadingTxtCell = document.createElement('td');
    leadingTxtCell.setAttribute('nowrap','true');
    leadingTxtCell.innerHTML = rowNum == 0 ? "returning object:" : "and object:" ;
    
    var bodyCell = document.createElement('td');
    bodyCell.setAttribute('nowrap','true');
    bodyCell.innerHTML = row;

    var newPlusCell = document.createElement('td');
    newPlusCell.setAttribute('id','add_row_plus_cell_' + rowNum);
    newPlusCell.setAttribute('vAlign','bottom');
    newPlusCell.innerHTML = "<img id='row_plus_button' src='" + WORKBENCH_STATIC_RESOURCES_PATH + "/images/plus_icon.jpg' onclick='addReturningObjectRow(document.getElementById(\"numReturningObjects\").value++);toggleFieldDisabled();' onmouseover='this.style.cursor=\"pointer\";'  style='padding-top: 4px;'/>";
    
    var newRow = document.createElement('tr');
    newRow.setAttribute('id','returning_objects_row_' + rowNum);
    newRow.appendChild(leadingTxtCell);
    newRow.appendChild(bodyCell);
    newRow.appendChild(newPlusCell);
    
    var lastRow = document.getElementById('sosl_search_textarea_row');    
    lastRow.parentNode.insertBefore(newRow, lastRow);
    
    if (rowNum > 0) {
        var row_plus_button = document.getElementById('row_plus_button');
        row_plus_button.parentNode.removeChild(row_plus_button);
    }
}

</script>
SEARCH_BUILDER_SCRIPT;

    if (getConfig("autoJumpToResults")) {
        print "<form method='POST' name='search_form' action='$_SERVER[PHP_SELF]#sr'>\n";
    } else {
        print "<form method='POST' name='search_form' action='$_SERVER[PHP_SELF]#sr'>\n";
    }

    print "<input type='hidden' id='numReturningObjects' name='numReturningObjects' value='" . count($searchRequest->getReturningObjects()) ."' />";

    print "<p class='instructions'>Enter a search string and optionally select the objects and fields to return to build a SOSL search below:</p>\n";
    print "<table id='search_form_table' border='0' width='1'>\n<tr>\n";

    print "<td NOWRAP>Search for </td><td NOWRAP colspan='2'><input type='text' id='SB_searchString' name='SB_searchString' value=\"" . htmlspecialchars($searchRequest->getSearchString(),ENT_QUOTES,'UTF-8') . "\" size='37' onKeyUp='buildSearch();' /> in ";

    $fieldTypeSelectOptions = array(
        'ALL FIELDS' => 'All Fields',
        'NAME FIELDS' => 'Name Fields',
        'PHONE FIELDS' => 'Phone Fields',
        'EMAIL FIELDS' => 'Email Fields'            
        );
        print "<select id='SB_fieldTypeSelect' name='SB_fieldTypeSelect' onChange='buildSearch();' onkeyup='buildSearch();'>\n";
        foreach ($fieldTypeSelectOptions as $opKey => $op) {
            print "<option value='$opKey'";
            if ($opKey == $searchRequest->getFieldType()) print " selected='selected' ";
            print ">$op</option>";
        }
        print "</select>";

        print " limited to <input id='SB_limit' name='SB_limit' type='text'  value='" . htmlspecialchars($searchRequest->getLimit(),ENT_QUOTES,'UTF-8') . "' size='5' onKeyUp='buildSearch();' /> maximum records</td></tr>\n";

        print "<tr id='sosl_search_textarea_row'><td valign='top' colspan='3'><br/>Enter or modify a SOSL search below:" .
        "<br/><textarea id='sosl_search_textarea' type='text' name='sosl_search' cols='100' rows='" . getConfig("textareaRows") . "' style='overflow: auto; font-family: monospace, courier;'>". htmlspecialchars($searchRequest->getSoslSearch(),ENT_QUOTES,'UTF-8') . "</textarea>" .
      "</td></tr>";

        print "<tr><td><input type='submit' name='searchSubmit' value='Search' />";
        //print "<input type='reset' value='Reset' />";

        //save and retrieve named searches
        print "<td align='right' colspan='2'>";

        print "&nbsp;Run: " .
          "<select name='getSr' style='width: 10em;' onChange='document.search_form.submit();'>" . 
          "<option value='' selected='selected'></option>";
        if (isset($_SESSION['savedSearchRequests'])) {
            foreach ($_SESSION['savedSearchRequests'] as $srName => $sr) {
                if($srName != null) print "<option value='$srName'>$srName</option>";
            }
        }
        print "</select>";

        print "&nbsp;&nbsp;Save as: <input type='text' id='saveSr' name='saveSr' value='" . htmlspecialchars($searchRequest->getName(),ENT_QUOTES,'UTF-8') . "' style='width: 10em;'/>\n";

        print "<input type='submit' name='doSaveSr' value='Save' onclick='return doesSearchHaveName();' />\n";
        print "<input type='submit' name='clearAllSr' value='Clear All'/>\n";

        print "&nbsp;&nbsp;" .
          "<img onmouseover=\"Tip('Save a search with a name and run it at a later time during your session. Note, if a search is already saved with the same name, the previous one will be overwritten.')\" align='absmiddle' src='" . getStaticResourcesPath() ."/images/help16.png'/>";


        print "</td></tr></table><p/></form>\n";

        $rowNum = 0;
        foreach ($searchRequest->getReturningObjects() as $ro) {
            print "<script>addReturningObjectRow(" .
            $rowNum++ . ", " .
        "\"" . $ro->getObject()     . "\", " . 
        "\"" . $ro->getFields()  . "\"" .
        ");</script>";
        }

        print "<script>toggleFieldDisabled();</script>";
}


function search($searchRequest) {
    try {
        $searchResponse = WorkbenchContext::get()->getPartnerConnection()->search($searchRequest->getSoslSearch());

        if (isset($searchResponse->searchRecords)) {
            $records = $searchResponse->searchRecords;
        } else {
            $records = null;
        }

        return $records;

    } catch (Exception $e) {
        displayError($e->getMessage(),false, true);
    }
}


//If the user selects to display the form on screen, they are routed to this function
function displaySearchResult($records, $searchTimeElapsed) {
    //Check if records were returned
    if ($records) {
        if (getConfig("areTablesSortable")) {
            addFooterScript("<script type='text/javascript' src='" . getStaticResourcesPath() . "/script/sortable.js'></script>");
        }
        
        try {
            print "<a name='sr'></a><div style='clear: both;'><br/><h2>Search Results</h2>\n";
            print "<p>Returned " . count($records) . " total record";
            if (count($records) !== 1) print 's';
            print " in ";
            printf ("%01.3f", $searchTimeElapsed);
            print " seconds:</p>";

            $searchResultArray = array();
            foreach ($records as $record) {
                $recordObject = new Sobject($record->record);
                $searchResultArray[$recordObject->type][] = $recordObject;
            }


            foreach ($searchResultArray as $recordSetName=>$records) {
                echo "<h3>$recordSetName</h3>";

                print "<table id='" . $recordSetName . "_results' class='" . getTableClass() ."'>\n";
                //Print the header row on screen
                $record0 = $records[0];
                print "<tr><th></th>";
                //If the user queried for the Salesforce ID, this special method is nessisary
                //to export it from the nested SOAP message. This will always be displayed
                //in the first column regardless of search order
                if (isset($record0->Id)) {
                    print "<th>Id</th>";
                }
                if ($record0->fields) {
                    foreach ($record0->fields->children() as $field) {
                        print "<th>";
                        print htmlspecialchars($field->getName(),ENT_QUOTES,'UTF-8');
                        print "</th>";
                    }
                } else {
                    print "</td></tr>";
                }
                print "</tr>\n";

                //Print the remaining rows in the body
                $rowNum = 1;
                foreach ($records as $record) {
                    print "<tr><td>$rowNum</td>";
                    $rowNum++;
                    //Another check if there are ID columns in the body
                    if (isset($record->Id)) {
                        print "<td>" . addLinksToUiForIds($record->Id) . "</td>";
                    }
                    //Print the non-ID fields
                    if (isset($record->fields)) {
                        foreach ($record->fields as $datum) {
                            print "<td>";
                            if ($datum) {
                                print localizeDateTimes(addLinksToUiForIds(htmlspecialchars($datum,ENT_QUOTES,'UTF-8')));
                            } else {
                                print "&nbsp;";
                            }
                            print "</td>";
                        }
                        print "</tr>\n";
                    } else {
                        print "</td></tr>\n";
                    }
                }
                print "</table>&nbsp;<p/>";
            }
            print    "</div>\n";
        } catch (Exception $e) {
            $errors = null;
            $errors = $e->getMessage();
            print "<p />";
            displayError($errors,false,true);
        }
    } else {
        print "<p><a name='sr'>&nbsp;</a></p>";
        displayError("Sorry, no records returned.");
    }
    include_once 'footer.php';
}

?>
