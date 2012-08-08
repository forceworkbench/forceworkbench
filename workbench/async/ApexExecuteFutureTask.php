<?php
require_once "FutureTask.php";

class ApexExecuteFutureTask extends FutureTask {

    private $executeAnonymousBlock;
    private $logCategory;
    private $logCategoryLevel;

    function __construct($executeAnonymousBlock, $logCategory, $logCategoryLevel) {
        parent::__construct();
        $this->executeAnonymousBlock = $executeAnonymousBlock;
        $this->logCategory = $logCategory;
        $this->logCategoryLevel = $logCategoryLevel;
    }

    function perform() {
        WorkbenchContext::get()->getApexConnection()->setDebugLevels($this->logCategory, $this->logCategoryLevel);
        return WorkbenchContext::get()->getApexConnection()->executeAnonymous($this->executeAnonymousBlock);
    }
}
