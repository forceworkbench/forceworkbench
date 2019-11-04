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

        if (isset($_POST['sourceType']) && $_POST['sourceType'] == "singleRecord") {
            $singleRecordCsv = array();

            if (requiresObject($action)) {
                $fields = WorkbenchContext::get()->describeSObjects(WorkbenchContext::get()->getDefaultObject())->fields;
            } else {
                $idField = new stdClass();
                $idField->name = "Id";
                $fields['Id'] = $idField;
            }

            $singleRecordFieldMap = convertFieldMapToArray($_POST, fieldsToNameArray($fields));
            
            $anySet = false;
            foreach ($fields as $field) {
                if (isset($_POST[$field->name])) {
                    if (get_magic_quotes_gpc()) {
                        $_POST[$field->name] = stripslashes($_POST[$field->name]);
                    }

                    $anySet |= $_POST[$field->name] != "";
                    $singleRecordCsv[0][] = $field->name;
                    $singleRecordCsv[1][] = trim($_POST[$field->name]);
                    $singleRecordFieldMap[$field->name]["csvField"] = $field->name;
                }
            }
            if (!$anySet) {
                displayError("Must set a value for at least one field to $action.", true, true);
            }
            $_SESSION['csv_array'] = $singleRecordCsv;
            $_SESSION['field_map'] = $singleRecordFieldMap;
        }

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
                isset($_SESSION['tempZipFile']) ? $_SESSION['tempZipFile'] :  null,
                isset($_POST['contentType']) ? $_POST['contentType'] :  null);
        } else {
            require_once 'header.php';
            $apiCall = ($action == 'insert') ? 'create' : $action;
            if ($action == "insert" || $action == "update" || $action == "upsert") {
                putSync(
                $apiCall,
                $extId,
                isset($_SESSION['field_map']) ? $_SESSION['field_map'] : null,
                isset($_SESSION['csv_array']) ? $_SESSION['csv_array'] : null,
                true);
            } else {
                putSyncIdOnly(
                    $action,
                    isset($_SESSION['field_map']) ? $_SESSION['field_map'] : null,
                    isset($_SESSION['csv_array']) ? $_SESSION['csv_array'] : null,
                    true
                );
            }
            include_once 'footer.php';
        }
        unset($_SESSION['field_map'],$_SESSION['csv_array'],$_SESSION['_ext_id'],$_SESSION['file_tmp_name'],$_SESSION['tempZipFile']);
    } else if (isset($_POST['action']) && $_POST['action'] == 'Map Fields') {
        require_once 'header.php';
        array_pop($_POST); //remove header row
        if (isset($_POST['_ext_id'])) {
            $_SESSION['_ext_id'] = $_POST['_ext_id'];
            $_POST['_ext_id'] = NULL;
        }

        if (requiresObject($action)) {
            $fields = WorkbenchContext::get()->describeSObjects(WorkbenchContext::get()->getDefaultObject())->fields;
        } else {
            $idField = new stdClass();
            $idField->name = "Id";
            $fields['Id'] = $idField;
        }

        $fieldNames = fieldsToNameArray($fields);
        $_SESSION['field_map'] = convertFieldMapToArray($_POST, $fieldNames);
        confirmFieldMappings(
            $confirmAction,
            $_SESSION['field_map'],
            isset($_SESSION['csv_array'])?$_SESSION['csv_array']:null,
            isset($_SESSION['_ext_id'])?$_SESSION['_ext_id']:null
        );
        include_once 'footer.php';
    } else if (isset($_REQUEST['sourceType']) && $_REQUEST['sourceType'] == "singleRecord") {
        require_once 'header.php';
        setFieldMappings($action, false);
        include_once 'footer.php';
    } else if (isset($_REQUEST['sourceType']) && $_REQUEST['sourceType'] == "file" && isset($_FILES['file'])) {
        require_once 'header.php';

        $validationResult = validateUploadedFile($_FILES['file']);
        if ($validationResult === 0) {
            $fileType = resolveFileType($_FILES['file']);
        }
        
        if ($validationResult || ($fileType != "csv" && $fileType != "zip")) {
            displayError($validationResult);
        } else if (requiresObject($action) && $_POST['default_object'] == "") {
            displayError("Must select an object to $action.");
        } else if ($fileType == "csv") {
            $csvFileName = basename($_FILES['file']['name']);
            $_SESSION['file_tmp_name'] = $_FILES['file']['tmp_name'];
            $_SESSION['csv_array'] = convertCsvFileToArray($_SESSION['file_tmp_name']);
            $csvArrayCount = count($_SESSION['csv_array']) - 1;
            if (!$csvArrayCount) {
                displayError("The file uploaded contains no records. Please try again.", false, true);
            } else if ($csvArrayCount > WorkbenchConfig::get()->value("maxFileLengthRows")) {
                displayError ("The file uploaded contains more than " . WorkbenchConfig::get()->value("maxFileLengthRows") . " records. Please try again.", false, true);
            }
            $info = "The file $csvFileName was uploaded successfully and contains $csvArrayCount row";
            if ($csvArrayCount !== 1) $info .= 's';
            displayInfo($info);
            print "<br/>";
            setFieldMappings($action,$_SESSION['csv_array']);
        } else if ($fileType == "zip") {
            if (!supportsBulk($action)) {
                displayError("ZIP-based "  . $action . "s not supported.", false, true);
                exit;                
            }
            
            if (!WorkbenchContext::get()->isApiVersionAtLeast(20.0)) {
                displayError("ZIP-based "  . $action . "s not supported until API 20.0", false, true);
                exit;
            }
            
            $_SESSION['tempZipFile'] = file_get_contents($_FILES['file']['tmp_name']);
            displayInfo(array("Successfully staged " . ceil(($_FILES["file"]["size"] / 1024)) . " KB zip file " . $_FILES["file"]["name"] . " for $action via the Bulk API. ", 
                        "Note, custom field mappings are not available for ZIP-based requests."));
            print "<br/>";
            print "<form method='POST' action=''>" . getCsrfFormTag() .
                  "<div class='instructions'>Choose the options below and confirm the $action:<p/></div>" . 
                  "<table border='0'>"; 
            
            if ($action == 'upsert') {
                print "<tr><td align='right'><label><strong>External Id:</strong> </label></td>" .
                      "<td><select name='_ext_id'>\n";
                foreach (WorkbenchContext::get()->describeSObjects($_POST['default_object'])->fields as $field) {
                    if ($field->idLookup) { 
                        print   " <option value='$field->name'";
                        if($field->name == 'Id') print " selected='true'";
                        print ">$field->name</option>\n";
                    }
                }
                print "</select></td></tr>";
            }
            
            print "<tr><td align='right'><label><strong>Manifest Format:</strong> </label></td>" .
                  "<td><select name='contentType'>\n" . 
                    "<option value='ZIP_CSV'>CSV</option>\n" . 
                    "<option value='ZIP_XML'>XML</option>\n" . 
                 "</select></td></tr>";
            
            print "</table>";
            
            displayBulkApiOptions($confirmAction, true);
    
            print "<br/><p><input type='submit' name='action' value='$confirmAction' /></p>\n";
            print "</form>\n";
        
        } else {
            throw new Exception("Illegal State");
        }
        include_once 'footer.php';
    } else {
        unset($_SESSION['field_map'],$_SESSION['csv_array'],$_SESSION['_ext_id'],$_SESSION['file_tmp_name'],$_SESSION['tempZipFile']);
        displayUploadFileWithObjectSelectionForm($action);
    }
}


