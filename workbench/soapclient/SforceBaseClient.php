<?php
/*
 * Copyright (c) 2007, salesforce.com, inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided
 * that the following conditions are met:
 *
 *    Redistributions of source code must retain the above copyright notice, this list of conditions and the
 *    following disclaimer.
 *
 *    Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *    the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *    Neither the name of salesforce.com, inc. nor the names of its contributors may be used to endorse or
 *    promote products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
 * PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */
require_once 'SforceEmail.php';
require_once 'SforceProcessRequest.php';
require_once 'ProxySettings.php';
require_once 'SforceHeaderOptions.php';


/**
 * This file contains one class.
 * @package SalesforceSoapClient
 */
/**
 * SalesforceSoapClient
 * @package SalesforceSoapClient
 */
class SforceBaseClient {
    protected $sforce;
    protected $sessionId;
    protected $location;

    protected $namespace;

    // Header Options
    protected $callOptions;
    protected $assignmentRuleHeader;
    protected $ownerChangeOptionsHeader;
    protected $emailHeader;
    protected $loginScopeHeader;
    protected $mruHeader;
    protected $queryHeader;
    protected $userTerritoryDeleteHeader;
    protected $sessionHeader;
    protected $allowFieldTruncationHeader;
    protected $allOrNoneHeader;
    protected $disableFeedTrackingHeader;
    protected $localeOptions;
    protected $packageVersionHeader;

    public function getNamespace() {
        return $this->namespace;
    }


    // clientId specifies which application or toolkit is accessing the
    // salesforce.com API. For applications that are certified salesforce.com
    // solutions, replace this with the value provided by salesforce.com.
    // Otherwise, leave this value as 'phpClient/1.0'.
    protected $clientId;

    public function printDebugInfo() {
        echo "PHP Toolkit Version: $this->version\r\n";
        echo 'Current PHP version: ' . phpversion();
        echo "\r\n";
        echo 'SOAP enabled: ';
        if (extension_loaded('soap')) {
            echo 'True';
        } else {
            echo 'False';
        }
        echo "\r\n";
        echo 'OpenSSL enabled: ';
        if (extension_loaded('openssl')) {
            echo 'True';
        } else {
            echo 'False';
        }
    }

    /**
     * Connect method to www.salesforce.com
     *
     * @param string $wsdl   Salesforce.com Partner WSDL
     */
    public function createConnection($wsdl, $proxy=null) {
        $_SERVER['HTTP_USER_AGENT'] = 'Salesforce/PHPToolkit/1.0';

        $soapClientArray = array();
        if (WorkbenchConfig::get()->value("debug") == true) {
            $soapClientArray['trace'] = 1;
        }
        $soapClientArray['exceptions'] = true;
        $soapClientArray['encoding'] = 'utf-8';

        if (WorkbenchConfig::get()->value("enableGzip") && phpversion() > '5.1.2') {
            $soapClientArray['compression'] = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 1;
        }

        if (WorkbenchConfig::get()->value("proxyEnabled")) {
            $proxySettings = array();
            $proxySettings['proxy_host'] = WorkbenchConfig::get()->value("proxyHost");
            $proxySettings['proxy_port'] = (int)WorkbenchConfig::get()->value("proxyPort"); // Use an integer, not a string
            $proxySettings['proxy_login'] = WorkbenchConfig::get()->value("proxyUsername");
            $proxySettings['proxy_password'] = WorkbenchConfig::get()->value("proxyPassword");

            $soapClientArray = array_merge($soapClientArray, $proxySettings);
        }

        $this->sforce = new SoapClient($wsdl, $soapClientArray);
        return $this->sforce;
    }

    public function setCallOptions($header) {
        if ($header != NULL) {
            $this->callOptions = new SoapHeader($this->namespace, 'CallOptions', array (
          'client' => $header->client,
          'defaultNamespace' => $header->defaultNamespace,
          'clientLog' => $header->clientLog,
          'debugExceptions' => $header->debugExceptions,
          'platform' => $header->platform,
          'remoteApplication' => $header->remoteApplication
            ));
        } else {
            $this->callOptions = NULL;
        }
    }

    /**
     * Login to Salesforce.com and starts a client session.
     *
     * @param string $username   Username
     * @param string $password   Password
     *
     * @return LoginResult
     */
    public function login($username, $password) {
        $this->setHeaders("login");
        $result = $this->sforce->login(array (
         'username' => $username,
         'password' => $password
        ));
        $result = $result->result;
        $this->_setLoginHeader($result);
        return $result;
    }

