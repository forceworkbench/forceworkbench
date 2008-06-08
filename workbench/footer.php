<?php
include_once('shared.php');
global $version;
?>
</div>

<div class='disclaimer'>

	<br/>
	Workbench v<?php echo $version; ?><br/>
	
	<?php
	//print $_SERVER[SERVER_NAME];
	if (!isset($_SERVER['HTTPS']) && $_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1' && $_SERVER['SERVER_NAME'] !== '10.8.45.11' ){
		print "<span style='font-size: 8pt; color: red;'>WARNING: Unsecure connection detected</span><br/>";
	}


	if(isset($GLOBALS['requestTimeStart'])){
		$requestTimeEnd = microtime(true);
		$requestTimeElapsed = $requestTimeEnd - $GLOBALS['requestTimeStart'];
		printf ("Requested in %01.3f sec<BR/>", $requestTimeElapsed);
	}
		
	if(stristr($version,'beta') || stristr($version,'alpha')){
		print "<br/><a href='http://groups.google.com/group/forceworkbench' target='_blank'>THANK YOU FOR BETA TESTING - PLEASE PROVIDE FEEDBACK</a>";
	}
	
	?>
</div>

</body>
</html>

<?php
//USAGE: debug($showSuperVars = true, $showSoap = true, $customName = null, $customValue = null)
debug(true,true,null,null);
?>