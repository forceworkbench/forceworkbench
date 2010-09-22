<?php

// ALL PUT FUNCTIONS:
// INSERT, UPDATE, UPSERT, DELETE, UNDELETE, AND PURGE


/**
 * Main logic and control flow for all PUT funcions
 * @param unknown_type $action
 */
function put($action) {

    $confirmAction = 'Confirm ' . ucwords($action);

    if (isset($_POST['action']) && $_POST['action'] == $confirmAction) {
        if ($action == 'upsert' && (isset($_SESSION['_ext_id']) || isset($_POST['_ext_id']))) {
            $extId = isset($_SESSION['_ext_id']) ? $_SESSION['_ext_id'] : $_POST['_ext_id'];
        } else {
            $extId = NULL;
        }

        if ($action == 'delete' && isset($_POST['doHardDelete']) && $_POST['doHardDelete']) {
            $action = 'hardDelete';
        }

        if (isset($_POST['doAsync'])) {
            putAsync(
                $action,
                $extId,
                isset($_SESSION['field_map']) ? $_SESSION['field_map'] : null,
                isset($_SESSION['csv_array']) ? $_SESSION['csv_array'] :  null,
                isset($_SESSION['tempZipFile']) ? $_SESSION['tempZipFile'] :  null);
        } else {
            require_once 'header.php';
            print "<h1>" . ucwords($action) . " Results</h1>";
            $apiCall = ($action == 'insert') ? 'create' : $action;
            if ($action == "insert" || $action == "update" || $action == "upsert") {
                putSync(
                $apiCall,
                $extId,
                isset($_SESSION['field_map']) ? $_SESSION['field_map'] : null,
                isset($_SESSION['csv_array']) ? $_SESSION['csv_array'] :  null,
                true);
            } else {
                putSyncIdOnly($action,$_SESSION['field_map'],$_SESSION['csv_array'],true);
            }
            include_once 'footer.php';
        }
        unset($_SESSION['field_map'],$_SESSION['csv_array'],$_SESSION['_ext_id'],$_SESSION['file_tmp_name'],$_SESSION['tempZipFile']);
    } elseif (isset($_POST['action']) && $_POST['action'] == 'Map Fields') {
        require_once 'header.php';
        array_pop($_POST); //remove header row
        if (isset($_POST['_ext_id'])) {
            $_SESSION['_ext_id'] = $_POST['_ext_id'];
            $_POST['_ext_id'] = NULL;
        }
        $_SESSION['field_map'] = convertFieldMapToArray($_POST);
        confirmFieldMappings(
            $confirmAction,
            $_SESSION['field_map'],
            isset($_SESSION['csv_array'])?$_SESSION['csv_array']:null,
            isset($_SESSION['_ext_id'])?$_SESSION['_ext_id']:null
        );
        include_once 'footer.php';
    } elseif (isset($_FILES['file'])) {
        require_once 'header.php';
                
        $validationResult = validateUploadedFile($_FILES['file']);
        $fileType = resolveFileType($_FILES['file']);
        
        if ($validationResult || ($fileType != "csv" && $fileType != "zip")) {
            displayUploadFileWithObjectSelectionForm('file',TRUE);
            displayError($validationResult);
        } else if (($action == "insert" || $action == "update" || $action == "upsert") && $_POST['default_object'] == "") {
            displayUploadFileWithObjectSelectionForm('file',$action);
            displayError("Must select an object to $action.");
        } else if ($fileType == "csv") {
            $csvFileName = basename($_FILES['file']['name']);
            $_SESSION['file_tmp_name'] = $_FILES['file']['tmp_name'];
            $_SESSION['csv_array'] = convertCsvFileToArray($_SESSION['file_tmp_name']);
            $csvArrayCount = count($_SESSION['csv_array']) - 1;
            if (!$csvArrayCount) {
                displayError("The file uploaded contains no records. Please try again.",false,true);
            } elseif ($csvArrayCount > $_SESSION['config']['maxFileLengthRows']) {
                displayError ("The file uploaded contains more than " . $_SESSION['config']['maxFileLengthRows'] . " records. Please try again.", false, true);
            }
            $info = "The file $csvFileName was uploaded successfully and contains $csvArrayCount row";
            if ($csvArrayCount !== 1) $info .= 's';
            displayInfo($info);
            print "<br/>";
            setFieldMappings($action,$_SESSION['csv_array']);
        } else if ($fileType == "zip") {
            $_SESSION['tempZipFile'] = file_get_contents($_FILES['file']['tmp_name']);
            displayInfo("Successfully staged " . ceil(($_FILES["file"]["size"] / 1024)) . " KB zip file " . $_FILES["file"]["name"] . " for $action via the Bulk API.");
            print "<br/>";
            print "<form method='POST' action='" . $_SERVER['PHP_SELF'] . "'>";
            
            if ($action == 'upsert') {
                print "<p><label>External Id: ";
                print "<select name='_ext_id'>\n";
                foreach (describeSObject($_POST['default_object'])->fields as $fields => $field) {
                    if ($field->idLookup) { 
                        print   " <option value='$field->name'";
                        if($field->name == 'Id') print " selected='true'";
                        print ">$field->name</option>\n";
                    }
                }
                print "</select></label></p>";
            }
            
            print "<input name='doAsync' type='hidden' value='true'/>"; // workaround for disabled checkbox
            //print "<p><input type='checkbox' checked='checked' disabled='disabled'>Process records asynchronously via Bulk API</p>";
            displayUnsupportedBulkConfigList("inline");
    
            print "<p>&nbsp;</p><p><input type='submit' name='action' value='$confirmAction' /></p>\n";
            print "</form>\n";
        
        } else {
            throw new Exception("Illegal State");
        }
        include_once 'footer.php';
    } else {
        require_once 'header.php';
        
        if ($_SESSION['config']['allOrNoneHeader_allOrNone'] && !apiVersionIsAtLeast(20.0)) {
            displayWarning(array("All-Or-None Transactional Processing Only Supported in API 20.0 and higher.",
                                 "You may continue to use API " . getApiVersion() . 
                                 " for this operation, but the default, partial save behavior will be used."));
        }
        
        print "<p class='instructions'>Select an object and upload a CSV or ZIP file to $action:</p>\n";
        displayUploadFileWithObjectSelectionForm('file', $action);
        include_once 'footer.php';
    }
}


