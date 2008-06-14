<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<meta http-equiv="Content-Language" content="UTF-8" />
<meta http-equiv="Content-Type" content="text/xhtml; charset=UTF-8" />
<link rel="stylesheet" href="style/master.css" type="text/css" />
<link rel="Shortcut Icon" href="images/blueBox.bmp" />

<?php
preg_match('/(\w+)\.php/',basename($_SERVER['PHP_SELF']),$pageTitle);
print "<title>Workbench - " . ucwords($pageTitle[1]) . "</title>"
?>

<body>
<script type="text/javascript" src="script/wz_tooltip.js"></script>
<div id='main_block'>

<div id='setupMenu'>
	<?php
	$setupBar_items = array ();
	
	if(!isset($_SESSION['sessionId']) || 'logout.php' == basename($_SERVER['PHP_SELF'])){
		$setupBar_items['login.php'] = array('Login','Logs into your Salesforce organization');
	} else {
		$setupBar_items['logout.php'] = array('Logout','Logs out of your Salesforce organization');
	}
	$setupBar_items['settings.php'] = array('Settings','Configure the Workbench');
	$setupBar_items['about.php'] = array('About','Learn about the Workbench');
	
	foreach($setupBar_items as $href => $label){
		print "<a href='$href'";
		if (!strcmp($href,basename($_SERVER['PHP_SELF']))){
			print " style='color: #0046ad;'";
		}
		print " onmouseover=\"Tip('$label[1]')\">$label[0]</a>&nbsp;&nbsp;";
	}
	?>
</div>

<div style="clear: both; text-align: center"><p>
<!-- <img src="images/workbench_logo.png" width="446" height="90" alt="Workbench logo" border="0" /> -->
<!-- <img src="images/workbench_logo60.png" width="297" height="60" alt="Workbench logo" border="0" /> -->
<!-- <img src="images/workbench2.png" width="336" height="48" alt="Workbench 2.0 Logo" border="0" /> -->
<img src="images/workbench2-futura.png" width="289" height="43" alt="Workbench 2.0 Logo" border="0" />
</p></div>

<div id='navmenu' style="clear: both;">
	<?php
	$navbar_items = array (
//	'login.php'=>array('Login','Logs into your Salesforce organization'),
//	'select.php'=>array('Select','Selects an action to perform on an object'),
	'describe.php'=>array('Describe','Describes the attributes, fields, record types, and child relationships of an object'),
	'insert.php'=>array('Insert','Creates new records from a CSV file'),
	'upsert.php'=>array('Upsert','Creates new records and/or updates existing records from a CSV file based on a unique External Id'),
	'update.php'=>array('Update','Updates existing records from a CSV file'),
	'delete.php'=>array('Delete','Moves records listed in a CSV file to the Recycle Bin. Note, some objects cannot be undeleted'),
	'undelete.php'=>array('Undelete','Restores records listed in a CSV file from the Recycle Bin. Note, some objects cannot be undeleted.'),
	'purge.php' =>array('Purge','Permenantly deletes records listed in a CSV file from your Recycle Bin.'),
	'query.php'=>array('Query','Queries the data in your organization and displays on the screen or exports to a CSV file'),
	'search.php'=>array('Search','Search the data in your organization across multiple objects'),
//	'settings.php'=>array('Settings','Configure the Workbench'),
//	'logout.php'=>array('Logout','Logs out of your Salesforce organization')
	);
	print "| ";
	foreach($navbar_items as $href => $label){
		print "<a href='$href'";
		if (!strcmp($href,basename($_SERVER['PHP_SELF']))){
			print " style='color: #0046ad;' ";
		}
		print " onmouseover=\"Tip('$label[1]')\">$label[0]</a> | ";
	}
	?>
</div>
<p/>
<?php
global $mySforceConnection;
if (isset($_SESSION['sessionId']) && $mySforceConnection && 'logout.php' != basename($_SERVER['PHP_SELF'])){

	if(!$_SESSION['getUserInfo'] || !$_SESSION['config']['cacheGetUserInfo']){
		try{
			global $mySforceConnection;
			$_SESSION['getUserInfo'] = $mySforceConnection->getUserInfo();

		} catch (Exception $e) {
			print "<p/>";
			$errors[] = $e->getMessage();
			show_error($errors);
			include_once('footer.php');
			exit;
	    }
	}

    print "<p id='myuserinfo'>Logged in as " . $_SESSION['getUserInfo']->userFullName . " at " . $_SESSION['getUserInfo']->organizationName . "</p>\n";
}
?>
