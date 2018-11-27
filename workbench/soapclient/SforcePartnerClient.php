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
require_once 'SforceBaseClient.php';
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
class SforcePartnerClient extends SforceBaseClient {
    const PARTNER_NAMESPACE = 'urn:partner.soap.sforce.com';

    function __construct() {
        $this->namespace = self::PARTNER_NAMESPACE;
    }

    /**
     * Adds one or more new individual objects to your organization's data.
     * @param array $sObjects    Array of one or more sObjects (up to 200) to create.
     * @param AssignmentRuleHeader $assignmentHeader is optional.  Defaults to NULL
     * @param MruHeader $mruHeader is optional.  Defaults to NULL
     * @return SaveResult
     */
    public function create($sObjects) {
        $arg = new stdClass;
        foreach ($sObjects as $sObject) {
            if (isset ($sObject->fields)) {
                $sObject->any = $this->_convertToAny($sObject->fields);
            }
        }
        $arg->sObjects = $sObjects;
        return parent::_create($arg);
    }

    /**
     * Merge records
     *
     * @param stdclass $mergeRequest
     * @param String $type
     * @return unknown
     */
    public function merge($mergeRequest) {
        if (isset($mergeRequest->masterRecord)) {
            if (isset ($mergeRequest->masterRecord->fields)) {
                $mergeRequest->masterRecord->any = $this->_convertToAny($mergeRequest->masterRecord->fields);
            }
            //return parent::merge($mergeRequest, $type);
            $arg = new stdClass;
            $arg->request = $mergeRequest;
            return $this->_merge($arg);
        }
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
            return parent::_sendEmail($arg);
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
            return parent::_sendEmail($arg);
        } else {
            $backtrace = debug_backtrace();
            die('Please pass in array to this function:  '.$backtrace[0]['function']);
        }
    }

    /**
     * Updates one or more new individual objects to your organization's data.
     * @param array sObjects    Array of sObjects
     * @param AssignmentRuleHeader $assignmentHeader is optional.  Defaults to NULL
     * @param MruHeader $mruHeader is optional.  Defaults to NULL
     * @return UpdateResult
     */
    public function update($sObjects) {
        $arg = new stdClass;
        foreach ($sObjects as $sObject) {
            if (isset ($sObject->fields)) {
                $sObject->any = $this->_convertToAny($sObject->fields);
            }
        }
        $arg->sObjects = $sObjects;
        return parent::_update($arg);
    }

    /**
     * Creates new objects and updates existing objects; uses a custom field to
     * determine the presence of existing objects. In most cases, we recommend
     * that you use upsert instead of create because upsert is idempotent.
     * Available in the API version 7.0 and later.
     *
     * @param string $extId        External Id
     * @param array  $sObjects  Array of sObjects
     * @return UpsertResult
     */
    public function upsert($extId, $sObjects) {
        //        $this->_setSessionHeader();
        $arg = new stdClass;
        $arg->externalIDFieldName = new SoapVar($extId, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
        foreach ($sObjects as $sObject) {
            if (isset ($sObject->fields)) {
                $sObject->any = $this->_convertToAny($sObject->fields);
            }
        }
        $arg->sObjects = $sObjects;
        return parent::_upsert($arg);
    }
}

class QueryResult {
    public $queryLocator;
    public $done;
    public $records;
    public $size;

    public function __construct($response) {
        $this->queryLocator = $response->queryLocator;
        $this->done = $response->done;
        $this->size = $response->size;

        $this->records = array();

        if (isset($response->records)) {
            if (is_array($response->records)) {
                foreach ($response->records as $record) {
                    $sobject = new SObject($record);
                    array_push($this->records, $sobject);
                };
            } else {
                $sobject = new SObject($response->records);
                array_push($this->records, $sobject);
            }
        }
    }
}


class SearchResult {
    public $searchRecords;

    public function __construct($response) {

        $this->searchRecords = array();

        if (isset($response->searchRecords)) {
            if (is_array($response->searchRecords)) {
                foreach ($response->searchRecords as $record) {
                    $sobject = new SObject($record);
                    array_push($this->searchRecords, $sobject);
                };
            } else {
                $sobject = new SObject($response->searchRecords);
                array_push($this->searchRecords, $sobject);
            }
        }
    }
}

/**
 * Salesforce Object
 *
 * @package SalesforceSoapClient
 */
class SObject {
    public $type;
    public $fields;
    public $fieldsToNull;
    //  public $sobject;

    public function __construct($response=NULL) {
        if (isset($response)) {
            if (isset($response->Id)) $this->Id = $response->Id[0];
            if (isset($response->type)) $this->type = $response->type;
            if (isset($response->fieldsToNull)) $this->fieldsToNull = $response->fieldsToNull;
            if (isset($response->any)) {
                // If ANY is an object, it is either a nested sObject or a nested QueryResult
                if ($response->any instanceof stdClass) {
                    //Nested sObjects (child to parent relationships)
                    if ($this->isSObject($response->any)) {
                        $parentSObjects = array();
                        $sobject = new SObject($response->any);
                        array_push($parentSObjects, $sobject);
                        $this->sobjects = $parentSObjects;
                        //Nested QueryResult (parent to child relationships)
                    } else {
                        $this->queryResult = new QueryResult($response->any);
                    }
                    //Otherwise ANY must be just a field
                } else {
                    //if ANY is not an array, make it one.
                    if (!is_array($response->any)) {
                        $response->any = array($response->any);
                    }

                    $anArray = array();
                    $fieldsToConvert = "";
                    $unknownFieldNum = 1;
                    foreach ($response->any as $item) {
                        if ($item instanceof stdClass) {
                            if ($this->isSObject($item)) {
                                $sobject = new SObject($item);
                                array_push($anArray, $sobject);
                            } else {
                                // this is for parent to child relationships
                                if (!isset($this->queryResult)) {
                                    $this->queryResult = array();
                                }
                                array_push($this->queryResult, new QueryResult($item));
                            }
                        } else {
                            //if the field is not wrapped in sf tag, consider it of an unknown type
                            if (!strstr($item, '<sf:')) {
                                $unknownTag = "Unknown_Field__" . $unknownFieldNum++;
                                $item = "<$unknownTag>$item</$unknownTag>";
                            }
                            $fieldsToConvert .= $item;
                        }
                    }
                    if (isset($fieldsToConvert)) {
                        $convertedFields = preg_replace('/sf:/', '', $fieldsToConvert);
                        $convertedFields = '<Object xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'.$convertedFields.'</Object>';
                        try {
                            libxml_disable_entity_loader(true);
                            $this->fields = simplexml_load_string(disallowDoctype($convertedFields));
                        } finally {
                            libxml_disable_entity_loader(false);
                        }
                    }
                    if (sizeof($anArray) > 0) {
                        $this->sobjects = $anArray;
                    }
                }
            }
        }
    }

    /*
     * If the stdClass has a done, we know it is a QueryResult
     */
    function isQueryResult($param) {
        return isset($param->done);
    }

    /*
     * If the stdClass has a type, we know it is an SObject
     */
    function isSObject($param) {
        return isset($param->type);
    }
}
?>