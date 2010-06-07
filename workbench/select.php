<?php
require_once('session.php');
require_once('shared.php');

//Has the user selected a default object and clicked one
//of the action buttons. If so, proceed to that page; otherwise,
//show the form to do so.

if (isset($_POST['actionJump']) && $_POST['actionJump'] != ""){
	$_SESSION['default_object'] = $_POST['default_object'];
	header("Location: $_POST[actionJump]");
} elseif (isset($_POST['select'])){
	include_once('header.php');
	show_error("Choose an object and an action to which to jump.");
	show_select_form();
	include_once('footer.php');;
} 

 else {
	include_once('header.php');
	show_select_form();
	include_once('footer.php');
}

function show_select_form(){
	?>
	<script>
	
	function toggleObjectSelectDisabled(){
		var actionJumpVal = document.getElementById('actionJump').value;
		
		if(actionJumpVal == 'select.php' || actionJumpVal == 'execute.php'|| actionJumpVal == 'settings.php' || actionJumpVal == 'delete.php' || actionJumpVal == 'undelete.php' || actionJumpVal == 'purge.php' || actionJumpVal == 'search.php'){
			document.getElementById('default_object').disabled = true;		
		} else {
			document.getElementById('default_object').disabled = false;			
		}
	}
	</script>
	<?php
	
	try{
	print "<form method='post' action='$_SERVER[PHP_SELF]'>\n";
	print "<p><strong>Select a default object and action:</strong></p>\n";
	
		//Display a list of actions as submit buttons. Jump to the selected
	//action's page on refresh (see IF statement at top)
	print "<p><strong>Jump to: </strong>" . 
		  "<select name='actionJump' id='actionJump' style='width: 20em;' onChange='toggleObjectSelectDisabled();'>" . 	
		  "<option value='select.php'></option>";
	foreach($GLOBALS["MENUS"] as $menu => $pages) {
		foreach($pages as $href => $page) {
			if($page->onMenuSelect) print "<option value='" . $href . "'>" . $page->title . "</option>";
		}
	}
	print "</select></p>";


	//Describe a list of all the objects in the user's org and display
	//in a drop down select box
	print "<p><strong>Object: &nbsp; </strong>";
	printObjectSelection($_SESSION['default_object'],'default_object');


	print "<p/><input type='submit' name='select' value='Select' />";
	print "</form>\n";
	} catch (Exception $e) {
		show_error($e->getMessage(),false,true);
	}
	
	print "<script>toggleObjectSelectDisabled();</script>";
}

?>