//CSV UPLOADING FUNCTIONS

/**
 * Form to upload CSV for all PUT functions
 *
 * @param $fileInputName
 * @param $showObjectSelect
 * @param $action
 */
function displayUploadFileWithObjectSelectionForm($fileInputName, $action) {
    print "<form enctype='multipart/form-data' method='post' action='" . $_SERVER['PHP_SELF'] . "'>\n";
    print "<input type='hidden' name='MAX_FILE_SIZE' value='" . $_SESSION['config']['maxFileSize'] . "' />\n";
    print "<p><input type='file' name='$fileInputName' size=44 /></p>\n";
    if ($action == "insert" || $action == "update" || $action == "upsert") {
        $filter1 = null;
        $filter2 = null;
        if($action == "insert") $filter1 = "createable";
        elseif($action == "update") $filter1 = "updateable";
        elseif ($action == "upsert") {$filter1 = "createable"; $filter2 = "updateable";}

        printObjectSelection($_SESSION['default_object'], 'default_object', "20", null, $filter1, $filter2);
         
        $submitLabel = 'Upload & Select Object';
    } else {
        $submitLabel = 'Upload';
    }
    print "<p><input type='submit' name='action' value='$submitLabel' /></p>\n";
    print "</form>\n";
}

/**
 * Determines if the specified file is CSV or ZIP file
 *
 * @param unknown_type $file
 */
function resolveFileType($file) {
    if (stristr($file['name'],'.csv') && (stristr($file['type'],'csv') || $file['type'] !== "application//vnd.ms-excel")) {
        return "csv";
    } else if (stristr($file['name'],'.zip') && (stristr($file['type'],'zip') || stristr($file['type'],'octet-stream'))) {
        return "zip";
    } else {
        throw new Exception("Unknown file type. Only CSV and ZIP files are allowed.");
    }
}

/**
 * Make sure the uploaded CSV is valid
 *
 * @param csvfile $file
 * @return error codes or 0 if ok
 */
function validateCsvUpload($file) {
    $validationResult = validateUploadedFile($file);

    if ((!stristr($file['type'],'csv') || $file['type'] !== "application//vnd.ms-excel") && !stristr($file['name'],'.csv')) {
        return("The file uploaded is not a valid CSV file. Please try again.");
    }

    return $validationResult;
}

/**
 * Read a CSV file and return a PHP array
 *
 * @param csv $file
 * @return PHP array
 */
function convertCsvFileToArray($file) {
    ini_set("auto_detect_line_endings", true); //detect mac os line endings too

    $csvArray = array();
    $handle = fopen($file, "r");
    for ($row=0; ($data = fgetcsv($handle)) !== FALSE; $row++) {
        for ($col=0; $col < count($data); $col++) {
            $csvArray[$row][$col] = $data[$col];
        }
    }
    fclose($handle);

    if ($csvArray !== NULL) {
        return($csvArray);
    } else {
        displayError("There were errors parsing the CSV file. Please try again.", false, true);
    }
}

/**
 * Prints CSV array to screen
 *
 * @param $csvArray
 */
function displayCsvArray($csvArray) {
    print "<table class='dataTable'>\n";
    print "<tr><th>&nbsp;</th>";
    for ($col=0; $col < count($csvArray[0]); $col++) {
        print "<th>";
        print htmlspecialchars($csvArray[0][$col],ENT_QUOTES,'UTF-8');
        print "</th>";
    }
    print "</tr>\n";
    for ($row=1; $row < count($csvArray); $row++) {
        print "<tr><td>$row</td>";
        for ($col=0; $col < count($csvArray[0]); $col++) {
            print "<td>";
            if ($csvArray[$row][$col]) {
                print addLinksToUiForIds(htmlspecialchars($csvArray[$row][$col],ENT_QUOTES,'UTF-8'));
            } else {
                print "&nbsp;";
            }
            print "</td>";
        }
        print "</tr>\n";
    }
    print "</table>\n";
}

