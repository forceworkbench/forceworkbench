<?php

require_once 'SforcePartnerClient.php';
require_once 'SforceEmail.php';


/**
 * This file contains two classes.
 * @package SalesforceSoapClient
 */
/**
 * SforcePartnerClient class.
 *
 * @package SalesforceSoapClient
 */
class SforceMockPartnerClient extends SforcePartnerClient  {
    public function login($username, $password) {
        $loginResult = new stdClass();
        $loginResult->sessionId = "xxx";
        $loginResult->serverUrl = "https://mocked.salesforce.com/services/Soap/u/21.0";
        $this->_setLoginHeader($loginResult);
        return $loginResult;
    }

    public function getUserInfo() {
        $result = new stdClass();
        $result->userFullName = "Mocked Admin";
        $result->userId = "005000000000000";
        $result->organizationName = "Mocked, Inc.";
        $result->organizationId = "00D000000000000";
        return $result;
    }

    public function describeGlobal() {
        $result = new stdClass();
        $result->types = array("Account", "Contact", "Custom_Object__c");
        return $result;
    }

}
?>