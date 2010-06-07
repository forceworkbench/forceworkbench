<?php
require_once ('session.php');
require_once ('shared.php');
require_once('header.php');
require_once ('soapclient/SforceMetadataClient.php');

$metadataConnection = new SforceMetadataClient();

print "<pre>";
print_r($metadataConnection->describeMetadata());
print "</pre>";

require_once('footer.php');
?>