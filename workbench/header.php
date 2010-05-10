<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<meta http-equiv="Content-Language" content="UTF-8" />
<meta http-equiv="Content-Type" content="text/xhtml; charset=UTF-8" />
<link rel="stylesheet" href="style/master.css" type="text/css" />
<link rel="Shortcut Icon" href="images/blueBox.bmp" />
</head>

<?php
print "<title>Workbench - " . $GLOBALS["PAGES"][basename($_SERVER['PHP_SELF'])]->title  . "</title>"
?>

<body>
<script type="text/javascript" src="script/wz_tooltip.js"></script>
<?php
if($_SESSION['config']['areTablesSortable'] && (basename($_SERVER['PHP_SELF'])=="query.php" || basename($_SERVER['PHP_SELF'])=="search.php")){
	print "<script type='text/javascript' src='script/sortable.js'></script>";	
} 

//check for latest version
if(!isset($_GET['skipVC']) && (isset($_GET['autoLogin']) || 'login.php'==basename($_SERVER['PHP_SELF']))){
	try{
		if(extension_loaded('curl')){
			$ch = curl_init();
			if(stristr($GLOBALS["WORKBENCH_VERSION"],'beta')){
				curl_setopt ($ch, CURLOPT_URL, 'http://forceworkbench.sourceforge.net/latestVersionAvailableBeta.txt');
			} else {
				curl_setopt ($ch, CURLOPT_URL, 'http://forceworkbench.sourceforge.net/latestVersionAvailable.txt');
			}
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$latestVersionAvailable = trim(curl_exec($ch));
			curl_close($ch);

			if (preg_match('/^[0-9]+.[0-9]+/',$latestVersionAvailable) && !stristr($GLOBALS["WORKBENCH_VERSION"],'trunk') && !stristr($GLOBALS["WORKBENCH_VERSION"],'alpha') && !stristr($GLOBALS["WORKBENCH_VERSION"],'i')){
				if($latestVersionAvailable != $GLOBALS["WORKBENCH_VERSION"]){
					print "<div style='background-color: #EAE9E4; width: 100%; padding: 2px;'><a href='http://code.google.com/p/forceworkbench/' target='_blank' style='font-size: 8pt; font-weight: bold; color: #0046ad;'>A newer version of Workbench is available for download</a></div><br/>";
				}
			}
		}
	} catch (Exception $e){
		//do nothing
	}
}

?>


<div id='main_block'>

<div id='setupMenu'>
	<?php

	global $mySforceConnection;
	if (isset($_SESSION['sessionId']) && $mySforceConnection && 'logout.php' != basename($_SERVER['PHP_SELF'])){
	
		if(!isset($_SESSION['getUserInfo']) || !$_SESSION['config']['cacheGetUserInfo']){
			try{
				global $mySforceConnection;
				$_SESSION['getUserInfo'] = $mySforceConnection->getUserInfo();
	
			} catch (Exception $e) {
				$errors[] = $e->getMessage();
				
				session_unset();
				session_destroy();
		    }
		}
	
	    
	}

	foreach($GLOBALS["PAGES"] as $href => $page){
		if(!$page->onMenuSetup) continue;
		
		if($href == "login.php" && isset($_SESSION['sessionId']) && basename($_SERVER['PHP_SELF']) != 'logout.php'){
			continue; //don't print Login
		} 
		
		if($href == "logout.php" && (!isset($_SESSION['sessionId']) || basename($_SERVER['PHP_SELF']) == 'logout.php')) {
			continue; //don't print Logout
		}
		
		print "<a href='$href'";
		if (!strcmp($href,basename($_SERVER['PHP_SELF']))){
			print " style='color: #0046ad;'";
		}
		print " onmouseover=\"Tip('$page->desc')\">$page->title</a>&nbsp;&nbsp;";
	}
	?>
	
</div>

<div style="clear: both; text-align: center"><p>
	<img src="images/workbench-2-squared.png" width="257" height="50" alt="Workbench 2 Logo" border="0" /></p>
</div>

<div id='navmenu' style="clear: both;">| 
	<?php
	foreach($GLOBALS["PAGES"] as $href => $page){
		if(!$page->onMenuMain) continue;
		print "<a href='$href'";
		if (!strcmp($href,basename($_SERVER['PHP_SELF']))){
			print " style='color: #0046ad;' ";
		}
		print " onmouseover=\"Tip('$page->desc')\">$page->title</a> | \n";
	}
	?>
</div>
<p/>

<?php
if(isset($_SESSION['getUserInfo'])){ 
	print "<p id='myuserinfo'>Logged in as " . $_SESSION['getUserInfo']->userFullName . " at " . $_SESSION['getUserInfo']->organizationName . " on API " . getApiVersion() . "</p>\n";
}

if(isset($errors)){
	print "<p/>";
	show_error($errors, false, true);
}

?>