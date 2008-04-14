<?php
require_once('SforceFieldTypes.php');

class SforceCustomObject {
  public function setDeploymentStatus($deploymentStatus) {
    $this->deploymentStatus = $deploymentStatus;
  }

  public function setDescription($description) {
    $this->description = $description;
  }

  public function setEnableActivities($enableActivities) {
    $this->enableActivities = $enableActivities;
  }

  public function setEnableDivisions($enableDivisions) {
    $this->enableDivisions = $enableDivisions;
  }

  public function setEnableHistory($enableHistory) {
    $this->enableHistory = $enableHistory;
  }

  public function setEnableReports($enableReports) {
    $this->enableReports = $enableReports;
  }

  public function setFields($fields) {
    $this->fields = $fields;
  }

  public function setFullName($fullName) {
    $this->fullName = $fullName;
  }

  public function setGender($gender) {
    $this->gender = $gender;
  }

  public function setHousehold($household) {
    $this->household = $household;
  }

  public function setLabel($label) {
    $this->label = $label;
  }

  public function setNameField($nameField) {
    $this->nameField = $nameField;
  }

  public function setPluralLabel($pluralLabel) {
    $this->pluralLabel = $pluralLabel;
  }

  public function setStartsWith($startsWith) {
    $this->startsWith = $startsWith;
  }
}

class SforceCustomField {
  public function setCaseSensitive($caseSensitive) {
    $this->caseSensitive = $caseSensitive;
  }

  public function setDefaultValue($defaultValue) {
    $this->defaultValue = $defaultValue;
  }

  public function setDescription($description) {
    $this->description = $description;
  }

  public function setDisplayFormat($displayFormat) {
    $this->displayFormat = $displayFormat;
  }

  public function setExternalId($externalId) {
    $this->externalId = $externalId;
  }

  public function setFormula($formula) {
    $this->formula = $formula;
  }

  public function setFormulaTreatBlankAs($formulaTreatBlankAs) {
    $this->formulaTreatBlankAs = $formulaTreatBlankAs;
  }

  public function setFullName($fullName) {
    $this->fullName = $fullName;
  }

  public function setInlineHelpText($inlineHelpText) {
    $this->inlineHelpText = $inlineHelpText;
  }

  public function setLabel($label) {
    $this->label = $label;
  }

  public function setLength($length) {
    $this->length = $length;
  }

  public function setMaskChar($maskChar) {
    $this->maskChar = $maskChar;
  }

  public function setMaskType($maskType) {
    $this->maskType = $maskType;
  }

  public function setPicklist($picklist) {
    $this->picklist = $picklist;
  }

  public function setPopulateExistingRows($populateExistingRows) {
    $this->populateExistingRows = $populateExistingRows;
  }

  public function setPrecision($precision) {
    $this->precision = $precision;
  }

  public function setReferenceTo($referenceTo) {
    $this->referenceTo = $referenceTo;
  }

  public function setRelationshipName($relationshipName) {
    $this->relationshipName = $relationshipName;
  }

  public function setRequired($required) {
    $this->required = $required;
  }

  public function setScale($scale) {
    $this->scale = $scale;
  }

  public function setStartingNumber($startingNumber) {
    $this->startingNumber = $startingNumber;
  }

  public function setSummarizeField($summarizeField) {
    $this->summarizeField = $summarizeField;
  }

  public function setSummaryFilterItems($summaryFilterItems) {
    $this->summaryFilterItems = $summaryFilterItems;
  }

  public function setSummaryForeignKey($summaryForeignKey) {
    $this->summaryForeignKey = $summaryForeignKey;
  }

  public function setSummaryOperation($summaryOperation) {
    $this->summaryOperation = $summaryOperation;
  }

  public function setType($type) {
    $this->type = $type;
  }

  public function setUnique($unique) {
    $this->unique = $unique;
  }

  public function setVisibleLines($visibleLines) {
    $this->visibleLines = $visibleLines;
  }
}
?>