    /**
     * Specifies the session ID returned from the login server after a successful
     * login.
     */
    protected function _setLoginHeader($loginResult) {
        $this->sessionId = $loginResult->sessionId;
        $this->setSessionHeader($this->sessionId);
        $serverURL = $loginResult->serverUrl;
        $this->setEndPoint($serverURL);
    }

    /**
     * Set the endpoint.
     *
     * @param string $location   Location
     */
    public function setEndpoint($location) {
        $this->location = $location;
        $this->sforce->__setLocation($location);
    }

    private function setHeaders($call=NULL) {
        $this->sforce->__setSoapHeaders(NULL);
        $headerArray = array();

        if (isset($this->sessionHeader)) {
            array_push($headerArray, $this->sessionHeader);
        }

        $header = $this->callOptions;
        if ($header != NULL) {
            array_push($headerArray, $header);
        }

        if ($call == "create" ||
            $call == "merge" ||
            $call == "update" ||
            $call == "upsert"
        ) {
            $header = $this->assignmentRuleHeader;
            if ($header != NULL) {
                array_push($headerArray, $header);
            }
        }

        if ($call == "merge" ||
            $call == "update" ||
            $call == "upsert"
        ) {
            $header = $this->ownerChangeOptionsHeader;
            if ($header != NULL) {
                array_push($headerArray, $header);
            }
        }

        if ($call == "login") {
            $header = $this->loginScopeHeader;
            if ($header != NULL) {
                array_push($headerArray, $header);
            }
        }

        if ($call == "create" ||
            $call == "resetPassword" ||
            $call == "update" ||
            $call == "upsert"
        ) {
            $header = $this->emailHeader;
            if ($header != NULL) {
                array_push($headerArray, $header);
            }
        }

        if ($call == "create" ||
            $call == "merge" ||
            $call == "query" ||
            $call == "retrieve" ||
            $call == "update" ||
            $call == "upsert"
        ) {
            $header = $this->mruHeader;
            if ($header != NULL) {
                array_push($headerArray, $header);
            }
        }

        if ($call == "create" ||
            $call == "update" ||
            $call == "upsert" ||
            $call == "undelete" ||
            $call == "createLead" ||
            $call == "merge" ||
            $call == "process"
        ) {
            $header = $this->allowFieldTruncationHeader;
            if ($header != NULL) {
                array_push($headerArray, $header);
            }
        }

        if ($call == "delete") {
            $header = $this->userTerritoryDeleteHeader;
            if ($header != NULL) {
                array_push($headerArray, $header);
            }
        }

        if ($call == "query" ||
            $call == "queryMore" ||
            $call == "retrieve") {
            $header = $this->queryHeader;
            if ($header != NULL) {
                array_push($headerArray, $header);
            }
        }
        
        if ($call == "create" ||
            $call == "update" ||
            $call == "upsert" ||
            $call == "undelete" ||
            $call == "delete"
        ) {
            $header = $this->allOrNoneHeader;
            if ($header != NULL) {
                array_push($headerArray, $header);
            }

            $header = $this->disableFeedTrackingHeader;
            if ($header != NULL) {
                array_push($headerArray, $header);
            }
        }

        if ($call == "describeSObject" ||
            $call == "describeSObjects"
        ) {
            $header = $this->localeOptions;
            if ($header != NULL) {
                array_push($headerArray, $header);
            }
        }
        
        // available for everything
        $header = $this->packageVersionHeader;
        if ($header != NULL) {
            array_push($headerArray, $header);
        }
        
        $this->sforce->__setSoapHeaders($headerArray);
    }

    public function setAssignmentRuleHeader($header) {
        if ($header != NULL) {
            $this->assignmentRuleHeader = new SoapHeader($this->namespace, 'AssignmentRuleHeader', array (
             'assignmentRuleId' => $header->assignmentRuleId,
             'useDefaultRule' => $header->useDefaultRuleFlag
            ));
        } else {
            $this->assignmentRuleHeader = NULL;
        }
    }

    public function setOwnerChangeOptionsHeader($header) {
        if ($header != NULL) {
            $this->ownerChangeOptionsHeader = new SoapHeader($this->namespace, 'OwnerChangeOptions', array (
             'transferAttachments' => $header->transferAttachments,
             'transferOpenActivities' => $header->transferOpenActivities
            ));
        } else {
            $this->ownerChangeOptionsHeader = NULL;
        }
    }

