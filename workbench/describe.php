<?php
require_once('session.php');
require_once('shared.php');
require_once ('header.php');

//Main Form Logic: If there is a default object in the session,
//describe it when the page loads; else, prompt to choose one.
//Note: The POSTed default object is passed to the SESSION default object
//in the session.php include

show_describeSObject_form();
if (isset($_SESSION['default_object']) && "" !== $_SESSION['default_object']){
	show_describeSObject_result();
}
require_once ('footer.php');


//Print a form with the global object types to choose for description
function show_describeSObject_form(){
	?>	
	<script type="text/javascript" src="script/simpletreemenu.js">
	/***********************************************
	* Simple Tree Menu - Dynamic Drive DHTML code library (www.dynamicdrive.com)
	* This notice MUST stay intact for legal use
	* Visit Dynamic Drive at http://www.dynamicdrive.com/ for full source code
	***********************************************/
	</script>
	<link rel="stylesheet" type="text/css" href="style/simpletree.css" />
	<?php
	
	print "<form name='describeForm' method='post' action='$_SERVER[PHP_SELF]'>" .
		  "<p class='instructions'>Choose an object to describe:</p>\n";
	printObjectSelection($_SESSION['default_object'], 'default_object', 30, "onChange='document.describeForm.submit();'");
	print  "</form>";
}



