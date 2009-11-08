<?php

// ALL PUT FUNCTIONS:
// INSERT, UPDATE, UPSERT, DELETE, UNDELETE, AND PURGE


/**
 * Main logic and control flow for all PUT funcions
 * @param unknown_type $action
 */
function put($action){
	$confirm_action = 'Confirm ' . ucwords($action);

	if(isset($_POST['action']) && $_POST['action'] == $confirm_action){
		if ($action == 'upsert' && isset($_SESSION['_ext_id'])) $ext_id = $_SESSION['_ext_id']; else $ext_id = NULL;
		if(isset($_POST['doAsync'])){
			putSObjectsAsync(
				$action,
				$ext_id,
				isset($_SESSION['field_map']) ? $_SESSION['field_map'] : null, 
				isset($_SESSION['csv_array']) ? $_SESSION['csv_array'] :  null);
		} else {
			require_once('header.php');
			print "<h1>" . ucwords($action) . " Results</h1>";
			if ($action == 'insert') $api_call = 'create'; else $api_call = $action;
			putSObjects(
				$api_call,
				$ext_id,
				isset($_SESSION['field_map']) ? $_SESSION['field_map'] : null, 
				isset($_SESSION['csv_array']) ? $_SESSION['csv_array'] :  null,
				true);
			include_once('footer.php');
		}
		unset($_SESSION['field_map'],$_SESSION['csv_array'],$_SESSION['_ext_id'],$_SESSION['file_tmp_name']);
	}

	elseif(isset($_POST['action']) && $_POST['action'] == 'Map Fields'){
		require_once('header.php');
		array_pop($_POST); //remove header row
		if (isset($_POST['_ext_id'])){
			$_SESSION['_ext_id'] = $_POST['_ext_id'];
			$_POST['_ext_id'] = NULL;
		}
		$_SESSION['field_map'] = field_map_to_array($_POST);
		field_mapping_confirm(
			$confirm_action,
			$_SESSION['field_map'],
			isset($_SESSION['csv_array'])?$_SESSION['csv_array']:null, 
			isset($_SESSION['_ext_id'])?$_SESSION['_ext_id']:null
		);
		include_once('footer.php');
	}

	elseif (isset($_FILES['file']) && isset($_POST['default_object'])){
		require_once('header.php');
		if (csv_upload_valid_check($_FILES['file'])){
			form_upload_objectSelect_show('file',TRUE);
			show_error(csv_upload_valid_check($_FILES['file']));
		} else {
			$csv_file_name = basename($_FILES['file']['name']);
			$_SESSION['file_tmp_name'] = $_FILES['file']['tmp_name'];
			$_SESSION['csv_array'] = csv_file_to_array($_SESSION['file_tmp_name']);
			$csv_array_count = count($_SESSION['csv_array']) - 1;
			if (!$csv_array_count) {
				show_error("The file uploaded contains no records. Please try again.",false,true);
			} elseif($csv_array_count > $_SESSION['config']['maxFileLengthRows']){
				show_error ("The file uploaded contains more than " . $_SESSION['config']['maxFileLengthRows'] . " records. Please try again.", false, true);
			}
			$info = "The file $csv_file_name was uploaded successfully and contains $csv_array_count row";
			if ($csv_array_count !== 1) $info .= 's';
			show_info($info);
			print "<br/>";
			field_mapping_set($action,$_SESSION['csv_array']);
		}
		include_once('footer.php');
	}

	else {
		require_once ('header.php');
		print "<p><strong>Select an object and upload a CSV file to $action:</strong></p>\n";
		form_upload_objectSelect_show('file',TRUE,$action);
		include_once('footer.php');
	}
}

//CSV UPLOADING FUNCTIONS

/**
 * Form to upload CSV for all PUT functions
 *
 * @param $file_input_name
 * @param $showObjectSelect
 * @param $action
 */