//CSV UPLOADING FUNCTIONS

/**
 * Form to upload CSV for all PUT functions
 *
 * @param $action
 */
function displayUploadFileWithObjectSelectionForm($action, $id = null, $warning = null) {
    require_once 'header.php';

    if ($action != "retrieve") {
        print "<p class='instructions'>" .
              "Select " .
              (requiresObject($action) ? "an object and " : "") .
              "  a CSV " .
              (supportsZips($action) ? "or ZIP "  : "") .
              " file containing records to $action, or " . ($action != "insert" ? "provide an id " : "choose ") ." to $action a single record." .
              (supportsZips($action) ? " Zipped requests must contain a CSV or XML-formatted manifest called request.txt, which may reference included binary files."  : "") .
              "</p>\n";
    }

    if ($warning) {
        displayWarning($warning);
        print "<p/>";
    }

    print "<form enctype='multipart/form-data' method='post' action=''>\n" . getCsrfFormTag();

    print "<table>";
    if (requiresObject($action)) {
        $filter1 = null;
        $filter2 = null;
        if($action == "insert") $filter1 = "createable";
        else if($action == "update") $filter1 = "updateable";
        else if ($action == "upsert") {$filter1 = "createable"; $filter2 = "updateable";}
        print "<tr><td>Object Type</td><td>";
        printObjectSelection(WorkbenchContext::get()->getDefaultObject(), 'default_object', "20", null, $filter1, $filter2);
        print "</td></tr>\n<tr><td colspan='2'></td></tr>\n";
    }

    print "<tr><td><label><input type='radio' id='sourceType_singleRecord' name='sourceType' value='singleRecord' checked=checked".
          "/>Single Record</label></td><td>" .
          ($id !== null || $action != "insert" ? "<input type='text' name='id' value='" . htmlspecialchars($id) .
                                                "' size='30' onfocus='document.getElementById(\"sourceType_singleRecord\").checked=true;' />" : "") ."</td></tr>\n";
    
    if ($action !== "retrieve") {
        print "<tr><td style='width: 10em;'><label><input type='radio' id='sourceType_file' name='sourceType' value='file'/>From File</label></td>\n" .
              "<td><input type='file' name='file' size='44' onchange='document.getElementById(\"sourceType_file\").checked=true;' />\n" .
              "<input type='hidden' name='MAX_FILE_SIZE' value='" . WorkbenchConfig::get()->value("maxFileSize") . "' /></td></tr>\n";
    }

    print "<tr><td colspan='2'><br/><input type='submit' name='action' value='Next' /></td></tr>\n";
    print "</table></form>\n";
    
    include_once 'footer.php';
}

