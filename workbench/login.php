<?php
//session_start();
require_once ('session.php');
require_once('shared.php');


if(isset($_GET['serverUrl']) && isset($_GET['sid'])){		//simulate adv login from url query params for web tab use
	$_POST['serverUrl'] = $_GET['serverUrl'];
	$_POST['sessionId'] = $_GET['sid'];
	$_POST['login_type'] = "adv";
	$_POST['actionJumpAdv'] = "select.php";
}

if(isset($_GET['un']) && isset($_GET['pw'])){		//simulate std login from un/pw query params for web tab use
	$_POST['usernameStd'] = $_GET['un'];
	$_POST['passwordStd'] = $_GET['pw'];
	$_POST['login_type'] = "std";
	$_POST['actionJumpStd'] = "select.php";
}

if(isset($_POST['login_type'])){
	if ($_POST['login_type']=='std'){
		process_login($_POST['usernameStd'], $_POST['passwordStd'], null, null, $_POST['actionJumpStd']);
	} elseif ($_POST['login_type']=='adv'){
		process_login($_POST['usernameAdv'], $_POST['passwordAdv'], $_POST['serverUrl'], $_POST['sessionId'], $_POST['actionJumpAdv']);
	} 
} else {
	checkLatestVersion();
	display_login(null);
}

function display_login($errors){
require_once ('header.php');

//Displays errors if there are any
if (isset($errors)) {
	show_error($errors);
}

if (isset($_COOKIE['user'])){
	$user = $_COOKIE['user'];
	$isRemembered = "checked='checked'";
	print "<body onLoad='givePassFocus();' />";
} elseif (isset($_POST['user'])){
	$user = $_POST['user'];
	$isRemembered = NULL;
	print "<body onLoad='giveUserFocus();' />";
} else {
	$user = null;
}


//Display main login form body
$defaultApiVersion = $_SESSION['config']['defaultApiVersion'];
print <<<LOGIN_FORM

<script type='text/javascript' language='JavaScript'>

function toggleUsernamePasswordSessionDisabled(){
	if(document.getElementById('sessionId').value){
		document.getElementById('usernameAdv').disabled = true;
		document.getElementById('passwordAdv').disabled = true;
	} else {
		document.getElementById('usernameAdv').disabled = false;
		document.getElementById('passwordAdv').disabled = false;
	}

	if(document.getElementById('usernameAdv').value || document.getElementById('passwordAdv').value){
		document.getElementById('sessionId').disabled = true;
	} else {
		document.getElementById('sessionId').disabled = false;
	}

}


function form_become_adv() {
	document.getElementById('login_std').style.display='none';
	//document.getElementById('apexLogo').style.display='none';
	document.getElementById('login_adv').style.display='inline';
}

function form_become_std() {
	document.getElementById('login_std').style.display='inline';
	//document.getElementById('apexLogo').style.display='inline'
	document.getElementById('login_adv').style.display='none';
}

function build_location(){
	var inst = document.getElementById('inst').value;
	var endp = document.getElementById('endp').value;
	document.getElementById('serverUrl').value = 'https://' + inst + '.salesforce.com/services/Soap/u/' + endp;
}

function giveUserFocus(){
	document.getElementById('username').focus();
}

function givePassFocus(){
	document.getElementById('password').focus();
}

</script>

<div id='intro_text'>
	<p>Use the standard login to login with your salesforce.com username and password or use the advanced
	   login to login with a valid salesforce.com session ID or to a specific API version:</p>
</div>

<div id='logo_block'>
	<!--<img id='apexLogo' src='images/appex_x_rgb.png' width='200' height='171' border='0' alt='Apex X Logo' />-->
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
</div>

<div id='login_block'>
	<form id='login_form' action='$_SERVER[PHP_SELF]' method='post'>
		<div id='login_become_select' style='text-align: right;'>
			<input type='radio' id='login_become_std' name='login_type' value='std' onClick='form_become_std();' checked='true' /><label for='login_become_std'>Standard</label>
			<input type='radio' id='login_become_adv' name='login_type' value='adv' onClick='form_become_adv();' /><label for='login_become_adv'>Advanced</label>
		</div>

		<div id='login_std'>
			<p><strong>Username: </strong><input type='text' name='usernameStd' id='username' size='45' value='$user' /></p>
			<p><strong>Password: </strong><input type='password' name='passwordStd'  id='password' size='45' /></p>
			<p><strong>Jump to: </strong>
			<select name='actionJumpStd' style='width: 24em;'>
				<option value='select.php'></option>
				<option value='describe.php'>Describe</option>
				<option value='insert.php'>Insert</option>
				<option value='upsert.php'>Upsert</option>
				<option value='update.php'>Update</option>
				<option value='delete.php'>Delete</option>
				<option value='undelete.php'>Undelete</option>
				<option value='purge.php'>Purge</option>
				<option value='query.php'>Query</option>
				<option value='search.php'>Search</option>
				<option value='execute.php'>Execute</option>
				<option value='settings.php'>Settings</option>
			</select></p>
			<p  style='text-align: right;'><label><input type='checkbox' name='rememberUser' $isRemembered />Remember username</label></p>
		</div>

		<div id='login_adv' style='display: none;'>
			<p><strong>Username: </strong><input type='text' name='usernameAdv' id='usernameAdv' size='65' value='$user' onkeyup='toggleUsernamePasswordSessionDisabled();' /></p>
			<p><strong>Password: </strong><input type='password' name='passwordAdv' id='passwordAdv' size='65' onkeyup='toggleUsernamePasswordSessionDisabled();' /></p>
			<p>-OR-</p>
			<p><strong>Session ID: </strong><input type='text' name='sessionId' id='sessionId' size='65' onkeyup='toggleUsernamePasswordSessionDisabled();' /></p>
			<p>&nbsp;</p>
			<p><strong>Server URL: </strong><input type='text' name='serverUrl' id='serverUrl' size='65' value='https://www.salesforce.com/services/Soap/u/$defaultApiVersion' /></p>
			<p><strong>QuickSelect: </strong>
LOGIN_FORM;
			
			print "<select name='inst' id='inst' onChange='build_location();'>";
			printSelectOptions($GLOBALS['config']['defaultInstance']['valuesToLabels'],$_SESSION['config']['defaultInstance']);
			print "</select>";

			print "<select name='endp' id='endp' onChange='build_location();'>";
			printSelectOptions($GLOBALS['config']['defaultApiVersion']['valuesToLabels'],$_SESSION['config']['defaultApiVersion']);	
			print "</select></p>";
			
			
print <<<LOGIN_FORM_PART_2
			<p><strong>Jump to: </strong>
			<select name='actionJumpAdv' style='width: 14em;'>
				<option value='select.php'></option>
				<option value='describe.php'>Describe</option>
				<option value='insert.php'>Insert</option>
				<option value='upsert.php'>Upsert</option>
				<option value='update.php'>Update</option>
				<option value='delete.php'>Delete</option>
				<option value='undelete.php'>Undelete</option>
				<option value='purge.php'>Purge</option>
				<option value='query.php'>Query</option>
				<option value='search.php'>Search</option>
				<option value='execute.php'>Execute</option>
				<option value='settings.php'>Settings</option>
			</select></p>
		</div>

		<div id='login_submit' style='text-align: right;'>
			<input type='submit' name='loginClick' value='Login'>
		</div>

	</form>
</div>
LOGIN_FORM_PART_2;


//if 'adv' is added to the login url and is not 0, default to advanced login
if(isset($_GET[adv]) && $_GET[adv] != 0){
	print "<script>
				document.getElementById('login_become_adv').checked=true; 
				form_become_adv(); 
			</script>";
	
}

include_once ('footer.php');



} //end display_form()