//ALL FIELD MAPPING FUNCTIONS

/**
 * Print screen for user to enter
 * field mapping values. This is used
 * for all PUT functions.
 *
 * @param $action
 * @param $csvArray
 */
function setFieldMappings($action,$csvArray) {
    if ($action == 'insert' || $action == 'upsert' || $action == 'update') {
        if (isset($_SESSION['default_object'])) {
            $describeSObjectResult = describeSObject($_SESSION['default_object']);
        } else {
            displayError("A default object is required to $action. Go to the Select page to choose a default object and try again.");
        }
    }

    print "<div id='setFieldMappingster_block_loading' style='display:block; color:#888;'><img src='images/wait16trans.gif' align='absmiddle'/> Loading...</div>";

    print "<div id='setFieldMappingster_block' style='display:none;'>";

    print "<form method='POST' action='" . $_SERVER['PHP_SELF'] . "'>";

    if ($action == 'upsert') {
        print "<p class='instructions'>Choose the Salesforce field to use as the External Id. Be sure to also map this field below:</p>\n";
        print "<table class='fieldMapping'><tr>\n";
        print "<td style='color: red;'>External Id</td>";
        print "<td><select name='_ext_id' style='width: 100%;'>\n";
        //        print "    <option value=''></option>\n";
        foreach ($describeSObjectResult->fields as $fields => $field) {
            if ($field->idLookup) { //limit the fields to only those with the idLookup property set to true. Corrected Issue #10
                print   " <option value='$field->name'";
                if($field->name == 'Id') print " selected='true'";
                print ">$field->name</option>\n";
            }
        }
        print "</select></td></tr></table>\n";


    } //end if upsert

    print "<p class='instructions'>Map the Salesforce fields to the columns from the uploaded CSV:</p>\n";
    print "<table class='fieldMapping'>\n";
    print "<tr><th>Salesforce Field</th>";
    print "<th>CSV Field</th>";
    if ($_SESSION['config']['showReferenceBy'] && ($action == 'insert' || $action == 'update' || $action == 'upsert'))
    print "<th onmouseover=\"Tip('For fields that reference other objects, external ids from the foreign objects provided in the CSV file and can be automatically matched to their cooresponding primary ids. Use this column to select the object and field by which to perform the Smart Lookup. If left unselected, standard lookup using the primary id will be performed. If this field is disabled, only stardard lookup is available because the foreign object contains no external ids.')\">Smart Lookup &nbsp; <img align='absmiddle' src='images/help16.png'/></th>";
    print "</tr>\n";

    if ($action == 'insert') {
        foreach ($describeSObjectResult->fields as $fields => $field) {
            if ($field->createable) {
                printPutFieldForMapping($field, $csvArray, true);
            }
        }
    }

    if ($action == 'update') {
        printPutFieldForMappingId($csvArray, true);
        foreach ($describeSObjectResult->fields as $fields => $field) {
            if ($field->updateable) {
                printPutFieldForMapping($field, $csvArray, true);
            }
        }
    }

    if ($action == 'upsert') {
        printPutFieldForMappingId($csvArray, true);
        foreach ($describeSObjectResult->fields as $fields => $field) {
            if ($field->updateable && $field->createable) {
                printPutFieldForMapping($field, $csvArray, true);
            }
        }
    }


    if ($action == 'delete' || $action == 'undelete' || $action == 'purge') {
        printPutFieldForMappingId($csvArray, false);
    }


    print "</table>\n";
    print "<p><input type='submit' name='action' value='Map Fields' />\n";
    print "<input type='button' value='Preview CSV' onClick='window.open(" . '"csv_preview.php"' . ")'></p>\n";
    print "</form>\n</div>\n";
    print "<script>document.getElementById('setFieldMappingster_block_loading').style.display = 'none';</script>";
    print "<script>document.getElementById('setFieldMappingster_block').style.display = 'block';</script>";
}

function printPutFieldForMappingId($csvArray, $showRefCol) {
    $idField = new stdClass();
    $idField->nillable = false;
    $idField->defaultedOnCreate = false;
    $idField->name = "Id";

    printPutFieldForMapping($idField, $csvArray, $showRefCol);
}

/**
 * Prints field mapping setter row
 * for non-Id fields
 *
 * @param $field
 * @param $csvArray
 */
function printPutFieldForMapping($field, $csvArray, $showRefCol) {
    print "<tr";
    if (!$field->nillable && !$field->defaultedOnCreate) print " style='color: red;'";
    print "><td>$field->name</td>";

    print "<td><select name='$field->name' style='width: 100%;'>";
    print "    <option value=''></option>\n";
    foreach ($csvArray[0] as $col) {
        print   "<option value='$col'";
        if (strtolower($col) == strtolower($field->name)) print " selected='true' ";
        print ">$col</option>\n";
    }
    print "</select></td>";

    if ($showRefCol && $_SESSION['config']['showReferenceBy']) {
        if (isset($field->referenceTo) && isset($field->relationshipName)) {
            $describeRefObjResult = describeSObject($field->referenceTo);
            printRefField($field, $describeRefObjResult);
        } else {
            print "<td>&nbsp;</td>\n";
        }
    }

    print "</tr>\n";
}