//Print the description of selected/default object type in multiple tables
function show_describeSObject_result(){
		try{
			//Ping Apex API
			$describeSObject_result = describeSObject($_SESSION['default_object']);
		} catch (Exception $e) {
			show_error($e->getMessage(), false, true);
    	}
		
		if(isset($_SESSION['config']['colorBooleanValues']) && $_SESSION['config']['colorBooleanValues'] || 
		   isset($_SESSION['config']['highlightCustomFields']) && $_SESSION['config']['highlightCustomFields'] || 
		   isset($_SESSION['config']['highlightSystemFields']) && $_SESSION['config']['highlightSystemFields']){
		   	
			print "<div style='float: right; border:1px solid #bbb; padding:0.5em; margin-right:1em;'>" .
		   	      "<strong>Legend:</strong>" . 
			      "<ul style='margin:0; padding-left: 2em'>";
			if($_SESSION['config']['highightBooleanValues']){
				print "<li class=\"trueColor\">True</span>\n";
				print "<li class=\"falseColor\">False</span>\n";
			} 
			if($_SESSION['config']['highlightCustomFields']){
				print "<li class=\"highlightCustomField\">Custom Field</li>\n";
			}
			if ($_SESSION['config']['highlightSystemFields']) {
				print "<li class=\"highlightSystemField\">System Field</li>\n";
			} 
			print "</ul>" . 
			      "</div>";
		}

		print "<br/><a href=\"javascript:ddtreemenu.flatten('describeTree', 'expand')\">Expand All</a> | <a href=\"javascript:ddtreemenu.flatten('describeTree', 'collapse')\">Collapse All</a>\n";
		print "<ul id='describeTree' class='treeview'>\n";


		print "<li>Attributes<ul>\n";
		foreach($describeSObject_result as $key => $value){
			//Change bool data to printed as TRUE and FALSE for visibility in table
			if (is_bool($value)){
				print "<li>$key: ";
				booleanDisplay($value);
				print "</li> \n";
			} elseif(is_string($value) || is_numeric($value)) {
				stringDisplay($key, $value);
			}
		}
		print "</ul></li>\n"; ///end attributes node

		print "<li>Fields (" . count($describeSObject_result->fields) . ")<ul>\n";
		foreach($describeSObject_result->fields as $key => $value){
			highlightSpecialField($value);
			foreach($value as $subkey => $subvalue){
				//Change bool data to printed as TRUE and FALSE for visibility in table
				if (is_bool($subvalue)){
					print "<li>$subkey: ";
					booleanDisplay($subvalue);
					print "</li> \n";
				} 
				//Because picklist are deeper in the SOAP message,
				//it requires more nested foreach loops
				elseif ($subkey == 'picklistValues'){
					if(!is_array($subvalue)) $subvalue = array($subvalue);
					print "<li>$subkey (" . count($subvalue) . ")<ul>\n";
					foreach($subvalue as $subsubkey => $subsubvalue){
						if($value->name == "Division") print "<li>$subsubvalue->label<ul>\n";
						else print  "<li>$subsubvalue->value<ul>\n";
						foreach($subsubvalue as $subsubsubkey => $subsubsubvalue){
							if (is_bool($subsubsubvalue)){
								print "<li>$subsubsubkey: ";
								booleanDisplay($subsubsubvalue);
								print "</li> \n";
							} elseif(is_string($subsubsubvalue) || is_numeric($subsubsubvalue)) {
								stringDisplay( $subsubsubkey, $subsubsubvalue);
							}
						}
						print "</ul></li>\n"; //end one picklist node
					}
					print "</ul></li>\n"; //end picklist node
				} elseif ($subkey == 'referenceTo'){ //do this for referenceTo arrays 
					if(is_array($subvalue)) {
						print "<li>$subkey<ul>\n";
						foreach($subvalue as $subsubkey => $subsubvalue){
							print  "<li><strong>$subsubvalue</strong></li>\n";
						}
						print "</ul></li>\n"; //end referenceTo node
					} elseif(is_string($subvalue) || is_numeric($subvalue)){
						stringDisplay($subkey, $subvalue);
					}
				} elseif(is_string($subvalue) || is_numeric($subvalue)){
					stringDisplay($subkey, $subvalue);
				}
			}
			print "</ul></li>\n"; ///end one field node
		}
		print "</ul></li>\n"; ///end fields node



		//Print Record Types, if they exists
		if (isset($describeSObject_result->recordTypeInfos)){
			if(!is_array($describeSObject_result->recordTypeInfos)) $describeSObject_result->recordTypeInfos = array($describeSObject_result->recordTypeInfos);
			print "<li>Record Types (" . count($describeSObject_result->recordTypeInfos) . ")<ul>\n";
			foreach($describeSObject_result->recordTypeInfos as $key => $value){
				if(isset($value->name)){
					print "<li>$value->name<ul>\n";
					foreach($value as $subkey => $subvalue){
						if (is_string($subvalue) || is_numeric($subvalue)){
							stringDisplay( $subkey, $subvalue);
						} elseif (is_bool($subvalue)){
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
		if (isset($describeSObject_result->childRelationships)){
			if(!is_array($describeSObject_result->childRelationships)) $describeSObject_result->childRelationships = array($describeSObject_result->childRelationships);
			print "<li>Child Relationships (" . count($describeSObject_result->childRelationships) . ")<ul>\n";
			foreach($describeSObject_result->childRelationships as $key => $value){
				print "<li>$value->childSObject<ul>\n";
				foreach($value as $subkey => $subvalue){
					if (is_string($subvalue) || is_numeric($subvalue)){
						stringDisplay( $subkey, $subvalue);
					} elseif (is_bool($subvalue)){
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
	if($_SESSION['config']['highightBooleanValues']){
		if ($value){
			print "<span class='describeValue trueColor'>True</span>";
		} else {
			print "<span class='describeValue falseColor'>False</span>";
		}			
	}
	else {
		if ($value){
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
		print "<li><span class='highlightSystemField'>$value->name</span><ul>\n";
	} 
	elseif($_SESSION['config']['highlightCustomFields'] && $value->custom){
		print "<li><span class='highlightCustomField'>$value->name</span><ul>\n";
	} 
	else {
		print "<li>$value->name<ul>\n";
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
if(isset($_REQUEST['default_object_changed']) && $_REQUEST['default_object_changed']){
	print "<script type='text/javascript'>ddtreemenu.flatten('describeTree', 'collapse');</script>";
}
?>