function process_Login($username, $password, $serverUrl, $sessionId, $actionJump){
	$username = htmlspecialchars(trim($username));
	$password = htmlspecialchars(trim($password));
	$serverUrl = htmlspecialchars(trim($serverUrl));
	$sessionId = htmlspecialchars(trim($sessionId));
	$actionJump = htmlspecialchars(trim($actionJump));

	if($_POST['rememberUser'] !== 'on') setcookie('user',NULL,time()-3600);

	if ($username && $password && $sessionId){
		$errors = null;
		$errors = 'Provide only username and password OR session id, but not all three.';
		display_login($errors);
		exit;
	}


	try{
		require_once ('soapclient/SforcePartnerClient.php');
		require_once ('soapclient/SforceHeaderOptions.php');
		$wsdl = 'soapclient/sforce.140.partner.wsdl';
		$mySforceConnection = new SforcePartnerClient();
	    $mySforceConnection->createConnection($wsdl);

	    if($username && $password && !$sessionId){
	    	if($serverUrl){
	    		$mySforceConnection->setEndpoint($serverUrl);
	    	} else {
	    		$mySforceConnection->setEndpoint("https://" . $_SESSION['config']['defaultInstance'] . ".salesforce.com/services/Soap/u/" . $_SESSION['config']['defaultApiVersion']);
	    	}
			$mySforceConnection->login($username, $password);
		} elseif ($sessionId && $serverUrl && !($username && $password)){
			if (stristr($serverUrl,'www') || stristr($serverUrl,'test') || stristr($serverUrl,'prerellogin')) {
				$errors = null;
				$errors = 'Must not connect to login server (www, test, or prerellogin) if providing a session id. Choose your specific Salesforce instance on the QuickSelect menu when using a session id; otherwise, provide a username and password and choose the appropriate a login server.';
				display_login($errors);
				exit;
			}
			$mySforceConnection->setEndpoint($serverUrl);
	    	$mySforceConnection->setSessionHeader($sessionId);
		}

		session_unset();
		session_destroy();
		session_start();
		    $_SESSION['location'] = $mySforceConnection->getLocation();
		    $_SESSION['sessionId'] = $mySforceConnection->getSessionId();
		    $_SESSION['wsdl'] = $wsdl;
			if($_POST['rememberUser'] == 'on'){
				 setcookie('user',$username,time()+60*60*24*7,'','','',TRUE);
			} else {
				setcookie('user',NULL,time()-3600);
			}
		session_write_close();

		header("Location: $actionJump");

	} catch (Exception $e) {
		$errors = null;
		$errors = $e->getMessage();
		display_login($errors);
		exit;
	}

}

function checkLatestVersion(){
	global $version;
	try{
		if(extension_loaded('curl')){
			$ch = curl_init();
			if(stristr($version,'beta')){
				curl_setopt ($ch, CURLOPT_URL, 'http://forceworkbench.sourceforge.net/latestVersionAvailableBeta.txt');
			} else {
				curl_setopt ($ch, CURLOPT_URL, 'http://forceworkbench.sourceforge.net/latestVersionAvailable.txt');
			}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$latestVersionAvailable = trim(curl_exec($ch));
			curl_close($ch);

			if (preg_match('/^[0-9]+.[0-9]+/',$latestVersionAvailable) && !stristr($version,'alpha')){
				if($latestVersionAvailable != $version){
					print "<span style='font-size: 8pt; font-weight: bold;'><a href='http://code.google.com/p/forceworkbench/'>A newer version of the Workbench is available for download</a></span><br/>";
				}
			}
		}
	} catch (Exception $e){
		//do nothing
	}
}

?>

