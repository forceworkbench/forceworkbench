<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<meta http-equiv="Content-Language" content="UTF-8" />
<meta http-equiv="Content-Type" content="text/xhtml; charset=UTF-8" />
<link rel="stylesheet" href="style/master.css" type="text/css" />
<link rel="Shortcut Icon" href="images/apex_x_icon.bmp" />


<title>Workbench</title>

<body>
<script type="text/javascript" src="script/wz_tooltip.js"></script>
<div id='main_block'>
<div id='navmenu' class='clear_both'>
	<img src="images/workbench_logo.png" width="446" height="90" alt="Workbench logo" border="0" />
	<p>
	<?php
	$navbar_items = array (
	'login.php'=>array('Login','Logs into your Salesforce organization'),
	'select.php'=>array('Select','Selects an action to perform on an object'),
	'describe.php'=>array('Describe','Describes the attributes, fields, record types, and child relationships of an object'),
	'insert.php'=>array('Insert','Creates new records from a CSV file'),
	'upsert.php'=>array('Upsert','Creates new records and/or updates existing records from a CSV file based on a unique External Id'),
	'update.php'=>array('Update','Updates existing records from a CSV file'),
	'delete.php'=>array('Delete','Moves records listed in a CSV file to the Recycle Bin. Note, some objects cannot be undeleted'),
	'undelete.php'=>array('Undelete','Restores records listed in a CSV file from the Recycle Bin. Note, some objects cannot be undeleted.'),
	'purge.php' =>array('Purge','Permenantly deletes records listed in a CSV file from your Recycle Bin.'),
	'export.php'=>array('Export','Queries the data in your organization and displays on the screen or exports to a CSV file'),
	'settings.php'=>array('Settings','Configure the Workbench'),
	'logout.php'=>array('Logout','Logs out of your Salesforce organization')
	);
	print "| ";
	foreach($navbar_items as $href => $label){
		print "<a href='$href'";
		if (!strcmp($href,basename($_SERVER[PHP_SELF]))){
			print " style='color: red;' ";
		}
		print " onmouseover=" . '"' . "Tip('$label[1]')". '"' . ">$label[0]</a> | ";
	}
	?>
	</p>
</div>
<?php
global $mySforceConnection;
if ($_SESSION[sessionId] && $mySforceConnection){
	try{
		global $mySforceConnection;
		$myUserInfo = $mySforceConnection->getUserInfo();
		print "<p id='myuserinfo'>Logged in as $myUserInfo->userFullName at $myUserInfo->organizationName</p>\n";
	} catch (Exception $e) {
      	$errors = null;
		$errors = $e->getMessage();
		show_error($errors);
		exit;
    }
}
?>
