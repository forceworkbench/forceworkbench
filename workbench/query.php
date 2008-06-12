<?php
require_once ('session.php');
require_once ('shared.php');

//correction for dynamic magic quotes
if(isset($_POST['soql_query'])){
	if(get_magic_quotes_gpc()){
		$_POST['soql_query'] = stripslashes($_POST['soql_query']);
	} else {
		$_SESSION['soql_query'] = $_POST['soql_query'];
	}
}


if (isset($_POST['justUpdate']) && $_POST['justUpdate'] == true){
	if (isset($_POST['default_object'])) $_SESSION['default_object'] = $_POST['default_object'];
	unset($_POST['QB_field_sel']);
	unset($_POST['QB_filter_field_sel']);
	unset($_POST['QB_oper_sel']);
	unset($_POST['QB_filter_txt']);
	unset($_POST['QB_filter_field_sel2']);
	unset($_POST['QB_oper_sel2']);
	unset($_POST['QB_filter_txt2']);
	unset($_POST['QB_nulls']);
	unset($_POST['QB_orderby_sort']);
	unset($_POST['QB_orderby_field']);
}


//Main form logic: When the user first enters the page, display form defaulted to
//show the query results with default object selected on a previous page, otherwise
// just display the blank form. When the user selects the SCREEN or CSV options, the
//query is processed by the correct function
if(isset($_POST['queryMore']) && isset($_SESSION['queryLocator'])){
	print "<body onLoad='toggleFieldDisabled();'>";
	require_once ('header.php');
	show_query_form($_POST['soql_query'],'screen',$_POST['query_action'],$_SESSION['default_object']);
	$queryTimeStart = microtime(true);
	$records = query(null,'QueryMore',$_SESSION['queryLocator']);
	$queryTimeEnd = microtime(true);
	$queryTimeElapsed = $queryTimeEnd - $queryTimeStart;
	show_query_result($records,$queryTimeElapsed);
	include_once('footer.php');
} else if (isset($_POST['querySubmit']) && $_POST['querySubmit']=='Query' && isset($_POST['soql_query']) && $_POST['export_action'] == 'screen') {
	print "<body onLoad='toggleFieldDisabled();'>";
	require_once ('header.php');
	show_query_form($_POST['soql_query'],'screen',$_POST['query_action'],$_SESSION['default_object']);
	$queryTimeStart = microtime(true);
	$records = query($_POST['soql_query'],$_POST['query_action']);
	$queryTimeEnd = microtime(true);
	$queryTimeElapsed = $queryTimeEnd - $queryTimeStart;
	show_query_result($records,$queryTimeElapsed);
	include_once('footer.php');
} elseif (isset($_POST['querySubmit']) && $_POST[querySubmit]=='Query' && $_POST[soql_query] && $_POST[export_action] == 'csv') {
	if (!substr_count($_POST['soql_query'],"count()")){
		$records = query($_POST['soql_query'],$_POST['query_action'],null,true);
		export_query_csv($records);
	} else {
		print "<body onLoad='toggleFieldDisabled();'>";
		require_once ('header.php');
		show_query_form($_POST['soql_query'],'csv',$_POST['query_action'],$_SESSION['default_object']);
		print "</form>"; //could include inside because if IE page loading bug
		print "<p>&nbsp;</p>";
		show_error("count() is not supported for CSV file export. Change export to Browser or choose fields and try again.");
		include_once('footer.php');
	}
} else {
	print "<body onLoad='toggleFieldDisabled();'>";
	require_once ('header.php');
	show_query_form($_SESSION['soql_query'],'screen','Query');
	print "</form>"; //could include inside because if IE page loading bug
	include_once('footer.php');
}



//Show the main SOQL query form with default query or last submitted query and export action (screen or CSV)

