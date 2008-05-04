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

/**
 * This file contains three classes.
 * @package SalesforceSoapClient
 */


class CallOptions {
	public $client;
	public $defaultNamespace;

	public function __construct($client, $defaultNamespace=NULL) {
		$this->client = $client;
		$this->defaultNamespace = $defaultNamespace;
	}
}



/**
 * To be used with Create and Update operations.
 * Only one attribute can be set at a time.
 *
 * @package SalesforceSoapClient
 */
class AssignmentRuleHeader {
	// int
	public $assignmentRuleId;
	// boolean
	public $useDefaultRuleFlag;

	/**
	 * Constructor.  Only one param can be set.
	 *
	 * @param int $id  AssignmentRuleId
	 * @param boolean $flag  UseDefaultRule flag
	 */
	public function __construct($id = NULL, $flag = NULL) {
		if ($id != NULL) {
			$this->assignmentRuleId = $id;
		}
		if ($flag != NULL) {
			$this->useDefaultRuleFlag = $flag;
		}
	}
}

/**
 * To be used with Create and Update operations.
 *
 * @package SalesforceSoapClient
 */
class MruHeader {
	// boolean that Indicates whether to update the list of most recently used items (True) or not (False).
	public $updateMruFlag;

	public function __construct($bool) {
		$this->updateMruFlag = $bool;
	}
}

/**
 * To be used with the Login operation.
 *
 * @package SalesforceSoapClient
 */
class LoginScopeHeader {
  // boolean that Indicates whether to update the list of most recently used items (True) or not (False).
  public $organizationId;
  public $portalId;

  public function __construct($orgId = NULL, $portalId = NULL) {
    $this->organizationId = $orgId;
    $this->portalId = $portalId;
  }
}

/**
 * To be used with Retrieve, Query, and QueryMore operations.
 *
 * @package SalesforceSoapClient
 */
class QueryOptions {
	// int - Batch size for the number of records returned in a query or queryMore call. The default is 500; the minimum is 200, and the maximum is 2,000.
	public $batchSize;

	/**
	 * Constructor
	 *
	 * @param int $limit  Batch size
	 */
	public function __construct($limit) {
		$this->batchSize = $limit;
	}
}

class EmailHeader {
	public $triggerAutoResponseEmail;
	public $triggerOtherEmail;
	public $triggerUserEmail;

	public function __construct($triggerAutoResponseEmail = false, $triggerOtherEmail = false, $triggerUserEmail = false) {
		$this->triggerAutoResponseEmail = $triggerAutoResponseEmail;
		$this->triggerOtherEmail = $triggerOtherEmail;
		$this->triggerUserEmail = $triggerUserEmail;
	}
}

class UserTerritoryDeleteHeader {
	public $transferToUserId;

	public function __construct($transferToUserId) {
		$this->transferToUserId = $transferToUserId;
	}
}
?>
