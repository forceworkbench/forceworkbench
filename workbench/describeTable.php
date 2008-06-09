<?php
require_once('session.php');
require_once('shared.php');

//Main Form Logic: If there is a default object in the session,
//describe it when the page loads; else, prompt to choose one.
//Note: The POSTed default object is passed to the SESSION default object
//in the session.php include
if ($_SESSION[default_object]){
	show_describeSObject_form();
	show_describeSObject_result();
} else {
	show_describeSObject_form();
	exit;
}


//Print a form with the global object types to choose for description
function show_describeSObject_form(){
	require_once ('header.php');

	print "<form method='post' action='$_SERVER[PHP_SELF]'>";
	print "<p><strong>Choose an object to describe:</strong></p>\n";
	myGlobalSelect($_SESSION[default_object]);
	print "<input type='submit' name='action' value='Describe' />";
	print "</form>";
}


//Print the description of selected/default object type in multiple tables
function show_describeSObject_result(){
	try{
		//Ping Apex API
		$describeSObject_result = describeSObject($_SESSION[default_object]);

		print "<h1>$_SESSION[default_object] Object Description</h1>";
		
		print "<a href='describe.php'>Return to Tree View</a>";

		//Print attributes table
		print "<h2>Attributes</h2>\n";
		print "<table class='description'>";
		foreach($describeSObject_result as $key => $value){
			//Print strings as is
			if (is_string($value)){
				print "<tr><td>$key</td><td>$value</td></tr> \n";
			}
			//Change bool data to printed as TRUE and FALSE for visibility in table
			elseif (is_bool($value)){
				print "<tr><td>$key</td><td>";
				if ($value){
					print "True";
				} else {
					print "False";
				}
				print "</td></tr> \n";
			}
		}
		print "</table>";


		//Print Fields table with nested foreach loops for buried data
		print "<h2>Fields</h2>\n";
		foreach($describeSObject_result->fields as $key => $value){
			print "<h3>$value->name</h3>\n";
			print "<table class='description'>";
			foreach($value as $subkey => $subvalue){
				if (is_string($subvalue)){
					print "<tr><td>$subkey</td><td>$subvalue</td></tr> \n";
				}
				//Change bool data to printed as TRUE and FALSE for visibility in table
				elseif (is_bool($subvalue)){
					print "<tr><td>$subkey</td><td>";
					if ($subvalue){
						print "True";
					} else {
						print "False";
					}
					print "</td></tr> \n";
				}
				//Because picklist are deeper in the SOAP message,
				//it requires more nested foreach loops
				elseif ($subkey == 'picklistValues'){
					print "<tr><td colspan=2>$subkey</td></tr> \n";
					foreach($subvalue as $subsubkey => $subsubvalue){
						foreach($subsubvalue as $subsubsubkey => $subsubsubvalue){
							if (is_string($subsubsubvalue)){
								print "<tr><td>&nbsp; &nbsp; $subsubsubkey</td><td>$subsubsubvalue</td></tr> \n";
							}
							elseif (is_bool($subsubsubvalue)){
								print "<tr><td>&nbsp; &nbsp; $subsubsubkey</td><td>";
								if ($subsubsubvalue){
									print "True";
								} else {
									print "False";
								}
								print "</td></tr> \n";
							}
						}
						print "<tr><td>&nbsp;</td><td>&nbsp;</td></tr> \n";
					}
				} elseif ($subkey == 'referenceTo'){ //do this for referenceTo arrays 
					print "<tr><td colspan=2>$subkey</td></tr>\n";
					foreach($subvalue as $subsubkey => $subsubvalue){
						print  "<tr><td>&nbsp;</td><td>$subsubvalue</td></tr>\n";
					}
					print "<tr><td>&nbsp;</td><td>&nbsp;</td></tr> \n"; //end referenceTo node
				}
			}
		print "</table>\n<br/>";
		}


		//Print Child Relationships, if they exists (conditional not working)
		if ($describeSObject_result->childRelationships){
		print "<h2>Child Relationships</h2>\n";
		foreach($describeSObject_result->childRelationships as $key => $value){
			print "<h3>$value->childSObject</h3>\n";
			print "<table class='description'>";
			foreach($value as $subkey => $subvalue){
				if (is_string($subvalue)){
					print "<tr><td>$subkey</td><td>$subvalue</td></tr> \n";
				}
				elseif (is_bool($subvalue)){
					print "<tr><td>$subkey</td><td>";
					if ($subvalue){
						print "True";
					} else {
						print "False";
					}
					print "</td></tr> \n";
				}
			}
		print "</table>\n</br>";
		}
		}

		//Print Record Types, if they exists (conditional not working)
		if ($describeSObject_result->recordTypeInfos){
		print "<h2>Record Types</h2>\n";
		foreach($describeSObject_result->recordTypeInfos as $key => $value){
			print "<h3>$value->name</h3>\n";
			print "<table class='description'>";
			foreach($value as $subkey => $subvalue){
				if (is_string($subvalue)){
					print "<tr><td>$subkey</td><td>$subvalue</td></tr> \n";
				}
				elseif (is_bool($subvalue)){
					print "<tr><td>$subkey</td><td>";
					if ($subvalue){
						print "True";
					} else {
						print "False";
					}
					print "</td></tr> \n";
				}

			}
			print "</table>\n<br/>";
		}
		}

		} catch (Exception $e) {
      	$errors = null;
		$errors = $e->getMessage();
		show_error($errors);
		exit;
    }
}

include_once('footer.php');
?>
