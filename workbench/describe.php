<?php
require_once 'session.php';
require_once 'shared.php';
require_once 'header.php';

//Main Form Logic: If there is a default object in the session,
//describe it when the page loads; else, prompt to choose one.
//Note: The POSTed default object is passed to the SESSION default object
//in the session.php include

show_describeSObject_form();
if (isset($_SESSION['default_object']) && "" !== $_SESSION['default_object']) {
	show_describeSObject_result();
}
require_once 'footer.php';


//Print a form with the global object types to choose for description
function show_describeSObject_form(){
	print "<form name='describeForm' method='POST' action='$_SERVER[PHP_SELF]'>" .
		  "<p class='instructions'>Choose an object to describe:</p>\n";
	printObjectSelection($_SESSION['default_object'], 'default_object', 30, "onChange=\"document.getElementById('loadingMessage').style.visibility='visible'; document.describeForm.submit();\"");
	print "<span id='loadingMessage' style='visibility:hidden; color:#888;'>&nbsp;&nbsp;<img src='images/wait16trans.gif' align='absmiddle'/> Loading...</span>\n";
	print  "</form><br/>\n";
}



//Print the description of selected/default object type in multiple tables
function show_describeSObject_result(){
		try {
			//Ping Apex API
			$describeSObjectResult = describeSObject($_SESSION['default_object']);
		} catch (Exception $e) {
			
			show_error($e->getMessage(), false, true);
    	}
		
		if(isset($_SESSION['config']['colorBooleanValues']) && $_SESSION['config']['colorBooleanValues'] || 
		   isset($_SESSION['config']['highlightCustomFields']) && $_SESSION['config']['highlightCustomFields'] || 
		   isset($_SESSION['config']['highlightSystemFields']) && $_SESSION['config']['highlightSystemFields']){
		   	
			print "<div style='float: right; border:1px solid #bbb; padding:0.5em; margin-right:1em;'>" .
		   	      "<strong>Legend:</strong>" . 
			      "<ul style='margin:0; padding-left: 2em'>";
			if ($_SESSION['config']['highightBooleanValues']) {
				print "<li class=\"trueColor\">True</li>\n";
				print "<li class=\"falseColor\">False</li>\n";
			} 
			if ($_SESSION['config']['highlightCustomFields']) {
				print "<li class=\"highlightCustomField\">Custom Field</li>\n";
			}
			if ($_SESSION['config']['highlightSystemFields']) {
				print "<li class=\"highlightSystemField\">System Field</li>\n";
			} 
			print "</ul>" . 
			      "</div>";
		}

		print "<a href=\"javascript:ddtreemenu.flatten('describeTree', 'expand')\">Expand All</a> | <a href=\"javascript:ddtreemenu.flatten('describeTree', 'collapse')\">Collapse All</a>\n";
		print "<ul id='describeTree' class='treeview'>\n";


		print "<li>Attributes<ul style='display:none;'>\n";
		foreach ($describeSObjectResult as $key => $value) {
			//Change bool data to printed as TRUE and FALSE for visibility in table
			if (is_bool($value)) {
				print "<li>$key: ";
				booleanDisplay($value);
				print "</li> \n";
			} elseif (is_string($value) || is_numeric($value)) {
				stringDisplay($key, $value);
			}
		}
		print "</ul></li>\n"; ///end attributes node

		print "<li>Fields (" . count($describeSObjectResult->fields) . ")<ul style='display:none;'>\n";
		foreach ($describeSObjectResult->fields as $key => $value) {
			highlightSpecialField($value);
			foreach ($value as $subkey => $subvalue) {
				//Change bool data to printed as TRUE and FALSE for visibility in table
				if (is_bool($subvalue)) {
					print "<li>$subkey: ";
					booleanDisplay($subvalue);
					print "</li> \n";
				} elseif ($subkey == 'picklistValues') {
					//Because picklist are deeper in the SOAP message,
				    //it requires more nested foreach loops
					if(!is_array($subvalue)) $subvalue = array($subvalue);
					print "<li>$subkey (" . count($subvalue) . ")<ul style='display:none;'>\n";
					foreach ($subvalue as $subsubkey => $subsubvalue) {
						if($value->name == "Division") print "<li>$subsubvalue->label<ul style='display:none;'>\n";
						else print  "<li>$subsubvalue->value<ul style='display:none;'>\n";
						foreach ($subsubvalue as $subsubsubkey => $subsubsubvalue) {
							if (is_bool($subsubsubvalue)) {
								print "<li>$subsubsubkey: ";
								booleanDisplay($subsubsubvalue);
								print "</li> \n";
							} elseif (is_string($subsubsubvalue) || is_numeric($subsubsubvalue)) {
								stringDisplay( $subsubsubkey, $subsubsubvalue);
							}
						}
						print "</ul></li>\n"; //end one picklist node
					}
					print "</ul></li>\n"; //end picklist node
				} elseif ($subkey == 'referenceTo') { //do this for referenceTo arrays 
					if (is_array($subvalue)) {
						print "<li>$subkey<ul style='display:none;'>\n";
						foreach ($subvalue as $subsubkey => $subsubvalue) {
							print  "<li><strong>$subsubvalue</strong></li>\n";
						}
						print "</ul></li>\n"; //end referenceTo node
					} elseif (is_string($subvalue) || is_numeric($subvalue)) {
						stringDisplay($subkey, $subvalue);
					}
				} elseif (is_string($subvalue) || is_numeric($subvalue)) {
					stringDisplay($subkey, $subvalue);
				}
			}
			print "</ul></li>\n"; ///end one field node
		}
		print "</ul></li>\n"; ///end fields node



		//Print Record Types, if they exists
		if (isset($describeSObjectResult->recordTypeInfos)) {
			if(!is_array($describeSObjectResult->recordTypeInfos)) $describeSObjectResult->recordTypeInfos = array($describeSObjectResult->recordTypeInfos);
			print "<li>Record Types (" . count($describeSObjectResult->recordTypeInfos) . ")<ul style='display:none;'>\n";
			foreach ($describeSObjectResult->recordTypeInfos as $key => $value) {
				if (isset($value->name)) {
					print "<li>$value->name<ul style='display:none;'>\n";
					foreach ($value as $subkey => $subvalue) {
						if (is_string($subvalue) || is_numeric($subvalue)) {
							stringDisplay( $subkey, $subvalue);
						} elseif (is_bool($subvalue)) {
							print "<li>$subkey: ";
							booleanDisplay($subvalue);
							print "</li> \n";
						}
					}
					print "</ul></li>\n"; ///end one record type node
				}
			}
			print "</ul></li>\n"; ///end record types node
		} //end record type exist conditional check


		//Print Child Relationships, if they exists
		if (isset($describeSObjectResult->childRelationships)) {
			if(!is_array($describeSObjectResult->childRelationships)) $describeSObjectResult->childRelationships = array($describeSObjectResult->childRelationships);
			print "<li>Child Relationships (" . count($describeSObjectResult->childRelationships) . ")<ul style='display:none;'>\n";
			foreach ($describeSObjectResult->childRelationships as $key => $value) {
				print "<li>$value->childSObject<ul style='display:none;'>\n";
				foreach ($value as $subkey => $subvalue) {
					if (is_string($subvalue) || is_numeric($subvalue)) {
						stringDisplay( $subkey, $subvalue);
					} elseif (is_bool($subvalue)) {
						print "<li>$subkey: ";
						booleanDisplay($subvalue);
						print "</li> \n";
					}
				}
				print "</ul></li>\n"; ///end one child relationship node
			}
			print "</ul></li>\n"; ///end child relationships node
		}
		print "</ul>\n"; //end tree
}

