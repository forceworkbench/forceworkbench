<?php
require_once 'restclient/RestClient.php';
require_once 'controllers/RestExplorerController.php';
require_once 'async/RestExplorerFutureTask.php';
require_once 'session.php';
require_once 'shared.php';

if(!isset($_SESSION['restExplorerController']) || isset($_GET['reset'])) {
    $_SESSION['restExplorerController'] = new RestExplorerController();
}
$c = $_SESSION['restExplorerController'];
$c->onPageLoad();

if ($c->doExecute || $c->autoExec == '1') {
    $f = new RestExplorerFutureTask($c);
    $result = $f->enqueueOrPerform();
}

require_once 'header.php';
?>
<link
	rel="stylesheet" type="text/css"
	href="<?php echo getPathToStaticResource('/style/restexplorer.css'); ?>" />
<script
	type="text/javascript"
	src="<?php echo getPathToStaticResource('/script/restexplorer.js'); ?>"></script>

<script
	type="text/javascript"
	src="<?php echo getPathToStaticResource('/script/simpletreemenu.js'); ?>">
    /***********************************************
    * Dynamic Countdown script- © Dynamic Drive (http://www.dynamicdrive.com)
    * This notice MUST stay intact for legal use
    * Visit http://www.dynamicdrive.com/ for this script and 100s more.
    ***********************************************/
</script>

<?php
if ($c->errors != null) {
    displayError($c->errors);
}
?>

<form action="" method="POST">
    <?php print getCsrfFormTag(); ?>
    <p><em>Choose an HTTP method to perform on the REST API service URI
    below:</em>
    
    <p>
    <?php 
    foreach (RestApiClient::getMethods() as $method) {
        echo "<label><input type='radio' name='requestMethod' value='$method'" . 
                ($c->requestMethod == $method ? "checked='checked'" : "")  . 
                " onclick='toggleRequestBodyDisplay(this, " . (in_array($method, RestApiClient::getMethodsWithBodies()) ? 'true' : 'false') .
                ");'/> $method </label>&nbsp;";
    }
    ?>
	&nbsp;
    <input  id="headersButton"
	        type="button"
			value="Headers"
			onclick="toggleRequestHeaders();"/>
    &nbsp;
	<input  id="resetButton"
	        type="button"
			value="Reset"
			onclick="resetUrl('<?php print WorkbenchContext::get()->getApiVersion(); ?>');"/>
    &nbsp;
	<input  id="upButton"
	        type="button" 
			value="Up"
			onclick="upUrl();"/>
    &nbsp;
    <span id='waitingIndicator'>
        <img src='<?php print getPathToStaticResource('/images/wait16trans.gif'); ?>'/> Processing...
    </span>
    </p>

    <input id="urlInput" 
           name="url" value="<?php echo htmlspecialchars($c->url); ?>"
    	   style="width: 35em; height: 1.2em; font-size: 18px; font-weight: bold;"
    	   onKeyPress="if (checkEnter(event)) {document.getElementById('execBtn').click(); return false;}" />
    &nbsp; 
    <input id="execBtn" name="doExecute" type="submit" value="Execute" class="disableWhileAsyncLoading" style="font-size: 18px;"/>

    <div id="requestHeaderContainer" style="display: none;">
        <p>
            <strong>Request Headers</strong>
        </p>
        <textarea id="requestHeaders" name="requestHeaders" style="width: 100%; height: 4em; font-family: courier, monotype;"><?php echo htmlspecialchars($c->requestHeaders); ?></textarea>
        <a id="requestHeadersDefaulter" class="miniLink pseudoLink" style="float: right;"
           onClick="document.getElementById('requestHeaders').value='<?php echo str_replace("\n", "\\n", $c->getDefaultRequestHeaders()); ?>';">Restore Default Headers</a>
        <br/>
    </div>

    <div id="requestBodyContainer" style="display: <?php echo in_array($c->requestMethod, RestApiClient::getMethodsWithBodies()) ? 'inline' : 'none';?>;">
        <p>
            <strong>Request Body</strong>
        </p>
        <textarea name="requestBody" style="width: 100%; height: 10em; font-family: courier, monotype;"><?php echo htmlspecialchars($c->requestBody); ?></textarea>
        <br/>
    </div>
</form>

<p />

<div id="restExplorerResultsContainer">
<?php
if (isset($c->autoExec) && !$c->autoExec) {
    displayError("This URI needs to be completed before executing. " .
                       "For example, it may need a merge field populated (e.g. {ID}) or a query string appended (e.g. ?q=)");
}

if (isset($result)) {
    echo $result;
}
?>
</div>

<script type="text/javascript">
    var restExplorer = function() {
        function showWaitingIndicator() {
            document.getElementById('waitingIndicator').style.display = 'inline';
        }

        if (<?php echo !hasRedis() ?>) {
            bindEvent(document.getElementById('execBtn'), 'click', showWaitingIndicator);

            var linkables = document.getElementById('restExplorerResultsContainer').getElementsByClassName('RestLinkable');
            for (var link in linkables) {
                bindEvent(linkables[link], 'click', showWaitingIndicator);
            }
        }
    }();
</script>

<?php
require_once 'footer.php';
?>
