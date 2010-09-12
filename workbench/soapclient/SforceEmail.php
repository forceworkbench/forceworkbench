<?php
define ("EMAIL_PRIORITY_HIGHEST", 'Highest');
define ("EMAIL_PRIORITY_HIGH", 'High');
define ("EMAIL_PRIORITY_NORMAL", 'Normal');
define ("EMAIL_PRIORITY_LOW", 'Low');
define ("EMAIL_PRIORITY_LOWEST", 'Lowest');

class Email {
  public function setBccSender($bccSender) {
    $this->bccSender = $bccSender;
  }

  public function setEmailPriority($priority) {
    $this->emailPriority = $priority;
  }
   
  public function setSubject($subject) {
    $this->subject = $subject;
  }

  public function setSaveAsActivity($saveAsActivity) {
    $this->saveAsActivity = $saveAsActivity;
  }

  public function setReplyTo($replyTo) {
    $this->replyTo = $replyTo;
  }

  public function setUseSignature($useSignature) {
    $this->useSignature = $useSignature;
  }
  
  public function setSenderDisplayName($name) {
    $this->senderDisplayName = $name;
  }
}

class SingleEmailMessage extends Email {
  public function __construct() {}


  public function setBccAddresses($addresses) {
    $this->bccAddresses = $addresses;
  }
  public $ccAddresses;

  public function setCcAddresses($addresses) {
    $this->ccAddresses = $addresses;
  }

  public function setCharset($charset) {
    $this->charset = $charset;
  }

  public function setHtmlBody($htmlBody) {
    $this->htmlBody = $htmlBody;
  }

  public function setPlainTextBody($plainTextBody) {
    $this->plainTextBody = $plainTextBody;
  }

  public function setTargetObjectId($targetObjectId) {
    $this->targetObjectId = $targetObjectId;
  }

  public function setTemplateId($templateId) {
    $this->templateId = $templateId;
  }

  public function setToAddresses($array) {
    $this->toAddresses = $array;
  }

  public function setWhatId($whatId) {
    $this->whatId = $whatId;
  }

  public function setFileAttachments($array) {
    $this->fileAttachments = $array;
  }

  public function setDocumentAttachments($array) {
    $this->documentAttachments = $array;
  }
}

class MassEmailMessage extends Email {
  public function setTemplateId($templateId) {
    $this->templateId = $templateId;
  }

  public function setWhatIds($array) {
    $this->whatIds = $array;
  }

  public function setTargetObjectIds($array) {
    $this->targetObjectIds = $array;
  }
}
?>