    public function setEmailHeader($header) {
        if ($header != NULL) {
            $this->emailHeader = new SoapHeader($this->namespace, 'EmailHeader', array (
             'triggerAutoResponseEmail' => $header->triggerAutoResponseEmail,
             'triggerOtherEmail' => $header->triggerOtherEmail,
             'triggerUserEmail' => $header->triggerUserEmail
            ));
        } else {
            $this->emailHeader = NULL;
        }
    }

    public function setLoginScopeHeader($header) {
        if ($header != NULL) {
            $this->loginScopeHeader = new SoapHeader($this->namespace, 'LoginScopeHeader', array (
        'organizationId' => $header->organizationId,
        'portalId' => $header->portalId
            ));
        } else {
            $this->loginScopeHeader = NULL;
        }
        //$this->setHeaders('login');
    }

    public function setMruHeader($header) {
        if ($header != NULL) {
            $this->mruHeader = new SoapHeader($this->namespace, 'MruHeader', array (
             'updateMru' => $header->updateMruFlag
            ));
        } else {
            $this->mruHeader = NULL;
        }
    }

    public function setSessionHeader($id) {
        if ($id != NULL) {
            $this->sessionHeader = new SoapHeader($this->namespace, 'SessionHeader', array (
             'sessionId' => $id
            ));
            $this->sessionId = $id;

            if (WorkbenchConfig::get()->value("includeSessionCookie")) {
                $this->sforce->__setCookie("sid", $id);
            }
        } else {
            $this->sessionHeader = NULL;
            $this->sessionId = NULL;
        }
    }

    public function setUserTerritoryDeleteHeader($header) {
        if ($header != NULL) {
            $this->serTerritoryDeleteHeader = new SoapHeader($this->namespace, 'UserTerritoryDeleteHeader  ', array (
             'transferToUserId  ' => $header->transferToUserId
            ));
        } else {
            $this->mruHeader = NULL;
        }
    }

    public function setQueryOptions($header) {
        if ($header != NULL) {
            $this->queryHeader = new SoapHeader($this->namespace, 'QueryOptions', array (
             'batchSize' => $header->batchSize,
             'scope' => ''
            ));
        } else {
            $this->queryHeader = NULL;
        }
    }

    public function setAllowFieldTruncationHeader($header) {
        if ($header != NULL) {
            $this->allowFieldTruncationHeader = new SoapHeader($this->namespace, 'AllowFieldTruncationHeader', array (
             'allowFieldTruncation' => $header->allowFieldTruncation
            ));
        } else {
            $this->allowFieldTruncationHeader = NULL;
        }
    }

    public function setAllOrNoneHeader($header) {
        if ($header != NULL) {
            $this->allOrNoneHeader = new SoapHeader($this->namespace, 'AllOrNoneHeader', array (
             'allOrNone' => $header->allOrNone
            ));
        } else {
            $this->allOrNoneHeader = NULL;
        }
    }

    public function setDisableFeedTrackingHeader($header) {
        if ($header != NULL) {
            $this->disableFeedTrackingHeader = new SoapHeader($this->namespace, 'DisableFeedTrackingHeader', array (
             'disableFeedTracking' => $header->disableFeedTracking
            ));
        } else {
            $this->disableFeedTrackingHeader = NULL;
        }
    }

    public function setLocaleOptions($header) {
        if ($header != NULL) {
            $this->localeOptions = new SoapHeader($this->namespace, 'LocaleOptions', array (
             'language' => $header->language
            ));
        } else {
            $this->localeOptions = NULL;
        }
    }

    public function setPackageVersionHeader($namespace, $majorVersion, $minorVersion) {
		$packageVersionComp = array(
				'namespace'    => new SoapVar($namespace, XSD_STRING),
				'majorNumber' => new SoapVar($majorVersion, XSD_INT),
				'minorNumber' => new SoapVar($minorVersion, XSD_INT)
		);

		$packageVersionVar = array(
				'packageVersions' => new SoapVar($packageVersionComp, SOAP_ENC_OBJECT)
		);

		$packageVersionBody = new SoapVar($packageVersionVar, SOAP_ENC_OBJECT);

		$this->packageVersionHeader = new SoapHeader($this->getNamespace(), 'PackageVersionHeader', $packageVersionBody, false);
    }
    
    public function getSessionId() {
        return $this->sessionId;
    }

    public function getLocation() {
        return $this->location;
    }

    public function getConnection() {
        return $this->sforce;
    }

