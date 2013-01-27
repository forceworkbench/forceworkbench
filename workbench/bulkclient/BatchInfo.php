<?php

/**
 * PHP BULK API CLIENT
 * @author Ryan Brainard
 *
 * BatchInfo.php
 * Represents a Force.com Bulk API BatchInfo object.
 *
 * For reference, see:
 * http://www.salesforce.com/us/developer/docs/api_asynch/Content/asynch_api_reference_batchinfo.htm
 *
 *
 * This client is NOT a supported product of or supported by salesforce.com, inc.
 * For support from the Open Source community, please visit the resources below:
 *
 * * Main Project Site
 *   https://github.com/ryanbrainard/forceworkbench
 *
 * * Feedback & Discussion
 *   http://groups.google.com/group/forceworkbench
 *
 * Copyright (c) 2013, salesforce.com, inc.
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
 *
 */

class BatchInfo {

    private $xml;

    public function __construct($xml) {
        $this->xml = new SimpleXMLElement($xml);

        if ($this->getExceptionCode() != "") {
            throw new Exception($this->getExceptionCode() . ": " . $this->getExceptionMessage());
        }
    }

    //GETTERS
    public function getId() {
        return $this->xml->id;
    }

    public function getJobId() {
        return $this->xml->jobId;
    }

    public function getState() {
        return $this->xml->state;
    }

    public function getStateMessage() {
        return $this->xml->stateMessage;
    }

    public function getCreatedDate() {
        return $this->xml->createdDate;
    }

    public function getSystemModstamp() {
        return $this->xml->systemModstamp;
    }

    public function getNumberRecordsProcessed() {
        return $this->xml->numberRecordsProcessed;
    }

    public function getExceptionCode() {
        return $this->xml->exceptionCode;
    }

    public function getExceptionMessage() {
        return $this->xml->exceptionMessage;
    }

    //New in 19.0 Below:

    public function getTotalProcessingTime() {
        return $this->xml->totalProcessingTime;
    }

    public function getApexProcessingTime() {
        return $this->xml->apexProcessingTime;
    }

    public function getApiActiveProcessingTime() {
        return $this->xml->apiActiveProcessingTime;
    }

    public function getNumberRecordsFailed() {
        return $this->xml->numberRecordsFailed;
    }
}
?>