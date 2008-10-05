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
	$_SESSION['debugLevel'] = $_POST['debugLevel'];
}

?>
<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
	<table border="0">
	  <tr>
	    <td>Enter Apex code to be executed as an anonymous block:</td>
	    <td align="right">
		    Debug Level: 
			<select id="debugLevel" name="debugLevel">
				<?php
					$debugLevels = array(
						array("Debugonly", "Debug Only"),
						array("Db", "Database"),
						array("Profiling", "Profiling"),
						array("Callout", "Callout"),
						array("Detail", "Detail"),
						array("None", "None")
					);
					
					foreach($debugLevels as $level){
						print "<option value=\"" . $level[0] . "\"";
						if($_SESSION['debugLevel'] == $level[0]){
							print " selected=\"selected\"";
						}
						print ">" . $level[1] . "</option>";
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





<?php
if(isset($_POST['execute']) && isset($_POST['scriptInput']) && $_POST['scriptInput'] != ""){
	print "<h2>Results</h2>";
	
	$apexServerUrl = str_replace("/u/","/s/",$_SESSION['location']);	
	$apexBinding = new SforceApexClient("soapclient/sforce.110.apex.wsdl",$_SESSION['sessionId'],$apexServerUrl,$_POST['debugLevel']);
	
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
