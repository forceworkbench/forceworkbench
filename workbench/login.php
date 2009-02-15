<?php
require_once ('session.php');
require_once('shared.php');

/*
 * For auto-login by GET params, allow users to either provide un/pw or sid, and optionally serverUrl and/or api version.
 * If the serverUrl is provided, it will be used alone, but if either
 */

if((isset($_GET['un']) && isset($_GET['pw'])) || isset($_GET['sid'])){		
		
	$un       = isset($_GET['un'])       ? $_GET['un']       : null;
	$pw       = isset($_GET['pw'])       ? $_GET['pw']       : null;
	$sid      = isset($_GET['sid'])      ? $_GET['sid']      : null;
	$startUrl = isset($_GET['startUrl']) ? $_GET['startUrl'] : "select.php";
	//error handling for these (so users can't set all three
	//is already done in the process_Login() function
	//as it applies to both ui and auto-login

	//make sure the user isn't setting invalid combinations of query params
	if(isset($_GET['serverUrl']) && isset($_GET['inst']) && isset($_GET['api'])){
		
		//display UI login page with error.
		display_login("Invalid auto-login parameters. Must set either serverUrl OR inst and/or api.");
		
	} else if(isset($_GET['serverUrl']) && !(isset($_GET['inst'])  || isset($_GET['api'])) ) {
		
		$serverUrl = $_GET['serverUrl'];
		
	} else {
		
		$serverUrl = "https://";
		
		if(isset($_GET['inst'])){
			$serverUrl .= $_GET['inst'];
		} else {
			$serverUrl .= $_SESSION['config']['defaultInstance'];
		}
		
		$serverUrl .= ".salesforce.com/services/Soap/u/";
		
		if(isset($_GET['api'])){
			$serverUrl .= $_GET['api'];
		} else {
			$serverUrl .= $_SESSION['config']['defaultApiVersion'];
		}
		
	}
	
	$_REQUEST['autoLogin'] = 1;
	process_Login($un, $pw, $serverUrl, $sid, $startUrl);	
}



if(isset($_POST['login_type'])){
	if ($_POST['login_type']=='std'){
		process_login($_POST['usernameStd'], $_POST['passwordStd'], null, null, $_POST['actionJumpStd']);
	} elseif ($_POST['login_type']=='adv'){
		process_login($_POST['usernameAdv'], $_POST['passwordAdv'], $_POST['serverUrl'], $_POST['sessionId'], $_POST['actionJumpAdv']);
	} 
} else {
	display_login(null);
}

