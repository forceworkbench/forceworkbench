<?php

/**
 * PHP BULK API CLIENT
 * @author Ryan Brainard
 *
 * JobInfo.php
 * Represents a Force.com Bulk API JobInfo object.
 *
 * For reference, see:
 * http://www.salesforce.com/us/developer/docs/api_asynch/Content/asynch_api_reference_jobinfo.htm
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
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF
 * THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

class JobInfo {
    private $xml;

    public function __construct($xml = null) {
        if ($xml != null) {
            $this->xml = new SimpleXMLElement($xml);
        } else {
            $this->xml = new SimpleXMLElement("<jobInfo xmlns=\"http://www.force.com/2009/06/asyncapi/dataload\"/>");

            //setting writeable fields in their required sequence; otherwise, API can't parse correctly
            //if any of them are still empty after  setting values, we unset them before converting to XML
            $this->xml->id = "";
            $this->xml->operation = "";
            $this->xml->object = "";
            $this->xml->state = "";
            $this->xml->externalIdFieldName = "";
            $this->xml->concurrencyMode = "";
            $this->xml->contentType = "";
            $this->xml->assignmentRuleId = "";
        }

        if ($this->getExceptionCode() != "") {
            throw new Exception($this->getExceptionCode() . ": " . $this->getExceptionMessage());
        }
    }

    public function asXml() {
        //removing empty fields to allow API to parse correctly
        //two loops are needed to not cause errors
        $emptyFields = array();
        foreach ($this->xml as $field=>$value) {
            if ($value == "") {
                $emptyFields[] = $field;
            }
        }
        foreach ($emptyFields as $field) {
            unset($this->xml->$field);
        }

        return $this->xml->asXML();
    }

    //SETTERS
    public function setId($id) {
        $this->xml->id = $id;
    }

    public function setOpertion($operation) {
        $this->xml->operation = $operation;
    }

    public function setObject($object) {
        $this->xml->object = $object;
    }

    public function setExternalIdFieldName($externalIdFieldName) {
        $this->xml->externalIdFieldName = $externalIdFieldName;
    }

    public function setAssignmentRuleId($assignmentRuleId) {
        $this->xml->assignmentRuleId = $assignmentRuleId;
    }

    public function setState($state) {
        $this->xml->state = $state;
    }

    public function setConcurrencyMode($concurrencyMode) {
        $this->xml->concurrencyMode = $concurrencyMode;
    }

    public function setContentType($contentType) {
        $this->xml->contentType = $contentType;
    }

    //GETTERS
    public function getId() {
        return $this->xml->id;
    }

    public function getOpertion() {
        return $this->xml->operation;
    }

    public function getObject() {
        return $this->xml->object;
    }

    public function getExternalIdFieldName() {
        return $this->xml->externalIdFieldName;
    }

    public function getCreatedById() {
        return $this->xml->createdById;
    }

    public function getCreatedDate() {
        return $this->xml->createdDate;
    }

    public function getSystemModstamp() {
        return $this->xml->systemModstamp;
    }

    public function getState() {
        return $this->xml->state;
    }

    public function getStateMessage() {
        return $this->xml->stateMessage;
    }

    public function getConcurrencyMode() {
        return $this->xml->concurrencyMode;
    }

    public function getContentType() {
        return $this->xml->contentType;
    }

    public function getNumberBatchesQueued() {
        return $this->xml->numberBatchesQueued;
    }

    public function getNumberBatchesInProgress() {
        return $this->xml->numberBatchesInProgress;
    }

    public function getNumberBatchesCompleted() {
        return $this->xml->numberBatchesCompleted;
    }

    public function getNumberBatchesFailed() {
        return $this->xml->numberBatchesFailed;
    }

    public function getNumberBatchesTotal() {
        return $this->xml->numberBatchesTotal;
    }

    public function getNumberRecordsProcessed() {
        return $this->xml->numberRecordsProcessed;
    }

    public function getNumberRetries() {
        return $this->xml->numberRetries;
    }

    public function getApiVersion() {
        return $this->xml->apiVersion;
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