/**
 * Generate and print the SmartLookup dropdown for field mapping
 *
 * @param $field
 * @param $describeRefObjResult
 */
function printRefField($field, $describeRefObjResult) {
    if (is_array($describeRefObjResult)) {
        $polyExtFields = array();
        foreach ($describeRefObjResult as $describeRefObjResultKey => $describeRefObjResult) {
            $extFields = null;
            if (isset($describeRefObjResult->fields)) {
                foreach ($describeRefObjResult->fields as $extFieldKey => $extFieldVal) {
                    if ($extFieldVal->idLookup == true) {
                        $extFields[$extFieldKey] = $extFieldVal;
                    }
                }
                $polyExtFields[$describeRefObjResult->name] = $extFields;
            }
        }

        //check if the new array has any fields
        print "<td><select name='$field->name:$field->relationshipName' style='width: 100%;'";

        $numOfExtFields = 0;
        foreach ($polyExtFields as $extFields) {
            if (count($extFields) > 1) {
                $numOfExtFields = $numOfExtFields + count($extFields) - 1;
            }
        }

        if ($numOfExtFields <= 0) {
            print " disabled='true' ";
        }
        print ">\n";

        print  " <option value='Id' selected='true'></option>\n";
        foreach ($polyExtFields as $objectType => $extFields) {
            if (count($extFields) > 0) {
                foreach ($extFields as $extFieldKey => $extFieldVal) {
                    if ($extFieldVal->name != 'Id') {
                        $isPolymorphic = is_array($field->referenceTo) ? "1" : "0";
                        print  " <option value='$field->name.$field->relationshipName.$isPolymorphic.$objectType.$extFieldVal->name'>$objectType.$extFieldVal->name</option>\n";
                    }
                }
            }
        }
        print "</select></td>\n";

    } else { //for scalar values
        //check to see if there are any IdLookup fields and if so move them to a new array
        $extFields = null;
        if (count($describeRefObjResult->fields) > 0) {
            foreach ($describeRefObjResult->fields as $extFieldKey => $extFieldVal) {
                if ($extFieldVal->idLookup == true) {
                    $extFields[$extFieldKey] = $extFieldVal;
                }
            }
        }


        //check if the new array has any fields and if so
        if (count($extFields) > 0) {
            print "<td><select name='$field->name:$field->relationshipName' style='width: 100%;'";
            if (count($extFields) == 1) print " disabled='true' "; //disable the selection if only one choice ('Id') is available
            print ">\n";

            print  " <option value='Id' selected='true'></option>\n";
            foreach ($extFields as $extFieldKey => $extFieldVal) {
                if ($extFieldVal->name != 'Id') {
                    $isPolymorphic = is_array($field->referenceTo) ? "1" : "0";
                    print  " <option value='$field->name.$field->relationshipName.$isPolymorphic.$describeRefObjResult->name.$extFieldVal->name'>$describeRefObjResult->name.$extFieldVal->name</option>\n";
                }
            }
            print "</select></td>\n";
        }
    }

}

/**
 * Convert the field map $POST to a PHP array
 * by decomposing the relationship map, if SmartLookup
 * is being used.
 *
 * @param unknown_type $fieldMap
 * @return unknown
 */
function convertFieldMapToArray($fieldMap) {
    $fieldMapArray = array();

    foreach ($fieldMap as $fieldMapKey=>$fieldMapValue) {
        if (preg_match('/^(\w+):(\w+)$/',$fieldMapKey,$keyMatches)) {
            if (preg_match('/^(\w+).(\w+).(\w+).(\w+).(\w+)$/',$fieldMapValue,$valueMatches)) {
                $fieldMapArray[$valueMatches[1]]["relationshipName"] = $valueMatches[2];
                $fieldMapArray[$valueMatches[1]]["isPolymorphic"] = $valueMatches[3];
                $fieldMapArray[$valueMatches[1]]["relatedObjectName"] = $valueMatches[4];
                $fieldMapArray[$valueMatches[1]]["relatedFieldName"] = $valueMatches[5];
            }
        } else if ($fieldMapValue) {
            $fieldMapArray[$fieldMapKey]["csvField"] = $fieldMapValue;
        }
    }

    return $fieldMapArray;
}

/**
 * Display the screen for field mapping user set for confirmation.
 * Also allows user to choose to use Bulk API to
 * do an async PUT opertion
 *
 * @param $action
 * @param $fieldMap
 * @param $csvArray
 * @param $extId
 */
