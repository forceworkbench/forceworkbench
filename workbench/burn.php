<?php
require_once 'session.php';
require_once 'shared.php';


if (isset($_GET['ajaxBurn'])) {
    $numToBurn = $_GET['ajaxBurn'];
    $burnt = 0;
    $burnErrors = array();

    global $partnerConnection;

    for ($b = 0; $b < $numToBurn; $b++) {
        try {
            $partnerConnection->getServerTimestamp();
            $burnt++;
        } catch (Exception $ex) {
            $burnErrors[] = $ex->getMessage();
        }
    }

    if ($burnt > 0) {
        $successMessage = "Burnt " . $burnt . " API call";
        $successMessage .= $burnt > 1 ? 's' : '';
        displayInfo($successMessage);
    } else {
        $burnErrors = array_merge(array("No API calls were burnt."),$burnErrors);
    }

    if (is_array($burnErrors) && count($burnErrors)>0) {
        displayError($burnErrors);
    }

    exit;
}

require_once 'header.php';
?>


<script type="text/javascript">
<!--
 function checkEnter(e) { //e is event object passed from function invocation
     var characterCode; //literal character code will be stored in this variable
     
     if (e && e.which) { //if which property of event object is supported (NN4)
         characterCode = e.which; //character code is contained in NN4's which property
     } else {
         characterCode = event.keyCode; //character code is contained in IE's keyCode property
     }
     
     if (characterCode == 13) { //if generated character code is equal to ascii 13 (if enter key)
         return true;
     } else {
         return false;
     }
     
 }

 // Get the HTTP Object
 function getHTTPObject() {
     if (window.ActiveXObject) {
        return new ActiveXObject("Microsoft.XMLHTTP");
     } else if (window.XMLHttpRequest) { 
         return new XMLHttpRequest();
     } else {
         alert("Your browser does not support AJAX.");
         return null;
     }
 }

function ajaxBurn() {
    var ajax = getHTTPObject();

    if (ajax != null) {
        ajax.open("GET", "<?php $_SERVER['PHP_SELF'] ?>?ajaxBurn=" + document.getElementById('burnNumOfCalls').value, true);
        ajax.send(null);
        document.getElementById('burnResults').innerHTML = "";
        document.getElementById('burnStatus').innerHTML = "<img src='images/wait16trans.gif'/>&nbsp; Burning...";
        ajax.onreadystatechange = function handleAjaxBurnResponse() {
            if (ajax.readyState == 4) {
                document.getElementById('burnStatus').innerHTML = "";
                document.getElementById('burnResults').innerHTML = ajax.responseText;
            }
        };
    } else {
        document.getElementById('burnStatus').innerHTML = "Unknown AJAX error";
    }
}


    
//-->
</script>

<?php
if(isset($infos)) displayInfo($infos);
if(isset($errors)) displayError($errors);
?>
<p />
<form name='afterburner'>
<table border='0'>


    <tr>
        <td align='right'><label for='burnNumOfCalls'>Number of Calls to
        Burn: &nbsp;</label></td>
        <td colspan='2'><input type='text' id='burnNumOfCalls'
            name='burnNumOfCalls' size='45'
            onKeyPress='if (checkEnter(event)) {ajaxBurn(); return false;}' /></td>
    </tr>

    <tr>
        <td>&nbsp;</td>
        <td id='burnStatus'></td>
        <td align='right'>
        <p><input type='button' value='Burn' onclick="ajaxBurn();" /></p>
        </td>
    </tr>
    <!--
    <tr>
        <td>&nbsp;</td>
         <td id='burnResults' colspan='2' align='center'></td> 
    </tr>
    -->
</table>
<div id='burnResults'></div>

</form>


<?php
require_once 'footer.php';
?>