function form_upload_objectSelect_show($file_input_name,$showObjectSelect = FALSE,$action=null){
	print "<form enctype='multipart/form-data' method='post' action='" . $_SERVER['PHP_SELF'] . "'>\n";
	print "<input type='hidden' name='MAX_FILE_SIZE' value='" . $_SESSION['config']['maxFileSize'] . "' />\n";
	print "<p><input type='file' name='$file_input_name' size=44 /></p>\n";
	if ($showObjectSelect){
		$filter1 = null;
		$filter2 = null;
		if($action == "insert") $filter1 = "createable";
		elseif($action == "update") $filter1 = "updateable";
		elseif($action == "upsert") {$filter1 = "createable"; $filter2 = "updateable";}
		
		 myGlobalSelect($_SESSION['default_object'], 'default_object', "20", null, $filter1, $filter2);
		 
		 $submitLabel = 'Upload & Select Object';
	} else {
		$submitLabel = 'Upload';
	}
	print "<p><input type='submit' name='action' value='$submitLabel' /></p>\n";
	print "</form>\n";
}

/**
 * Make sure the uploaded CSV is valid
 *
 * @param csvfile $file
 * @return error codes or 0 if ok
 */
function csv_upload_valid_check($file){
	if($file['error'] !== 0){
		$upload_error_codes = array(
		       0=>"There is no error, the file uploaded with success",
		       1=>"The file uploaded is too large. Please try again. (Error 1)", //as per PHP config
		       2=>"The file uploaded is too large. Please try again. (Error 2)", //as per form config
		       3=>"The file uploaded was only partially uploaded.  Please try again. (Error 3)",
		       4=>"No file was uploaded.  Please try again. (Error 4)",
		       6=>"Missing a temporary folder.  Please try again. (Error 6)",
		       7=>"Failed to write file to disk.  Please try again. (Error 7)",
		       8=>"File upload stopped by extension.  Please try again. (Error 8)"
			);
			
			if($_SESSION['config']['maxFileSize']['overrideable']){
				$upload_error_codes[2] = "The file uploaded is too large. Please try again or adjust in Settings. (Error 2)";
			}
			
		return($upload_error_codes[$file['error']]);
	}

	elseif(!is_uploaded_file($file['tmp_name'])){
		return("The file was not uploaded from your computer. Please try again.");
	}

	elseif((!stristr($file['type'],'csv') || $file['type'] !== "application//vnd.ms-excel") && !stristr($file['name'],'.csv')){
		return("The file uploaded is not a valid CSV file. Please try again.");
	}

	elseif($file['size'] == 0){
		return("The file uploaded contains no data. Please try again.");
	}

	else{
		return(0);
	}
}

/**
 * Read a CSV file and return a PHP array
 *
 * @param csv $file
 * @return PHP array
 */
function csv_file_to_array($file){
	$csv_array = array();
	$handle = fopen($file, "r");
	for ($row=0; ($data = fgetcsv($handle)) !== FALSE; $row++) {
	   for ($col=0; $col < count($data); $col++) {
	       $csv_array[$row][$col] = $data[$col];
	   }
	}
	fclose($handle);

	if ($csv_array !== NULL){
		return($csv_array);
	} else {
		show_error("There were errors parsing the CSV file. Please try again.", false, true);
	}
}

/**
 * Prints CSV array to screen
 *
 * @param $csv_array
 */