function booleanDisplay($value) {
	if ($_SESSION['config']['highightBooleanValues']) {
		if ($value) {
			print "<span class='describeValue trueColor'>True</span>";
		} else {
			print "<span class='describeValue falseColor'>False</span>";
		}			
	}
	else {
		if ($value) {
			print "<span class='describeValue'>True</span>";
		} else {
			print "<span class='describeValue'>False</span>";
		}
	}	
}

function highlightSpecialField( $value ) {
	// Define system fields array
	$systemFields = array("Id","IsDeleted","CreatedById","CreatedDate","LastModifiedById","LastModifiedDate","SystemModstamp");
	
	if ($_SESSION['config']['highlightSystemFields'] && in_array($value->name,$systemFields)) {
		print "<li><span class='highlightSystemField'>$value->name</span><ul style='display:none;'>\n";
	} elseif ($_SESSION['config']['highlightCustomFields'] && $value->custom) {
		print "<li><span class='highlightCustomField'>$value->name</span><ul style='display:none;'>\n";
	} else {
		print "<li>$value->name<ul style='display:none;'>\n";
	}		
}

function stringDisplay($key, $value) {
	print "<li>$key: <span class='describeValue'>$value</span></li> \n";
}
?>
<script type="text/javascript">
//ddtreemenu.createTree(treeid, enablepersist, opt_persist_in_days (default is 1))
ddtreemenu.createTree("describeTree", true);
</script>
<?php
if (isset($_REQUEST['default_object_changed']) && $_REQUEST['default_object_changed']) {
	print "<script type='text/javascript'>ddtreemenu.flatten('describeTree', 'collapse');</script>";
}
?>