function show_query_form($soql_query,$export_action,$query_action){

if ($_SESSION['default_object']){
	$describeSObject_result = describeSObject($_SESSION['default_object'], true);
} else {
	show_info('First choose an object to use the SOQL builder wizard.');
}

print "<script>\n";
print "var field_type_array = new Array()\n";
if(isset($describeSObject_result)){
	foreach($describeSObject_result->fields as $fields => $field){
		print " field_type_array[\"$field->name\"]=[\"$field->type\"];\n";
	}
}

print <<<QUERY_BUILDER_SCRIPT

function toggleFieldDisabled(){
	var QB_field_sel = document.getElementById('QB_field_sel');

	if(document.getElementById('myGlobalSelect').value){
		QB_field_sel.disabled = false;
	} else {
		QB_field_sel.disabled = true;
	}


	var isFieldSelected = false;
	for (var i = 0; i < QB_field_sel.options.length; i++)
		if (QB_field_sel.options[i].selected)
			isFieldSelected = true;

	if(isFieldSelected){
			document.getElementById('QB_filter_field_sel').disabled = false;
			document.getElementById('QB_orderby_field').disabled = false;
			document.getElementById('QB_orderby_sort').disabled = false;
			document.getElementById('QB_nulls').disabled = false;
			document.getElementById('QB_limit_txt').disabled = false;
			if(document.getElementById('QB_filter_field_sel').value){
				document.getElementById('QB_filter_txt').disabled = false;
				document.getElementById('QB_oper_sel').disabled = false;
			} else {
				document.getElementById('QB_filter_txt').disabled = true;
				document.getElementById('QB_oper_sel').disabled = true;
			}
	} else{
			document.getElementById('QB_filter_field_sel').disabled = true;
			document.getElementById('QB_oper_sel').disabled = true;
			document.getElementById('QB_filter_txt').disabled = true;
			document.getElementById('QB_orderby_field').disabled = true;
			document.getElementById('QB_orderby_sort').disabled = true;
			document.getElementById('QB_nulls').disabled = true;
			document.getElementById('QB_limit_txt').disabled = true;
	}

	if (isFieldSelected && document.getElementById('QB_filter_field_sel').value && document.getElementById('QB_oper_sel').value && document.getElementById('QB_filter_txt').value){
		document.getElementById('QB_filter_field_sel2').disabled = false;
		if(document.getElementById('QB_filter_field_sel2').value){
			document.getElementById('QB_filter_txt2').disabled = false;
			document.getElementById('QB_oper_sel2').disabled = false;
		} else {
			document.getElementById('QB_filter_txt2').disabled = true;
			document.getElementById('QB_oper_sel2').disabled = true;
		}
	} else {
		document.getElementById('QB_filter_field_sel2').disabled = true;
		document.getElementById('QB_oper_sel2').disabled = true;
		document.getElementById('QB_filter_txt2').disabled = true;
	}
}

function updateObject(){
  document.query_form.justUpdate.value = 1;
  document.query_form.submit();
}

function build_query(){
	toggleFieldDisabled();
	var myGlobalSelect = document.getElementById('myGlobalSelect').value;
	var QB_field_sel = document.getElementById('QB_field_sel');
	QB_fields_selected = new Array();
	for (var i = 0; i < QB_field_sel.options.length; i++){
		if (QB_field_sel.options[i].selected){
			QB_fields_selected.push(QB_field_sel.options[i].value);
		}
	}

	var soql_select = '';
	if(QB_fields_selected.toString().indexOf('count()') != -1 && QB_fields_selected.length > 1){
		alert('Warning: Choosing count() with other fields will result in a malformed query. Unselect either count() or the other fields to continue.');
	} else	if (QB_fields_selected.length > 0){
		var soql_select = 'SELECT ' + QB_fields_selected + ' FROM ' + myGlobalSelect;
	}


	var QB_filter_field_sel = document.getElementById('QB_filter_field_sel').value;
	var QB_oper_sel = document.getElementById('QB_oper_sel').value;
	var QB_filter_txt = document.getElementById('QB_filter_txt').value;
	if (QB_filter_field_sel && QB_oper_sel && QB_filter_txt){
		if (QB_oper_sel == 'starts'){
			QB_oper_sel = 'LIKE'
			QB_filter_txt = QB_filter_txt + '%';
		} else if (QB_oper_sel == 'ends'){
			QB_oper_sel = 'LIKE'
			QB_filter_txt = '%' + QB_filter_txt;
		} else if (QB_oper_sel == 'contains'){
			QB_oper_sel = 'LIKE'
			QB_filter_txt = '%' + QB_filter_txt + '%';
		}

		if ((QB_filter_txt == 'null') ||
			(field_type_array[QB_filter_field_sel] == "datetime") ||
		    (field_type_array[QB_filter_field_sel] == "date") ||
		    (field_type_array[QB_filter_field_sel] == "currency") ||
		    (field_type_array[QB_filter_field_sel] == "percent") ||
		    (field_type_array[QB_filter_field_sel] == "double") ||
		    (field_type_array[QB_filter_field_sel] == "int") ||
		    (field_type_array[QB_filter_field_sel] == "boolean")){
				QB_filter_txt_q = QB_filter_txt;
		} else {
			QB_filter_txt_q = '\'' + QB_filter_txt + '\'';
		}

		var soql_where = ' WHERE ' + QB_filter_field_sel + ' ' + QB_oper_sel + ' ' + QB_filter_txt_q;
	} else {
		var soql_where = '';
	}


	var QB_filter_field_sel2 = document.getElementById('QB_filter_field_sel2').value;
	var QB_oper_sel2 = document.getElementById('QB_oper_sel2').value;
	var QB_filter_txt2 = document.getElementById('QB_filter_txt2').value;
	if (QB_filter_field_sel2 && QB_oper_sel2 && QB_filter_txt2){
		if (QB_oper_sel2 == 'starts'){
			QB_oper_sel2 = 'LIKE'
			QB_filter_txt2 = QB_filter_txt2 + '%';
		} else if (QB_oper_sel2 == 'ends'){
			QB_oper_sel2 = 'LIKE'
			QB_filter_txt2 = '%' + QB_filter_txt2;
		} else if (QB_oper_sel2 == 'contains'){
			QB_oper_sel2 = 'LIKE'
			QB_filter_txt2 = '%' + QB_filter_txt2 + '%';
		}
		if ((QB_filter_txt2 == 'null') ||
			(field_type_array[QB_filter_field_sel2] == "datetime") ||
		    (field_type_array[QB_filter_field_sel2] == "date") ||
		    (field_type_array[QB_filter_field_sel2] == "currency") ||
		    (field_type_array[QB_filter_field_sel2] == "percent") ||
		    (field_type_array[QB_filter_field_sel2] == "double") ||
		    (field_type_array[QB_filter_field_sel2] == "int") ||
		    (field_type_array[QB_filter_field_sel2] == "boolean")){
				QB_filter_txt_q2 = QB_filter_txt2;
		} else {
			QB_filter_txt_q2 = '\'' + QB_filter_txt2 + '\'';
		}

		var soql_where2 = ' AND ' + QB_filter_field_sel2 + ' ' + QB_oper_sel2 + ' ' + QB_filter_txt_q2;
	} else {
		var soql_where2 = '';
	}

	if(soql_where && soql_where2){
		soql_where = soql_where + soql_where2;
	}

	var QB_orderby_field = document.getElementById('QB_orderby_field').value;
	var QB_orderby_sort = document.getElementById('QB_orderby_sort').value;
	var QB_nulls = document.getElementById('QB_nulls').value;
	if (QB_orderby_field){
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
</script>
QUERY_BUILDER_SCRIPT;


	if($_SESSION['config']['autoJumpToQueryResults']){
		print "<form method='POST' name='query_form' action='$_SERVER[PHP_SELF]#qr'>\n";
	} else {
		print "<form method='POST' name='query_form' action='$_SERVER[PHP_SELF]'>\n";
	}
	print "<input type='hidden' name='justUpdate' value='0' />";
	print "<p><strong>Choose the object, fields, and critera to build a SOQL query below:</strong></p>\n";
	print "<table border='0' width=1>\n";
    print "<tr><td valign='top' width='1'>";

    //extracted myGlobalSelect Function
	print "Object:\n<select id='myGlobalSelect' name='default_object' style='width: 16em;' onChange='updateObject();'>\n";
	print "<option value=''></option>";
	if (!$_SESSION['myGlobal']){
		try{
		global $mySforceConnection;
		$_SESSION['myGlobal'] = $mySforceConnection->describeGlobal();
		} catch (Exception $e) {
	      	$errors = null;
			$errors = $e->getMessage();
			show_error($errors);
			exit;
	    }
	}
	//Print the global object types in a dropdown select box
	foreach($_SESSION['myGlobal']->types as $type){
		print "	<option value='$type'";
		if ($_SESSION['default_object'] == $type){
			print " selected='true'";
			}
		print " />$type</option> \n";
	}
	print "</select><p/>\n";

    print "Fields:<select id='QB_field_sel' name='QB_field_sel[]' multiple='mutliple' size='10' style='width: 16em;' onChange='build_query();'>\n";
	if(isset($describeSObject_result)){

		print   " <option value='count()'";
		if(isset($_POST['QB_field_sel'])){ //check to make sure something is selected; otherwise warnings will display
			foreach ($_POST['QB_field_sel'] as $selected_field){
				if ('count()' == $selected_field) print " selected='selected' ";
			}
		}
		print ">count()</option>\n";

		print ">$field->name</option>\n";
		foreach($describeSObject_result->fields as $fields => $field){
			print   " <option value='$field->name'";
			if(isset($_POST['QB_field_sel'])){ //check to make sure something is selected; otherwise warnings will display
				foreach ($_POST['QB_field_sel'] as $selected_field){
					if ($field->name == $selected_field) print " selected='selected' ";
				}
			}
			print ">$field->name</option>\n";
		}
	}
	print "</select></td>\n";
	print "<td valign='top'>";




	print "<table border='0' align='right'>\n";
	print "<tr><td valign='top' colspan=2>Export to:<br/>" .
			"<label><input type='radio' name='export_action' value='screen' ";
		if ($export_action == 'screen') print "checked='true'";
	print " >Browser</label>&nbsp;";

	print "<label><input type='radio' name='export_action' value='csv' ";
		if ($export_action == 'csv') print "checked='true'";
	print " >CSV File</label>";

	print "<td valign='top' colspan=2>Deleted and archived records:<br/>" .
			"<label><input type='radio' name='query_action' value='Query' ";
		if ($query_action == 'Query') print "checked='true'";
	print " >Exclude</label>&nbsp;";

	print "<label><input type='radio' name='query_action' value='QueryAll' ";
		if ($query_action == 'QueryAll') print "checked='true'";
	print " >Include</label></td></tr>\n";




	print "<tr><td><br/>Sort results by:</td> <td><br/>&nbsp;</td> <td><br/>&nbsp;</td> <td><br/>Max Records:</td></tr>\n";
	print "<tr>";
		print "<td><select id='QB_orderby_field' name='QB_orderby_field' style='width: 16em;' onChange='build_query();'>\n";
		print "<option value=''></option>\n";
		if(isset($describeSObject_result)){
			foreach($describeSObject_result->fields as $fields => $field){
				print   " <option value='$field->name'";
				if (isset($_POST['QB_orderby_field']) && $field->name == $_POST['QB_orderby_field']) print " selected='selected' ";
				print ">$field->name</option>\n";
			}
		}
		print "</select></td>\n";

		$QB_orderby_sort_options = array(
			'ASC' => 'A to Z',
			'DESC' => 'Z to A'
		);
		print "<td><select id='QB_orderby_sort' name='QB_orderby_sort' style='width: 10em;' onChange='build_query();'>\n";
		foreach ($QB_orderby_sort_options as $op_key => $op){
			print "<option value='$op_key'";
			if (isset($_POST['QB_orderby_sort']) && $op_key == $_POST['QB_orderby_sort']) print " selected='selected' ";
			print ">$op</option>\n";
		}
		print "</select></td>\n";

		$QB_nulls_options = array(
			'FIRST' => 'Nulls First',
			'LAST' => 'Nulls Last'
		);
		print "<td><select id='QB_nulls' name='QB_nulls' style='width: 10em;' onChange='build_query();'>\n";
		foreach ($QB_nulls_options as $op_key => $op){
			print "<option value='$op_key'";
			if (isset($_POST['QB_nulls']) && $op_key == $_POST['QB_nulls']) print " selected='selected' ";
			print ">$op</option>\n";
		}
		print "</select></td>\n";

		print "<td><input type='text' id='QB_limit_txt' size='11' name='QB_limit_txt' value='" . $_POST['QB_limit_txt'] . "' onkeyup='build_query();' /></td>\n";

	print "</tr>\n";


	print "<tr><td valign='top' colspan=4>\n";
	print "<br/>Filter results by:<br/>\n";

	print "<select id='QB_filter_field_sel' name='QB_filter_field_sel' style='width: 16em;' onChange='build_query();'>\n";
	print "<option value=''></option>";
	if(isset($describeSObject_result)){
		foreach($describeSObject_result->fields as $fields => $field){
			print   " <option value='$field->name'";
			if (isset($_POST['QB_filter_field_sel']) && $field->name == $_POST['QB_filter_field_sel']) print " selected='selected' ";
			print ">$field->name</option>\n";
		}
	}
	print "</select>\n";


	$ops = array(
		'=' => '=',
		'!=' => '&ne;',
		'<' => '&lt;',
		'<=' => '&le;',
		'>' => '&gt;',
		'>=' => '&ge;',
		'starts' => 'starts with',
		'ends' => 'ends with',
		'contains' => 'contains'
	);

	print "<select id='QB_oper_sel' name='QB_oper_sel' style='width: 10em;' onChange='build_query();'>\n";
		foreach ($ops as $op_key => $op){
			print "<option value='$op_key'";
			if (isset($_POST['QB_oper_sel']) && $op_key == $_POST['QB_oper_sel']) print " selected='selected' ";
			print ">$op</option>\n";
		}
	print "</select>\n";

	print "<input type='text' id='QB_filter_txt' size='31' name='QB_filter_txt' value='" . $_POST['QB_filter_txt'] . "' onkeyup='build_query();' />";
	print "</td></tr>\n";


	print "<tr><td valign='top' colspan=4>\n";
	print "<br/>Then filter results by:<br/>\n";

	print "<select id='QB_filter_field_sel2' name='QB_filter_field_sel2' style='width: 16em;' onChange='build_query();'>\n";
	print "<option value=''></option>\n";
	if(isset($describeSObject_result)){
		foreach($describeSObject_result->fields as $fields => $field){
			print   " <option value='$field->name'";
			if (isset($_POST['QB_filter_field_sel2']) && $field->name == $_POST['QB_filter_field_sel2']) print " selected='selected' ";
			print ">$field->name</option>\n";
		}
	}
	print "</select> \n";


	$ops = array(
		'=' => '=',
		'!=' => '&ne;',
		'<' => '&lt;',
		'<=' => '&le;',
		'>' => '&gt;',
		'>=' => '&ge;',
		'starts' => 'starts with',
		'ends' => 'ends with',
		'contains' => 'contains'
	);

	print "<select id='QB_oper_sel2' name='QB_oper_sel2' style='width: 10em;' onChange='build_query();'>";
		foreach ($ops as $op_key => $op){
			print "<option value='$op_key'";
			if (isset($_POST['QB_oper_sel2']) && $op_key == $_POST['QB_oper_sel2']) print " selected='selected' ";
			print ">$op</option>";
		}
	print "</select>\n";

	print "<input type='text' id='QB_filter_txt2' size='31' name='QB_filter_txt2' value='" . $_POST['QB_filter_txt2'] . "' onkeyup='build_query();' />\n";
	print "</td></tr>\n";




	print "</table>\n";

	print "</td></tr>\n";


	print "<tr><td valign='top' colspan=5><br/>Enter or modify a SOQL query below:\n" .
			"<br/><textarea id='soql_query_textarea' type='text' name='soql_query' cols='118' rows='4' style='overflow: auto;'>$soql_query</textarea>\n" .
		  "</td></tr>\n";


	print "<tr><td colspan=5><input type='submit' name='querySubmit' value='Query' />\n";
	print "<input type='reset' value='Reset' />\n";
	print "</td></tr></table><p/>\n";
}


function query($soql_query,$query_action,$query_locator = null,$suppressScreenOutput=false){
	try{

	global $mySforceConnection;
	if ($query_action == 'Query') $query_response = $mySforceConnection->query($soql_query);
	if ($query_action == 'QueryAll') $query_response = $mySforceConnection->queryAll($soql_query);
	if ($query_action == 'QueryMore' && isset($query_locator)) $query_response = $mySforceConnection->queryMore($query_locator);

	if (substr_count($soql_query,"count()") && $suppressScreenOutput == false){
		show_info("Query would return " . $query_response->size . " records.");
		$records = $query_response->size;
		include_once('footer.php');
		exit;
	}

	if(isset($query_response->records)){
		$records = $query_response->records;
	} else {
		$records = null;
	}
	
	$_SESSION['totalQuerySize'] = $query_response->size;

	if(!$query_response->done){
		$_SESSION['queryLocator'] = $query_response->queryLocator;
	} else {
		$_SESSION['queryLocator'] = null;
	}	

	while($_SESSION['config']['autoRunQueryMore'] && !$query_response->done){
		$query_response = $mySforceConnection->queryMore($query_response->queryLocator);
		$records = array_merge($records,$query_response->records);
	}

	return $records;

	} catch (Exception $e){
		$errors = null;
		$errors = $e->getMessage();
		print "<p><a name='qr'>&nbsp;</a></p>";
		show_error($errors);
		include_once('footer.php');
		exit;
	}
}


//If the user selects to display the form on screen, they are routed to this function
function show_query_result($records, $queryTimeElapsed){
	//Check if records were returned
	if ($records) {
    try {
    print "<a name='qr'></a><div style='clear: both;'><br/><h2>Query Results</h2>\n";
    if(isset($_SESSION['queryLocator']) && !$_SESSION['config']['autoRunQueryMore']){
    	preg_match("/-(\d+)/",$_SESSION['queryLocator'],$lastRecord);
    	print "<p>Returned records " . ($lastRecord[1] - count($records) + 1) . " - " . $lastRecord[1] . " of ";
    } else if (!$_SESSION['config']['autoRunQueryMore']){
    	print "<p>Returned records " . ($_SESSION['totalQuerySize'] - count($records) + 1) . " - " . $_SESSION['totalQuerySize'] . " of ";
    } else {
    	print "<p>Returned ";
    }
    
    print $_SESSION['totalQuerySize'] . " total record";
    if ($_SESSION['totalQuerySize'] !== 1) print 's';
    print " in ";
	printf ("%01.3f", $queryTimeElapsed);
	print " seconds:</p>\n";
	
	if (!$_SESSION['config']['autoRunQueryMore'] && $_SESSION['queryLocator']){
		 print "<p><input type='submit' name='queryMore' id='queryMoreButtonTop' value='More...' /></p>\n";	
	}

    print "<table class='data_table'>\n";
	//Print the header row on screen
	$record0 = new SObject($records[0]);
	print "<tr><td>1</td>";
	//If the user queried for the Salesforce ID, this special method is nessisary
	//to export it from the nested SOAP message. This will always be displayed
	//in the first column regardless of query order
	if ($record0->Id){
		print "<th>Id</th>";
	}
	if ($record0->fields){
		foreach($record0->fields->children() as $field){
	 			print "<th>";
	        	print htmlspecialchars($field->getName(),ENT_QUOTES,'UTF-8');
	        	print "</th>";
	        }
	}else {
		print "</td></tr>";
	}
    print "</tr>\n";

		//Print the remaining rows in the body
		$rowNum = 2;
      foreach ($records as $record) {
        $record = new SObject($record);


        print "<tr><td>$rowNum</td>";
        $rowNum++;
        //Another check if there are ID columns in the body
        if (isset($record->Id)){
        	print "<td>$record->Id</td>";
        }
        //Print the non-ID fields
        if (isset($record->fields)){
		foreach($record->fields as $datum){
			print "<td>";
			if($datum){
			print htmlspecialchars($datum,ENT_QUOTES,'UTF-8');
			} else {
				print "&nbsp;";
			}
			print "</td>";
		}
		print "</tr>\n";
        } else{
        	print "</td></tr>\n";
        }
      }
      print "</table>";
	  
      if (!$_SESSION['config']['autoRunQueryMore'] && $_SESSION['queryLocator']){
	    print "<p><input type='submit' name='queryMore' id='queryMoreButtonBottom' value='More...' /></p>";	
	  }
	  
	  print	"</form></div>\n";
    } catch (Exception $e) {
      	$errors = null;
		$errors = $e->getMessage();
		print "<p />";
		show_error($errors);
		include_once('footer.php');
		exit;
    }
  } else {
  	print "<p><a name='qr'>&nbsp;</a></p>";
  	show_error("Sorry, no records returned.");
  }
  include_once('footer.php');
}


//Export the above query to a CSV file
function export_query_csv($records){
	if ($records) {
	    try {
		    $csv_line = array();
			$csv_file = fopen('php://output','w') or die("Error opening php://output");
			$csv_filename = "export" . date(YmdHis) . ".csv";
			header("Content-Type: application/csv");
			header("Content-Disposition: attachment; filename=$csv_filename");


		    //Get first row
		    $record0 = new SObject($records[0]);

		    //If the user queried for the Salesforce ID, this special method is nessisary
			//to export it from the nested SOAP message. This will always be displayed
			//in the first column regardless of query order
			if ($record0->Id){
				$csv_line[] = "Id";
			}

			//Remaining headers
			if ($record0->fields){
				foreach($record0->fields->children() as $field){
			 		$csv_line[] = $field->getName();
			        }
			}

		  //Write first row to CSV and unset variable
		  fputcsv($csv_file,$csv_line);
		  unset($csv_line);

	      //Export remaining rows and write to CSV line-by-line
	      foreach ($records as $record) {
	        $record = new SObject($record);
	        if ($record->Id){
	        	$csv_line[] = $record->Id;
	        }
	        if ($record->fields){
			foreach($record->fields as $datum){
				if($datum){
					$csv_line[] = $datum;
				} else {
					$csv_line[] = '';
				}
			}
	        }
	        fputcsv($csv_file,$csv_line);
	        unset($csv_line);
	      }
		    fclose($csv_file) or die("Error closing php://output");
	    } catch (Exception $e) {
	      	$errors = null;
			$errors = $e->getMessage();
			show_query_form($_POST['soql_query'],'csv');
			print "<p />";
			show_error($errors);
			include_once('footer.php');
			exit;
	    }
	  }else
	  {
	  	$errors = null;
		$errors = $e->getMessage();
		show_query_form($_POST['soql_query'],'csv');
		print "<p />";
		show_error($errors);
		include_once('footer.php');
		exit;
	  }
}

?>
