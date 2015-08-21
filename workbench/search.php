<?php
require_once 'soxl/SearchObjects.php';
require_once 'session.php';
require_once 'shared.php';

//clear all saved queries in cookies
// TODO: remove after next version
$persistedSavedSearchRequestsKey = "PSSR@";
if (isset($_COOKIE[$persistedSavedSearchRequestsKey])) {
    setcookie($persistedSavedSearchRequestsKey, null, time() - 3600);
}

$defaultSettings['numReturningObjects'] = 1;

if (isset($_POST['searchSubmit'])) {
    $searchRequest = new SearchRequest($_REQUEST);
} else if(isset($_SESSION['lastSearchRequest'])) {
    $searchRequest = $_SESSION['lastSearchRequest'];
} else {
    $searchRequest = new SearchRequest($defaultSettings);
}

if (isset($_GET['srjb'])) {
    if ($searchRequestJsonString = base64_decode($_REQUEST['srjb'], true)) {
        if ($searchRequestJson = json_decode($searchRequestJsonString, true)) {
            $searchRequest = new SearchRequest($searchRequestJson);
            $_POST['searchSubmit'] = 'Search'; //simulate the user clicking 'Search' to run immediately
        } else {
            displayErrorBeforeForm('Could not parse search request');
        }
    } else {
        displayErrorBeforeForm('Could not decode search request');
    }
}

$_SESSION['lastSearchRequest'] = $searchRequest;

//Main form logic: When the user first enters the page, display form defaulted to
//show the search results with default object selected on a previous page, otherwise
// just display the blank form.
if (isset($_POST['searchSubmit']) && isset($searchRequest)) {
    require_once 'header.php';
    displaySearchForm($searchRequest);
    print updateUrlScript($searchRequest);
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
    
    print "</script>\n";
    print "<script src='" . getPathToStaticResource('/script/search.js') . "' type='text/javascript'></script>\n";

    print "<form method='POST' name='search_form' action='search.php'>\n";
    print getCsrfFormTag();
    print "<input type='hidden' id='numReturningObjects' name='numReturningObjects' value='" . count($searchRequest->getReturningObjects()) ."' />";

    print "<p class='instructions'>Enter a search string and optionally select the objects and fields to return to build a SOSL search below:</p>\n";
    print "<table id='search_form_table' border='0' width='1'>\n<tr>\n";

    print "<td NOWRAP>Search for </td><td NOWRAP colspan='2'><input type='text' id='SB_searchString' name='SB_searchString' value=\"" . htmlspecialchars($searchRequest->getSearchString(),ENT_QUOTES) . "\" size='37' onKeyUp='buildSearch();' /> in ";

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

        print " limited to <input id='SB_limit' name='SB_limit' type='text'  value='" . htmlspecialchars($searchRequest->getLimit(),ENT_QUOTES) . "' size='5' onKeyUp='buildSearch();' /> maximum records</td></tr>\n";

        print "<tr id='sosl_search_textarea_row'><td valign='top' colspan='3'><br/>Enter or modify a SOSL search below:" .
        "<br/><textarea id='sosl_search_textarea' type='text' name='sosl_search' cols='100' rows='" . WorkbenchConfig::get()->value("textareaRows") . "' style='overflow: auto; font-family: monospace, courier;'>". htmlspecialchars($searchRequest->getSoslSearch(),ENT_QUOTES) . "</textarea>" .
      "</td></tr>";

        print "<tr><td><input type='submit' name='searchSubmit' value='Search' />";

        print "<td colspan=4 align='right'>";
        print "&nbsp;&nbsp;" .
            "<img onmouseover=\"Tip('Where did saved searches go? They have been replaced with bookmarkable and shareable searched! Just run a search and bookmark the URL to save or copy and paste to share.')\" align='absmiddle' src='" . getPathToStaticResource('/images/help16.png') . "'/>";
        print "</td></tr></table><p/>\n";


        print "</form>\n";

        $rowNum = 0;
        foreach ($searchRequest->getReturningObjects() as $ro) {
            print "<script>addReturningObjectRow(" .
            $rowNum++ . ", " .
        "\"" . htmlspecialchars($ro->getObject(),ENT_QUOTES) . "\", " .
        "\"" . htmlspecialchars($ro->getFields(),ENT_QUOTES) . "\"" .
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
        if (WorkbenchConfig::get()->value("areTablesSortable")) {
            addFooterScript("<script type='text/javascript' src='" . getPathToStaticResource('/script/sortable.js') . "></script>");
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
                        print htmlspecialchars($field->getName(),ENT_QUOTES);
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
                        print "<td>" . addLinksToIds($record->Id) . "</td>";
                    }
                    //Print the non-ID fields
                    if (isset($record->fields)) {
                        foreach ($record->fields as $datum) {
                            print "<td>";
                            if ($datum) {
                                print localizeDateTimes(addLinksToIds(htmlspecialchars($datum,ENT_QUOTES)));
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

function displayErrorBeforeForm($msg) {
    include_once("header.php");
    print "<p>";
    displayError($msg);
    print "</p>";
}

function updateUrlScript($searchRequest) {
    return "<script type='text/javascript'>window.history.replaceState({}, document.title, '" . srjb($searchRequest) . "');</script>";
}

function srjb($searchRequest) {
    return  basename($_SERVER['SCRIPT_NAME']) . '?srjb=' . urlencode(base64_encode($searchRequest->toJson())) .
    (WorkbenchConfig::get()->value("autoJumpToResults") ? '#sr' : '');
}

?>
