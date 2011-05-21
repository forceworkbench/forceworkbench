<?php
require_once 'session.php';
require_once 'shared.php';
require_once 'header.php';
?>

<p class='instructions'>Choose an object to describe:</p>

<form name='describeForm' method='POST' action='<?php print $_SERVER['PHP_SELF']; ?>'>
    <?php
    printObjectSelection(WorkbenchContext::get()->getDefaultObject(), 'default_object', 30,
        "onChange=\"document.getElementById('loadingMessage').style.visibility='visible'; document.describeForm.submit();\"");
    ?>

    <span id='loadingMessage' style='visibility:hidden; color:#888;'>
        &nbsp;&nbsp;<img src='<?php print getStaticResourcesPath(); ?>/images/wait16trans.gif' align='absmiddle'/> Loading...
    </span>

</form>
<br/>

<?php
if (WorkbenchContext::get()->getDefaultObject()) {
    $describeSObjectResult = WorkbenchContext::get()->describeSObjects(WorkbenchContext::get()->getDefaultObject());

    $forceCollapse = WorkbenchContext::get()->hasDefaultObjectChanged();

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

    printTree("describeTree", $processedResults, $forceCollapse, null, false, false);
}

require_once 'footer.php';
?>