    public function getFunctions() {
        return $this->sforce->__getFunctions();
    }

    public function getTypes() {
        return $this->sforce->__getTypes();
    }

    public function getLastRequest() {
        return $this->sforce->__getLastRequest();
    }

    public function getLastRequestHeaders() {
        return $this->sforce->__getLastRequestHeaders();
    }

    public function getLastResponse() {
        return $this->sforce->__getLastResponse();
    }

    public function getLastResponseHeaders() {
        return $this->sforce->__getLastResponseHeaders();
    }

    protected function _convertToAny($fields) {
        $anyString = '';
        foreach ($fields as $key => $value) {
            if ($value instanceOf SObject) { //additional processing for nested sObject in field value for use with external ids
                if (isset ($value->fields)) {
                    $nestedAnyString = $this->_convertToAny($value->fields);
                    $anyString = $anyString . '<' . $key . '>';
                    if (isset($value->type)) { //check if partner wsdl (should always be, but just in case)
                        $anyString = $anyString . '<type>' . $value->type . '</type>';
                    }
                    $anyString = $anyString . $nestedAnyString . '</' . $key . '>';
                }
            } else {
                $anyString = $anyString . '<' . $key . '>' . $value . '</' . $key . '>';
            }
        }
        return $anyString;
    }

    protected function _create($arg) {
        $this->setHeaders("create");
        return $this->sforce->create($arg)->result;
    }

    protected function _merge($arg) {
        $this->setHeaders("merge");
        return $this->sforce->merge($arg)->result;
    }

    protected function _process($arg) {
        $this->setHeaders();
        return $this->sforce->process($arg)->result;
    }

    protected function _update($arg) {
        $this->setHeaders("update");
        return $this->sforce->update($arg)->result;
    }

    protected function _upsert($arg) {
        $this->setHeaders("upsert");
        return $this->sforce->upsert($arg)->result;
    }

    public function sendSingleEmail($request) {
        if (is_array($request)) {
            $messages = array();
            foreach ($request as $r) {
                $email = new SoapVar($r, SOAP_ENC_OBJECT, 'SingleEmailMessage', $this->namespace);
                array_push($messages, $email);
            }
            $arg = new stdClass;
            $arg->messages = $messages;
            return $this->_sendEmail($arg);
        } else {
            $backtrace = debug_backtrace();
            die('Please pass in array to this function:  '.$backtrace[0]['function']);
        }
    }

    public function sendMassEmail($request) {
        if (is_array($request)) {
            $messages = array();
            foreach ($request as $r) {
                $email = new SoapVar($r, SOAP_ENC_OBJECT, 'MassEmailMessage', $this->namespace);
                array_push($messages, $email);
            }
            $arg = new stdClass;
            $arg->messages = $messages;
            return $this->_sendEmail($arg);
        } else {
            $backtrace = debug_backtrace();
            die('Please pass in array to this function:  '.$backtrace[0]['function']);
        }
    }

    protected function _sendEmail($arg) {
        $this->setHeaders();
        return $this->sforce->sendEmail($arg)->result;
    }

    /**
     * Converts a Lead into an Account, Contact, or (optionally) an Opportunity.
     *
     * @param array $leadConverts    Array of LeadConvert
     *
     * @return LeadConvertResult
     */
    public function convertLead($leadConverts) {
        $this->setHeaders("convertLead");
        $arg = new stdClass;
        $arg->leadConverts = $leadConverts;
        return $this->sforce->convertLead($arg);
    }

    /**
     * Deletes one or more new individual objects to your organization's data.
     *
     * @param array $ids    Array of fields
     * @return DeleteResult
     */
    public function delete($ids) {
        $this->setHeaders("delete");
        $arg = new stdClass;
        $arg->ids = $ids;
        return $this->sforce->delete($arg)->result;
    }

    /**
     * Deletes one or more new individual objects to your organization's data.
     *
     * @param array $ids    Array of fields
     * @return DeleteResult
     */
    public function undelete($ids) {
        $this->setHeaders("undelete");
        $arg = new stdClass;
        $arg->ids = $ids;
        return $this->sforce->undelete($arg)->result;
    }

    /**
     * Deletes one or more new individual objects to your organization's data.
     *
     * @param array $ids    Array of fields
     * @return DeleteResult
     */
    public function emptyRecycleBin($ids) {
        $this->setHeaders();
        $arg = new stdClass;
        $arg->ids = $ids;
        return $this->sforce->emptyRecycleBin($arg)->result;
    }

