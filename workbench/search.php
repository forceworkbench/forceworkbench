<?php
require_once ('session.php');
require_once ('shared.php');

if(isset($_POST['sosl_search'])){
	
	//correction for dynamic magic quotes
	if(get_magic_quotes_gpc()){
		$_POST['sosl_search'] = stripslashes($_POST['sosl_search']);
	}
	
	foreach($_POST as $postKey => $postValue){
		$_SESSION[$postKey] = $postValue;
	}
}


//Main form logic: When the user first enters the page, display form defaulted to
//show the search results with default object selected on a previous page, otherwise
// just display the blank form. When the user selects the SCREEN or CSV options, the
//search is processed by the correct function
if (isset($_POST['searchSubmit']) && isset($_POST['sosl_search'])) {
	print "<body onLoad='toggleFieldDisabled();'>";
	require_once ('header.php');
	show_search_form($_POST['sosl_search']);
	$searchTimeStart = microtime(true);
	$records = search($_POST['sosl_search']);
	$searchTimeEnd = microtime(true);
	$searchTimeElapsed = $searchTimeEnd - $searchTimeStart;
	show_search_result($records,$searchTimeElapsed);
	include_once('footer.php');
} else {
	print "<body onLoad='toggleFieldDisabled();'>";
	require_once ('header.php');
	show_search_form($_SESSION['sosl_search']);
	include_once('footer.php');
}



//Show the main SOSL search form with default search or last submitted search and export action (screen or CSV)