function confirmFieldMappings($action,$fieldMap,$csvArray,$extId) {
    if (!($fieldMap && $csvArray)) {
        displayError("CSV file and field mapping not initialized successfully. Upload a new file and map fields.",true,true);
    } else {

        if (($action == 'Confirm Update') || ($action == 'Confirm Delete') || ($action == 'Confirm Undelete') || ($action == 'Confirm Purge')) {
            if (!isset($fieldMap['Id'])) {
                displayError("Salesforce ID not selected. Please try again.",false,true);
            } else {
                ob_start();

                if (($action == 'Confirm Delete') || ($action == 'Confirm Undelete') || ($action == 'Confirm Purge')) {
                    displayFieldMappings($fieldMap, null, false);
                } else {
                    displayFieldMappings($fieldMap, null, true);
                }

                $idCol = array_search($fieldMap['Id'],$csvArray[0]);
                for ($row=1,$idCount = 0; $row < count($csvArray); $row++) {
                    if ($csvArray[$row][$idCol]) {
                        $idCount++;
                    }
                }
                $fieldMappingTable = ob_get_clean();
                displayInfo ("The file uploaded contains $idCount records with Salesforce IDs with the field mapping below.");
                print "<p class='instructions'>Confirm the mappings below:</p>";
                print "<p>$fieldMappingTable</p>";
            }
        } else {
            $recordCount = count($csvArray) - 1;
            displayInfo ("The file uploaded contains $recordCount records to be added to " . $_SESSION['default_object']);
            print "<p class='instructions'>Confirm the mappings below:</p>";
            displayFieldMappings($fieldMap, $extId, true);
        }

        print "<form method='POST' action='" . $_SERVER['PHP_SELF'] . "'>";

        //Hard Delete option
        if (apiVersionIsAtLeast(19.0) && $action == 'Confirm Delete') {
            print "<p><label><input type='checkbox' id='doHardDelete' name='doHardDelete' onChange=\"".
                  "if (this.checked) {" .
                  "    document.getElementById('doAsync').checked = true;" . 
                  "    document.getElementById('asyncDeleteObjectSelection').style.display = 'inline';" .
                  "    document.getElementById('unsupportedBulkConfigList').style.display = 'inline';" .
                  "}\"/> " . 
                  "Permanently hard delete records</label>" .
                  "&nbsp;<img onmouseover=\"Tip('When specified, the deleted records are not stored in the Recycle Bin. " . 
                  "Instead, the records become immediately eligible for deletion, don\'t count toward the storage space used " . 
                  "by your organization, and may improve performance. The Administrative permission for this operation, " .
                  "\'Bulk API Hard Delete\', is disabled by default and must be enabled by an administrator. " . 
                  "A Salesforce user license is required for hard delete. Hard Delete is only available via Bulk API.')\" ".
                  "align='absmiddle' src='images/help16.png'/>" . 
                  "</p>";
        }

        //Async Options
        if((apiVersionIsAtLeast(17.0) && in_array($action, array('Confirm Insert', 'Confirm Update', 'Confirm Upsert')))
           || (apiVersionIsAtLeast(18.0) && $action == 'Confirm Delete')) {
            print "<p><label><input id='doAsync' name='doAsync' type='checkbox' onChange=\"".
                   "var doHardDelete = document.getElementById('doHardDelete');" .
                   "var asyncDeleteObjectSelection = document.getElementById('asyncDeleteObjectSelection');" .
                   "var unsupportedBulkConfigList = document.getElementById('unsupportedBulkConfigList');" .
                   "if (this.checked) {" .  
                   "     if (asyncDeleteObjectSelection != null) asyncDeleteObjectSelection.style.display = 'inline';" .
                   "     if (unsupportedBulkConfigList != null) unsupportedBulkConfigList.style.display = 'inline';" .
                   "} else {" .
                   "     if (doHardDelete != null) doHardDelete.checked = false;" .
                   "     if (asyncDeleteObjectSelection != null) asyncDeleteObjectSelection.style.display = 'none';" .
                   "     if (unsupportedBulkConfigList != null) unsupportedBulkConfigList.style.display = 'none';" .
                   "}\"/> " .
                  "Process records asynchronously via Bulk API</label>" .
                  "&nbsp;<img onmouseover=\"Tip('Processing records asynchronously is recommended for large data loads. " .
                  "The data will be uploaded to Salesforce via the Bulk API in batches and processed when server resources are available. " . 
                  "After batches have completed, results can be downloaded. Batch size and concurrency options are available in Settings.')\" ".
                  "align='absmiddle' src='images/help16.png'/>" . 
                  "</p>";

            // object selection for Bulk API Delete
            if ($action == 'Confirm Delete') {
                print "<div id='asyncDeleteObjectSelection' style='display: none; margin-left: 3em;'>Object Type: ";
                printObjectSelection($_SESSION['default_object']);
                print "</div>";
            }
            
            displayUnsupportedBulkConfigList("none");
        }

        print "<p>&nbsp;</p><p><input type='submit' name='action' value='$action' /></p>\n";
        print "</form>\n";
    }
}

