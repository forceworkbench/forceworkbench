<?php
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	TO CONFIGURE THE WORKBENCH FOR ALL YOUR USERS, ADJUST THE "DEFAULT" VALUES FOR THE KEYS BELOW.
//
//	IF YOU WOULD LIKE TO ALLOW YOUR USERS TO OVERRIDE THE DEFAUTS SET THE "OVERRIDEABLE" VALUE TO true.
//	THIS WILL CAUSE THE KEY TO APPEAR ON THE 'SETTINGS' PAGE AND WILL BE CUSTOMIZABLE ON A USER-BY-USER
//	BASIS. THIS IS ACCOMPLISHED BY SETTING COOKIES IN THE USER'S BROWSER AND THE USER'S SETTINGS WILL
//	RETURN TO THE DEFAULTS SET BELOW IF THE COOKIES ARE CLEARED. THE "LABEL" AND "DESCRIPTION" VALUES CONTROL
//	HOW THE SETTING IS DISPLAYED ON THE 'SETTINGS' PAGE.
//
//	DO NOT ALTER THE KEY NAME, "DATATYPE", "MAXVALUE", OR "MINVALUE" VALUES. THESE MUST REMAIN AS IS FOR THE
//	FOR WORKBENCH TO FUNCTION PROPERLY.
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////



$config["abcOrder"] = array(
	"label" => "Alphabetize Field Names",
	"description" => "Alphabetizes field names for across application. Otherwise, field names are displayed in the order returned by Salesforce.",
	"default" => false,
	"overrideable" => true,
	"dataType" => boolean
);

$config["cacheDescribeGlobal"] = array(
	"label" => "Cache Object Names",
	"description" => "Caches the results from the describeGlobal() API call. Improves performance of the Workbench, but not recommended if actively making changes to metadata of your Salesforce organization.",
	"default" => true,
	"overrideable" => true,
	"dataType" => "boolean"
);

//$config["cacheDescribeSObject"] = array(
//	"label" => "Cache Field Names",
//	"description" => "Caches the results from the describeSobject() API calls. Improves performance of the Workbench, but not recommended if actively making changes to metadata of your Salesforce organization.",
//	"default" => true,
//	"overrideable" => false,
//	"dataType" => "boolean"
//);

$config["showReferenceBy"] = array(
	"label" => "Display Reference By Column",
	"description" => "Show the Reference By column for insert, update, and upsert operations for mapping with foreign keys via relationships.",
	"default" => false,
	"overrideable" => true,
	"dataType" => "boolean"
);

$config["emailHeader_triggerAutoResponseEmail"] = array(
	"label" => "Trigger Auto-Response Emails",
	"description" => "Send Auto-Response e-mails for insert, update, and upsert of Leads and Cases",
	"default" => false,
	"overrideable" => true,
	"dataType" => "boolean"
);

$config["emailHeader_triggertriggerUserEmail"] = array(
	"label" => "Trigger User Emails",
	"description" => "Send e-mails to users when resetting a password, creating a new user, adding comments to a case, or creating or modifying a task",
	"default" => false,
	"overrideable" => true,
	"dataType" => "boolean"
);

$config["emailHeader_triggerOtherEmail"] = array(
	"label" => "Trigger Other Emails",
	"description" => "Send other e-mails for insert, update, and upsert of records",
	"default" => false,
	"overrideable" => true,
	"dataType" => "boolean"
);

$config["mruHeader_updateMru"] = array(
	"label" => "Update MRU",
	"description" => "Indicates whether to update the list of most recently used items for queries of one record",
	"default" => false,
	"overrideable" => true,
	"dataType" => "boolean"
);

$config["UserTerritoryDeleteHeader_transferToUserId"] = array(
	"label" => "Territory Delete Transfer User",
	"description" => "The ID of the user to whom open opportunities in that user's territory will be assigned when an opportunity's owner is removed from a territory",
	"default" => false,
	"overrideable" => true,
	"dataType" => "boolean"
);

$config["debug"] = array(
	"label" => "Debug Mode",
	"description" => "Enables debugging mode for showing supervariables and SOAP messages.",
	"default" => false,
	"overrideable" => true, //TODO: Set back to false
	"dataType" => boolean
);

$config["assignmentRuleHeader_useDefaultRule"] = array(
	"label" => "Use Default Assignment Rule",
	"description" => "Apply default Assignment Rule to apply to insert, update, and upsert operations",
	"default" => false,
	"overrideable" => true,
	"dataType" => "boolean"
);

$config["assignmentRuleHeader_assignmentRuleId"] = array(
	"label" => "Assignment Rule Id",
	"description" => "Specify an Assignment Rule Id to apply to insert, update, and upsert operations",
	"default" => null,
	"overrideable" => true,
	"dataType" => "string"
);

$config["callOptions_client"] = array(
	"label" => "Client Id",
	"description" => "Specify a Client Id for a partner with special API functionality.",
	"default" => null,
	"overrideable" => true,
	"dataType" => "string"
);

$config["callOptions_defaultNamespace"] = array(
	"label" => "Default Namespace",
	"description" => " A string that identifies a developer namespace prefix",
	"default" => null,
	"overrideable" => true,
	"dataType" => "string"
);


$config["maxFileSize"] = array(
	"label" => "Maxiumum File Size (bytes)",
	"description" => "Maximum file size for upload in bytes.",
	"default" => 512000,
	"overrideable" => false,
	"dataType" => "int"
);

$config["maxFileLengthRows"] = array(
	"label" => "Maxiumum File Length (rows)",
	"description" => "Maximum file size for upload in number of CSV rows.",
	"default" => 2000,
	"overrideable" => false,
	"dataType" => "int"
);


$config["batchSize"] = array(
	"label" => "Batch Size",
	"description" => "Number of records that are batched together in one API call. Recommended to leave at 200.",
	"default" => 200,
	"overrideable" => true,
	"dataType" => "int",
	"minValue" => 0,
	"maxValue" => 200
);

$config["queryOptions_batchSize"] = array(
	"label" => "Query Batch Size",
	"description" => "Requested query batch size. This is not a guranteed value and depends on the data set being returned.",
	"default" => 500,
	"overrideable" => true,
	"dataType" => "int",
	"minValue" => 200,
	"maxValue" => 2000
);

?>
