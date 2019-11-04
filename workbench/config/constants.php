<?php
//////////////////////////////////////////////////////////////////////////////////////////
//
//
//  CONSTANTS. DO NOT CHANGE.
//
//
//////////////////////////////////////////////////////////////////////////////////////////

$GLOBALS["WORKBENCH_VERSION"] = "47.0.2";

class Page {
    public $title;
    public $desc;
    public $requiresSfdcSession;
    public $isReadOnly;
    public $onNavBar;
    public $onMenuSelect;
    public $showTitle;
    public $window;
    public $noHeaderFooterForExceptionHandler;

    public function __construct($title, $desc, $requiresSfdcSession=true, $isReadOnly=false, $onNavBar=false, $onMenuSelect=false, $showTitle=true, $window='', $noHeaderFooterForExceptionHandler=false) {
        $this->title = $title;
        $this->desc = $desc;
        $this->requiresSfdcSession = $requiresSfdcSession;
        $this->isReadOnly = $isReadOnly;
        $this->onNavBar = $onNavBar;
        $this->onMenuSelect = $onMenuSelect;
        $this->showTitle = $showTitle;
        $this->window = $window;
        $this->noHeaderFooterForExceptionHandler = $noHeaderFooterForExceptionHandler;
    }
}

$GLOBALS["MENUS"] = array(
    'WORKBENCH' => array(
        'login.php'           => new Page('Login','Logs into your Salesforce organization.',false,true,true,false,false,''),
        'select.php'          => new Page('Select','Select action to which to jump.',true,true,false,false,false,''),
        'settings.php'        => new Page('Settings','Configuration for Workbench.',false,true,true,false,true,''),
        'logout.php'          => new Page('Logout','Logs out of your Salesforce organization.',false,true,true,false,false,''),
        'help.php'            => new Page('Help','Get help about using Workbench.',false,true,true,false,true,''),
        'about.php'           => new Page('About','Learn about Workbench.',false,true,true,false,true,''),
        'terms.php'           => new Page('Terms of Service','Terms of Service.',false,true,false,false,true,''),
        'healthcheck.php'     => new Page('Health Check','Health Check',false,true,false,false,false,'')
    ),

    'Info' => array(
        'describe.php'          => new Page('Standard & Custom Objects','Describes the attributes, fields, record types, and child relationships of an object in a tree format.',true,true,true,'usesObject',true,''),
        'metadataDescribeAndList.php'      => new Page('Metadata Types & Components','Describes and lists the metadata components in this organization.',true,true,true,true,true,''),
        'sessionInfo.php'       => new Page('Session Information','Information about the current session.',true,true,true,false,true,''),
    ),

    'Queries' => array(
        'query.php'                     => new Page('SOQL Query','Queries the data in your organization and displays on the screen or exports to a CSV file.',true,true,true,'usesObject',true,''),
        'asyncSOQL.php'                 => new Page('Async SOQL Query','Define, execute and monitor Async SOQL queries',true,true,true,'usesObject',true,'',true),
        'asyncSOQLViewJobDetails.php'   => new Page('Job Details','Receives and responds to xhttp request from Async SOQL View Status page to display job details as well as cancel specified job',false,true,false,false,false,''),
        'asyncSOQLDisplayFields.php'    => new Page('Display Object Fields','Receives xhttp request and adds source/target object fields to the page.',false,true,false,false,false,''),
        'asyncSOQLSubmitJob.php'        => new Page('Submit Job','Receives xhttp request for Async SOQL job submission, processes it and submits the job.',false,true,false,false,false,''),
        'asyncSOQLDefineQuery.php'      => new Page('Async SOQL Define Query','Contains DOM elements to submit new Async SOQL query and display result.',true,true,false,false,false,''),
        'asyncSOQLViewStatus.php'       => new Page('Async SQOL View Status','Displays details of all Async SOQL jobs.',true,true,false,false,false,''),      
        'search.php'                    => new Page('SOSL Search','Search the data in your organization across multiple objects.',true,true,true,'usesObject',true,''),
        'streaming.php'                 => new Page('Streaming Push Topics','Streaming latest query results in push topics using CometD long polling.',true,false,true,true,true,''),

    ),

    'Data' => array(
        'retrieve.php'  => new Page('Retrieve','View a record.',true,true,false,false,false,''),
        'insert.php'    => new Page('Insert','Creates new records.',true,false,true,'usesObject',true,''),
        'update.php'    => new Page('Update','Updates existing records.',true,false,true,'usesObject',true,''),
        'upsert.php'    => new Page('Upsert','Creates new records and/or updates existing records based on a unique External Id.',true,false,true,'usesObject',true,''),
        'delete.php'    => new Page('Delete','Moves records to the Recycle Bin.',true,false,true,true,true,''),
        'undelete.php'  => new Page('Undelete','Restores records from the Recycle Bin.',true,false,true,true,true,''),
        'purge.php'     => new Page('Purge','Permanently deletes records from your Recycle Bin.',true,false,true,true,true,'')
     ),

    'Migration' => array(
        'metadataDeploy.php'    => new Page('Deploy','Deploys metadata components to this organization.',true,false,true,true,true,''),
        'metadataRetrieve.php'  => new Page('Retrieve','Retrieves metadata components from this organization.',true,true,true,true,true,''),
    ),

    'Utilities' => array(
        'restExplorer.php'            => new Page('REST Explorer','Explore and discover the REST API.',true,false,true,true,true,''),
        'execute.php'                 => new Page('Apex Execute','Execute Apex code as an anonymous block.',true,false,true,true,true,''),
        'pwdMgmt.php'                 => new Page('Password Management','Set and Reset Passwords.',true,false,true,false,true,''),
        'asyncStatus.php'             => new Page('Bulk API Job Status','Asynchronous data load status and results.',true,true,true,false,true,''),
        'metadataStatus.php'          => new Page('Metadata API Process Status','Metadata API status and results.',true,true,true,false,true,''),
        'downloadAsyncBatch.php'      => new Page('Download Bulk API Batch','Downloads Bulk API requests and results.',true,true,false,false,true,''),
        'downloadResultsWithData.php' => new Page('Download DML Results','Downloads DML results.',true,true,false,false,true,''),
        'csv_preview.php'             => new Page('CSV Preview','Previews CSV upload.',true,true,false,false,true,''),
        'jumpToSfdc.php'              => new Page('Jump to SFDC','Jumps to SFDC user interface for a given id.',true,true,false,false,true,''),
        'cometdProxy.php'             => new Page('CometD Proxy','CometD Proxy for Streaming API support.',true,true,false,false,false,''),
        'future_get.php'              => new Page('Future Get','Future Get',true,false,false,false,false,'')
     )
);

?>
