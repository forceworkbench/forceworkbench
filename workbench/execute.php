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
	$_SESSION['LogCategory'] = "Apex_code";
	$_SESSION['LogCategoryLevel'] = "DEBUG";
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
					$LogCategory = array(
						array("Db", "Database"),
						array("Workflow", "Workflow"),
						array("Validation", "Validation"),
						array("Callout", "Callout"),
						array("Apex_code", "Apex Code"),
						array("Apex_profiling", "Apex Profiling")
					);
					
					foreach($LogCategory as $category){
						print "<option value=\"" . $category[0] . "\"";
						if($_SESSION['LogCategory'] == $category[0]){
							print " selected=\"selected\"";
						}
						print ">" . $category[1] . "</option>";
					}

				?>
			</select>
	
			&nbsp;
			
			Log Level: 
			<select id="LogCategoryLevel" name="LogCategoryLevel">
				<?php
					$LogCategoryLevel = array(
						array("ERROR", "Error"),
						array("WARN", "Warn"),
						array("INFO", "Info"),
						array("DEBUG", "Debug"),
						array("FINE", "Fine"),
						array("FINER", "Finer"),
						array("FINEST", "Finest")
					);
					
					foreach($LogCategoryLevel as $level){
						print "<option value=\"" . $level[0] . "\"";
						if($_SESSION['LogCategoryLevel'] == $level[0]){
							print " selected=\"selected\"";
						}
						print ">" . $level[1] . "</option>";
					}

				?>
			</select>
	
			&nbsp;
			
			API Version: 
			<select id="apiVersion" name="apiVersion">
				<?php
					$apiVersions = array(
						"14.0",
						"13.0",
						"12.0",
						"11.1",
						"11.0",
						"10.0",
						"9.0",
						"8.0"
					);
					
					foreach($apiVersions as $verion){
						print "<option value=\"" . $verion . "\"";
						if($_SESSION['apiVersion'] == $verion){
							print " selected=\"selected\"";
						}
						print ">" . $verion . "</option>";
					}

				?>
			</select>
			
		</td>
	  </tr>
	  <tr>
	    <td colspan="2">
			
			<textarea id='scriptInput' name='scriptInput' cols='100' rows='6' style='overflow: auto;'><? echo $_SESSION['scriptInput']; ?></textarea>
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
if(isset($_POST['execute']) && isset($_POST['scriptInput']) && $_POST['scriptInput'] != ""){
	print "<h2>Results</h2>";
	
	$apexServerUrl = str_replace("/u/","/s/",$_SESSION['location']);
	$apexServerUrl = preg_replace("/\d\d?\.\d/",$_POST['apiVersion'],$apexServerUrl);

	$apexBinding = new SforceApexClient("soapclient/sforce.140.apex.wsdl",$_SESSION['sessionId'],$apexServerUrl,$_POST['LogCategory'],$_POST['LogCategoryLevel']);
	
	try {
		$executeAnonymousResultWithDebugLog = $apexBinding->executeAnonymous($_POST['scriptInput']);
	} catch(Exception $e) {
		show_error($e->getMessage());
		continue;
	}
	
	if($executeAnonymousResultWithDebugLog->executeAnonymousResult->success){
		if(isset($executeAnonymousResultWithDebugLog->debugLog) && $executeAnonymousResultWithDebugLog->debugLog != ""){
			print('<pre>' . $executeAnonymousResultWithDebugLog->debugLog . '</pre>');
		} else {
			show_info("Execution was successful, but returned no results. Confirm debug levels.");
		}
		
	} else {
		$error;	
		
		if(isset($executeAnonymousResultWithDebugLog->executeAnonymousResult->line)){
			$error .=  "LINE: " . $executeAnonymousResultWithDebugLog->executeAnonymousResult->line;
		}
		
		if(isset($executeAnonymousResultWithDebugLog->executeAnonymousResult->column)){
			$error .=  " COL: " . $executeAnonymousResultWithDebugLog->executeAnonymousResult->column;
		}
		
		if(isset($executeAnonymousResultWithDebugLog->executeAnonymousResult->compileProblem)){
			$error .=  "\nCOMPILE ERROR: " . $executeAnonymousResultWithDebugLog->executeAnonymousResult->compileProblem;
		}
		
		if(isset($executeAnonymousResultWithDebugLog->executeAnonymousResult->exceptionMessage)){
			$error .= "\nEXCEPTION: " . $executeAnonymousResultWithDebugLog->executeAnonymousResult->exceptionMessage;
		}
		
		if(isset($executeAnonymousResultWithDebugLog->executeAnonymousResult->exceptionStackTrace)){
			$error .= "\nSTACKTRACE: " . $executeAnonymousResultWithDebugLog->executeAnonymousResult->exceptionStackTrace;
		}
		
		show_error($error);
	}
	
//	print('<pre>');
//	print_r($executeAnonymousResultWithDebugLog);
//	print('</pre>');
} else if(isset($_POST['execute']) && isset($_POST['scriptInput']) && $_POST['scriptInput'] == ""){
	show_info("Anonymous block must not be blank.");
}


require_once('footer.php');
?>
