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
require_once ('SforceBaseClient.php');

/**
 * This file contains two classes.
 * @package SalesforceSoapClient
 */
/**
 * SforceEnterpriseClient class.
 *
 * @package SalesforceSoapClient
 */
class SforceEnterpriseClient extends SforceBaseClient {
  const ENTERPRISE_NAMESPACE = 'urn:enterprise.soap.sforce.com';

  function SforceEnterpriseClient() {
    $this->namespace = self::ENTERPRISE_NAMESPACE;
  }

  /**
   * Adds one or more new individual objects to your organization's data.
   * @param array $sObjects    Array of one or more sObjects (up to 200) to create.
   * @param AssignmentRuleHeader $assignment_header is optional.  Defaults to NULL
   * @param MruHeader $mru_header is optional.  Defaults to NULL
   * @return SaveResult
   */
  public function create($sObjects, $type) {
    foreach ($sObjects as &$sobject) {
      $sobject = new SoapVar($sobject, SOAP_ENC_OBJECT, $type, $this->namespace);
    }
    $arg = $sObjects;

    return parent::_create(new SoapParam($arg, "sObjects"));
  }

  /**
   * Updates one or more new individual objects to your organization's data.
   * @param array sObjects    Array of sObjects
   * @param AssignmentRuleHeader $assignment_header is optional.  Defaults to NULL
   * @param MruHeader $mru_header is optional.  Defaults to NULL
   * @return UpdateResult
   */
  public function update($sObjects, $type, $assignment_header = NULL, $mru_header = NULL) {
    foreach ($sObjects as &$sObject) {
      $sObject = new SoapVar($sObject, SOAP_ENC_OBJECT, $type, $this->namespace);
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
   * @param string $ext_Id        External Id
   * @param array  $sObjects  Array of sObjects
   * @return UpsertResult
   */
  public function upsert($ext_Id, $sObjects) {
    $arg = new stdClass;
    $arg->externalIDFieldName = new SoapVar($ext_Id, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
    foreach ($sObjects as &$sObject) {
      $sObject = new SoapVar($sObject, SOAP_ENC_OBJECT, 'Contact', $this->namespace);
    }
    $arg->sObjects = $sObjects;
    return parent::_upsert($arg);
  }

  /**
   * Merge records
   *
   * @param stdclass $mergeRequest
   * @param String $type
   * @return unknown
   */
  public function merge($mergeRequest, $type) {
    $mergeRequest->masterRecord = new SoapVar($mergeRequest->masterRecord, SOAP_ENC_OBJECT, $type, $this->namespace);
    $arg->request = new SoapVar($mergeRequest, SOAP_ENC_OBJECT, 'MergeRequest', $this->namespace);
    return parent::_merge($arg);
  }
}
?>