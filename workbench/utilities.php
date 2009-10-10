<?php
require_once ('session.php');
require_once ('shared.php');
require_once ('header.php');
?>

<h2>Utilities</h2>

<ul>
	<?php 
	foreach($GLOBALS["PAGES"] as $filename => $page){
		if($page->onMenuUtilities) print "<li><a href='" . $filename . "'>" . $page->title . "</a></li>";
	} 
	?>
</ul>

	
<?php
require_once ('footer.php');
?>