function csv_array_show($csv_array){
	print "<table class='data_table'>\n";
	print "<tr><th>&nbsp;</th>";
		for($col=0; $col < count($csv_array[0]); $col++){
			print "<th>";
			print htmlspecialchars($csv_array[0][$col],ENT_QUOTES,'UTF-8');
			print "</th>";
		}
	print "</tr>\n";
	for($row=1; $row < count($csv_array); $row++){
		print "<tr><td>$row</td>";
		for($col=0; $col < count($csv_array[0]); $col++){
			print "<td>";
			if ($csv_array[$row][$col]){
				print addLinksToUiForIds(htmlspecialchars($csv_array[$row][$col],ENT_QUOTES,'UTF-8'));
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
 * @param $csv_array
 */
function field_mapping_set($action,$csv_array){
	if ($action == 'insert' || $action == 'upsert' || $action == 'update'){
		if (isset($_SESSION['default_object'])){
			$describeSObject_result = describeSObject($_SESSION['default_object']);
		} else {
		show_error("A default object is required to $action. Go to the Select page to choose a default object and try again.");
	}
	}

	print "<form method='POST' action='" . $_SERVER['PHP_SELF'] . "'>";

	if ($action == 'upsert'){
		print "<p><strong>Choose the Salesforce field to use as the External Id. Be sure to also map this field below:</strong></p>\n";
		print "<table class='field_mapping'><tr>\n";
		print "<td style='color: red;'>External Id</td>";
		print "<td><select name='_ext_id' style='width: 100%;'>\n";
//		print "	<option value=''></option>\n";
		foreach($describeSObject_result->fields as $fields => $field){
			if($field->idLookup){ //limit the fields to only those with the idLookup property set to true. Corrected Issue #10
				print   " <option value='$field->name'";
				if($field->name == 'Id') print " selected='true'";
				print ">$field->name</option>\n";
			}
		}
		print "</select></td></tr></table>\n";


	} //end if upsert

	print "<p><strong>Map the Salesforce fields to the columns from the uploaded CSV:</strong></p>\n";
	print "<table class='field_mapping'>\n";
	print "<tr><th>Salesforce Field</th>";
	print "<th>CSV Field</th>";
	if ($_SESSION['config']['showReferenceBy'] && ($action == 'insert' || $action == 'update' || $action == 'upsert'))
		print "<th onmouseover=\"Tip('For fields that reference other objects, external ids from the foreign objects provided in the CSV file and can be automatically matched to their cooresponding primary ids. Use this column to select the object and field by which to perform the Smart Lookup. If left unselected, standard lookup using the primary id will be performed. If this field is disabled, only stardard lookup is available because the foreign object contains no external ids.')\">Smart Lookup &nbsp; <img align='absmiddle' src='images/help16.png'/></th>";
	print "</tr>\n";

	if ($action == 'insert'){
		foreach($describeSObject_result->fields as $fields => $field){
			if ($field->createable){
				printPutFieldForMapping($field, $csv_array);
			}
		}
	}

	if ($action == 'update'){
		field_mapping_idOnly_set($csv_array, true);
		foreach($describeSObject_result->fields as $fields => $field){
			if ($field->updateable){
				printPutFieldForMapping($field, $csv_array);
			}
		}
	}

	if ($action == 'upsert'){
		field_mapping_idOnly_set($csv_array, true);
		foreach($describeSObject_result->fields as $fields => $field){
			if ($field->updateable && $field->createable){
				printPutFieldForMapping($field, $csv_array);
			}
		}
	}


	if ($action == 'delete' || $action == 'undelete' || $action == 'purge'){
		field_mapping_idOnly_set($csv_array, false);
	}


	print "</table>\n";
	print "<p><input type='submit' name='action' value='Map Fields' />\n";
	print "<input type='button' value='Preview CSV' onClick='window.open(" . '"csv_preview.php"' . ")'></p>\n";
	print "</form>\n";
}

/**
 * Print field mapping for the Id field only.
 * Used for id-only PUT functions, and also for 
 * the Id field for other PUT functions.
 *
 * @param $csv_array
 * @param boolean $showRefCol
 */
function field_mapping_idOnly_set($csv_array, $showRefCol){
	print "<tr style='color: red;'><td>Id</td>";
	print "<td><select name='Id' style='width: 100%;'>";
	print "	<option value=''></option>\n";
	foreach($csv_array[0] as $col){
		print   "<option value='$col'";
		if (strtolower($col) == 'id') print " selected='true' ";
		print ">$col</option>\n";
	}
	print "</select></td>";
	if ($showRefCol && $_SESSION['config']['showReferenceBy']) print "<td></td>";
	print"</tr>\n";
}

/**
 * Prints field mapping setter row
 * for non-Id fields
 *
 * @param $field
 * @param $csv_array
 */
function printPutFieldForMapping($field, $csv_array){
		print "<tr";
		if (!$field->nillable && !$field->defaultedOnCreate) print " style='color: red;'";
		print "><td>$field->name</td>";

		print "<td><select name='$field->name' style='width: 100%;'>";
		print "	<option value=''></option>\n";
		foreach($csv_array[0] as $col){
			print   "<option value='$col'";
			if (strtolower($col) == strtolower($field->name)) print " selected='true' ";
			print ">$col</option>\n";
		}
		print "</select></td>";

		if($_SESSION['config']['showReferenceBy']){
			if(isset($field->referenceTo) && isset($field->relationshipName)){
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
function printRefField($field, $describeRefObjResult){
	if(is_array($describeRefObjResult)){
		$polyExtFields = array();
		foreach($describeRefObjResult as $describeRefObjResultKey => $describeRefObjResult){
			$extFields = null;
			if(isset($describeRefObjResult->fields)){
				foreach($describeRefObjResult->fields as $extFieldKey => $extFieldVal){
					if($extFieldVal->idLookup == true){
						$extFields[$extFieldKey] = $extFieldVal;
					}
				}
				$polyExtFields[$describeRefObjResult->name] = $extFields;
			}
		}

		//check if the new array has any fields
		print "<td><select name='$field->name:$field->relationshipName' style='width: 100%;'";

		$numOfExtFields = 0;
		foreach($polyExtFields as $extFields){
			if(count($extFields) > 1){
				$numOfExtFields = $numOfExtFields + count($extFields) - 1;
			}
		}

		if($numOfExtFields <= 0){
			print " disabled='true' ";
		}
		print ">\n";

		print  " <option value='Id' selected='true'></option>\n";
		foreach($polyExtFields as $objectType => $extFields){
			if(count($extFields) > 0){
				foreach($extFields as $extFieldKey => $extFieldVal){
					if ($extFieldVal->name != 'Id'){
						print  " <option value='$field->name.$field->relationshipName.$objectType.$extFieldVal->name'>$objectType.$extFieldVal->name</option>\n";
					}
				}
			}
		}
		print "</select></td>\n";

	} else { //for scalar values
		//check to see if there are any IdLookup fields and if so move them to a new array
		$extFields = null;
		if(count($describeRefObjResult->fields) > 0){
			foreach($describeRefObjResult->fields as $extFieldKey => $extFieldVal){
				if($extFieldVal->idLookup == true){
					$extFields[$extFieldKey] = $extFieldVal;
				}
			}
		}


		//check if the new array has any fields and if so
		if(count($extFields) > 0){
			print "<td><select name='$field->name:$field->relationshipName' style='width: 100%;'";
			if (count($extFields) == 1) print " disabled='true' "; //disable the selection if only one choice ('Id') is available
			print ">\n";

			print  " <option value='Id' selected='true'></option>\n";
			foreach($extFields as $extFieldKey => $extFieldVal){
				if ($extFieldVal->name != 'Id'){
					print  " <option value='$field->name.$field->relationshipName.$describeRefObjResult->name.$extFieldVal->name'>$describeRefObjResult->name.$extFieldVal->name</option>\n";
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
 * @param unknown_type $field_map
 * @return unknown
 */
function field_map_to_array($field_map){
	$field_map_array = array();

	foreach($field_map as $fieldMapKey=>$fieldMapValue){
		if(preg_match('/^(\w+):(\w+)$/',$fieldMapKey,$keyMatches)){
			if(preg_match('/^(\w+).(\w+).(\w+).(\w+)$/',$fieldMapValue,$valueMatches)){
				$field_map_array[$valueMatches[1]]["relationshipName"] = $valueMatches[2];
				$field_map_array[$valueMatches[1]]["relatedObjectName"] = $valueMatches[3];
				$field_map_array[$valueMatches[1]]["relatedFieldName"] = $valueMatches[4];
			}
		} else if ($fieldMapValue){
			$field_map_array[$fieldMapKey]["csvField"] = $fieldMapValue;
		}
	}

	return $field_map_array;
}

/**
 * Display the screen for field mapping user set for confirmation.
 * Also allows user to choose to use Bulk API to 
 * do an async PUT opertion
 *
 * @param $action
 * @param $field_map
 * @param $csv_array
 * @param $ext_id
 */
function field_mapping_confirm($action,$field_map,$csv_array,$ext_id){
	if (!($field_map && $csv_array)){
		show_error("CSV file and field mapping not initialized successfully. Upload a new file and map fields.");
	} else {

	if (($action == 'Confirm Update') || ($action == 'Confirm Delete') || ($action == 'Confirm Undelete') || ($action == 'Confirm Purge')){
		if (!isset($field_map['Id'])){
			show_error("Salesforce ID not selected. Please try again.",false,true);
		} else {
		ob_start();
		
		if(($action == 'Confirm Delete') || ($action == 'Confirm Undelete') || ($action == 'Confirm Purge')){
			field_mapping_show($field_map, null, false);
		} else {
			field_mapping_show($field_map, null, true);
		}
		
		$id_col = array_search($field_map['Id'],$csv_array[0]);
		for($row=1,$id_count = 0; $row < count($csv_array); $row++){
			if ($csv_array[$row][$id_col]){
				$id_count++;
			}
		}
		$field_mapping_table = ob_get_clean();
		show_info ("The file uploaded contains $id_count records with Salesforce IDs with the field mapping below.");
		print "<p><strong>Confirm the mappings below:</strong></p>";
		print "<p>$field_mapping_table</p>";
		}
	} else {
		$record_count = count($csv_array) - 1;
		show_info ("The file uploaded contains $record_count records to be added to " . $_SESSION['default_object']);
		print "<p><strong>Confirm the mappings below:</strong></p>";
		field_mapping_show($field_map, $ext_id, true);
	}

	print "<form method='POST' action='" . $_SERVER['PHP_SELF'] . "'>";
	
	if(($action == 'Confirm Insert') || ($action == 'Confirm Update') || ($action == 'Confirm Upsert')){
		print "<p><label><input type='checkbox' name='doAsync'/> Load records asynchronously via Bulk API</label>" .
		  "&nbsp;<img onmouseover=\"Tip('Loading records asynchronously is recommended for large data loads. The data will be uploaded to Salesforce via the Bulk API in batches and processed when server resources are available. After batches have completed, results can be downloaded. Batch size and concurrency options are available in Settings.')\" align='absmiddle' src='images/help16.png'/>" . 
		  "</p><p>&nbsp;</p>";
	}
	
	print "<p><input type='submit' name='action' value='$action' /></p>\n";
	print "</form>\n";
	}
}

/**
 * Display the field mapping for confirmation
 * Only used for non-Id PUT functions.
 *
 * @param unknown_type $field_map
 * @param unknown_type $ext_id
 */
function field_mapping_show($field_map,$ext_id,$showRefCol){
	if ($ext_id){
		print "<table class='field_mapping'>\n";
		print "<tr><td>External Id</td> <td>$ext_id</td></tr>\n";
		print "</table><p/>\n";
	}

	print "<table class='field_mapping'>\n";
	print "<tr><th>Salesforce Field</th>";
	print "<th>CSV Field</th>";
	if ($showRefCol && $_SESSION['config']['showReferenceBy']) print "<th>Smart Lookup</th>";
	print "</tr>\n";

	foreach($field_map as $salesforce_field=>$fieldMapArray){
		print "<tr><td>$salesforce_field</td>";
		print "<td>" . $fieldMapArray['csvField'] . "</td>";
		if ($showRefCol && $_SESSION['config']['showReferenceBy']){
			print "<td>";
			if (isset($fieldMapArray['relatedObjectName']) && isset($fieldMapArray['relatedFieldName'])){
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
 * Display wrapping screen for doing id-only PUT functions
 * after user has already uploaded the file
 * and confirmed field mappings. This function
 * calls idOnlyCallIds that does the actual work.
 *
 * @param $action
 */
function idOnlyCall($action){
	if(isset($_POST['action']) && $_POST['action'] == 'Confirm ' . ucfirst($action)){
		require_once('header.php');
		print "<h1>" . ucfirst($action) . " Results</h1>";
		idOnlyCallIds($action,$_SESSION['field_map'],$_SESSION['csv_array'],true);
		unset($_SESSION['field_map'],$_SESSION['csv_array'],$_SESSION['update_file_tmp_name']);
		include_once('footer.php');
	}

	elseif(isset($_POST['action']) && $_POST['action'] == 'Map Fields'){
		require_once('header.php');
		array_pop($_POST); //remove header row
		$_SESSION['field_map'] = $_POST;
		field_mapping_confirm('Confirm ' . ucfirst($action),$_SESSION['field_map'],$_SESSION['csv_array'],null);
		include_once('footer.php');
	}

	elseif (isset($_FILES['file']) && $_FILES['file']){
		require_once('header.php');
		if (csv_upload_valid_check($_FILES['file'])){
			form_upload_objectSelect_show('file',FALSE);
			show_error(csv_upload_valid_check($_FILES['file']), false, true);
		} else {
			$csv_file_name = basename($_FILES['file']['name']);
			$_SESSION['file_tmp_name'] = $_FILES['file']['tmp_name'];
			$_SESSION['csv_array'] = csv_file_to_array($_SESSION['file_tmp_name']);
			$csv_array_count = count($_SESSION['csv_array']) - 1;
			if (!$csv_array_count) {
				show_error("The file uploaded contains no records. Please try again.", false, true);
			}
			elseif ($csv_array_count > $_SESSION['config']['maxFileLengthRows']) {
				show_error("The file uploaded contains more than " . $_SESSION['config']['maxFileLengthRows'] . " records. Please try again.", false, true);
			}
			$info = "The file $csv_file_name was uploaded successfully and contains $csv_array_count row";
			if ($csv_array_count !== 1) $info .= 's';
				show_info($info);
				print "<br/>";
				field_mapping_set($action,$_SESSION['csv_array']);
			}
		}
		else {
			require_once ('header.php');
			print "<p><strong>Upload a CSV file with Salesforce IDs to $action:</strong></p>\n";
			form_upload_objectSelect_show('file',FALSE);
			include_once('footer.php');
		}
}

/**
 * Does the actual work of PUTting 
 * for id-only funcitons and showing results
 *
 * @param $api_call
 * @param $field_map
 * @param $csv_array
 * @param $show_results
 */
function idOnlyCallIds($api_call,$field_map,$csv_array,$show_results){
	$orig_csv_array = $csv_array;
	
	if (!($field_map && $csv_array)){
		show_error("CSV file and field mapping not initialized successfully. Upload a new file and map fields.");
	} else {

	$id_array =  array();
	$id_col = array_search($field_map['Id'],$csv_array[0]);

	for($row=1; $row < count($csv_array); $row++){
		if ($csv_array[$row][$id_col]){
			$id_array[] = $csv_array[$row][$id_col];
		}
	}

	$results = array();
	$id_array_all = $id_array;

	while($id_array){
		$id_arrayBatch = array_splice($id_array,0,$_SESSION['config']['batchSize']);
		try{
			global $mySforceConnection;
			if($api_call == 'purge') $api_call = 'emptyRecycleBin';
			$results_more = $mySforceConnection->$api_call($id_arrayBatch);

		    if(!$results){
		    	$results = $results_more;
		    } else {
		    	$results = array_merge($results,$results_more);
		    }

		} catch (Exception $e) {
			show_error($e->getMessage(),false,true);
	    }
	}
	if($show_results) show_putAndId_results($results,$api_call,$orig_csv_array,$id_array_all);
	}
}

/**
 * Does the actual work of PUTting 
 * for non-id-only funcitons and showing results
 *  
 * @param $api_call
 * @param $ext_id
 * @param $field_map
 * @param $csv_array
 * @param $show_results
 */
function putSObjects($api_call,$ext_id,$field_map,$csv_array,$show_results){
	$orig_csv_array = $csv_array;//backing up for results
	if (!($field_map && $csv_array && $_SESSION['default_object'])){
		show_error("CSV file and field mapping not initialized. Upload a new file and map fields.");
	} else {
		$csv_header = array_shift($csv_array);
		$results = array();

		while($csv_array){
			$sObjects = array();
			$csv_arrayBatch = array_splice($csv_array,0,$_SESSION['config']['batchSize']);

			for($row=0; $row < count($csv_arrayBatch); $row++){
			    $sObject = new SObject;
		    	$sObject->type = $_SESSION['default_object'];
		    	if($_SESSION['config']['fieldsToNull']) $sObject->fieldsToNull = array();
		    	$fields = array();

				foreach($field_map as $salesforce_field=>$fieldMapArray){
					if(isset($fieldMapArray['relatedObjectName']) && isset($fieldMapArray['relatedFieldName']) && isset($fieldMapArray['csvField'])){
						$refSObject = new SObject;
				    	$refSObject->type = $fieldMapArray['relatedObjectName'];
						$col = array_search($fieldMapArray['csvField'],$csv_header);
				    	if($csv_arrayBatch[$row][$col] != ""){
				    		$refSObject->fields = array($fieldMapArray['relatedFieldName'] => htmlspecialchars($csv_arrayBatch[$row][$col],ENT_QUOTES,'UTF-8'));
				    	}
				    	$field = array($fieldMapArray['relationshipName'] => $refSObject);
					} else if(isset($salesforce_field) && isset($fieldMapArray['csvField'])){
						$col = array_search($fieldMapArray['csvField'],$csv_header);
						if($csv_arrayBatch[$row][$col] != ""){
							$field = array($salesforce_field => htmlspecialchars($csv_arrayBatch[$row][$col],ENT_QUOTES,'UTF-8'));
						} elseif($_SESSION['config']['fieldsToNull']){
							$sObject->fieldsToNull[] = $salesforce_field;
						}
					}

					if (!$fields){
						$fields = $field;
					} else {
						$fields = array_merge($fields,$field);
					}
				}

			    $sObject->fields = $fields;
			    array_push($sObjects, $sObject);
			    unset($sObject);
			}


			try{
				global $mySforceConnection;
				if ($api_call == 'upsert'){
					$results_more = $mySforceConnection->$api_call($ext_id,$sObjects);					
				} else {
					$results_more = $mySforceConnection->$api_call($sObjects);
				}
				unset($sObjects);
			} catch (Exception $e) {
		      	$errors = null;
				$errors = $e->getMessage();
				show_error($errors);
				include_once("footer.php");
				exit;
		    }
		    if(!$results){
		    	$results = $results_more;
		    } else {
		    	$results = array_merge($results,$results_more);
		    }
		}
		if($show_results) show_putAndId_results($results,$api_call,$orig_csv_array,null);
	}
}

/**
 * Does the actual work of PUTting 
 * for asyncPUT funcitons and forwarding 
 * on to the results page.
 *
 * @param unknown_type $api_call
 * @param unknown_type $ext_id
 * @param unknown_type $field_map
 * @param unknown_type $csv_array
 */
function putSObjectsAsync($api_call,$ext_id,$field_map,$csv_array){
	if (!($field_map && $csv_array && $_SESSION['default_object'])){  
		show_error("CSV file and field mapping not initialized. Upload a new file and map fields.");
	} else {
		require_once ('restclient/BulkApiClient.php');
		try{
			$job = new JobInfo();
			$job->setObject($_SESSION['default_object']);
			$job->setOpertion($api_call);
			$job->setContentType("CSV");
			$job->setConcurrencyMode($_SESSION['config']['asyncConcurrencyMode']);
			if($_SESSION['config']['assignmentRuleHeader_assignmentRuleId'] != "") $job->setAssignmentRuleId($_SESSION['config']['assignmentRuleHeader_assignmentRuleId']);
			if($api_call == "upsert" && isset($ext_id)) $job->setExternalIdFieldName($ext_id);
			
			$asyncConnection = getAsyncApiConnection();
			$job = $asyncConnection->createJob($job);
		} catch (Exception $e) {
			show_error($e->getMessage(), true, true);
	    }

		if($job->getId() == null){
			show_error("No job id found. Aborting Bulk API operation.", true, true);
		}
		
		$csv_header = array_shift($csv_array);
		$results = array();

		while($csv_array){
			$sObjects = array();
			$csv_arrayBatch = array_splice($csv_array,0,$_SESSION['config']['asyncBatchSize']);

			$asyncCsv = array();
			
			$asyncCsvHeaderRow = array();
			foreach($field_map as $salesforce_field=>$fieldMapArray){
				if(isset($fieldMapArray['csvField'])){
					if(isset($fieldMapArray['relationshipName']) && isset($fieldMapArray['relatedFieldName'])){
						$asyncCsvHeaderRow[] = $fieldMapArray['relationshipName'] . "." . $fieldMapArray['relatedFieldName'];
					} elseif(isset($salesforce_field)) {
						$asyncCsvHeaderRow[] = $salesforce_field;
					}
				}
			}
			$asyncCsv[] = $asyncCsvHeaderRow;
			
			for($row=0; $row < count($csv_arrayBatch); $row++){
				//create new row
				$asyncCsvRow = array();
				foreach($field_map as $salesforce_field=>$fieldMapArray){
					$col = array_search($fieldMapArray['csvField'],$csv_header);
					if(isset($salesforce_field) && isset($fieldMapArray['csvField'])){
						if($csv_arrayBatch[$row][$col] == "" && $_SESSION['config']['fieldsToNull']) {
							$asyncCsvRow[] = "#N/A";
						} else {
							$asyncCsvRow[] = htmlspecialchars($csv_arrayBatch[$row][$col],ENT_QUOTES,'UTF-8');
						}
					}
				}

				//add row to the array
				$asyncCsv[] = $asyncCsvRow;
			}

			try{
				$batch = $asyncConnection->createBatch($job, arr_to_csv($asyncCsv));
			} catch (Exception $e) {
				show_error($e->getMessage(), true, true);
		    }
		}
		
		try{
			$job = $asyncConnection->updateJobState($job->getId(), "Closed");
		} catch (Exception $e) {
			show_error($e->getMessage(), true, true);
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
 * @param $api_call
 * @param $csv_array
 * @param $idArray
 */
function show_putAndId_results($results,$api_call,$csv_array,$idArray){	
	//check if only result is returned
	if(!is_array($results)) $results = array($results);
	
	unset($_SESSION['resultsWithData']);
	$resultsWithData = array(); //create array to hold results with data for download later
	$_SESSION['resultsWithData'][0] = array("Salesforce Id","Result","Status");
	$_SESSION['resultsWithData'][0] = array_merge($_SESSION['resultsWithData'][0],$csv_array[0]);
	
	$success_count = 0;
	$error_count = 0;
	ob_start();
	for($row=0; $row < count($results); $row++){
		$excel_row = $row + 1;
		
		$_SESSION['resultsWithData'][$row+1] = array(); //create array for row
		
		if ($results[$row]->success){
			$success_count++;
			print "<tr>";
			print "<td>" . $excel_row . "</td>";
			print "<td>" . addLinksToUiForIds($results[$row]->id) . "</td>";
			$_SESSION['resultsWithData'][$row+1][0] = $results[$row]->id;
			print "<td>Success</td>";
			$_SESSION['resultsWithData'][$row+1][1] = "Success";
			if (($api_call == 'upsert' && $results[$row]->created) || $api_call == 'create'){
				print "<td>Created</td>";
				$_SESSION['resultsWithData'][$row+1][2] = "Created";
			} elseif (($api_call == 'upsert' && !$results[$row]->created) || $api_call == 'update') {
				print "<td>Updated</td>";
				$_SESSION['resultsWithData'][$row+1][2] = "Updated";
			} elseif (($api_call == 'delete') || ($api_call == 'undelete')) {
				print "<td>" . ucwords($api_call) . "d </td>";
				$_SESSION['resultsWithData'][$row+1][2] = ucwords($api_call) . "d";
			} elseif ($api_call == 'emptyRecycleBin') {
				print "<td>Purged</td>";
				$_SESSION['resultsWithData'][$row+1][2] = "Purged";
			}
			print "</tr>\n";
		} else {
			$error_count++;
			print "<tr style='color: red;'>";
			print "<td>" . $excel_row . "</td>";
						
			if(!isset($results[$row]->id) && isset($idArray)){
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

		$_SESSION['resultsWithData'][$row+1] = array_merge($_SESSION['resultsWithData'][$row+1],$csv_array[$row+1]);
		
	}
	print "</table><br/>";
	$results_table = ob_get_clean();
	show_info("There were $success_count successes and $error_count errors.");
	
	print "<br/><form action='downloadResultsWithData.php' method='GET'><input type='hidden' name='action' value='$api_call'/><input type='submit' value='Download Full Results'/></form>";
	
	print "<br/>\n<table class='data_table'>\n";
	print "<th>&nbsp;</th> <th style='width: 30%'>Salesforce Id</th> <th style='width: 30%'>Result</th> <th style='width: 35%'>Status</th>\n";
	print "<p>$results_table</p>";
}


?>