    //    public function purge($ids) {
    //        return $this->emptyRecycleBin($ids);
    //    }

    /**
     * Process Submit Request for Approval
     *
     * @param array $processRequestArray
     * @return ProcessResult
     */
    public function processSubmitRequest($processRequestArray) {
        if (is_array($processRequestArray)) {
            foreach ($processRequestArray as &$process) {
                $process = new SoapVar($process, SOAP_ENC_OBJECT, 'ProcessSubmitRequest', $this->namespace);
            }
            $arg = new stdClass;
            $arg->actions = $processRequestArray;
            return $this->_process($arg);
        } else {
            $backtrace = debug_backtrace();
            die('Please pass in array to this function:  '.$backtrace[0]['function']);
        }
    }

    /**
     * Process Work Item Request for Approval
     *
     * @param array $processRequestArray
     * @return ProcessResult
     */
    public function processWorkitemRequest($processRequestArray) {
        if (is_array($processRequestArray)) {
            foreach ($processRequestArray as &$process) {
                $process = new SoapVar($process, SOAP_ENC_OBJECT, 'ProcessWorkitemRequest', $this->namespace);
            }
            $arg = new stdClass;
            $arg->actions = $processRequestArray;
            return $this->_process($arg);
        } else {
            $backtrace = debug_backtrace();
            die('Please pass in array to this function:  '.$backtrace[0]['function']);
        }
    }

    /**
     * Retrieves a list of available objects for your organization's data.
     *
     * @return DescribeGlobalResult
     */
    public function describeGlobal() {
        $this->setHeaders("describeGlobal");
        return $this->sforce->describeGlobal()->result;
    }

