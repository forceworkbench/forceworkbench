<?php
require_once 'controllers/RestExplorerController.php';
require_once 'session.php';
require_once 'shared.php';

if(!isset($_SESSION['restExplorerController']) || isset($_GET['reset'])) {
    $_SESSION['restExplorerController'] = new RestExplorerController();
}
$c = $_SESSION['restExplorerController'];
$c->onPageLoad();

require_once 'header.php';
?>
<link
	rel="stylesheet" type="text/css"
	href="<?php echo getStaticResourcesPath() . "/style/restexplorer.css"; ?>" />
<script
	type="text/javascript"
	src="<?php echo getStaticResourcesPath() . "/script/restexplorer.js"; ?>"></script>

<script
	type="text/javascript"
	src="<?php echo getStaticResourcesPath() . "/script/simpletreemenu.js"; ?>"></script>

<?php
if ($c->errors != null) {
    displayError($c->errors);
}
?>

<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
    <p><em>Choose an HTTP method to perform on the REST API service URI
    below:</em>
    
    <p>
    <?php 
    foreach (array("GET", "POST", "PATCH", "DELETE", "HEAD") as $method) {
        echo "<label><input type='radio' name='requestMethod' value='$method'" . 
                ($c->requestMethod == $method ? "checked='checked'" : "")  . 
                " onclick='toggleRequestBodyDisplay(this);'/> $method </label>&nbsp;";
    }
    ?>
	&nbsp;
	<input  id="upButton"
	        type="button" 
			src="<?php echo getStaticResourcesPath() . "/images/up.png"; ?>" 
			value="Up" 
			onclick="upUrl();"/>
    </p>
    
    <input id="urlInput" 
           name="url" value="<?php echo htmlentities($c->url); ?>"
    	   style="width: 35em; height: 1.2em; font-size: 18px; font-weight: bold;"
    	   onKeyPress="if (checkEnter(event)) {document.getElementById('execBtn').click(); return false;}" />
    &nbsp; 
    <input id="execBtn" name="doExecute" type="submit" value="Execute" style="font-size: 18px;"/>
    
    <div id="requestBodyContainer" style="display: <?php echo in_array($c->requestMethod, array('POST', 'PATCH')) ? 'inline' : 'none';?>;">
        <p>
            <br />
            <strong>Request Body</strong>
        </p>
        <textarea name="requestBody" style="width: 100%; height: 10em; font-family: courier, monotype;"><?php echo htmlentities($c->requestBody); ?></textarea>
    </div>
</form>

<p />
<?php
if (isset($c->autoExec) && !$c->autoExec) {
    displayError("This URI needs to be completed before executing." .
                       "For example, it may need a merge field populated (e.g. {ID}) or a query string appended (e.g. ?q=)");
}
?>

<p/>

<?php if ($c->showResponse) { ?>
<div style="float: left;">
    <?php if (trim($c->instResponse) != "") { ?>
            <a href="javascript:ddtreemenu.flatten('responseList', 'expand')">Expand All</a> | 
            <a href="javascript:ddtreemenu.flatten('responseList', 'contact')">Collapse All</a> |
            <a id="codeViewPortToggler" href="javascript:toggleCodeViewPort();">Show Raw Response</a>
            
            <div id="responseListContainer" class="results"></div>
            
            <script type='text/javascript'>convert(<?php echo $c->instResponse ?>);</script>
    <?php
        } else {
            displayInfo("No Body Returned. Dislaying Headers Only.");
            echo "<pre>" . htmlentities($c->rawResponse->header) . "</pre>";
        }
    ?>
</div>

<div id="codeViewPortContainer" style="display: none;">
    <strong>Raw Response</strong> 
    <p id="codeViewPort"><?php echo htmlentities($c->rawResponse->header); ?><br /><?php echo htmlentities($c->rawResponse->body); ?></p>
</div>


<?php } ?>


<?php
require_once 'footer.php';
?>