<!-- Receives ajax requests from Async SOQL Submit Job functionality (through async_submitjobs.js) to display source and target fields based on the selected source/target objects in the Submit Job tab -->

<?php
require_once 'soxl/QueryObjects.php';
require_once 'session.php';
require_once 'shared.php';

set_exception_handler('handleAllExceptionsNoHeaders');
$action = $_REQUEST["action"];

if (($action != 'displaySourceFields') && ($action != 'displayTargetFields')) {
	displayError("Appropriate object was not selected for filling the fields.");
} else {
	$object = $_REQUEST["object"];
	$queryRequest = new QueryRequest($defaultSettings);
	$queryRequest->setExportTo('screen');
	$queryRequest->setQueryAction('Query');
	$queryRequest->setObject($object);

	$describeSObjectResult = WorkbenchContext::get()->describeSObjects($queryRequest->getObject());
	
  	if (isset($describeSObjectResult)) {
  		$field_type_array = array();
        foreach ($describeSObjectResult->fields as $fields => $field) {
				$field_type_array[$field->name] = $field->type;
           	}
        if (!empty($field_type_array)) {
	  		if ($action=='displaySourceFields') {
	  			print "<input type='hidden' id='field_array' name='field_array' value=".json_encode($field_type_array).">";
	  			print "<select id='QB_field_sel' name='QB_field_sel[]' multiple='mutliple' size='5' style='width: 16em; height: 100%; display:inline-block;' onChange='buildQuery();'>\n";
	  			print "<option value='count()'>count()</option>\n";
	  		} else if ($action=='displayTargetFields') {
	  			print "<option value=''></option>\n";
	  		}

			foreach ($describeSObjectResult->fields as $fields => $field) {
			    print   " <option value='$field->name'";
			    if ($queryRequest->getFields() != null) { //check to make sure something is selected; otherwise warnings will display
			        foreach ($queryRequest->getFields() as $selectedField) {
			            if ($field->name == $selectedField) print " selected='selected' ";
			        }
			    }
			    print ">$field->name</option>\n";
			}
			print "</select>";		
		}
	}	
}

?>


