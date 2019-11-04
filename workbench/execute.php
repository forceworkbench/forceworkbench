<?php
$MIGRATION_MESSAGE = "Anonymous Apex can be executed from Visual Studio Code. <a href=\"https://developer.salesforce.com/tools/vscode/en/getting-started/install\">Try it today!</a>";


require_once 'session.php';
require_once 'shared.php';
require_once 'header.php';
require_once 'soapclient/SforceApexClient.php';
require_once 'async/ApexExecuteFutureTask.php';

//correction for dynamic magic quotes
if (isset($_POST['scriptInput']) && get_magic_quotes_gpc()) {
    $_POST['scriptInput'] = stripslashes($_POST['scriptInput']);
}

if (isset($_POST['execute'])) {
    $_SESSION['scriptInput'] = $_POST['scriptInput'];
    $_SESSION['LogCategory'] = $_POST['LogCategory'];
    $_SESSION['LogCategoryLevel'] = $_POST['LogCategoryLevel'];
} else if (!isset($_SESSION['LogCategory']) && !isset($_SESSION['LogCategoryLevel'])) {
    $_SESSION['LogCategory'] = WorkbenchConfig::get()->value("defaultLogCategory");
    $_SESSION['LogCategoryLevel'] = WorkbenchConfig::get()->value("defaultLogCategoryLevel");
}


?>
<form id="executeForm" action="" method="POST">
<?php print getCsrfFormTag(); ?>
<table border="0">
    <tr>
        <td>
        <p class='instructions'>Enter Apex code to be executed as an anonymous block:</p>
        </td>
    </tr>
    <tr>
        <td align="right">Log Category: <select id="LogCategory"
            name="LogCategory">
            <?php
            printSelectOptions(WorkbenchConfig::get()->valuesToLabels('defaultLogCategory'),$_SESSION['LogCategory']);
            ?>
        </select> &nbsp; Log Level: <select id="LogCategoryLevel"
            name="LogCategoryLevel">
            <?php
            printSelectOptions(WorkbenchConfig::get()->valuesToLabels('defaultLogCategoryLevel'),$_SESSION['LogCategoryLevel']);
            ?>
        </select></td>
    </tr>
    <tr>
        <td colspan="2"><textarea id='scriptInput' name='scriptInput'
            cols='100'
            rows='<?php print WorkbenchConfig::get()->value("textareaRows") ?>'
            style='overflow: auto; font-family: monospace, courier;'><?php echo htmlspecialchars(isset($_SESSION['scriptInput'])?$_SESSION['scriptInput']:null,ENT_QUOTES); ?></textarea>
        <p />
        <input type='submit' name="execute" value='Execute' class='disableWhileAsyncLoading' /> <input
            type='reset' value='Reset' class='disableWhileAsyncLoading' /></td>
    </tr>
</table>
</form>


<script type="text/javascript">
     document.getElementById('scriptInput').focus();
</script>


<?php
if (isset($_POST['execute']) && isset($_POST['scriptInput']) && $_POST['scriptInput'] != "") {
    print "<h2>Results</h2>";
    $asyncJob = new ApexExecuteFutureTask($_POST['scriptInput'], $_POST['LogCategory'], $_POST['LogCategoryLevel']);
    echo $asyncJob->enqueueOrPerform();
} else if (isset($_POST['execute']) && isset($_POST['scriptInput']) && $_POST['scriptInput'] == "") {
    displayInfo("Anonymous block must not be blank.");
}

require_once 'footer.php';
?>