function displayUnsupportedBulkConfigList($display) {
    // all configs not supported by Bulk API
    $bulkUnsupportedConfigs = array(
        "mruHeader_updateMru",
        "allOrNoneHeader_allOrNone",
        "emailHeader_triggerAutoResponseEmail",
        "emailHeader_triggertriggerUserEmail",
        "emailHeader_triggerOtherEmail",
        "allowFieldTruncationHeader_allowFieldTruncation",
        "UserTerritoryDeleteHeader_transferToUserId"
    );

    // find this user's settings that are in the unsupported config list
    $bulkUnsupportedSettings = array();
    foreach ($bulkUnsupportedConfigs as $c) {
        if ($GLOBALS["config"][$c]["default"] != getConfig($c)) {
            $bulkUnsupportedSettings[] = $c;
        }
    }
    
    // print out a warning if any settings were found
    if (count($bulkUnsupportedSettings) > 0) {
        print "<div id='unsupportedBulkConfigList' style='display: $display; color: orange;'>" .
                  "<p style='margin-left: 3em;'>" .  
                      "<img src='images/warning24.png' style='margin-right: 5px;'/>" .
                      "The follow settings are not supported by the Bulk API and will be ignored:" . 
                      "<ul style='margin-left: 3em;'>";
        
        foreach ($bulkUnsupportedSettings as $s) {
            print "<li>" . $GLOBALS['config'][$s]['label'] . "</li>";
        }
        
        print "</ul>" . 
              "</p>" . 
              "</div>";
    } 
}

/**
 * Display the field mapping for confirmation
 * Only used for non-Id PUT functions.
 *
 * @param unknown_type $fieldMap
 * @param unknown_type $extId
 */
function displayFieldMappings($fieldMap,$extId,$showRefCol) {
    if ($extId) {
        print "<table class='fieldMapping'>\n";
        print "<tr><td>External Id</td> <td>$extId</td></tr>\n";
        print "</table><p/>\n";
    }

    print "<table class='fieldMapping'>\n";
    print "<tr><th>Salesforce Field</th>";
    print "<th>CSV Field</th>";
    if ($showRefCol && $_SESSION['config']['showReferenceBy']) print "<th>Smart Lookup</th>";
    print "</tr>\n";

    foreach ($fieldMap as $salesforceField=>$fieldMapArray) {
        print "<tr><td>$salesforceField</td>";
        print "<td>" . $fieldMapArray['csvField'] . "</td>";
        if ($showRefCol && $_SESSION['config']['showReferenceBy']) {
            print "<td>";
            if (isset($fieldMapArray['relatedObjectName']) && isset($fieldMapArray['relatedFieldName'])) {
                print $fieldMapArray['relatedObjectName'] . "." . $fieldMapArray['relatedFieldName'];
            }
            print "</td>";
        }
        print "</tr>\n";
    }

    print "</table>\n";
}

//FUNCTIONS THAT DO THE ACTUAL PARSING AND PUTting TO THE API


/**
 * Does the actual work of PUTting
 * for id-only funcitons and showing results
 *
 * @param $apiCall
 * @param $fieldMap
 * @param $csvArray
 * @param $showResults
 */
function putSyncIdOnly($apiCall,$fieldMap,$csvArray,$showResults) {
    $origCsvArray = $csvArray;

    if (!($fieldMap && $csvArray)) {
        displayError("CSV file and field mapping not initialized successfully. Upload a new file and map fields.");
    } else {

        $idArray =  array();
        $idCol = array_search($fieldMap['Id'],$csvArray[0]);

        for ($row=1; $row < count($csvArray); $row++) {
            if ($csvArray[$row][$idCol]) {
                $idArray[] = $csvArray[$row][$idCol];
            }
        }

        $results = array();
        $idArrayAll = $idArray;

        while($idArray) {
            $idArrayBatch = array_splice($idArray,0,$_SESSION['config']['batchSize']);
            try {
                global $partnerConnection;
                if($apiCall == 'purge') $apiCall = 'emptyRecycleBin';
                $resultsMore = $partnerConnection->$apiCall($idArrayBatch);

                if (!$results) {
                    $results = $resultsMore;
                } else {
                    $results = array_merge($results,$resultsMore);
                }

            } catch (Exception $e) {
                displayError($e->getMessage(),false,true);
            }
        }
        if($showResults) displayIdOnlyPutResults($results,$apiCall,$origCsvArray,$idArrayAll);
    }
}

/**
 * Does the actual work of PUTting
 * for non-id-only funcitons and showing results
 *
 * @param $apiCall
 * @param $extId
 * @param $fieldMap
 * @param $csvArray
 * @param $showResults
 */
