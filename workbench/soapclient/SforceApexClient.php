<?php
require_once 'SoapBaseClient.php';

class SforceApexClient extends SoapBaseClient {

    protected function getNamespace() {
        return 'http://soap.sforce.com/2006/08/apex';
    }

    public function executeAnonymous($executeAnonymousBlock) {
        $executeAnonymousRequest = new stdClass;
        $executeAnonymousRequest->String = $executeAnonymousBlock;
        $executeAnonymousResult = $this->sforce->__soapCall("executeAnonymous",array($executeAnonymousRequest),null,null,$outputHeaders);

        $executeAnonymousResultWithDebugLog = new stdClass;
        $executeAnonymousResultWithDebugLog->executeAnonymousResult = $executeAnonymousResult->result;
        foreach ($outputHeaders as $outputHeader) {
            $executeAnonymousResultWithDebugLog->debugLog .= $outputHeader->debugLog;
        }
        return $executeAnonymousResultWithDebugLog;
    }
}
?>
