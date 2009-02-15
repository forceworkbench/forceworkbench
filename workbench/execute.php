<?php
require_once ('session.php');
require_once ('shared.php');
require_once('header.php');
require_once ('soapclient/SforceApexClient.php');

//correction for dynamic magic quotes
if(isset($_POST['scriptInput']) && get_magic_quotes_gpc()){
	$_POST['scriptInput'] = stripslashes($_POST['scriptInput']);
}

if(isset($_POST['execute'])){
	$_SESSION['scriptInput'] = $_POST['scriptInput'];
	$_SESSION['LogCategory'] = $_POST['LogCategory'];
	$_SESSION['LogCategoryLevel'] = $_POST['LogCategoryLevel'];
	$_SESSION['apiVersion'] = $_POST['apiVersion'];
} else if(!isset($_SESSION['LogCategory']) && !isset($_SESSION['LogCategoryLevel'])){
	$_SESSION['LogCategory'] = $_SESSION['config']['defaultLogCategory'];
	$_SESSION['LogCategoryLevel'] = $_SESSION['config']['defaultLogCategoryLevel'];
	preg_match("/(\d\d?\.\d)/",$_SESSION['location'],$apiVersionCurrent);
	$_SESSION['apiVersion'] = $apiVersionCurrent[1];
}


?>
<form id="executeForm" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
	<table border="0">
	  <tr>
	    <td><strong>Enter Apex code to be executed as an anonymous block:</strong><p/></td>
	  </tr>
	  <tr>
	    <td align="right">
		    Log Category: 
			<select id="LogCategory" name="LogCategory">
				<?php		
				printSelectOptions($config['defaultLogCategory']['valuesToLabels'],$_SESSION['LogCategory']);
				?>
			</select>
	
			&nbsp;
			
			Log Level: 
			<select id="LogCategoryLevel" name="LogCategoryLevel">
				<?php
				printSelectOptions($config['defaultLogCategoryLevel']['valuesToLabels'],$_SESSION['LogCategoryLevel']);
				?>
			</select>
	
			&nbsp;
			
			Apex API Version: 
			<select id="apiVersion" name="apiVersion">
				<?php
					$apiVersions = array(
						"15.0",
						"14.0",
						"13.0",
						"12.0",
						"11.1",
						"11.0",
						"10.0",
						"9.0",
						"8.0"
					);
					
					$apiVersionMatched = false;
					foreach($apiVersions as $verion){
						print "<option value=\"" . $verion . "\"";
						if($_SESSION['apiVersion'] == $verion){
							print " selected=\"selected\"";
							$apiVersionMatched = true;
						}
						print ">" . $verion . "</option>";
					}

				?>
			</select>
			&nbsp;<img onmouseover="Tip('Apex API Version defaults to the logged in API version, but can be set independently and specifies against which version the Apex script will be compiled.')" align='absmiddle' src='images/help16.png'/>
		</td>
	  </tr>
	  <tr>
	    <td colspan="2">
			
			<textarea id='scriptInput' name='scriptInput' cols='100' rows='7' style='overflow: auto; font-family: monospace, courier;'><?php echo htmlspecialchars($_SESSION['scriptInput'],ENT_QUOTES,'UTF-8'); ?></textarea>
			<p/>
			<input type='submit' name="execute" value='Execute'/> <input type='reset' value='Reset'/>
			
		</td>
	  </tr>
	</table>
</form>


<script type="text/javascript">
 	document.getElementById('scriptInput').focus();
</script>


<?php
if(!$apiVersionMatched){
	show_info("API version used for login is not supported for Apex execution. Execute will use default Apex API version unless otherwise specified.");
}


if(isset($_POST['execute']) && isset($_POST['scriptInput']) && $_POST['scriptInput'] != ""){
	print "<h2>Results</h2>";
	
	$apexServerUrl = str_replace("/u/","/s/",$_SESSION['location']);
	$apexServerUrl = preg_replace("/\d\d?\.\d/",$_POST['apiVersion'],$apexServerUrl);

	$apexBinding = new SforceApexClient("soapclient/sforce.150.apex.wsdl",$apexServerUrl,$_POST['LogCategory'],$_POST['LogCategoryLevel']);
	
	try {
		$executeAnonymousResultWithDebugLog = $apexBinding->executeAnonymous($_POST['scriptInput']);
	} catch(Exception $e) {
		show_error($e->getMessage());
	}
	
	if($executeAnonymousResultWithDebugLog->executeAnonymousResult->success){
		if(isset($executeAnonymousResultWithDebugLog->debugLog) && $executeAnonymousResultWithDebugLog->debugLog != ""){
			print('<pre>' . htmlspecialchars($executeAnonymousResultWithDebugLog->debugLog,ENT_QUOTES,'UTF-8') . '</pre>');
		} else {
			show_info("Execution was successful, but returned no results. Confirm log category and level.");
		}
		
	} else {
		$error;	
		
		if(isset($executeAnonymousResultWithDebugLog->executeAnonymousResult->compileProblem)){
			$error .=  "COMPILE ERROR: " . $executeAnonymousResultWithDebugLog->executeAnonymousResult->compileProblem;
		}
		
		if(isset($executeAnonymousResultWithDebugLog->executeAnonymousResult->exceptionMessage)){
			$error .= "\nEXCEPTION: " . $executeAnonymousResultWithDebugLog->executeAnonymousResult->exceptionMessage;
		}
		
		if(isset($executeAnonymousResultWithDebugLog->executeAnonymousResult->exceptionStackTrace)){
			$error .= "\nSTACKTRACE: " . $executeAnonymousResultWithDebugLog->executeAnonymousResult->exceptionStackTrace;
		}
		
			
		if(isset($executeAnonymousResultWithDebugLog->executeAnonymousResult->line)){
			$error .=  "\nLINE: " . $executeAnonymousResultWithDebugLog->executeAnonymousResult->line;
		}
		
		if(isset($executeAnonymousResultWithDebugLog->executeAnonymousResult->column)){
			$error .=  " COLUMN: " . $executeAnonymousResultWithDebugLog->executeAnonymousResult->column;
		}
		
		show_error($error);
		
		print('<pre style="color: red;">' . htmlspecialchars($executeAnonymousResultWithDebugLog->debugLog,ENT_QUOTES,'UTF-8') . '</pre>');
	}
	
//	print('<pre>');
//	print_r($executeAnonymousResultWithDebugLog);
//	print('</pre>');
} else if(isset($_POST['execute']) && isset($_POST['scriptInput']) && $_POST['scriptInput'] == ""){
	show_info("Anonymous block must not be blank.");
}


require_once('footer.php');
?>
