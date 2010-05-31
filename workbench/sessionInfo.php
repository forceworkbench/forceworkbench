<?php
require_once ('session.php');
require_once ('shared.php');
require_once ('header.php');
?>	
<script type="text/javascript" src="script/simpletreemenu.js">
/***********************************************
* Simple Tree Menu - Dynamic Drive DHTML code library (www.dynamicdrive.com)
* This notice MUST stay intact for legal use
* Visit Dynamic Drive at http://www.dynamicdrive.com/ for full source code
***********************************************/
</script>
<link rel="stylesheet" type="text/css" href="style/simpletree.css" />
<?php

print "<p/>" .
      "<a href=\"javascript:ddtreemenu.flatten('sessionInfoTree', 'expand')\">Expand All</a> | <a href=\"javascript:ddtreemenu.flatten('sessionInfoTree', 'collapse')\">Collapse All</a>\n" .
      "<ul id='sessionInfoTree' class='treeview'>";

print "<li>User Info<ul>\n";
foreach($_SESSION['getUserInfo'] as $uiKey => $uiValue) {
	print "<li>$uiKey: <span style='font-weight:bold;'>$uiValue</span></li>\n";
}
print "</ul></li>\n"; 

require_once ('footer.php');
?>
<script type="text/javascript">
ddtreemenu.createTree("sessionInfoTree", true);
ddtreemenu.flatten('sessionInfoTree', 'expand');
</script>