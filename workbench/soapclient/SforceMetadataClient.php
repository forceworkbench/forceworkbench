<?php
require_once('SoapBaseClient.php');

class SforceMetadataClient extends SoapBaseClient {
	
    protected function getNamespace() {
    	return 'http://soap.sforce.com/2006/04/metadata';
    }

    protected function getServerUrl() {
    	return str_replace("/u/","/m/",$_SESSION['location']);
    }

	protected function getWsdl() {
		return "soapclient/sforce." . str_replace(".","",max($GLOBALS['API_VERSIONS'])) . ".metadata.wsdl";
	}
	
	
}
?>

