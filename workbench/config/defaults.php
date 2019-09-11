<?php
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    TO CONFIGURE WORKBENCH FOR ALL YOUR USERS, ADJUST THE "DEFAULT" VALUES FOR THE KEYS BELOW.
//    FOR EASIER UPGRADING, YOU MAY IT IS RECOMMENDED TO MAKE THESE CHANGES TO THE overrides.php FILE
//    WHICH ARE APPLIED AFTER LOADING CONFIGURATIONS FROM THIS FILE.
//
//    IF YOU WOULD LIKE TO ALLOW YOUR USERS TO OVERRIDE THE DEFAULTS SET THE "OVERRIDEABLE" VALUE TO true.
//    THIS WILL CAUSE THE KEY TO APPEAR ON THE 'SETTINGS' PAGE AND WILL BE CUSTOMIZABLE ON A USER-BY-USER
//    BASIS. THIS IS ACCOMPLISHED BY SETTING COOKIES IN THE USER'S BROWSER AND THE USER'S SETTINGS WILL
//    RETURN TO THE DEFAULTS SET BELOW IF THE COOKIES ARE CLEARED. THE "LABEL" AND "DESCRIPTION" VALUES CONTROL
//    HOW THE SETTING IS DISPLAYED ON THE 'SETTINGS' PAGE.
//
//    DO NOT ALTER THE KEY NAME, "DATATYPE", "MAXVALUE", OR "MINVALUE" VALUES. THESE MUST REMAIN AS IS FOR THE
//    FOR WORKBENCH TO FUNCTION PROPERLY.
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$config["header_General"] = array(
    "label" => "General Options",
    "display" => true,
    "isHeader" => true
);

    $config["linkIdToUi"] = array(
        "label" => "Allow Viewing Records in Salesforce",
        "description" => "Display links on ids to view the corresponding record in the Salesforce user interface. Note, links to objects without detail pages will fail.",
        "default" => true,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["showIdActionsHover"] = array(
        "label" => "Show Ids Actions Hover",
        "description" => "Display menu on Id fields with links to actions.",
        "default" => true,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["abcOrder"] = array(
        "label" => "Alphabetize Field Names",
        "description" => "Alphabetizes field names for across application. Otherwise, field names are displayed in the order returned by Salesforce.",
        "default" => true,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["mruHeader_updateMru"] = array(
        "label" => "Update Recent Items on Sidebar",
        "description" => "Indicates whether to update the list of most recently used (MRU) items on the Salesforce sidebar. For queries, the MRU is only updated when returning one record.",
        "default" => false,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["asyncAutoRefresh"] = array(
        "label" => "Auto Update Asynchronous Operations",
        "description" => "Automatically updates status pages for asynchronous operations.",
        "default" => true,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["checkForLatestVersion"] = array(
        "label" => "Check for Latest Version",
        "description" => "Enables checking for the latest version of Workbench.",
        "default" => true,
        "overrideable" => false,
        "dataType" => "boolean"
    );

    $config["displayRequestTime"] = array(
        "label" => "Display Request Time",
        "description" => "Display the time to render the page in the footer.",
        "default" => true,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["includeSessionCookie"] = array(
        "label" => "Include Session Cookie",
        "description" => "Include the SFDC session id as a cookie",
        "default" => false,
        "overrideable" => false,
        "dataType" => "boolean"
    );

    $config["debug"] = array(
        "label" => "Debug Mode",
        "description" => "Enables debugging mode for showing super-variables and SOAP messages.",
        "default" => false,
        "overrideable" => false, // Only enable in trusted environment, as internal information can be exposed
        "dataType" => "boolean"
    );

    $timezones = array(''=>'UTC');
    if (function_exists('timezone_identifiers_list')) {
        foreach (timezone_identifiers_list() as $timezone) {
                $tz = explode('/',$timezone);
                if (isset($tz[1])) {
                    $timezones[$timezone] = $tz[0].'/'.str_replace('_',' ',$tz[1]);
                }
        }
    }
    $timezones = array_unique($timezones);

    $config["convertTimezone"] = array(
                "label" => "Timezone",
                "description" => "Modifies returned date time fields and data from UTC to selected timezone.",
                "default" => "",
                "overrideable" => true,
                "dataType" => "picklist",
                "valuesToLabels" => $timezones
    );

    $config["localeDateTimeFormat"] = array(
                "label" => "Date/Time Format",
                "description" => "Date/Time format to convert the date/time.",
                "default" => "",
                "overrideable" => true,
                "dataType" => "picklist",
                "valuesToLabels" => array(
			        ""               => "Default",
			        "F jS, Y g:i A"  => "April 30th, 2010 5:30 PM",
			        "jS F Y g:i A"   => "30th April 2010 5:30 PM",
			        "m/d/Y H:i:s"    => "04/30/2010 17:30:00",
			        "m/d/Y g:i:s A"  => "04/30/2010 5:30:00 PM",
			        "d/m/Y H:i:s"    => "30/04/2010 17:30:00",
			        "d/m/Y g:i:s A"  => "30/04/2010 5:30:00 PM",
			        "Y/m/d H:i:s"    => "2010/04/30 17:30:00",
			        "Y/m/d g:i:s A"  => "2010/04/30 5:30:00 PM"
			    )
    );

    $config["callOptions_defaultNamespace"] = array(
        "label" => "Default Namespace",
        "description" => " A string that identifies a developer namespace prefix.",
        "default" => null,
        "overrideable" => true,
        "dataType" => "string"
    );

    $config["textareaRows"] = array(
        "label" => "Text Area Rows",
        "description" => "Number of rows for Query, Search, and Execute text areas.",
        "default" => 5,
        "overrideable" => true,
        "dataType" => "int",
        "minValue" => 1,
        "maxValue" => 100
    );

    $config["enableLogging"] = array(
        "label" => "Enable Logging",
        "description" => "Enables logging to syslog.",
        "default" => true,
        "overrideable" => false,
        "dataType" => "boolean"
    );

    $config["logPrefix"] = array(
        "label" => "Logging Prefix",
        "description" => "String to append to logs",
        "default" => "forceworkbench",
        "overrideable" => false,
        "dataType" => "string"
    );

    $config["logHandler"] = array(
        "label" => "Logging Handle",
        "description" => "Log handler to which to log. Valid values are 'syslog', 'file', 'stdout', 'stderr'",
        "default" => "stderr",
        "overrideable" => false,
        "dataType" => "string"
    );

    $config["syslogFacility"] = array(
        "label" => "Syslog Facility",
        "description" => "Syslog facility to which to log.",
        "default" => LOG_USER,
        "overrideable" => false,
        "dataType" => "int"
    );

    $config["logFile"] = array(
        "label" => "Log File",
        "description" => "Location of log file for Workbench logs",
        "default" => "forceworkbench.log",
        "overrideable" => false,
        "dataType" => "String"
    );

    $config["asyncTimeoutSeconds"] = array(
        "label" => "Async Timeout in Seconds",
        "description" => "Async Timeout in Seconds",
        "default" => 35 * 60,
        "overrideable" => false,
        "dataType" => "int"
    );

$config["header_LoginOptions"] = array(
    "label" => "Login Options",
    "display" => true,
    "isHeader" => true
);
   $config["defaultLoginType"]  = array(
        "label" => "Default Login Type",
        "description" => "Determines default login type that is loaded on startup.",
        "default" => "std",
        "overrideable" => true,
        "dataType" => "picklist",
        "valuesToLabels" => array(
            "std" => "Standard",
            "adv" => "Advanced",
            "oauth" => "OAuth Remote Access",
        )
    );

    $config["defaultInstance"]  = array(
        "label" => "Default Instance",
        "description" => "Default instance to be used for login.",
        "default" => "login",
        "overrideable" => true,
        "dataType" => "picklist",
        "labelKey" => "0",
        "valuesToLabels" => array(
            "login" => array("Login: Production/Developer",""),
            "test"  => array("Login: Sandbox (test)",""),
            "prerellogin.pre" => array("Login: Pre-Release", ""),
            "ssl" => array("NA0 (ssl)","0"),
            "na1" => array("NA1","3"),
            "na2" => array("NA2","4"),
            "na3" => array("NA3","5"),
            "na4" => array("NA4","6"),
            "na5" => array("NA5","7"),
            "na6" => array("NA6","8"),
            "na7" => array("NA7","A"),
            "na8" => array("NA8","C"),
            "na9" => array("NA9","E"),
            "na10" => array("NA10","F"),
            "na11" => array("NA11","G"),
            "na12" => array("NA12","U"),
            "na13" => array("NA13","a"),
            "na14" => array("NA14","d"),
            "na15" => array("NA15","i"),
            "na16" => array("NA15","j"),
            "ap" => array("AP0 (ap)","1"),
            "ap1" => array("AP1","9"),
            "emea" => array("EU0 (emea)","2"),
            "eu1" => array("EU1","D"),
            "eu2" => array("EU2","b"),
            "eu3" => array("EU3","w"),
            "tapp0" => array("Sandbox: CS0 (tapp0)","T"),
            "cs1" => array("Sandbox: CS1","S"),
            "cs2" => array("Sandbox: CS2","R"),
            "cs3" => array("Sandbox: CS3","Q"),
            "cs4" => array("Sandbox: CS4","P"),
            "cs5" => array("Sandbox: CS5","O"),
            "cs6" => array("Sandbox: CS6","N"),
            "cs7" => array("Sandbox: CS7","M"),
            "cs8" => array("Sandbox: CS8","L"),
            "cs9" => array("Sandbox: CS9","K"),
            "cs10" => array("Sandbox: CS10","J"),
            "cs11" => array("Sandbox: CS11","Z"),
            "cs12" => array("Sandbox: CS12","V"),
            "cs13" => array("Sandbox: CS13","W"),
            "cs14" => array("Sandbox: CS14","c"),
            "cs15" => array("Sandbox: CS15","e"),
            "cs16" => array("Sandbox: CS16","f"),
            "cs17" => array("Sandbox: CS17","g"),
            "prerelna1.pre" => array("Pre-Release: NA1","t"),
            "mobile1.t" => array("Mobile 1",""),
            "mobile2.t" => array("Mobile 2","")
        )
    );

    $GLOBALS['API_VERSIONS'] = array(
	"47.0" => "47.0",
        "46.0" => "46.0",
        "45.0" => "45.0",
        "44.0" => "44.0",
        "43.0" => "43.0",
        "42.0" => "42.0",
        "41.0" => "41.0",
        "40.0" => "40.0",
        "39.0" => "39.0",
        "37.0" => "37.0",
        "36.0" => "36.0",
        "35.0" => "35.0",
        "34.0" => "34.0",
        "33.0" => "33.0",
        "32.0" => "32.0",
        "31.0" => "31.0",
        "30.0" => "30.0",
        "29.0" => "29.0",
        "28.0" => "28.0",
        "27.0" => "27.0",
        "26.0" => "26.0",
        "25.0" => "25.0",
        "24.0" => "24.0",
        "23.0" => "23.0",
        "22.0" => "22.0",
        "21.0" => "21.0",
        "20.0" => "20.0",
        "19.0" => "19.0",
        "18.0" => "18.0",
        "17.0" => "17.0",
        "16.0" => "16.0",
        "15.0" => "15.0",
        "14.0" => "14.0",
        "13.0" => "13.0",
        "12.0" => "12.0",
        "11.1" => "11.1",
        "11.0" => "11.0",
        "10.0" => "10.0",
        "9.0"  => "9.0",
        "8.0"  => "8.0"
    );

    $config["defaultApiVersion"]  = array(
        "label" => "Default API Version",
        "description" => "Default API version to be used for login. This setting does not affect the API version of the current session. Recommended to choose latest version. Some features may act unexpectedly when using older versions.",
        "default" => "46.0",
        "overrideable" => true,
        "dataType" => "picklist",
        "valuesToLabels" => $GLOBALS['API_VERSIONS']
    );

    $config["useSfdcFrontdoor"] = array(
        "label" => "Pass Session to Salesforce",
        "description" => "When jumping from Workbench to Salesforce, should Workbench pass its session id to Salesforce. Automatic will only use pass the session if the Salesforce session is not likely set.",
        "default" => "NEVER",
        "overrideable" => true,
        "dataType" => "picklist",
        "valuesToLabels" => array(
            "AUTO"    => "Automatic",
            "ALWAYS"  => "Always",
            "NEVER "  => "Never"
        )
    );

    $config["fuzzyServerUrlLookup"] = array(
        "label" => "Enable Server URL Fuzzy Lookup",
        "description" => "When logging in with a Session Id, Workbench attempts to guess the associated Server URL. This may fail for orgs that have been migrated from one instance to another.",
        "default" => true,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["loginScopeHeader_organizationId"] = array(
        "label" => "Portal Organization Id",
        "description" => "Specify an org id for Self-Service, Customer Portal, and Partner Portal Users. Leave blank for standard Salesforce users.",
        "default" => null,
        "overrideable" => true,
        "dataType" => "string"
    );

    $config["loginScopeHeader_portalId"] = array(
        "label" => "Portal Id",
        "description" => "Specify an portal id for Customer Portal, and Partner Portal Users. Leave blank for standard Salesforce users.",
        "default" => null,
        "overrideable" => true,
        "dataType" => "string",
        "minApiVersion" => 10.0
    );

    $config["callOptions_client"] = array(
        "label" => "Client Id",
        "description" => "Specify a Client Id for a partner with special API functionality.",
        "default" => "WORKBENCH_DEFAULT", //Value 'WORKBENCH_DEFAULT' will convert to Workbench UserAgent format, unless otherwise overriden by user
        "overrideable" => true,
        "dataType" => "string"
    );

    // This should never be overrideable by end users; instead, admins SHOULD override default in overrides.php
    $config["termsFile"] = array(
        "label" => "Terms of Service File",
        "description" => "An HTML file containing Terms of Service that will be required for login.",
        "default" => null,
        "overrideable" => false,
        "dataType" => "string"
    );

$config["header_Describe"] = array(
    "label" => "Describe Results",
    "display" => true,
    "isHeader" => true
);

    $config["localOptions_language"] = array(
        "label" => "Label Language",
        "description" => "Specifies the language of the labels returned in Describe results.",
        "default" => null,
        "overrideable" => true,
        "dataType" => "picklist",
        "minApiVersion" => 12.0,
        "valuesToLabels" => array(
            "en_US" => "English",
            "de"    => "German",
            "es"    => "Spanish",
            "fr"    => "French",
            "it"    => "Italian",
            "ja"    => "Japanese",
            "sv"    => "Swedish",
            "ko"    => "Korean",
            "zh_TW" => "Chinese (Traditional)",
            "zh_CN" => "Chinese (Simplified)",
            "pt_BR" => "Portuguese (Brazilian)",
            "nl_NL" => "Dutch",
            "da"    => "Danish",
            "th"    => "Thai",
            "fi"    => "Finnish",
            "ru"    => "Russian"   
        )
    );


$config["header_DataManagement"] = array(
    "label" => "Data Management Options",
    "display" => true,
    "isHeader" => true
);


    $config["showReferenceBy"] = array(
        "label" => "Enable Smart Lookup",
        "description" => "Show the Smart Lookup column for insert, update, and upsert operations for mapping with foreign keys via relationships.",
        "default" => true,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["fieldsToNull"] = array(
        "label" => "Insert Null Values",
        "description" => "Forces null values to be committed to the database when inserting, updating, or upserting records; otherwise null values will be overridden by the existing values in the database.",
        "default" => false,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["allOrNoneHeader_allOrNone"] = array(
        "label" => "All-Or-None Transactional Processing",
        "description" => "Forces operations to rollback all changes in one batch unless all records in that batch are processed successfully. If unchecked, records without errors are committed, while records with errors are marked as failed in the results. Warning, Workbench may automatically divides a CSV file into multiple batches, each of which is considered a separate API transaction.",
        "default" => false,
        "overrideable" => true,
        "dataType" => "boolean",
        "minApiVersion" => 20.0
    );
    
    $config["emailHeader_triggerAutoResponseEmail"] = array(
        "label" => "Trigger Auto-Response Emails",
        "description" => "Send Auto-Response e-mails when inserting Leads and Cases.",
        "default" => false,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["emailHeader_triggertriggerUserEmail"] = array(
        "label" => "Trigger User Emails",
        "description" => "Send e-mails to users when resetting a password, creating a new user, adding comments to a case, or creating or modifying a task.",
        "default" => false,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["emailHeader_triggerOtherEmail"] = array(
        "label" => "Trigger Other Emails",
        "description" => "Send other e-mails for insert, update, and upsert of records.",
        "default" => false,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["allowFieldTruncationHeader_allowFieldTruncation"] = array(
        "label" => "Allow Field Truncation",
        "description" => "Specifies to automatically truncate string values that are too long when performing Insert, Update, Upsert, or Execute; otherwise a STRING_TOO_LONG error is returned. This is ignored in all previous API versions.",
        "default" => false,
        "overrideable" => true,
        "dataType" => "boolean",
        "minApiVersion" => 15.0        
    );


    $config["disableFeedTrackingHeader_disableFeedTracking"] = array(
        "label" => "Disable Feed Tracking",
        "description" => "Specifies whether the changes made in the current call are tracked in feeds.",
        "default" => false,
        "overrideable" => true,
        "dataType" => "boolean",
        "minApiVersion" => 17.0
    );

    $config["ownerChangeOptions_transferAttachments"] = array(
        "label" => "Transfer Attachments on Owner Change",
        "description" => "If true, the record’s notes, attachments, and Google Docs are transferred to the new record owner. If false, the original record owner retains ownership.",
        "default" => true,
        "overrideable" => true,
        "dataType" => "boolean",
        "minApiVersion" => 27.0
    );

    $config["ownerChangeOptions_transferOpenActivities"] = array(
        "label" => "Transfer Open Activities on Owner Change",
        "description" => "If true, the record’s open activities are transferred to the new record owner. If false, the original record owner retains ownership.",
        "default" => true,
        "overrideable" => true,
        "dataType" => "boolean",
        "minApiVersion" => 27.0
    );

    $config["assignmentRuleHeader_useDefaultRule"] = array(
        "label" => "Use Default Assignment Rule",
        "description" => "Apply default Assignment Rule to apply to insert, update, and upsert operations. May not be used in conjunction with Assignment Rule Id option.",
        "default" => false,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["assignmentRuleHeader_assignmentRuleId"] = array(
        "label" => "Assignment Rule Id",
        "description" => "Specify an Assignment Rule Id to apply to insert, update, and upsert operations. May not be used if Use Default Assignment Rule option is checked.",
        "default" => null,
        "overrideable" => true,
        "dataType" => "string"
    );

    $config["UserTerritoryDeleteHeader_transferToUserId"] = array(
        "label" => "Territory Delete Transfer User",
        "description" => "The ID of the user to whom open opportunities will be assigned when an opportunity's owner is removed from a territory",
        "default" => null,
        "overrideable" => true,
        "dataType" => "string"
    );

    $config["asyncConcurrencyMode"]  = array(
        "label" => "Asynchronous Concurrency Mode",
        "description" => "When loading records asynchronously via the Bulk API, determines if batches are processed by Salesforce in parallel or serially. Parallel is recommended unless serial processing is needed to avoid contention.",
        "default" => "Parallel",
        "overrideable" => true,
        "dataType" => "picklist",
        "valuesToLabels" => array(
            "Parallel" => "Parallel",
            "Serial" => "Serial"
         )
    );

    $config["streamingV2Enabled"]  = array(
        "label" => "Streaming V2",
        "description" => "Enables client to use Streaming V2 endpoint and exposes options to subscribe to previous events.",
        "default" => true,
        "overrideable" => true,
        "dataType" => "boolean",
        "minApiVersion" => 40.0
    );

$config["header_queryAndSearchOptions"] = array(
    "label" => "Query & Search Options",
    "display" => true,
    "isHeader" => true
);

    $config["autoJumpToResults"] = array(
        "label" => "Automatically Jump to Results",
        "description" => "When displaying query results in the browser, automatically jump to the top of the query results.",
        "default" => true,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["areTablesSortable"] = array(
        "label" => "Sortable Results (Beta)",
        "description" => "Allow results tables to be sorted directly in the browser after the query or search has been completed. This feature is currently in beta, as some complex queries produce unexpected results.",
        "default" => false,
        "overrideable" => true,
        "dataType" => "boolean",
    );

    $config["autoRunQueryMore"] = array(
        "label" => "Automatically Retrieve More Query Results",
        "description" => "Automatically retrieve all query results with queryMore() API call for browser view; otherwise, 'More...' button is show when additional results are available. If a large query is run with this setting enabled, the operation may be subject to unexpected timeouts. CSV exports automatically retrieve all results.",
        "default" => false,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["allowQueryCsvExport"] = array(
        "label" => "Allows Query CSV Export",
        "description" => "Allows queries to export to CSV. Does not affect Bulk CSV export.",
        "default" => true,
        "overrideable" => false,
        "dataType" => "boolean"
    );

    $config["queryOptions_batchSize"] = array(
        "label" => "Preferred Query Batch Size",
        "description" => "Requested query batch size. This is not a guaranteed value and depends on the data set being returned.",
        "default" => 500,
        "overrideable" => true,
        "dataType" => "int",
        "minValue" => 200,
        "maxValue" => 2000
    );

    $config["allowParentRelationshipQueries"] = array(
        "label" => "Allows SOQL Parent Relationship Queries",
        "description" => "Allows parent relationship queries in SOQL.",
        "default" => false,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["allowAsyncSoqlUI"] = array(
        "label" => "Allow AsyncSOQL UI",
        "description" => "Allow queries in AsyncSOQL.",
        "default" => true,
        "overrideable" => true,
        "dataType" => "boolean",
        "minApiVersion" => 36.0
    );

    $config["header_PackageVersion"] = array(
        "label" => "Package Version",
        "display" => true,
        "isHeader" => true
    );

    $config["packageVersionHeader_include"] = array(
        "label" => "Use Package Version Header",
        "description" => "Indicates whether Workbench should use the following package version information.",
        "default" => false,
        "overrideable" => true,
        "dataType" => "boolean",
        "minApiVersion" => 16.0
    );

    $config["packageVersion_namespace"] = array(
        "label" => "Namespace",
        "description" => "The unique namespace of the managed package.",
        "default" => null,
        "overrideable" => true,
        "dataType" => "string",
        "minApiVersion" => 16.0
    );
    
    $config["packageVersion_majorNumber"] = array(
        "label" => "Major Number",
        "description" => "The major version number of a package version. A package version is denoted by majorNumber.minorNumber, for example 2.1.",
        "default" => null,
        "overrideable" => true,
        "dataType" => "int",
        "minApiVersion" => 16.0
    );

    $config["packageVersion_minorNumber"] = array(
        "label" => "Minor Number",
        "description" => "The minor version number of a package version. A package version is denoted by majorNumber.minorNumber, for example 2.1.",
        "default" => null,
        "overrideable" => true,
        "dataType" => "int",
        "minApiVersion" => 16.0
    );    
    
$config["header_Execute"] = array(
    "label" => "Apex Execute Logging Options",
    "display" => true,
    "isHeader" => true
);

    $config["defaultLogCategory"]  = array(
        "label" => "Default Log Category",
        "description" => "Default Log Category when displaying results from anonymous Apex execution. Defaults will not apply until after logging in again.",
        "default" => "Apex_code",
        "overrideable" => true,
        "dataType" => "picklist",
        "valuesToLabels" => array(
            "Db" => "Database",
            "Workflow" => "Workflow",
            "Validation" => "Validation",
            "Callout" => "Callout",
            "Apex_code" => "Apex Code",
            "Apex_profiling" => "Apex Profiling"
        )
    );

    $config["defaultLogCategoryLevel"]  = array(
        "label" => "Default Log Level",
        "description" => "Default Log Level when displaying results from anonymous Apex execution. Defaults will not apply until after logging in again.",
        "default" => "DEBUG",
        "overrideable" => true,
        "dataType" => "picklist",
        "valuesToLabels" => array(
            "ERROR" => "Error",
            "WARN" => "Warn",
            "INFO" => "Info",
            "DEBUG" => "Debug",
            "FINE" => "Fine",
            "FINER" => "Finer",
            "FINEST" => "Finest"
        )
    );

$config["header_Performance"] = array(
    "label" => "Performance Options",
    "display" => true,
    "isHeader" => true
);

    $config["cacheGetUserInfo"] = array(
        "label" => "Cache User Info",
        "description" => "Caches the results from the getUserInfo() API call. Improves performance of Workbench in that user info does not need to be retrieved more than once, but not recommended if active session should be checked on each page load.",
        "default" => true,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["cacheDescribeGlobal"] = array(
        "label" => "Cache Object Names",
        "description" => "Caches the results from the describeGlobal() API call. Improves performance of Workbench in that object names do not need to be retrieved more than once. Recommended unless actively making changes to metadata of your Salesforce organization.",
        "default" => true,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["cacheDescribeSObject"] = array(
        "label" => "Cache Object Descriptions",
        "description" => "Caches the results from the describeSobject() API calls. Improves performance of Workbench in that the object descriptions and field names do not need to be retrieved more than once. Recommended unless actively making changes to metadata of your Salesforce organization.",
        "default" => true,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["enableGzip"] = array(
        "label" => "Enable Compression",
        "description" => "Enables GZIP compression to improve performance of API call response time. Recommended to leave enabled unless SOAP capturing is necessary.",
        "default" => true,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["sessionIdleMinutes"] = array(
        "label" => "Session Idle Minutes",
        "description" => "Amount of minutes that must pass between requests before checking that the Salesforce session is still alive.",
        "default" => 5,
        "overrideable" => false,
        "dataType" => "int",
        "minValue" => 0
    );

    $config["batchSize"] = array(
        "label" => "Synchronous Record Batch Size",
        "description" => "Number of records that are batched together in one synchronous API call. Recommended to leave at 200.",
        "default" => 200,
        "overrideable" => true,
        "dataType" => "int",
        "minValue" => 1,
        "maxValue" => 200
    );

    $config["asyncBatchSize"] = array(
        "label" => "Asynchronous Record Batch Size",
        "description" => "Number of records that are batched together for asynchronous record loading via the Bulk API.",
        "default" => 5000,
        "overrideable" => true,
        "dataType" => "int",
        "minValue" => 1
    );

    $config["asyncRecommendationThreshold"] = array(
        "label" => "Bulk API Recommendation Threshold",
        "description" => "Recommend using the Bulk API if the number of records exceeds this number.",
        "default" => 1000,
        "overrideable" => false,
        "dataType" => "int",
        "minValue" => 1
    );

    $config["memoryUsageWarningThreshold"] = array(
        "label" => "Memory Usage Warning Threshold",
        "description" => "Recommend using an alternative method if memory usage exceeds this percentage.",
        "default" => 80,
        "overrideable" => false,
        "dataType" => "int",
        "minValue" => 1,
        "maxValue" => 100
    );

    $config["maxFileSize"] = array(
        "label" => "Maximum File Size (bytes)",
        "description" => "Maximum file size for upload in bytes.",
        "default" => 10000000,
        "overrideable" => false,
        "dataType" => "int",
        "minValue" => 1
    );

    $config["maxFileLengthRows"] = array(
        "label" => "Maximum File Length (rows)",
        "description" => "Maximum file size for upload in number of CSV rows.",
        "default" => 50000,
        "overrideable" => false,
        "dataType" => "int",
        "minValue" => 1
    );

    $config["header_SecurityOptions"] = array(
        "label" => "Security Options",
        "display" => true,
        "isHeader" => true
    );

    $config["invalidateSessionOnLogout"] = array(
        "label" => "Invalidate Session on Logout",
        "description" => "Invalidates the current API session when logging out of Workbench.",
        "default" => true,
        "overrideable" => true,
        "dataType" => "boolean",
        "minApiVersion" => 13.0
    );

    // This should never be overrideable by end users; instead, admins can override default in overrides.php
    $config["requireSSL"] = array(
        "label" => "Requires a Secure Connection",
        "description" => "Requires a secure connection to between this computer and the Workbench server AND between Workbench server and Salesforce API. Returns an HTTP 403.4 error if not satisfied.",
        "default" => false,
        "overrideable" => false,
        "dataType" => "boolean"
    );

    // same as requireSSL, but only displays a warning
    $config["checkSSL"] = array(
        "label" => "Check for Secure Connection",
        "description" => "Display a warning to users in the footer if an unsecure connection to between this computer and the Workbench server OR between Workbench server and Salesforce API is detected.",
        "default" => true,
        "overrideable" => false,
        "dataType" => "boolean"
    );

    $config["useHTTPS"] = array(
        "label" => "Connect to Salesforce over HTTPS",
        "description" => "Use HTTPS to connect to Salesforce API from Workbench server. Does not guarantee HTTPS will be used from this computer to Workbench server. Disabling this setting will also change redirect Server URLs returned from Salesforce to use HTTP. Must login again for changes to take effect.",
        "default" => true,
        "overrideable" => true,
        "dataType" => "boolean"
    );

    $config["redirectToHTTPS"] = array(
        "label" => "Redirect to HTTPS",
        "description" => "Redirect user to HTTPS if unsecure connection is used",
        "default" => false,
        "overrideable" => false,
        "dataType" => "boolean"
    );

    $config["readOnlyMode"] = array(
        "label" => "Read Only Mode",
        "description" => "Disable access to pages that can change data or metadata. Do not use as a replacement for server-side validation.",
        "default" => false,
        "overrideable" => false,
        "dataType" => "boolean"
    );

    // This should never be overrideable by end users; instead, admins can override default in overrides.php
    $config["loginCsrfEnabled"] = array(
        "label" => "Enable CSRF Protection for Login",
        "description" => "Enable CSRF (Cross-Site Request Forgery) protection for login.",
        "default" => false,
        "overrideable" => false,
        "dataType" => "boolean"
    );

    // This should never be overrideable by end users; instead, admins SHOULD override default in overrides.php
    $config["csrfSecret"] = array(
        "label" => "CSRF Salting Secret",
        "description" => "Used for salting the CSRF (Cross-Site Request Forgery) tokens.",
        "default" => "OVERRIDE_ME_IN_CONFIG_OVERRIDES_PHP",
        "overrideable" => false,
        "dataType" => "string"
    );

    // This should never be overrideable by end users; instead, admins SHOULD override default in overrides.php
    $config["sodiumKey"] = array(
        "label" => "Libsodium Encryption Key",
        "description" => "Used for salting libsodium encryption.",
        "default" => "OVERRIDE_ME_IN_CONFIG_OVERRIDES_PHP",
        "overrideable" => false,
        "dataType" => "string"
    );

    // This should never be overrideable by end users; instead, admins SHOULD override default in overrides.php
    $config["nonce"] = array(
        "label" => "Nonce For libsodium Encryption",
        "description" => "required nonce value for libsodium encryption.",
        "default" => "OVERRIDE_ME_IN_CONFIG_OVERRIDES_PHP",
        "overrideable" => false,
        "dataType" => "string"
    );

    // This should never be overrideable by end users; instead, admins SHOULD override default in overrides.php
    $config["orgIdWhiteList"] = array(
        "label" => "Organization Id Whitelist",
        "description" => "A comma-separated list of organization ids which are the only ones allowed to connect to Workbench.",
        "default" => "",
        "overrideable" => false,
        "dataType" => "string"
    );

    // This should never be overrideable by end users; instead, admins SHOULD override default in overrides.php
    $config["orgIdBlackList"] = array(
        "label" => "Organization Id Blacklist",
        "description" => "A comma-separated list of organization ids which are not allowed to connect to Workbench.",
        "default" => "",
        "overrideable" => false,
        "dataType" => "string"
    );

    // This should never be overrideable by end users; instead, admins SHOULD override default in overrides.php
    $config["oauthRequired"] = array(
        "label" => "Require OAuth Login",
        "description" => "Require OAuth Login",
        "default" => false,
        "overrideable" => false,
        "dataType" => "boolean"
    );

    // This should never be overrideable by end users; instead, admins SHOULD override default in overrides.php
    $config["oauthConfigs"] = array(
        "label" => "OAuth 2.0 Consumer Key",
        "description" => "OAuth 2.0 Consumer Key",
        "overrideable" => false,
        "dataType" => "complex",
        "default" => array()
    );

$config["header_proxyOptions"] = array(
    "label" => "Proxy Options",
    "display" => false,
    "isHeader" => true
);

    $config["proxyEnabled"] = array(
        "label" => "Connect with Proxy",
        "description" => "Check this box to use the proxy information below to connect to Salesforce.",
        "default" => false,
        "overrideable" => false,
        "dataType" => "boolean"
    );

    $config["proxyHost"] = array(
        "label" => "Proxy Host",
        "description" => "Proxy Host",
        "default" => null,
        "overrideable" => false,
        "dataType" => "string"
    );


    $config["proxyPort"] = array(
        "label" => "Proxy Port Number",
        "description" => "Proxy Port Number",
        "default" => null,
        "overrideable" => false,
        "dataType" => "int",
        "minValue" => 0,
        "maxValue" => 65536
    );


    $config["proxyUsername"] = array(
        "label" => "Proxy Username",
        "description" => "Proxy Username",
        "default" => null,
        "overrideable" => false,
        "dataType" => "string"
    );


    $config["proxyPassword"] = array(
        "label" => "Proxy Password",
        "description" => "Proxy Password",
        "default" => null,
        "overrideable" => false,
        "dataType" => "password"
    );

$config["header_internal"] = array(
    "label" => "Internal Only",
    "display" => false,
    "isHeader" => true
);

    $config["redisUrl"] = array(
        "label" => "Redis URL",
        "description" => "Redis URL used for async processing.",
        "default" => "",
        "overrideable" => false,
        "dataType" => "string"
    );

    $config["sessionStore"] = array(
        "label" => "Session Store",
        "description" => "Only redis:// URL are currently supported; otherwise, blank for default value",
        "default" => "",
        "overrideable" => false,
        "dataType" => "string"
    );
?>
