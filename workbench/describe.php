<?php
require_once 'session.php';
require_once 'shared.php';
require_once 'header.php';
?>

<p class='instructions'>Choose an object to describe:</p>

<form name='describeForm' method='POST' action='<?php print $_SERVER['PHP_SELF']; ?>'>
    <?php
    printObjectSelection($_SESSION['default_object'], 'default_object', 30,
        "onChange=\"document.getElementById('loadingMessage').style.visibility='visible'; document.describeForm.submit();\"");
    ?>

    <span id='loadingMessage' style='visibility:hidden; color:#888;'>
        &nbsp;&nbsp;<img src='<?php print getStaticResourcesPath(); ?>/images/wait16trans.gif' align='absmiddle'/> Loading...
    </span>

</form>
<br/>

<?php
if (isset($_SESSION['default_object']) && "" !== $_SESSION['default_object']) {
    $describeSObjectResult = WorkbenchContext::get()->describeSObjects($_SESSION['default_object']);

    $forceCollapse = isset($_REQUEST['default_object_changed']) && $_REQUEST['default_object_changed'];

    $processedResults = processResults($describeSObjectResult, "Attributes", true);

    ?>
    <div class="legend">
        <strong>Legend:</strong>
        <ul>
            <li class="trueColor">True</li>
            <li class="falseColor">False</li>
            <li class="highlightCustomField">Custom Field</li>
            <li class="highlightSystemField">System Field</li>
        </ul>
    </div>
    <?php

    printTree("describeTree", $processedResults, $forceCollapse, null, true, false);
}

require_once 'footer.php';
?>