/**
 * Determines if the specified file is CSV or ZIP file
 *
 * @param $file
 */
function resolveFileType($file) {
    if (endsWith($file['name'],'.csv', true)) {
        return "csv";
    } else if (endsWith($file['name'],'.zip', true)) {
        return "zip";
    } else {
        throw new WorkbenchHandledException("Unknown file type. Only CSV and ZIP files are allowed.");
    }
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
    $memLimitBytes = toBytes(ini_get("memory_limit"));
    $memWarningThreshold = WorkbenchConfig::get()->value("memoryUsageWarningThreshold") / 100;
    $headerCount = 0;
    for ($row=0; ($data = fgetcsv($handle)) !== FALSE; $row++) {
        if ($memLimitBytes != 0 && (memory_get_usage() / $memLimitBytes > $memWarningThreshold)) {
            displayError("Workbench almost exhausted all its memory after only processing $row rows of data.
            When performing a large data load, it is recommended to use a zipped request for processing with the Bulk API.
            To do so, rename your CSV file to 'request.txt', zip it, and try uploading again to Workbench.", false, true);
            fclose($handle);
            return;
        }

        if ($row == 0) {
            $headerCount = count($data);
        } else {
            $colCount = count($data);
            if ($headerCount != $colCount) {
                fclose($handle);
                throw new WorkbenchHandledException("Invalid CSV file. All rows must have same number of columns.\n" .
                                                    "Header contains " . amt($headerCount, "column") .
                                                    ", but data row $row contains " . amt($colCount, "column") . ".");
            }
        }

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
        print htmlspecialchars($csvArray[0][$col],ENT_QUOTES);
        print "</th>";
    }
    print "</tr>\n";
    for ($row=1; $row < count($csvArray); $row++) {
        print "<tr><td>$row</td>";
        for ($col=0; $col < count($csvArray[0]); $col++) {
            print "<td>";
            if ($csvArray[$row][$col]) {
                print addLinksToIds(htmlspecialchars($csvArray[$row][$col],ENT_QUOTES));
            } else {
                print "&nbsp;";
            }
            print "</td>";
        }
        print "</tr>\n";
    }
    print "</table>\n";
}


function fieldsToNameArray($fields, $includeBase64Fields = true) {
    $fieldNames = array();
    foreach ($fields as $field) {
        if (!$includeBase64Fields && $field->type == "base64") {
            continue;
        }
        
        $fieldNames[] = $field->name;
    }
    return $fieldNames;
}

function queryCurrentRecord($describeSObjectResult, $id) {
    $soql = "SELECT " .
            implode(",", fieldsToNameArray($describeSObjectResult->fields, false)) .
            " FROM " . $describeSObjectResult->name .
            " WHERE Id = '" . $id . "'";

    try {
        $queryResponse = WorkbenchContext::get()->getPartnerConnection()->queryAll($soql);
        if ($queryResponse->size == 1) {
            return new SObject($queryResponse->records[0]);
        }
    } catch (Exception $e) {
        workbenchLog(LOG_DEBUG, "queryCurrentRecord failed", $e->getMessage());
        return null;
    }

    return null;
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

    $currRecord = null;
    $id = isset($_REQUEST['id']) ? trim(htmlspecialchars($_REQUEST['id'], ENT_QUOTES)) : null;

    if (requiresObject($action)) {
        // if an object is not explicitly given, infer from the id, if that was given
        if (!isset($_REQUEST['default_object'])) {
            WorkbenchContext::get()->setDefaultObject(WorkbenchContext::get()->getObjectTypeByKeyPrefixOrId($id));
        }

        if (WorkbenchContext::get()->getDefaultObject()) {
            $objectType = WorkbenchContext::get()->getDefaultObject();
            $describeSObjectResult = WorkbenchContext::get()->describeSObjects($objectType);
            if (!$csvArray && !empty($id)) {
                $currRecord = queryCurrentRecord($describeSObjectResult, $id);
                if ($currRecord == null) {
                    displayUploadFileWithObjectSelectionForm($action, $id,
                                                             "An existing " . WorkbenchContext::get()->getDefaultObject() .
                                                             " could not found with the id '$id'. Confirm both the object type and id are correct.");
                    exit;
                }
            }
        } else {
            if (!$csvArray && !empty($_REQUEST['id'])) {
                displayUploadFileWithObjectSelectionForm($action, $id, "The object type of id '$id' is unknown. Choose an object type and confirm the id is correct.");
            } else {
                displayUploadFileWithObjectSelectionForm($action, $id, "Must choose an object to $action.");
            }
            exit;
        }
    } else if (!$csvArray && $id) {
        $objectType = WorkbenchContext::get()->getObjectTypeByKeyPrefixOrId($id);
        if ($objectType) {
            $describeSObjectResult = WorkbenchContext::get()->describeSObjects($objectType);
            $currRecord = queryCurrentRecord($describeSObjectResult, $id);
        } else {
            $currRecord = new SObject();
            $currRecord->fields = new StdClass();
            $currRecord->fields->Id = $id;
        }
    }

    if ($currRecord != null && isset($describeSObjectResult)) {
        $isDeleted = isset($currRecord->fields->IsDeleted) && $currRecord->fields->IsDeleted == "true";
            $dmlActions = array(
                    "update" => !$isDeleted && $describeSObjectResult->updateable,
                    "delete" => !$isDeleted && $describeSObjectResult->deletable,
                    "undelete" => $isDeleted && $describeSObjectResult->undeletable,
                    "purge" => $isDeleted && $describeSObjectResult->undeletable,

        );
        
        if (isset($dmlActions[$action]) && !$dmlActions[$action]) {
            displayWarning("It does not seem like this record can be " . $action . "d. Are you sure you wish to continue?");
        }
    }

    print "<form method='POST' action=''>" . getCsrfFormTag();

    if ($action == 'upsert') {
        print "<p class='instructions'>Choose the Salesforce field to use as the External Id. Be sure to also map this field below:</p>\n";
        print "<table class='fieldMapping'><tr>\n";
        print "<td style='color: red;'>External Id</td>";
        print "<td><select name='_ext_id' style='width: 100%;'>\n";
        foreach ($describeSObjectResult->fields as $fields => $field) {
            if ($field->idLookup) { //limit the fields to only those with the idLookup property set to true. Corrected Issue #10
                print   " <option value='$field->name'";
                if($field->name == 'Id') print " selected='true'";
                print ">$field->name</option>\n";
            }
        }
        print "</select></td></tr></table>\n";


    } //end if upsert


    if ($action == "retrieve") {
        print "<h3 style='margin:0px;'><a href='describe.php?id=$id' style='text-decoration:none; color:inherit;'>" . $objectType . "</a></h3>";
        print "<h1 style='margin-top:0px;'>" . (isset($currRecord->fields->Name) ? htmlspecialchars($currRecord->fields->Name) : $currRecord->fields->Id). "</h1>";

        foreach ($dmlActions as $dmlAction => $enabled) {
            print "<input type=\"button\" onclick=\"window.location.href='$dmlAction.php?sourceType=singleRecord&id=$id'\" value=\"" . ucfirst($dmlAction) . "\" " . (!$enabled ? "disabled=disabled" : "") . "/>&nbsp;&nbsp;";
        }
        if (WorkbenchConfig::get()->value('linkIdToUi')) {
            $uiViewable = isset($describeSObjectResult->urlDetail) || in_array($objectType, array("Dashboard", "Report", "Division", "BusinessHours", "BrandTemplate"));
            print "<input type=\"button\" onclick=\"window.open('" . getJumpToSfdcUrlPrefix() . "$id')\" value=\"View in Salesforce\" " . ($uiViewable ? "" : "disabled=disabled") ."/>&nbsp;&nbsp;";
        }
        print "<p/>";
    } else {
        if ($csvArray) {
            $instructions = "Map the Salesforce fields to the columns from the uploaded CSV:";
        } else if (requiresObject($action)) {
            $instructions = "Provide values for the " . WorkbenchContext::get()->getDefaultObject() . " fields below:";
        } else {
            $instructions = "Confirm the id to $action below:";
        }

        print "<p class='instructions'>$instructions</p>\n";
    }

    print "<table class='fieldMapping'>\n";
    print "<tr><th>Field</th>";

    if ($csvArray) {
        print "<th>CSV Field</th>";
    } else {
        print "<th>Value</th>";
    }

    if (WorkbenchConfig::get()->value("showReferenceBy") && ($action == 'insert' || $action == 'update' || $action == 'upsert'))
    print "<th onmouseover=\"Tip('For fields that reference other objects, external ids from the foreign objects provided can be automatically matched to their corresponding primary ids. Use this column to select the object and field by which to perform the Smart Lookup. If left unselected, standard lookup using the primary id will be performed. If this field is disabled, only standard lookup is available because the foreign object contains no external ids.')\">Smart Lookup &nbsp; <img align='absmiddle' src='" . getPathToStaticResource('/images/help16.png') . "'/></th>";
    print "</tr>\n";

    if ($action == 'insert') {
        foreach ($describeSObjectResult->fields as $field) {
            if ($field->createable) {
                printPutFieldForMapping($field, $csvArray, true, null);
            }
        }
    }

    if ($action == 'update') {
        printPutFieldForMappingId($csvArray, true, $currRecord);
        foreach ($describeSObjectResult->fields as $field) {
            if ($field->updateable) {
                printPutFieldForMapping($field, $csvArray, true, $currRecord);
            }
        }
    }

    if ($action == 'upsert') {
        printPutFieldForMappingId($csvArray, true, $currRecord);
        foreach ($describeSObjectResult->fields as $field) {
            if ($field->updateable && $field->createable) {
                printPutFieldForMapping($field, $csvArray, true, $currRecord);
            }
        }
    }


    if ($action == 'delete' || $action == 'undelete' || $action == 'purge') {
        printPutFieldForMappingId($csvArray, false, $currRecord);
    }

    if ($action == "retrieve") {
        foreach ($describeSObjectResult->fields as $field) {
            // fix an IIS issue where the id is not in fields
            if (!isset($currRecord->fields->Id) && isset($currRecord->Id)) {
                $currRecord->fields->Id = $currRecord->Id;
            }
            printPutFieldForMapping($field, false, false, $currRecord, false);
        }
    }

    print "</table>\n";

    if ($csvArray) {
        print "<p><input type='submit' name='action' value='Map Fields' />\n";
        print "<input type='button' value='Preview CSV' onClick='window.open(" . '"csv_preview.php"' . ")'></p>\n";
    } else if ($action !== "retrieve") {
        print "<input type='hidden' name='sourceType' value='singleRecord' />\n";
        print "<p><input type='submit' name='action' value='Confirm " . ucwords($action) . "' />\n";
    }


    print "</form>\n";
}

function printPutFieldForMappingId($csvArray, $showRefCol, $currentRecord) {
    $idField = new stdClass();
    $idField->nillable = false;
    $idField->defaultedOnCreate = false;
    $idField->name = "Id";
    $idField->type = "id";
    $idField->label = "Id";
    $idField->length = 18;

    // fix an IIS issue where the id is not in fields
    if (!isset($currentRecord->fields->Id) && isset($currentRecord->Id)) {
        $currentRecord->fields->Id = $currentRecord->Id;
    }

    printPutFieldForMapping($idField, $csvArray, $showRefCol, $currentRecord);
}

/**
 * Prints field mapping setter row
 * for non-Id fields
 *
 * @param $field
 * @param $csvArray
 */
function printPutFieldForMapping($field, $csvArray, $showRefCol, $currentRecord, $editable = true) {
    print "<tr";
    if ($editable && !$field->nillable && !$field->defaultedOnCreate) print " style='color: red;'";
    print "><td style='cursor: pointer;' onmouseover=\"Tip('Type: " . htmlspecialchars($field->type, ENT_QUOTES) .
                                                      " <br/> Length: " .htmlspecialchars($field->length, ENT_QUOTES) . "')\">" .
          htmlspecialchars($field->name, ENT_QUOTES) . ($currentRecord && $field->type == "base64" ? "&nbsp;<em style='color:grey'>(current value not retrieved)</em>" : "") . "</td>";

    if ($csvArray) {
        print "<td><select name='$field->name' style='width: 100%;'>";
        print "    <option value=''></option>\n";
        foreach ($csvArray[0] as $col) {
            print   "<option value='$col'";
            if (strtolower($col) == strtolower($field->name)) print " selected='true' ";
            print ">$col</option>\n";
        }
        print "</select></td>";
    } else {
        $fieldName = htmlspecialchars($field->name, ENT_QUOTES);
        $fieldValue = "";
        if ($currentRecord != null && isset($currentRecord->fields->$fieldName)) {
            $fieldValue = htmlspecialchars($currentRecord->fields->$fieldName);
        }
        print "<td>" . ($editable
                ? "<input name='$field->name' style='width: 97%;' value=\"$fieldValue\"/>"
                : (($field->type == 'datetime') ? localizeDateTimes($fieldValue) : addLinksToIds($fieldValue))) .
              "</td>";
    }

    if ($showRefCol && WorkbenchConfig::get()->value("showReferenceBy")) {
        if (isset($field->referenceTo) && isset($field->relationshipName)) {
            $describeRefObjResult = WorkbenchContext::get()->describeSObjects($field->referenceTo);
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
 */
function convertFieldMapToArray($fieldMap, $fieldNames) {
    $fieldMapArray = array();

    foreach ($fieldMap as $fieldMapKey=>$fieldMapValue) {
        if (!fieldMapKeyMatchesFields($fieldMapKey, $fieldNames)) {
            continue;
        }

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

function fieldMapKeyMatchesFields($fieldMapKey, $fieldNames) {
    // this should be faster for normal fields, so try this first
    if (in_array($fieldMapKey, $fieldNames)){
        return true;
    }

    // now iterate for SmartLookup mappings
    foreach ($fieldNames as $fieldName) {
        if (strpos($fieldMapKey, $fieldName) === 0) {
            return true;
        }
    }

    return false;
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
function confirmFieldMappings($action, $fieldMap, $csvArray, $extId) {
    if (!($fieldMap && $csvArray)) {
        displayError("CSV file and field mapping not initialized successfully. Upload a new file and map fields.",true,true);
        exit;
    } 

    $recommendDoAsync = extension_loaded('curl')
                        && count($csvArray) >= WorkbenchConfig::get()->value("asyncRecommendationThreshold")
                        && !in_array($action, array('Confirm Undelete', 'Confirm Purge'));

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
            displayInfo ("The file uploaded contains $idCount records with Salesforce IDs with the field mapping below." .
                         ($recommendDoAsync ? " Due to the number of records being processed, it is recommended to use the Bulk API." : "")
                        );
            print "<p class='instructions'>Confirm the mappings below:</p>";
            print "<p>$fieldMappingTable</p>";
        }
    } else {
        $recordCount = count($csvArray) - 1;
        displayInfo ("The file uploaded contains $recordCount records to be added to " . WorkbenchContext::get()->getDefaultObject());
        print "<p class='instructions'>Confirm the mappings below:</p>";
        displayFieldMappings($fieldMap, $extId, true);
    }

    print "<form method='POST' action=''>" . getCsrfFormTag();
    displayBulkApiOptions($action, false, $recommendDoAsync);
    print "<p>&nbsp;</p><p><input type='submit' name='action' value='$action' /></p>\n";
    print "</form>\n";
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
    if ($showRefCol && WorkbenchConfig::get()->value("showReferenceBy")) print "<th>Smart Lookup</th>";
    print "</tr>\n";

    foreach ($fieldMap as $salesforceField=>$fieldMapArray) {
        print "<tr><td>$salesforceField</td>";

        if (!isset($fieldMapArray['csvField'])) {
            throw new Exception("Unknown CSV field mapping to Salesforce field: " . $salesforceField);
        }

        print "<td>" . $fieldMapArray['csvField'] . "</td>";
        if ($showRefCol && WorkbenchConfig::get()->value("showReferenceBy")) {
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
        $idCol = array_search($fieldMap['Id']['csvField'],$csvArray[0]);

        for ($row=1; $row < count($csvArray); $row++) {
            if ($csvArray[$row][$idCol]) {
                $idArray[] = $csvArray[$row][$idCol];
            }
        }

        $results = array();
        $idArrayAll = $idArray;

        while($idArray) {
            $idArrayBatch = array_splice($idArray,0,WorkbenchConfig::get()->value("batchSize"));
            try {
                if($apiCall == 'purge') $apiCall = 'emptyRecycleBin';
                $resultsMore = WorkbenchContext::get()->getPartnerConnection()->$apiCall($idArrayBatch);

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
    if (!($fieldMap && $csvArray && WorkbenchContext::get()->getDefaultObject())) {
        displayError("CSV file and field mapping not initialized. Upload a new file and map fields.",true,true);
    } else {
        $csvHeader = array_shift($csvArray);
        $results = array();

        while($csvArray) {
            $sObjects = array();
            $csvArrayBatch = array_splice($csvArray,0,WorkbenchConfig::get()->value("batchSize"));

            for ($row=0; $row < count($csvArrayBatch); $row++) {
                $sObject = new SObject;
                $sObject->type = WorkbenchContext::get()->getDefaultObject();
                if(WorkbenchConfig::get()->value("fieldsToNull")) $sObject->fieldsToNull = array();
                $fields = array();

                foreach ($fieldMap as $salesforceField=>$fieldMapArray) {
                    if (isset($fieldMapArray['relatedObjectName']) && isset($fieldMapArray['relatedFieldName']) && isset($fieldMapArray['csvField'])) {
                        $refSObject = new SObject;
                        $refSObject->type = $fieldMapArray['relatedObjectName'];
                        $col = array_search($fieldMapArray['csvField'],$csvHeader);
                        if ($csvArrayBatch[$row][$col] != "") {
                            $refSObject->fields = array($fieldMapArray['relatedFieldName'] => htmlspecialchars($csvArrayBatch[$row][$col],ENT_QUOTES));
                        }
                        $field = array($fieldMapArray['relationshipName'] => $refSObject);
                    } else if (isset($salesforceField) && isset($fieldMapArray['csvField'])) {
                        $col = array_search($fieldMapArray['csvField'],$csvHeader);
                        if ($csvArrayBatch[$row][$col] != "") {
                            $field = array($salesforceField => htmlspecialchars($csvArrayBatch[$row][$col],ENT_QUOTES));
                        } else if (WorkbenchConfig::get()->value("fieldsToNull")) {
                            $sObject->fieldsToNull[] = $salesforceField;
                        }
                    }

                    if (isset($field)) {
                        if (!$fields) {
                            $fields = $field;
                        } else {
                            $fields = array_merge($fields,$field);
                        }
                    }
                }

                $sObject->fields = $fields;
                array_push($sObjects, $sObject);
                unset($sObject);
            }


            try {
                if ($apiCall == 'upsert') {
                    $resultsMore = WorkbenchContext::get()->getPartnerConnection()->$apiCall($extId,$sObjects);
                } else {
                    $resultsMore = WorkbenchContext::get()->getPartnerConnection()->$apiCall($sObjects);
                }
                unset($sObjects);
            } catch (Exception $e) {
                $errors = null;
                $errors = $e->getMessage();
                displayError($errors);
                include_once("footer.php");
                exit;
            }

            if (isset($resultsMore) && !is_array($resultsMore)) {
                $resultsMore = array($resultsMore);
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
function putAsync($apiCall, $extId, $fieldMap, $csvArray, $zipFile, $contentType) {    
    $doingZip = isset($zipFile);
    
    if (!$doingZip && !($fieldMap && $csvArray && WorkbenchContext::get()->getDefaultObject())) {
        displayError("CSV file and field mapping not initialized or object not selected. Upload a new file and map fields.",true,true);
    } else {
        try {
            $job = new JobInfo();
            $job->setObject(WorkbenchContext::get()->getDefaultObject());
            $job->setOpertion($apiCall);
            $job->setContentType(isset($contentType) ? $contentType : ($doingZip ? "ZIP_CSV" : "CSV"));
            $job->setConcurrencyMode(WorkbenchConfig::get()->value("asyncConcurrencyMode"));
            if(WorkbenchConfig::get()->value("assignmentRuleHeader_assignmentRuleId")) $job->setAssignmentRuleId(WorkbenchConfig::get()->value("assignmentRuleHeader_assignmentRuleId"));
            if($apiCall == "upsert" && isset($extId)) $job->setExternalIdFieldName($extId);

            $job = WorkbenchContext::get()->getAsyncBulkConnection()->createJob($job);
        } catch (Exception $e) {
            displayError($e->getMessage(), true, true);
        }

        if ($job->getId() == null) {
            displayError("No job id found. Aborting Bulk API operation.", true, true);
        }

        if ($doingZip) {
            try {
               WorkbenchContext::get()->getAsyncBulkConnection()->createBatch($job, $zipFile);
            } catch (Exception $e) {
                displayError($e->getMessage(), true, true);
            }
        } else {
            $csvHeader = array_shift($csvArray);
            $results = array();
    
            while($csvArray) {
                $sObjects = array();
                $csvArrayBatch = array_splice($csvArray,0,WorkbenchConfig::get()->value("asyncBatchSize"));
    
                $asyncCsv = array();
    
                $asyncCsvHeaderRow = array();
                foreach ($fieldMap as $salesforceField=>$fieldMapArray) {
                    if (isset($fieldMapArray['csvField'])) {
                        if (isset($fieldMapArray['relationshipName']) && isset($fieldMapArray['relatedFieldName'])) {
                            $asyncCsvHeaderRow[] = ($fieldMapArray['isPolymorphic'] ? ($fieldMapArray['relatedObjectName'] . ":") : "") .
                            $fieldMapArray['relationshipName'] . "." .
                            $fieldMapArray['relatedFieldName'];
                        } else if (isset($salesforceField)) {
                            $asyncCsvHeaderRow[] = $salesforceField;
                        }
                    }
                }
                $asyncCsv[] = $asyncCsvHeaderRow;
    
                for ($row=0; $row < count($csvArrayBatch); $row++) {
                    //create new row
                    $asyncCsvRow = array();
                    foreach ($fieldMap as $salesforceField=>$fieldMapArray) {
                        if (isset($fieldMapArray['csvField'])) {
                            $col = array_search($fieldMapArray['csvField'],$csvHeader);
                            if (isset($salesforceField) && isset($fieldMapArray['csvField'])) {
                                if ($csvArrayBatch[$row][$col] == "" && WorkbenchConfig::get()->value("fieldsToNull")) {
                                    $asyncCsvRow[] = "#N/A";
                                } else {
                                    $asyncCsvRow[] = $csvArrayBatch[$row][$col];
                                }
                            }
                        }
                    }
    
                    //add row to the array
                    $asyncCsv[] = $asyncCsvRow;
                }
    
                try {
                    WorkbenchContext::get()->getAsyncBulkConnection()->createBatch($job, convertArrayToCsv($asyncCsv));
                } catch (Exception $e) {
                    displayError($e->getMessage(), true, true);
                }
            }
        }

        try {
            $job = WorkbenchContext::get()->getAsyncBulkConnection()->updateJobState($job->getId(), "Closed");
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
            print "<td>" . addLinksToIds($results[$row]->id) . "</td>";
            $_SESSION['resultsWithData'][$row+1][0] = $results[$row]->id;
            print "<td>Success</td>";
            $_SESSION['resultsWithData'][$row+1][1] = "Success";
            if (($apiCall == 'upsert' && $results[$row]->created) || $apiCall == 'create') {
                print "<td>Created</td>";
                $_SESSION['resultsWithData'][$row+1][2] = "Created";
            } else if (($apiCall == 'upsert' && !$results[$row]->created) || $apiCall == 'update') {
                print "<td>Updated</td>";
                $_SESSION['resultsWithData'][$row+1][2] = "Updated";
            } else if (($apiCall == 'delete') || ($apiCall == 'undelete')) {
                print "<td>" . ucwords($apiCall) . "d </td>";
                $_SESSION['resultsWithData'][$row+1][2] = ucwords($apiCall) . "d";
            } else if ($apiCall == 'emptyRecycleBin') {
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
                print "<td>" . addLinksToIds(htmlspecialchars($idArray[$row])) . "</td>";
            } else {
                $_SESSION['resultsWithData'][$row+1][0] = $results[$row]->id; //add id from results for everything else
                print "<td>" . addLinksToIds(htmlspecialchars($results[$row]->id)) . "</td>";
            }

            $errMsgs = "";
            $statusCodes = "";
            if (is_array($results[$row]->errors)) {
                $errMsgs = implode("; ", array_map("extractMessage", $results[$row]->errors));
                $statusCodes = implode("; ", array_map("extractStatusCode", $results[$row]->errors));
            } else {
                $errMsgs .= $results[$row]->errors->message;
                $statusCodes .= $results[$row]->errors->statusCode;
            }

            print "<td>" . ucwords($errMsgs) . "</td>";
            $_SESSION['resultsWithData'][$row+1][1] = ucwords($errMsgs);

            print "<td>" . $statusCodes . "</td>";
            $_SESSION['resultsWithData'][$row+1][2] = $statusCodes;

            print "</tr>\n";
        }

        $_SESSION['resultsWithData'][$row+1] = array_merge($_SESSION['resultsWithData'][$row+1],$csvArray[$row+1]);

    }
    print "</table><br/>";
    $resultsTable = ob_get_clean();
    displayInfo("There " . ($successCount == 1 ? "was" : "were") . " $successCount success" . ($successCount == 1 ? "" : "es") . " and $errorCount error" . ($errorCount == 1 ? "" : "s"));

    print "<br/><form action='downloadResultsWithData.php' method='GET'><input type='hidden' name='action' value='$apiCall'/><input type='submit' value='Download Full Results'/></form>";

    print "<br/>\n<table class='dataTable'>\n";
    print "<th>&nbsp;</th> <th style='width: 30%'>Salesforce Id</th> <th style='width: 30%'>Result</th> <th style='width: 35%'>Status</th>\n";
    print "<p>$resultsTable</p>";
}

function extractMessage($e) {
    return $e->message;
}

function extractStatusCode($e) {
    return $e->statusCode;
}

function supportsBulk($action) {
    return in_array($action, array("insert", "update", "upsert", "delete"));    
}

function requiresObject($action) {
    return in_array($action, array("insert", "update", "upsert", "retrieve"));
}

function supportsZips($action) {
    return supportsBulk($action) && WorkbenchContext::get()->isApiVersionAtLeast(20.0);
}

function displayBulkApiOptions($action, $forceDoAsync, $recommendDoAsync = false) {
    //Hard Delete option
    if (WorkbenchContext::get()->isApiVersionAtLeast(19.0) && $action == 'Confirm Delete') {
        print "<p><label><input type='checkbox' id='doHardDelete' name='doHardDelete' onClick=\"".
              "if (this.checked && " . ($forceDoAsync ? "false" : "true") . ") {" .
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
              "align='absmiddle' src='" . getPathToStaticResource('/images/help16.png') . "'/>" .
              "</p>";
    }

    //Async Options
    if((WorkbenchContext::get()->isApiVersionAtLeast(17.0) && in_array($action, array('Confirm Insert', 'Confirm Update', 'Confirm Upsert')))
       || (WorkbenchContext::get()->isApiVersionAtLeast(18.0) && $action == 'Confirm Delete')) {
        if ($forceDoAsync) {
            print "<input name='doAsync' type='hidden' value='true'/>";
        } else {
           print "<p><label><input id='doAsync' name='doAsync' type='checkbox' " .
               ($recommendDoAsync ? "checked='checked' " : "") . 
               "onClick=\"".
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
              "align='absmiddle' src='" . getPathToStaticResource('/images/help16.png') . "'/>" .
              "</p>";
        }

        // object selection for Bulk API Delete
        if ($action == 'Confirm Delete') {
            print "<div id='asyncDeleteObjectSelection' style='display: " .
                   ($forceDoAsync || $recommendDoAsync ? "inline" : "none; margin-left: 3em;") . 
                   "'>Object Type: ";
            printObjectSelection(WorkbenchContext::get()->getDefaultObject());
            print "</div>";
        }

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
            if (WorkbenchConfig::get()->overridden($c)) {
                $bulkUnsupportedSettings[] = $c;
            }
        }
        
        // print out a warning if any settings were found
        if (count($bulkUnsupportedSettings) > 0) {
            print "<div id='unsupportedBulkConfigList' style='display: " . ($forceDoAsync || $recommendDoAsync ? "inline" : "none") . "; color: orange;'>" .
                      "<p " . ($forceDoAsync ? "" : "style='margin-left: 3em;'") . ">" .  
                          "<img src='" . getPathToStaticResource('/images/warning24.png') . "' /> " .
                          "The following settings are not supported by the Bulk API and will be ignored:" . 
                          "<ul " . ($forceDoAsync ? "" : "style='margin-left: 5em;'") . ">";
            
            foreach ($bulkUnsupportedSettings as $s) {
                print "<li>" . WorkbenchConfig::get()->label($s) . "</li>";
            }
            
            print "</ul>" . 
                  "</p>" . 
                  "</div>";
        } 
    }
}
?>