function putSync($apiCall,$extId,$fieldMap,$csvArray,$showResults) {
    $origCsvArray = $csvArray;//backing up for results
    if (!($fieldMap && $csvArray && $_SESSION['default_object'])) {
        displayError("CSV file and field mapping not initialized. Upload a new file and map fields.",true,true);
    } else {
        $csvHeader = array_shift($csvArray);
        $results = array();

        while($csvArray) {
            $sObjects = array();
            $csvArrayBatch = array_splice($csvArray,0,$_SESSION['config']['batchSize']);

            for ($row=0; $row < count($csvArrayBatch); $row++) {
                $sObject = new SObject;
                $sObject->type = $_SESSION['default_object'];
                if($_SESSION['config']['fieldsToNull']) $sObject->fieldsToNull = array();
                $fields = array();

                foreach ($fieldMap as $salesforceField=>$fieldMapArray) {
                    if (isset($fieldMapArray['relatedObjectName']) && isset($fieldMapArray['relatedFieldName']) && isset($fieldMapArray['csvField'])) {
                        $refSObject = new SObject;
                        $refSObject->type = $fieldMapArray['relatedObjectName'];
                        $col = array_search($fieldMapArray['csvField'],$csvHeader);
                        if ($csvArrayBatch[$row][$col] != "") {
                            $refSObject->fields = array($fieldMapArray['relatedFieldName'] => htmlspecialchars($csvArrayBatch[$row][$col],ENT_QUOTES,'UTF-8'));
                        }
                        $field = array($fieldMapArray['relationshipName'] => $refSObject);
                    } else if (isset($salesforceField) && isset($fieldMapArray['csvField'])) {
                        $col = array_search($fieldMapArray['csvField'],$csvHeader);
                        if ($csvArrayBatch[$row][$col] != "") {
                            $field = array($salesforceField => htmlspecialchars($csvArrayBatch[$row][$col],ENT_QUOTES,'UTF-8'));
                        } elseif ($_SESSION['config']['fieldsToNull']) {
                            $sObject->fieldsToNull[] = $salesforceField;
                        }
                    }

                    if (!$fields) {
                        $fields = $field;
                    } else {
                        $fields = array_merge($fields,$field);
                    }
                }

                $sObject->fields = $fields;
                array_push($sObjects, $sObject);
                unset($sObject);
            }


            try {
                global $partnerConnection;
                if ($apiCall == 'upsert') {
                    $resultsMore = $partnerConnection->$apiCall($extId,$sObjects);
                } else {
                    $resultsMore = $partnerConnection->$apiCall($sObjects);
                }
                unset($sObjects);
            } catch (Exception $e) {
                $errors = null;
                $errors = $e->getMessage();
                displayError($errors);
                include_once("footer.php");
                exit;
            }
            if (!$results) {
                $results = $resultsMore;
            } else {
                $results = array_merge($results,$resultsMore);
            }
        }
        if($showResults) displayIdOnlyPutResults($results,$apiCall,$origCsvArray,null);
    }
}

/**
 * Does the actual work of PUTting
 * for asyncPUT funcitons and forwarding
 * on to the results page.
 *
 * @param unknown_type $apiCall
 * @param unknown_type $extId
 * @param unknown_type $fieldMap
 * @param unknown_type $csvArray
 */
function putAsync($apiCall,$extId,$fieldMap,$csvArray,$zipFile) {    
    $doingZip = isset($zipFile);
    
    if (!$doingZip && !($fieldMap && $csvArray && $_SESSION['default_object'])) {
        displayError("CSV file and field mapping not initialized or object not selected. Upload a new file and map fields.",true,true);
    } else {
        require_once 'bulkclient/BulkApiClient.php';
        try {
            $job = new JobInfo();
            $job->setObject($_SESSION['default_object']);
            $job->setOpertion($apiCall);
            $job->setContentType($doingZip ? "ZIP_CSV" : "CSV");
            $job->setConcurrencyMode($_SESSION['config']['asyncConcurrencyMode']);
            if(isset($_SESSION['config']['assignmentRuleHeader_assignmentRuleId'])) $job->setAssignmentRuleId($_SESSION['config']['assignmentRuleHeader_assignmentRuleId']);
            if($apiCall == "upsert" && isset($extId)) $job->setExternalIdFieldName($extId);

            $asyncConnection = getAsyncApiConnection();
            $job = $asyncConnection->createJob($job);
        } catch (Exception $e) {
            displayError($e->getMessage(), true, true);
        }

        if ($job->getId() == null) {
            displayError("No job id found. Aborting Bulk API operation.", true, true);
        }

        if ($doingZip) {
            try {
               $asyncConnection->createBatch($job, $zipFile);
            } catch (Exception $e) {
                displayError($e->getMessage(), true, true);
            }
        } else {
            $csvHeader = array_shift($csvArray);
            $results = array();
    
            while($csvArray) {
                $sObjects = array();
                $csvArrayBatch = array_splice($csvArray,0,$_SESSION['config']['asyncBatchSize']);
    
                $asyncCsv = array();
    
                $asyncCsvHeaderRow = array();
                foreach ($fieldMap as $salesforceField=>$fieldMapArray) {
                    if (isset($fieldMapArray['csvField'])) {
                        if (isset($fieldMapArray['relationshipName']) && isset($fieldMapArray['relatedFieldName'])) {
                            $asyncCsvHeaderRow[] = ($fieldMapArray['isPolymorphic'] ? ($fieldMapArray['relatedObjectName'] . ":") : "") .
                            $fieldMapArray['relationshipName'] . "." .
                            $fieldMapArray['relatedFieldName'];
                        } elseif (isset($salesforceField)) {
                            $asyncCsvHeaderRow[] = $salesforceField;
                        }
                    }
                }
                $asyncCsv[] = $asyncCsvHeaderRow;
    
                for ($row=0; $row < count($csvArrayBatch); $row++) {
                    //create new row
                    $asyncCsvRow = array();
                    foreach ($fieldMap as $salesforceField=>$fieldMapArray) {
                        $col = array_search($fieldMapArray['csvField'],$csvHeader);
                        if (isset($salesforceField) && isset($fieldMapArray['csvField'])) {
                            if ($csvArrayBatch[$row][$col] == "" && $_SESSION['config']['fieldsToNull']) {
                                $asyncCsvRow[] = "#N/A";
                            } else {
                                $asyncCsvRow[] = htmlspecialchars($csvArrayBatch[$row][$col],ENT_QUOTES,'UTF-8');
                            }
                        }
                    }
    
                    //add row to the array
                    $asyncCsv[] = $asyncCsvRow;
                }
    
                try {
                    $batch = $asyncConnection->createBatch($job, convertArrayToCsv($asyncCsv));
                } catch (Exception $e) {
                    displayError($e->getMessage(), true, true);
                }
            }
        }

        try {
            $job = $asyncConnection->updateJobState($job->getId(), "Closed");
        } catch (Exception $e) {
            displayError($e->getMessage(), true, true);
        }

        header("Location: asyncStatus.php?jobId=" . $job->getId());
    }
}