    /**
     * Use describeLayout to retrieve information about the layout (presentation
     * of data to users) for a given object type. The describeLayout call returns
     * metadata about a given page layout, including layouts for edit and
     * display-only views and record type mappings. Note that field-level security
     * and layout editability affects which fields appear in a layout.
     *
     * @param string Type   Object Type
     * @return DescribeLayoutResult
     */
    public function describeLayout($type) {
        $this->setHeaders("describeLayout");
        $arg = new stdClass;
        $arg->sObjectType = new SoapVar($type, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
        return $this->sforce->describeLayout($arg)->result;
    }

    /**
     * Describes metadata (field list and object properties) for the specified
     * object.
     *
     * @param string $type    Object type
     * @return DescribsSObjectResult
     */
    public function describeSObject($type) {
        $this->setHeaders("describeSObject");
        $arg = new stdClass;
        $arg->sObjectType = new SoapVar($type, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
        return $this->sforce->describeSObject($arg)->result;
    }

    /**
     * An array-based version of describeSObject; describes metadata (field list
     * and object properties) for the specified object or array of objects.
     *
     * @param array $arrayOfTypes    Array of object types.
     * @return DescribsSObjectResult
     */
    public function describeSObjects($arrayOfTypes) {
        $this->setHeaders("describeSObjects");
        return $this->sforce->describeSObjects($arrayOfTypes)->result;
    }

    /**
     * The describeTabs call returns information about the standard apps and
     * custom apps, if any, available for the user who sends the call, including
     * the list of tabs defined for each app.
     *
     * @return DescribeTabSetResult
     */
    public function describeTabs() {
        $this->setHeaders("describeTabs");
        return $this->sforce->describeTabs()->result;
    }

    /**
     * Retrieves the list of individual objects that have been deleted within the
     * given timespan for the specified object.
     *
     * @param string $type    Ojbect type
     * @param date $startDate  Start date
     * @param date $endDate   End Date
     * @return GetDeletedResult
     */
    public function getDeleted($type, $startDate, $endDate) {
        $this->setHeaders("getDeleted");
        $arg = new stdClass;
        $arg->sObjectType = new SoapVar($type, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
        $arg->startDate = $startDate;
        $arg->endDate = $endDate;
        return $this->sforce->getDeleted($arg)->result;
    }

    /**
     * Retrieves the list of individual objects that have been updated (added or
     * changed) within the given timespan for the specified object.
     *
     * @param string $type    Ojbect type
     * @param date $startDate  Start date
     * @param date $endDate   End Date
     * @return GetUpdatedResult
     */
    public function getUpdated($type, $startDate, $endDate) {
        $this->setHeaders("getUpdated");
        $arg = new stdClass;
        $arg->sObjectType = new SoapVar($type, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
        $arg->startDate = $startDate;
        $arg->endDate = $endDate;
        return $this->sforce->getUpdated($arg)->result;
    }

    /**
     * Executes a query against the specified object and returns data that matches
     * the specified criteria.
     *
     * @param String $query Query String
     * @param QueryOptions $queryOptions  Batch size limit.  OPTIONAL
     * @return QueryResult
     */
    public function query($query) {
        $this->setHeaders("query");
        $QueryResult = $this->sforce->query(array (
                      'queryString' => $query
        ))->result;
        $this->_handleRecords($QueryResult);
        return $QueryResult;
    }

    /**
     * Retrieves the next batch of objects from a query.
     *
     * @param QueryLocator $queryLocator Represents the server-side cursor that tracks the current processing location in the query result set.
     * @param QueryOptions $queryOptions  Batch size limit.  OPTIONAL
     * @return QueryResult
     */
    public function queryMore($queryLocator) {
        $this->setHeaders("queryMore");
        $arg = new stdClass;
        $arg->queryLocator = $queryLocator;
        $QueryResult = $this->sforce->queryMore($arg)->result;
        $this->_handleRecords($QueryResult);
        return $QueryResult;
    }

    /**
     * Retrieves data from specified objects, whether or not they have been deleted.
     *
     * @param String $query Query String
     * @param QueryOptions $queryOptions  Batch size limit.  OPTIONAL
     * @return QueryResult
     */
    public function queryAll($query, $queryOptions = NULL) {
        $this->setHeaders("queryAll");
        $QueryResult = $this->sforce->queryAll(array (
                        'queryString' => $query
        ))->result;
        $this->_handleRecords($QueryResult);
        return $QueryResult;
    }


    private function _handleRecords(& $QueryResult) {
        if ($QueryResult->size > 0 && isset($QueryResult->records)) {
            if ($QueryResult->size == 1) {
                $recs = array (
                $QueryResult->records
                );
            } else {
                $recs = $QueryResult->records;
            }
            $QueryResult->records = $recs;
        }
    }


    /**
     * Retrieves one or more objects based on the specified object IDs.
     *
     * @param string $fieldList      One or more fields separated by commas.
     * @param string $sObjectType    Object from which to retrieve data.
     * @param array $ids            Array of one or more IDs of the objects to retrieve.
     * @return sObject[]
     */
    public function retrieve($fieldList, $sObjectType, $ids) {
        $this->setHeaders("retrieve");
        $arg = new stdClass;
        $arg->fieldList = $fieldList;
        $arg->sObjectType = new SoapVar($sObjectType, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
        $arg->ids = $ids;
        return $this->sforce->retrieve($arg)->result;
    }

    /**
     * Executes a text search in your organization's data.
     *
     * @param string $searchString   Search string that specifies the text expression to search for.
     * @return SearchResult
     */
    public function search($searchString) {
        $this->setHeaders("search");
        $arg = new stdClass;
        $arg->searchString = new SoapVar($searchString, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
        $SearchResult = $this->sforce->search($arg)->result;
        if (isset($SearchResult->searchRecords) && !is_array($SearchResult->searchRecords)) {
            $SearchResult->searchRecords = array($SearchResult->searchRecords);
        }
        return $SearchResult;
    }

    /**
     * Retrieves the current system timestamp (GMT) from the Web service.
     *
     * @return timestamp
     */
    public function getServerTimestamp() {
        $this->setHeaders("getServerTimestamp");
        return $this->sforce->getServerTimestamp()->result;
    }

    public function getUserInfo() {
        $this->setHeaders("getUserInfo");
        return $this->sforce->getUserInfo()->result;
    }

    /**
     * Sets the specified user's password to the specified value.
     *
     * @param string $userId    ID of the User.
     * @param string $password  New password
     */
    public function setPassword($userId, $password) {
        $this->setHeaders("setPassword");
        $arg = new stdClass;
        $arg->userId = new SoapVar($userId, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
        $arg->password = $password;
        return $this->sforce->setPassword($arg);
    }

    /**
     * Changes a user's password to a system-generated value.
     *
     * @param string $userId    Id of the User
     * @return password
     */
    public function resetPassword($userId) {
        $this->setHeaders("resetPassword");
        $arg = new stdClass;
        $arg->userId = new SoapVar($userId, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
        return $this->sforce->resetPassword($arg)->result;
    }

    /**
     * Logs out the current user
     */
    public function logout() {
        $this->setHeaders("logout");
        return $this->sforce->logout();
    }
}
?>
