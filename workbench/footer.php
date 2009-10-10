<?php
include_once('shared.php');
?>
</div>

<div id='disclaimer'>
	<br/>
	
	<?php
	//print $_SERVER[SERVER_NAME];
	if(isset($_SESSION["config"]["checkSSL"]) && $_SESSION["config"]["checkSSL"] == true){
		//is connection unsecure from this machine to Workbench?
		$unsecureLocal2Wb = !isset($_SERVER['HTTPS']) && $_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1' && $_SERVER['SERVER_NAME'] !== 'workbench';
		
		//is connection unsecure from Workbench to Salesforce?
		$unsecureWb2sfdc = isset($_SESSION['location']) && !strstr($_SESSION['location'],'https');
		
		if ($unsecureLocal2Wb || $unsecureWb2sfdc){
			print "<span style='font-size: 8pt; color: red;'>WARNING: Unsecure connection detected";
			
			if($unsecureLocal2Wb) print " to Workbench";
			if($unsecureLocal2Wb && $unsecureWb2sfdc) print " and";
			if($unsecureWb2sfdc) print " to Salesforce";

			print "</span><br/>";
		}
	}

	if(isset($GLOBALS['requestTimeStart']) && isset($_SESSION["config"]["displayRequestTime"]) && $_SESSION["config"]["displayRequestTime"]){
		$requestTimeEnd = microtime(true);
		$requestTimeElapsed = $requestTimeEnd - $GLOBALS['requestTimeStart'];
		printf ("Requested in %01.3f sec<BR/>", $requestTimeElapsed);
	}
		
	print "Workbench<a href='utilities.php' style='text-decoration:none'> </a>v" . $GLOBALS["WORKBENCH_VERSION"] . "<br/>\n";

	
//	if(stristr($GLOBALS["WORKBENCH_VERSION"],'beta') || stristr($GLOBALS["WORKBENCH_VERSION"],'alpha')){
//		print "<br/><a href='http://groups.google.com/group/forceworkbench' target='_blank'>THANK YOU FOR BETA TESTING - PLEASE PROVIDE FEEDBACK</a>";
//	}
	
	?>
</div>

</body>
</html>

<?php
//USAGE: debug($showSuperVars = true, $showSoap = true, $customName = null, $customValue = null)
debug(true,true,null,null);
?>