function show_search_form($sosl_search){


print "<script>\n";

print <<<SEARCH_BUILDER_SCRIPT

function toggleFieldDisabled(){
	if(document.getElementById('SB_searchString').value){
		document.getElementById('SB_limit').disabled = false;
		document.getElementById('SB_fieldTypeSelect').disabled = false;
		document.getElementById('SB_objSelect1').disabled = false;
		document.getElementById('SB_objDetail1').disabled = false;
	} else {
		document.getElementById('SB_limit').disabled = true;
		document.getElementById('SB_fieldTypeSelect').disabled = true;
		document.getElementById('SB_objSelect1').disabled = true;
		document.getElementById('SB_objDetail1').disabled = true;
	}
	
	if(!document.getElementById('SB_objSelect1').value || document.getElementById('SB_objSelect1').disabled){
		document.getElementById('SB_objDetail1').disabled = true;
		document.getElementById('SB_objSelect2').disabled = true;
		document.getElementById('SB_objDetail2').disabled = true;
	} else {
		document.getElementById('SB_objDetail1').disabled = false;
		document.getElementById('SB_objSelect2').disabled = false;
		document.getElementById('SB_objDetail2').disabled = false;
	}

	if(!document.getElementById('SB_objSelect2').value || document.getElementById('SB_objSelect2').disabled){
		document.getElementById('SB_objDetail2').disabled = true;
		document.getElementById('SB_objSelect3').disabled = true;
		document.getElementById('SB_objDetail3').disabled = true;
	} else {
		document.getElementById('SB_objDetail2').disabled = false;
		document.getElementById('SB_objSelect3').disabled = false;
		document.getElementById('SB_objDetail3').disabled = false;
	}
	
	if(!document.getElementById('SB_objSelect3').value || document.getElementById('SB_objSelect3').disabled){
		document.getElementById('SB_objDetail3').disabled = true;
	} else {
		document.getElementById('SB_objDetail3').disabled = false;
	}
}

//function updateObject(){
//  document.search_form.justUpdate.value = 1;
//  document.search_form.submit();
//}

function build_search(){
	toggleFieldDisabled();
	
	var searchString = 'FIND {' + document.getElementById('SB_searchString').value + '}';
	
	var fieldTypeSelect = '';
	if(document.getElementById('SB_fieldTypeSelect').value && !document.getElementById('SB_fieldTypeSelect').disabled){
		fieldTypeSelect = ' IN ' + document.getElementById('SB_fieldTypeSelect').value;
	}
	
	var objSelect1 = '';
	if(document.getElementById('SB_objSelect1').value && !document.getElementById('SB_objSelect1').disabled){
		objSelect1 = ' RETURNING ' + document.getElementById('SB_objSelect1').value;

		if(document.getElementById('SB_objDetail1').value && !document.getElementById('SB_objDetail1').disabled){
			objSelect1 += '(' + document.getElementById('SB_objDetail1').value + ')';
		}
	}
	
	var objSelect2 = '';
	if(document.getElementById('SB_objSelect2').value && !document.getElementById('SB_objSelect2').disabled){
		objSelect2 = ', ' + document.getElementById('SB_objSelect2').value;

		if(document.getElementById('SB_objDetail2').value && !document.getElementById('SB_objDetail2').disabled){
			objSelect2 += '(' + document.getElementById('SB_objDetail2').value + ')';
		}
	}
	
	var objSelect3 = '';
	if(document.getElementById('SB_objSelect3').value && !document.getElementById('SB_objSelect3').disabled){
		objSelect3 = ', ' + document.getElementById('SB_objSelect3').value;

		if(document.getElementById('SB_objDetail3').value && !document.getElementById('SB_objDetail3').disabled){
			objSelect3 += '(' + document.getElementById('SB_objDetail3').value + ')';
		}
	}
	
	var limit = '';
	if(document.getElementById('SB_limit').value && !document.getElementById('SB_limit').disabled){
		limit = ' LIMIT ' + document.getElementById('SB_limit').value;
	}


	if (searchString)
		document.getElementById('sosl_search_textarea').value = searchString + fieldTypeSelect + objSelect1 + objSelect2 + objSelect3 + limit;

}
</script>
SEARCH_BUILDER_SCRIPT;

	if($_SESSION['config']['autoJumpToSearchResults']){
		print "<form method='POST' name='search_form' action='$_SERVER[PHP_SELF]#sr'>\n";
	} else {
		print "<form method='POST' name='search_form' action='$_SERVER[PHP_SELF]#sr'>\n";
	}
	
	print "<p><strong>Enter a search string and optionally select the objects and fields to return to build a SOSL search below:</strong></p>\n";
	print "<table border='0' width=1>\n<tr>\n";
    
    print "<td>Search for </td><td><input type='text' id='SB_searchString' name='SB_searchString' value=\"" . htmlspecialchars($_SESSION['SB_searchString'],ENT_QUOTES,'UTF-8') . "\" size='37' onKeyUp='build_search();' /> in ";
    
	$fieldTypeSelectOptions = array(
		'ALL FIELDS' => 'All Fields',
		'NAME FIELDS' => 'Name Fields',
		'PHONE FIELDS' => 'Phone Fields',
		'EMAIL FIELDS' => 'Email Fields'			
	);
	print "<select id='SB_fieldTypeSelect' name='SB_fieldTypeSelect' onChange='build_search();'>\n";
	foreach ($fieldTypeSelectOptions as $op_key => $op){
		print "<option value='$op_key'";
		if (isset($_SESSION['SB_fieldTypeSelect']) && $op_key == $_SESSION['SB_fieldTypeSelect']) print " selected='selected' ";
		print ">$op</option>";
	}
	print "</select>";

    print " limited to <input id='SB_limit' name='SB_limit' type='text'  value='" . htmlspecialchars($_SESSION['SB_limit'],ENT_QUOTES,'UTF-8') . "' size='5' onKeyUp='build_search();' /> maximum records</td></tr>\n";

	print "<tr><td colspan='2'></td></tr>";
	print "<tr><td>returning object </td><td NOWRAP>";
	myGlobalSelect($_SESSION['SB_objSelect1'],'SB_objSelect1',20,"onChange='build_search();'");
	print " including fields <input id='SB_objDetail1' name='SB_objDetail1' type='text' value=\"" . htmlspecialchars($_SESSION['SB_objDetail1'],ENT_QUOTES,'UTF-8') . "\" size='40'  onKeyUp='build_search();' />";
		print "&nbsp;<img onmouseover=\"Tip('List the API names of the fields to be returned; otherwise, only the Id is returned. Optionally include WHERE and LIMIT statements to futher filter search results.')\" align='absmiddle' src='images/help16.png'/>";
		print "</td></tr>";
	print "<tr><td colspan='2'></td></tr>";
	print "<tr><td>and object </td><td NOWRAP>";
	myGlobalSelect($_SESSION['SB_objSelect2'],'SB_objSelect2',20,"onChange='build_search();'");
	print " including fields <input id='SB_objDetail2' name='SB_objDetail2' type='text' value=\"" . htmlspecialchars($_SESSION['SB_objDetail2'],ENT_QUOTES,'UTF-8') . "\" size='40' onKeyUp='build_search();' /></td></tr>";
	
	print "<tr><td colspan='2'></td></tr>";
	print "<tr><td>and object </td><td NOWRAP>";
	myGlobalSelect($_SESSION['SB_objSelect3'],'SB_objSelect3',20,"onChange='build_search();'");
	print " including fields <input id='SB_objDetail3' name='SB_objDetail3' type='text' value=\"" . htmlspecialchars($_SESSION['SB_objDetail3'],ENT_QUOTES,'UTF-8') . "\" size='40' onKeyUp='build_search();' /></td></tr>";

	print "<tr><td valign='top' colspan='3'><br/>Enter or modify a SOSL search below:" .
			"<br/><textarea id='sosl_search_textarea' type='text' name='sosl_search' cols='100' rows='4' style='overflow: auto; font-family: monospace, courier;'>". htmlspecialchars($sosl_search,ENT_QUOTES,'UTF-8') . "</textarea>" .
		  "</td></tr>";


	print "<tr><td colspan=3><input type='submit' name='searchSubmit' value='Search' />";
	print "<input type='reset' value='Reset' />";
	print "</td></tr></table><p/></form>\n";
}


