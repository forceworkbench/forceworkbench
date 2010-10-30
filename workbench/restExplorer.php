<?php
require_once 'controllers/RestExplorerController.php';
require_once 'session.php';
require_once 'shared.php';
require_once 'header.php';

if(!isset($_SESSION['restExplorerController']) || isset($_GET['reset'])) {
    $_SESSION['restExplorerController'] = new RestExplorerController();
}
$c = $_SESSION['restExplorerController'];
$c->onPageLoad();
?>
<link
	rel="stylesheet" type="text/css"
	href="<?php echo getStaticResourcesPath() . "/style/restexplorer.css"; ?>" />
<script
	type="text/javascript"
	src="<?php echo getStaticResourcesPath() . "/script/restexplorer.js"; ?>"></script>
<script type="text/javascript">
      function toggleRequestBodyDisplay(radio) {
          if (radio.checked && radio.value == 'POST') {
              document.getElementById('requestBodyContainer').style.display = 'inline';
          } else {
              document.getElementById('requestBodyContainer').style.display = 'none';
          }
      }
  </script>
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
    foreach (array("GET", "POST", "HEAD") as $method) {
        echo "<label><input type='radio' name='requestMethod' value='$method'" . 
                ($c->requestMethod == $method ? "checked='checked'" : "")  . 
                " onclick='toggleRequestBodyDisplay(this);'/> $method </label>&nbsp;";
    }
    ?>
    </p>
    
    <input name="url" value="<?php echo $c->url; ?>"
    	   style="width: 35em; height: 1.2em; font-size: 18px; font-weight: bold;"
    	   onKeyPress="if (checkEnter(event)) {document.getElementById('execBtn').click(); return false;}" />
    &nbsp; 
    <input id="execBtn" name="doExecute" type="submit" value="Execute" style="font-size: 18px;"/>
    
    <div id="requestBodyContainer" style="display: <?php echo $c->requestMethod == 'POST' ? 'inline' : 'none';?>;">
        <p>
            <br />
            <strong>Request Body</strong>
        </p>
        <textarea name="requestBody" style="width: 100%; height: 10em; font-family: courier, monotype;"><?php echo $c->requestBody; ?></textarea>
    </div>
</form>

<p />
<?php
if (isset($c->autoExec) && !$c->autoExec) {
    displayError("This URI needs to be completed before executing." .
                       "For example, it may need a merge field populated (e.g. {ID}) or a query string appended (e.g. ?q=)");
}
?>

<p />

<?php if ($c->response != null) { ?>
<div style="float: left;">
    <?php if ($c->requestMethod != "HEAD") { ?>
            <a href="javascript:ddtreemenu.flatten('responseList', 'expand')">Expand All</a> | 
            <a href="javascript:ddtreemenu.flatten('responseList', 'contact')">Collapse All</a>
            
            <div id="responseListContainer" class="results"></div>
    <?php
        } else {
            displayInfo("The HEAD method does not return a body to display.");
            echo "<p/>";
        }
    ?>
</div>

<!--<div id="rawJson" class="codeViewPortContainer" style="float: right;">
    <strong>Raw Response</strong>
    <p class="codeViewPort"><?php echo $c->rawResponseHeaders; ?><br /><?php echo $c->rawResponse; ?></p>
</div>
-->
<?php } ?>

<?php if ($c->response != null) echo "<script type='text/javascript'>convert($c->response);</script>"; ?>

<?php
require_once 'footer.php';
?>