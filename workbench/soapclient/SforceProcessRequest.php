<?php
abstract class ProcessRequest {
	public $comments;
	public $nextApproverIds;
}

class ProcessSubmitRequest extends ProcessRequest {
	public $objectId;
}

class ProcessWorkitemRequest extends ProcessRequest {
  public $action;
  public $workitemId;
}
?>