function search($sosl_search){
	try{

		global $mySforceConnection;
		$search_response = $mySforceConnection->search($sosl_search);
	
		if(isset($search_response->searchRecords)){
			$records = $search_response->searchRecords;
		} else {
			$records = null;
		}
	
		return $records;

	} catch (Exception $e){
		$errors = null;
		$errors = $e->getMessage();
		show_error($errors);
		include_once('footer.php');
		exit;
	}
}


//If the user selects to display the form on screen, they are routed to this function
function show_search_result($records, $searchTimeElapsed){
	//Check if records were returned
	if ($records) {
    try {   	
    print "<a name='sr'></a><div style='clear: both;'><br/><h2>Search Results</h2>\n";
    print "<p>Returned " . count($records) . " total record";
    if (count($records) !== 1) print 's';
    print " in ";
	printf ("%01.3f", $searchTimeElapsed);
	print " seconds:</p>";
	
	$searchResultArray = array();
	foreach($records as $record){
		$recordObject = new Sobject($record->record);
		$searchResultArray[$recordObject->type][] = $recordObject;
	}


	foreach($searchResultArray as $recordSetName=>$records){
		echo "<h3 style='color: #0046ad;'>$recordSetName</h3>";
		
	    print "<table class='data_table'>\n";
		//Print the header row on screen
		$record0 = $records[0];
		print "<tr><td>1</td>";
		//If the user queried for the Salesforce ID, this special method is nessisary
		//to export it from the nested SOAP message. This will always be displayed
		//in the first column regardless of search order
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
	      print "</table><p/>";
	}
	  print	"</div>\n";
    } catch (Exception $e) {
      	$errors = null;
		$errors = $e->getMessage();
		print "<p />";
		show_error($errors);
		include_once('footer.php');
		exit;
    }
  } else {
  	print "<p><a name='sr'>&nbsp;</a></p>";
  	show_error("Sorry, no records returned.");
  }
  include_once('footer.php');
}

?>
