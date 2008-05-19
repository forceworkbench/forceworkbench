<?php
include_once('shared.php');
?>
</div>

<div class='disclaimer'>

	<br/>
	Workbench v<?php echo $GLOBALS['version']; ?> <a href='about.php'>About</a><br/>


	<?php
	//print $_SERVER[SERVER_NAME];
	if (!$_SERVER[HTTPS] && $_SERVER[SERVER_NAME] !== 'localhost' && $_SERVER[SERVER_NAME] !== '127.0.0.1' && $_SERVER[SERVER_NAME] !== '10.8.45.11' ){
		print "<span style='font-size: 8pt; color: red;'>WARNING: Unsecure connection detected</span><br/>";
	}
	
	
	
	print date("Y/m/d H:i:s",$_SERVER[REQUEST_TIME]); ?>
</div>

</body>
</html>

<?php
//USAGE: debug($showSuperVars = true, $showSoap = true, $customName = null, $customValue = null)
debug(true,true,null,null);
?>