function display_login($errors){
require_once ('header.php');

//Displays errors if there are any
if (isset($errors)) {
	show_error($errors);
}

$isRemembered = "";
if (isset($_COOKIE['user'])){
	$user = $_COOKIE['user'];
	$isRemembered = "checked='checked'";
	print "<body onLoad='givePassFocus();' />";
} elseif (isset($_POST['user'])){
	$user = $_POST['user'];
	print "<body onLoad='giveUserFocus();' />";
} else {
	$user = null;
}


//Display main login form body
$defaultApiVersion = $_SESSION['config']['defaultApiVersion'];

print "<script type='text/javascript' language='JavaScript'>\n";

print "var instNumDomainMap = [];\n";
if($_SESSION['config']['fuzzyServerUrlLookup']){
	foreach($GLOBALS['config']['defaultInstance']['valuesToLabels'] as $subdomain => $instInfo){
		if(isset($instInfo[1]) && $instInfo[1] != ""){
			print "\t" . "instNumDomainMap['$instInfo[1]'] = '$subdomain';" . "\n";
		}
	}
}
print "\n";

print <<<LOGIN_FORM

function fuzzyServerUrlSelect(){
	var sid = document.getElementById('sessionId').value
	var sidIndex = sid.indexOf('00D');
		
	if(sidIndex > -1){
		var instNum = sid.substring(sidIndex + 3, sidIndex + 4);
		var instVal = instNumDomainMap[instNum];
		if(instVal != null){
			document.getElementById('inst').value = instVal;
			build_location();
		}
	}
}

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

function checkCaps( pwcapsDivId, e ) {
    var key = 0;
    var shifted = false;

    // IE
    if ( document.all ) {
		key = e.keyCode;
    // Everything else
    } else {
		key = e.which;
    }

    shifted = e.shiftKey;

    var pwcaps = document.getElementById(pwcapsDivId);

    var upper = (key >= 65 && key <= 90);
    var lower = (key >= 97 && key <= 122);
    
    if ( (upper && !shifted) || (lower && shifted) ) {
		pwcaps.style.visibility='visible';
    } else if ( (lower && !shifted) || (upper && shifted) ) {
		pwcaps.style.visibility='hidden';
		
    }
}

</script>

<div id='intro_text'>
	&nbsp;<!--<p>Use the standard login to login with your salesforce.com username and password to your default instance or use the advanced
	login for other login options. Go to Settings for more login configurations.</p>-->
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
			<p><strong>Password: </strong><input type='password' name='passwordStd'  id='password' size='45' onkeypress="checkCaps('pwcapsStd',event);" /></p>
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
			<p  style='text-align: right;'><span id='pwcapsStd' style='visibility: hidden; color: red; font-weight: bold; margin-right: 30px;'>Caps lock is on!</span><label><input type='checkbox' name='rememberUser' $isRemembered />Remember username</label></p>
		</div>

		<div id='login_adv' style='display: none;'>
			<p><strong>Username: </strong><input type='text' name='usernameAdv' id='usernameAdv' size='65' value='$user' onkeyup='toggleUsernamePasswordSessionDisabled();' onchange='toggleUsernamePasswordSessionDisabled();' /></p>
			<p><strong>Password: </strong><input type='password' name='passwordAdv' id='passwordAdv' size='65' onkeyup='toggleUsernamePasswordSessionDisabled();' onchange='toggleUsernamePasswordSessionDisabled();'  onkeypress="checkCaps('pwcapsAdv',event);"/></p>
			<p>-OR-<span id='pwcapsAdv' style='visibility: hidden; color: red; font-weight: bold; margin-left: 75px;'>Caps lock is on!</span></p>
			<p><strong>Session ID: </strong><input type='text' name='sessionId' id='sessionId' size='65' onkeyup='toggleUsernamePasswordSessionDisabled(); fuzzyServerUrlSelect();' onchange="toggleUsernamePasswordSessionDisabled(); fuzzyServerUrlSelect();"/></p>
			<p>&nbsp;</p>
			<p><strong>Server URL: </strong><input type='text' name='serverUrl' id='serverUrl' size='65' value='https://www.salesforce.com/services/Soap/u/$defaultApiVersion' /></p>
			<p><strong>QuickSelect: </strong>
LOGIN_FORM;
			
			print "<select name='inst' id='inst' onChange='build_location();'>";
			
			$instanceNames = array();
			foreach($GLOBALS['config']['defaultInstance']['valuesToLabels'] as $subdomain => $instInfo){
				$instanceNames[$subdomain] = $instInfo[0];
			}
			
			printSelectOptions($instanceNames,$_SESSION['config']['defaultInstance']);
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
if(isset($_GET['adv']) && $_GET['adv'] != 0){
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
		$wsdl = 'soapclient/sforce.150.partner.wsdl';
		$mySforceConnection = new SforcePartnerClient();
	    $mySforceConnection->createConnection($wsdl);
	    
	    //set call options header for login before a session exists
		if(isset($_GET['clientId'])){
			$mySforceConnection->setCallOptions(new CallOptions($_GET['clientId'], $_SESSION['config']['callOptions_defaultNamespace']));
			
		} else if($_SESSION['config']['callOptions_client'] || $_SESSION['config']['callOptions_defaultNamespace']){
			$mySforceConnection->setCallOptions(new CallOptions($_SESSION['config']['callOptions_client'], $_SESSION['config']['callOptions_defaultNamespace']));
		}

		//set login scope header for login before a session exists
		if(isset($_GET['orgId']) || isset($_GET['portalId'])){
			$mySforceConnection->setLoginScopeHeader(new LoginScopeHeader($_GET['orgId'], $_GET['portalId']));	
				
		} else if($_SESSION['config']['loginScopeHeader_organizationId'] || $_SESSION['config']['loginScopeHeader_portalId']){
			$mySforceConnection->setLoginScopeHeader(new LoginScopeHeader($_SESSION['config']['loginScopeHeader_organizationId'], $_SESSION['config']['loginScopeHeader_portalId']));
		}		

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

		if($_REQUEST['autoLogin'] == 1){
			$actionJump .= "?autoLogin=1";
			$_SESSION['tempClientId'] = $_GET['clientId'];
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



?>