// ALL PUT RESULTS FUNCTIONS

/**
 * Display the PUT results from all synchronous
 * functions.
 *
 * @param $results
 * @param $apiCall
 * @param $csvArray
 * @param $idArray
 */
function displayIdOnlyPutResults($results,$apiCall,$csvArray,$idArray) {
    //check if only result is returned
    if(!is_array($results)) $results = array($results);

    unset($_SESSION['resultsWithData']);
    $resultsWithData = array(); //create array to hold results with data for download later
    $_SESSION['resultsWithData'][0] = array("Salesforce Id","Result","Status");
    $_SESSION['resultsWithData'][0] = array_merge($_SESSION['resultsWithData'][0],$csvArray[0]);

    $successCount = 0;
    $errorCount = 0;
    ob_start();
    for ($row=0; $row < count($results); $row++) {
        $excelRow = $row + 1;

        $_SESSION['resultsWithData'][$row+1] = array(); //create array for row

        if ($results[$row]->success) {
            $successCount++;
            print "<tr>";
            print "<td>" . $excelRow . "</td>";
            print "<td>" . addLinksToUiForIds($results[$row]->id) . "</td>";
            $_SESSION['resultsWithData'][$row+1][0] = $results[$row]->id;
            print "<td>Success</td>";
            $_SESSION['resultsWithData'][$row+1][1] = "Success";
            if (($apiCall == 'upsert' && $results[$row]->created) || $apiCall == 'create') {
                print "<td>Created</td>";
                $_SESSION['resultsWithData'][$row+1][2] = "Created";
            } elseif (($apiCall == 'upsert' && !$results[$row]->created) || $apiCall == 'update') {
                print "<td>Updated</td>";
                $_SESSION['resultsWithData'][$row+1][2] = "Updated";
            } elseif (($apiCall == 'delete') || ($apiCall == 'undelete')) {
                print "<td>" . ucwords($apiCall) . "d </td>";
                $_SESSION['resultsWithData'][$row+1][2] = ucwords($apiCall) . "d";
            } elseif ($apiCall == 'emptyRecycleBin') {
                print "<td>Purged</td>";
                $_SESSION['resultsWithData'][$row+1][2] = "Purged";
            }
            print "</tr>\n";
        } else {
            $errorCount++;
            print "<tr style='color: red;'>";
            print "<td>" . $excelRow . "</td>";

            if (!isset($results[$row]->id) && isset($idArray)) {
                $_SESSION['resultsWithData'][$row+1][0] = $idArray[$row]; //add id from idArray for id-only calls
                print "<td>" . addLinksToUiForIds($idArray[$row]) . "</td>";
            } else {
                $_SESSION['resultsWithData'][$row+1][0] = $results[$row]->id; //add id from results for everything else
                print "<td>" . addLinksToUiForIds($results[$row]->id) . "</td>";
            }

            print "<td>" . ucwords($results[$row]->errors->message) . "</td>";
            $_SESSION['resultsWithData'][$row+1][1] = ucwords($results[$row]->errors->message);
            print "<td>" . $results[$row]->errors->statusCode . "</td>";
            $_SESSION['resultsWithData'][$row+1][2] = $results[$row]->errors->statusCode;
            //print "<td>" . $results[$row]->errors->fields . "</td>"; //APIDOC: Reserved for future use. Array of one or more field names. Identifies which fields in the object, if any, affected the error condition.
            print "</tr>\n";
        }

        $_SESSION['resultsWithData'][$row+1] = array_merge($_SESSION['resultsWithData'][$row+1],$csvArray[$row+1]);

    }
    print "</table><br/>";
    $resultsTable = ob_get_clean();
    displayInfo("There were $successCount successes and $errorCount errors.");

    print "<br/><form action='downloadResultsWithData.php' method='GET'><input type='hidden' name='action' value='$apiCall'/><input type='submit' value='Download Full Results'/></form>";

    print "<br/>\n<table class='dataTable'>\n";
    print "<th>&nbsp;</th> <th style='width: 30%'>Salesforce Id</th> <th style='width: 30%'>Result</th> <th style='width: 35%'>Status</th>\n";
    print "<p>$resultsTable</p>";
}
?>