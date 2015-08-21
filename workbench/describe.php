<?php
require_once 'session.php';
require_once 'shared.php';
require_once 'header.php';

if (isset($cacheCleared)) {
    displayInfo("Cache Cleared Successfully");
    print "<p/>";
}

if (isset($_REQUEST['keyPrefix']) || isset($_REQUEST['id'])) {
    $keyPrefixOrId = isset($_REQUEST['keyPrefix']) ? $_REQUEST['keyPrefix'] : $_REQUEST['id'];
    $specifiedType = WorkbenchContext::get()->getObjectTypeByKeyPrefixOrId(trim($keyPrefixOrId));
    if ($specifiedType == null) {
        displayWarning("Unknown object type");
    }
    WorkbenchContext::get()->setDefaultObject($specifiedType);
}
?>

<p class='instructions'>Choose an object to describe:</p>

<form name='describeForm' method='POST' action='describe.php'>
    <?php
    print getCsrfFormTag();
    printObjectSelection(WorkbenchContext::get()->getDefaultObject(), 'default_object', 30,
        "onChange=\"document.getElementById('loadingMessage').style.visibility='visible'; document.describeForm.submit();\"");
    ?>

    <span id='loadingMessage' style='visibility:hidden; color:#888;'>
        &nbsp;&nbsp;<img src='<?php print getPathToStaticResource('/images/wait16trans.gif'); ?>' align='absmiddle'/> Loading...
    </span>

</form>
<br/>

<?php
if (WorkbenchContext::get()->getDefaultObject()) {
    $describeSObjectResult = WorkbenchContext::get()->describeSObjects(WorkbenchContext::get()->getDefaultObject());

    $alwaysArrayChildren = array("recordTypeInfos", "childRelationships");
    foreach ($alwaysArrayChildren as $child) {
        if (isset($describeSObjectResult->$child) && !is_array($describeSObjectResult->$child)) {
            $describeSObjectResult->$child = array($describeSObjectResult->$child);
        }
    }

    $forceCollapse = WorkbenchContext::get()->hasDefaultObjectChanged();

    $processedResults = ExpandableTree::processResults($describeSObjectResult, "Attributes", true);

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

    $tree = new ExpandableTree("describeTree", $processedResults);
    $tree->setForceCollapse($forceCollapse);
    $tree->setAdditionalMenus(ExpandableTree::getClearCacheMenu());
    $tree->printTree();
}

require_once 'footer.php';
?>
