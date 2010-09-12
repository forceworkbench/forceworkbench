<?php
$q = "";
if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != "") {
    $q = "?" . $_SERVER['QUERY_STRING'];
}

header( "Location: login.php$q" ) ;
?>