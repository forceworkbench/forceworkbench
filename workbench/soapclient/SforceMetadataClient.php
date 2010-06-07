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
	
	public function describeMetadata() {
		$request = new stdClass;
		preg_match('!/(\d{1,2}\.\d)!',$_SESSION['location'],$apiVersionMatches);
		$request->asOfVersion = $apiVersionMatches[1];
		return $this->sforce->__soapCall("describeMetadata",array($request))->result;
	}
	
}
?>

