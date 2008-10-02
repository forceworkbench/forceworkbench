<?php
require_once('session.php');
require_once('shared.php');
?>

<script type="text/javascript" src="script/simpletreemenu.js">
/***********************************************
* Simple Tree Menu- � Dynamic Drive DHTML code library (www.dynamicdrive.com)
* This notice MUST stay intact for legal use
* Visit Dynamic Drive at http://www.dynamicdrive.com/ for full source code
***********************************************/
</script>

<link rel="stylesheet" type="text/css" href="style/simpletree.css" />

<?php

//Main Form Logic: If there is a default object in the session,
//describe it when the page loads; else, prompt to choose one.
//Note: The POSTed default object is passed to the SESSION default object
//in the session.php include
if ($_SESSION['default_object']){
	show_describeSObject_form();
	show_describeSObject_result();
} else {
	show_describeSObject_form();
	include_once('footer.php');
	exit;
}


//Print a form with the global object types to choose for description
function show_describeSObject_form(){
	require_once ('header.php');

	print "<form name='describeForm' method='post' action='$_SERVER[PHP_SELF]' onChange='document.describeForm.submit();'>";
	print "<p><strong>Choose an object to describe:</strong></p>\n";
	myGlobalSelect($_SESSION['default_object']);

	print "<input type='submit' name='action' value='Describe' />";
	print "</form>";
}



//Print the description of selected/default object type in multiple tables
function show_describeSObject_result(){
		try{
			//Ping Apex API
			$describeSObject_result = describeSObject($_SESSION['default_object']);
		} catch (Exception $e) {
	      	$errors = null;
			$errors = $e->getMessage();
			show_error($errors);
			exit;
    	}


		print "<h2>$_SESSION[default_object] Object Description</h2>";


		print "<a href=\"javascript:ddtreemenu.flatten('describeTree', 'expand')\">Expand All</a> | <a href=\"javascript:ddtreemenu.flatten('describeTree', 'contact')\">Collapse All</a> | <a href=\"describeTable.php\">Table View</a>\n";
		print "<ul id='describeTree' class='treeview'>\n";


		print "<li>Attributes<ul>\n";
		foreach($describeSObject_result as $key => $value){
			//Print strings as is
			if (is_string($value)){
				print "<li>$key: <strong>$value</strong></li> \n";
			}
			//Change bool data to printed as TRUE and FALSE for visibility in table
			elseif (is_bool($value)){
				print "<li>$key: ";
				if ($value){
					print "<strong>True</strong>";
				} else {
					print "<strong>False</strong>";
				}
				print "</li> \n";
			}
		}
		print "</ul></li>\n"; ///end attributes node



		print "<li>Fields<ul>\n";
		foreach($describeSObject_result->fields as $key => $value){
			print "<li>$value->name<ul>\n";
			foreach($value as $subkey => $subvalue){
				if (is_string($subvalue)){
					print "<li>$subkey: <strong>$subvalue</strong></li>\n";
				}
				//Change bool data to printed as TRUE and FALSE for visibility in table
				elseif (is_bool($subvalue)){
					print "<li>$subkey: ";
					if ($subvalue){
						print "<strong>True</strong>";
					} else {
						print "<strong>False</strong>";
					}
					print "</li> \n";
				}
				//Because picklist are deeper in the SOAP message,
				//it requires more nested foreach loops
				elseif ($subkey == 'picklistValues'){
					print "<li>$subkey<ul>\n";
					foreach($subvalue as $subsubkey => $subsubvalue){
						print  "<li>$subsubvalue->label<ul>\n";
						foreach($subsubvalue as $subsubsubkey => $subsubsubvalue){
							if (is_string($subsubsubvalue)){
								print "<li>$subsubsubkey: <strong>$subsubsubvalue</strong></li> \n";
							}
							elseif (is_bool($subsubsubvalue)){
								print "<li>$subsubsubkey: ";
								if ($subsubsubvalue){
									print "<strong>True</strong>";
								} else {
									print "<strong>False</strong>";
								}
								print "</li> \n";
							}
						}
						print "</ul></li>\n"; //end one picklist node
					}
					print "</ul></li>\n"; //end picklist node
				} elseif ($subkey == 'referenceTo'){ //do this for referenceTo arrays 
					print "<li>$subkey<ul>\n";
					foreach($subvalue as $subsubkey => $subsubvalue){
						print  "<li>$subsubvalue</li>\n";
					}
					print "</ul></li>\n"; //end referenceTo node
				}
			}
			print "</ul></li>\n"; ///end one field node
		}
		print "</ul></li>\n"; ///end fields node



		//Print Record Types, if they exists
		if (isset($describeSObject_result->recordTypeInfos)){
			print "<li>Record Types<ul>\n";
			foreach($describeSObject_result->recordTypeInfos as $key => $value){
				if(isset($value->name)){
					print "<li>$value->name<ul>\n";
					foreach($value as $subkey => $subvalue){
						if (is_string($subvalue)){
							print "<li>$subkey: <strong>$subvalue</strong><li>\n";
						}
						elseif (is_bool($subvalue)){
							print "<li>$subkey: ";
							if ($subvalue){
								print "<strong>True</strong>";
							} else {
								print "<strong>False</strong>";
							}
							print "</li> \n";
						}
					}
					print "</ul></li>\n"; ///end one record type node
				}
			}
			print "</ul></li>\n"; ///end record types node
		} //end record type exist conditional check


		//Print Child Relationships, if they exists
		if (isset($describeSObject_result->childRelationships)){
			print "<li>Child Relationships<ul>\n";
			foreach($describeSObject_result->childRelationships as $key => $value){
				print "<li>$value->childSObject<ul>\n";
				foreach($value as $subkey => $subvalue){
					if (is_string($subvalue)){
						print "<li>$subkey: <strong>$subvalue</strong></li> \n";
					}
					elseif (is_bool($subvalue)){
						print "<li>$subkey: ";
						if ($subvalue){
							print "<strong>True</strong>";
						} else {
							print "<strong>False</strong>";
						}
						print "</li> \n";
					}
				}
				print "</ul></li>\n"; ///end one child relationship node
			}
			print "</ul></li>\n"; ///end child relationships node
		}
		print "</ul>\n"; //end tree
}


?>
<script type="text/javascript">
//ddtreemenu.createTree(treeid, enablepersist, opt_persist_in_days (default is 1))
ddtreemenu.createTree("describeTree", true);
</script>
<?php

include_once('footer.php');
?>
