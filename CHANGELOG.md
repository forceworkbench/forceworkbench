
**26.0.0** 2013/01/12

*   Upgraded Partner, Metadata, Apex, and Streaming clients to Force.com API 26.0
*   Bug Fix: Eliminated double escaping of HTML entities for Bulk API DML operations
*   Bug Fix: Corrected handling of cookie Path component in CometD reverse proxy
*   Bug Fix: Require REST Explorer resource paths to begin with a slash
*   Bug Fix: Temporarily block DML operations on objects with more than 10000 chars

**25.0.2~4** 2012/09/16

*   Enable async processing for all REST Explorer operations
*   Remove full WorkbenchContext from RestExplorerController to optimize serialization used for async processing
*   Fix error handling for REST Explorer async operations

**25.0.1** 2012/09/09

*   Redis-based queueing and async processing framework
*   Async processing for SOQL Query, Apex Execute, and REST Explorer pages
*   Added support for signed request-based logins with the pilot Canvas Framework in Winter '13
*   Encryption of Salesforce session id in the PHP session
*   Set default timezone to UTF if not already set in php.ini.
*   Partial upgrade of Partner, Metadata, and Apex clients to Force.com API 26.0
*   Added favicon.ico in web root
*   OAuth login uses state parameter instead of PHP session with added CSRF protection
*   Reenabled logging of unknown exceptions with greater number of known exceptions
*   Check user agreed to terms of service on every request
*   Bug Fix: Block DML requests with different number of rows per column
*   Bug Fix: Corrected HTML escaping in debug mode
*   Bug Fix: DML results with batch size + 1 rows display correctly
*   Bug Fix: Handle objects with only one field

**25.0.0** 2012/08/10

*   Upgraded Partner, Metadata, Apex, Bulk, REST, and Streaming clients to Force.com API 25.0
*   Added new Salesforce instances to instance picklist
*   Bug Fix: Allow more than one error per row to displayed on DML operation failures (e.g two triggered validation rules)
*   Bug Fix: Scrub additional headers of REST Explorer to be compatible with CURL versions that do not already do this
*   Bug Fix: Block invalid CSV files if all rows do not have same number of columns
*   Bug Fix: Handle DML operations for sobjects with unknown key prefixes

**24.0.1 Beta** 2012/04/29 - 2012/06/25

*   Support for saving session data to Redis to allow for multiple, horizontally scalable, stateless app servers
*   Encapulation of configuration into a single point of access
*   Removal of Workbench configuration from PHP session
*   Option to auto-forward unsecure requests to HTTPS endpoint
*   Support for remote term files
*   Changed static resource versioning to query parameters instead of file paths to simplify build process
*   Changed logging to JSON format for machine readability
*   Support for multiple log handlers with syslog and file-based handlers
*   Complete support for HTTP\_X\_FORWARD_* headers
*   Add BAYEUX_BROWSER cookie support to Streaming API client

**24.0.0** 2012/02/27

*   Upgraded Partner, Metadata, Apex, Bulk, REST, and Streaming clients to Force.com API 24.0
*   Added partial support for Phing-based builds
*   Added support for environment variable based configuration
*   Added script for converting file-based configuration to environment variable-based configuration
*   Removed hand cursor from async result processing times pseudo link
*   Disabled parent relationship queries by default with user option to enable
*   Show warning pop-up when clearing saved queries
*   Added support for SSL detection with HTTP\_X\_FORWARDED_PROTO
*   Bug Fix: Special case for EmailTemplate metadata component listing
*   Bug Fix: Improve query table copy and pasting

**23.0.0** 2011/10/26

*   Upgraded Partner, Metadata, Apex, Bulk, REST, and Streaming clients to Force.com API 23.0
*   Removed streaming 22.0/23.0 stagger support for Streaming API GA in API 23.0
*   Bug Fix: Treat various expected API errors as WorkbenchHandledExceptions
*   Bug Fix: Logout OAuth users without API access

**22.0.1** 2011/08/29

*   Single record detail pages with id link integration
*   Single record DML actions with detail page and id link integration
*   OAuth 2.0 login support
*   Optional Login CSRF protection
*   CSRF protection for all writable POSTs
*   Organization id blacklist/whitelist
*   Clear cache after a metadata deployment
*   Add buttons to explictly clear Workbench cache
*   Change PHPSESSION to be HTTPOnly
*   Add top-level PHP error handler
*   Request and top-level error/exception logging to syslog
*   Better protection for non-overrideable settings
*   Support parsing of Enterprie API server URLs on login
*   Show login errors on login page with non-secret fields pre-populated
*   Send user to logout page on authentication errors
*   Allow latest version checks to be disabled
*   Added ability to configure terms of service to which users must agree to login
*   Replace expected Exceptions with HandledExceptions
*   Support /id URLs in REST Explorer
*   Add configuration to disable parent relationship queries
*   Add configuration to disable CSV export
*   Add configuration for end-to-end SSL requiredness
*   Disable pilot Streaming API client when connected to Winter '12 instance
*   Clear cache when using 70% of memory
*   Add licensing and copyright details
*   Deny access to config files via .htaccess
*   Bug Fix: Field alphabetizing fails if no fields exist for an object
*   Bug Fix: Stop describing objects DML actions that do not require objects
*   Bug Fix: Re-allow logins to instances with port numbers
*   Bug Fix: Allow programmic portal id login to work
*   Bug Fix: Reviewed and added XSS protection to various areas of the app
*   Bug Fix: XSS protection for PHP\_SELF and disallow URIs with PATH\_INFO
*   Bug Fix: Allow Streaming API client to work with proxies that add additional headers
*   Bug Fix: Trim cookies in streaming client
*   Bug Fix: Use SSL on non-IIS servers in streaming client
*   Bug Fix: Content-Type Header Not Being Set in PhpReverseProxy if !function_exists('getallheaders')

**22.0.0** 2011/06/26

*   Upgraded Partner, Metadata, Apex, Bulk, REST, and Streaming clients to Force.com API 22.0
*   Added Streaming API (pilot) support. Manage Push Topics, subscriptions, and monitor meta channels. Includes reverse proxy to handle cross-domain traffic
*   Revamped API connection and cache management with new Workbench Context. Additional items will be folded into this in the coming releases to allow for a common way for accessing connections, caching values, and other application-wide coordination.
*   Added proxy support for Bulk and REST API clients and tools
*   Continue incrementing query results rows for paginated results
*   Added support for login startUrl parameters with additional query parameters
*   Show waiting indicator for REST Explorer
*   Added a tool tip with secondary user info
*   Show number of sub-items in more folder trees
*   Warn when memory usage is too high on querying to CSV
*   Bug Fix: Allow deletes, undeletes, and purges to read CSVs with Id column in non-first column
*   Bug Fix: Make best attempt to find older WSDL for Metadata and Apex
*   Bug Fix: Warn when memory usage is too high on querying to CSV
*   Bug Fix: Single-quoted data in SOQL filters not appearing on page reload

**21.0.1** 2011/03/15

*   Stream Bulk API results and REST Explorer binary downloads directly to end user without loading into Workbench memory.
*   Allow Bulk API Client to take any Salesforce SOAP API endpoint, instead of just Partner API. Pre-constructed Bulk API are also allowed now.
*   Allow Bulk API Client to export results to local files.
*   Check memory usage on CSV uploads and recommend using Bulk API if memory is almost exhausted.
*   Only recommend Bulk API for DML is cURL extension is installed.
*   Remove dependency on PECL library for hashing footer scripts.
*   Ping Salesforce on page loads after five minutes of inactivity to check if session is still alive.
*   Bug Fix: Use object in FROM clause for Bulk Queries instead of object specified in query form.
*   Bug Fix: Download ZIP-based Bulk API requests as ZIP files.
*   Bug Fix: Allow REST Explorer to download binaries from Chatter feeds.
*   Bug Fix: Auto-execute REST-based queryMore requests in REST Explorer.

**21.0.0** 2011/02/23

*   Upgraded Partner, Metadata, Apex, Bulk, and Rest clients to Force.com API 21.0
*   Added Rest API discovery with REST Explorer integrated into Workbench and added support for custom request headers, support for PUT verb, and automatic display of raw non-JSON responses (JSON responses are shown in expandable tree).
*   Added Bulk API Query support for running SOQL queries asynchronously and returning results in either CSV or XML format.
*   Enhanced links from Workbench to Salesforce to only pass sessions where needed.
*   Default to using Bulk API for large data loads with customizable threshold.
*   Bug Fix: CSV file formats supported in all browsers.
*   Bug Fix: Localization of dates works correctly even if timezone is unset
*   Bug Fix: Metadata Deploy with Perform Retrieve option allows ZIP to be downloaded

  
**20.0.0** 2010/10/10

*   Upgraded Partner, Metadata, Apex, and Bulk clients to Force.com API 20.0
*   SOQL Matrix View to view SOQL query results on a matrix grid with arbitrary columns and rows
*   Support for binary file DML operations with new Bulk API ZIP\_CSV and ZIP\_XML content types
*   Timezone conversion for date times
*   Localization formatting for date times
*   All-Or-None transactional DML processing
*   Auto-refresh on async status pages
*   Display object and fields labels in any language
*   Support for AllOrNoneHeader, DisableFeedTrackingHeader, LocaleOptions, PackageVersionHeader SOAP headers
*   Keyboard shortcut (Crtl+Alt+W) for adding filter/object rows in SOQL/SOSL builders
*   Show warning for unsupported settings for current API version or Bulk API operation
*   Removed Jump To menu by default. Can be re-added with new setting.
*   Added escaping for package names in Metadata Retrieve
*   Allow Bulk API to be used down to API 16.0
*   Read-only Mode for kiosk support
*   Performance: New .htaccess files for enabling caching of static content and GZIP compression if Workbench is installed on a Apache Web Server
*   Performance: Delayed loading of some UI elements during download and rendering
*   Performance: Limited cookie traffic to only overridden configs
*   Performance: Moved all external JavaScript to footer
*   Performance: Achieved YSlow A rating
*   Bug Fix: string-based configs not clearing to defaults
*   Bug Fix: invalid Async Ids in Metadata API operations
*   Bug Fix: Bulk API operations with polymorphic foreign key lookups
*   Bug Fix: Session Info not displaying for users without Metadata API access
*   Changed version numbering scheme to align with Salesforce.com API versions and clients

  
**3.0.19** 2010/06/17

*   Upgraded Partner, Apex, and Bulk clients to Force.com API 19.0.
*   Added client and user interface support for Metadata API operations describeMetadata(), listMetadata(), deploy(), retrieve(), checkStatus(), deployCheckStatus(), and retrieveCheckStatus(). Users can describe and list metadata types and components, retrieve and deploy packaged and unpackaged metadata components, and monitor asynchronous operations with tree-based visualizations.
*   Revamped user interface to use drop-down menus, new logo, and lighter color scheme with a smaller header footprint giving back more real estate.
*   Added Bulk API support for new hardDelete() operation.
*   Added ability to download Bulk API batch requests.
*   Added Apex/API/Total Processing Time and Failed Records values to Bulk API Status page.
*   Add ability to change API versions without re-login.
*   Added new page for viewing user session information.
*   Extended saved queries and searches to persist user sessions with ability widen scope to outside given user or organization.
*   Added picklist field statistics to describe results.
*   Added convenience command to jump to Run All Tests page in Salesforce.
*   Added warning to Settings page if user attempts to navigate with changes and confirmation dialogue when settings are saved.
*   Added ability to set server port number on auto login.
*   Added unhanded exception handler.
*   Fixed various bugs, including describe.php not auto-submitting on Google Chrome and intermittent logouts when changing settings.

  

  
**2.5.18** 2010/02/21

*   Upgraded Partner, Apex, and Bulk clients to Force.com API 18.0.
*   Added ability to save named queries and searches and recall them at a later time during the Workbench session.
*   Enhanced query and search builders to support an unlimited number of filters (SOQL) and object selections (SOSL).
*   Allow SOQL query results using constructs introduced in API 18.0, such as aggregate function, date functions, GROUP BY, HAVING, and WITH (DATA CATEGORY) keywords. Note, the SOQL query builder in Workbench does not yet support assist with building these queries, but will now display their results.
*   Added support for bulk Delete operations with Bulk API 18.0.
*   Client-side sorting of data tables, including query and search results.
*   Display of stateInfo in batches and jobs for Bulk API and allow failed results to be downloaded.
*   Pretty printing of SOAP messages in debug mode.
*   Added support for Mac-style CSVs.
*   Various minor bug fixes, including: corrected display issues with queries of history entities, added missing attributes in describe results, fixed minor JavaScript events for better keyboard support.

**2.4.17** 2009/10/10

*   Upgraded Partner and Apex clients to Force.com API 17.0.
*   Added REST client for asynchronous Bulk API data loading operations and fully integrated with existing Workbench functions.
*   Support for new 17.0+ describe object results and object operation filtering.
*   Compatibility with new preferred login.salesforce.com production login endpoint.
*   Added setting for Default Login Type (Standard or Advanced)
*   Expanded multiple functions for compatibility in non-production Salesforce environments
*   Enforce stricter adherence to Partner WSDL versions.
*   Dynamically choose latest Apex API WSDL.
*   Add option to disable HTTPS for testing purposes.
*   Auto-redirect to Login page after logout.
*   Added 'Text Area Rows' option to change the number of rows in text boxes for Query, Search, and Execute functions.
*   Cleaned up PHP errors for users displaying notices to end users.
*   Added AJAX-based API Call Afterburner for testing purposes.
*   Removed "OwnerId" and "RecordTypeId" syntax highlighting in Describe results.
*   Corrected multiple IE cosmetic bugs.
*   Fixed field name alphabetizing to be natural, case-insensitive (A, a, B, b; instead of (A, B, a, b).
*   Added CSS typefaces and fallbacks for Linux users.
*   Refactoring of duplicated page info, error messaging, endpoint construction, API versioning.

**2.3.16** 2009/06/21

*   Upgraded Partner and Apex clients to Force.com API 16.0
*   Added support for parent-to-child SOQL relationship queries to display nested sObjects For example, \[SELECT Id, Name, (SELECT Id, LastName, FirstName FROM Contacts) FROM Account\] will display Accounts with their child Contacts.
*   Added the ability to download full results history from file-based API operations. Similar to the success and error files produced by the Data Loader, but in one convenient file that can be downloaded on demand.
*   Introduced optional Describe Results Highlighting for custom fields, system fields, and boolean values.
*   Extended "Link Ids to Record Detail" feature across the entire application. Now, if a Salesforce Id appears anywhere from a query result to a Apex debug log to an upsert, it is automatically hyperlinked to the record detail in the Salesforce user interface. This feature can optionally be disabled.
*   Added advanced login support for new Salesforce instances.
*   Added additional set of buttons at top of Setting page for easier access.
*   Corrected error if more than one Document or Attachment body was queried
*   Made Search results consistent with the rest of the application to start results incrementing at 1.
*   Minor bug fixes to CSV preview.

**2.2.15** 2009/02/15

*   Upgraded Partner and Apex clients to Force.com API 15.0
*   Added support for child-to-parent SOQL relationship queries to unwrap nested sObjects and display inline in the browser and for CSV exports. For example, \[SELECT Id, Opportunity.Account.Name, ContactId FROM OpportunityContactRole\] will unwrap the Opportunity.Account.Name value and display inline with Id and Contact. Parent-to-child relationship queries are not yet supported, and a error message will be displayed as such.
*   Added support for AllowFieldTruncation SOAP header and accessible under Settings | Data Management.
*   Fixed bug to allow Client Id to be set before login for access to PE and GE organizations
*   Shows new version notifications on select.php if auto-login is used
*   Query results incremented with continuously across queryMore() batches.
*   Added caps lock warning feature if user has caps lock on when typing password on either standard or advanced login.
*   Ids in query results are hyperlinked to their corresponding record in the Salesforce user interface, with setting to disable if desired.
*   Added Server URL Fuzzy Lookup to guess the instance from the session id on advanced logins, with setting to disable if desired
*   Exposed new auto-login query parameters: inst, api, clientId, orgId, portalId, startUrl
*   Improved logout behavior when a bad sessions is detected
*   Data table row numbering no longer starts at 2, and numbers continue for queryMore results
*   Implemented Login Scope Header both in Settings and for orgId and portalId auto-login parameters
*   Removed proxy configurations from Settings overrides by default. Admins can still make changes in config.php

**2.1.14** 2008/10/21

*   Upgraded WSDL to Force.com API Partner 14.0
*   Added Execute function to run Apex scripts in the Workbench
*   Added support for semi-joins and anti-joins in API 14.0
*   Added support for IN, NOT IN, INCLUDES, and EXCLUDES to query builder
*   Exposed 'un' and 'pw' URL query parameters for quick standard login with username and password directly in the URL, which can be helpful for bookmarking the login to a test environment. Note, this option is NOT recommended for a production environment, as your password is sent in plain text.
*   Exposed 'login.php?adv=1' URL query parameter to automatically choose the Advanced default login screen.
*   Added settings for default API version and server URL, and the ability to disable unsecure connection detection and request timer.
*   Created collapsible sections for debug logs
*   Corrected minor bugs, including:

*   CSV-exported queries now automatically calls queryMore()
*   Customized settings now loaded before login to allow so client id and proxy settings are not lost after logout
*   Standardized use of ap0-api and eu0-api endpoints in QuickSelect
*   Select screen no longer is blank if no choice is provided
*   'Use Default Assignment Rule' can be saved successfully
*   Enhanced XSS security
*   Fatal errors on blank CSV output and describe on PE orgs

**2.0.13** 2008/06/16

*   Upgraded WSDL to Force.com API Partner 13.0
*   Added SOSL Search functionality with search builder wizard
*   Added Smart Lookup for Insert, Update, and Upsert by foreign idLookup fields
*   Added caching describeSObjectResults and getUserInfoResults
*   Added Settings page and config.php for extensible, dual-level configuration of all aspects of the Workbench
*   Added support for Force.com API SOAP Headers:

*   Auto-Assignment Rules
*   Default Namespace
*   Client Id
*   Trigger Auto-Assignment, User, and/or Other Emails
*   Territory Delete Transfer User
*   Query Batch Size
*   Update Most Recently Used (MRU) items list on sidebar
*   Manual or automatic queryMore()

*   Exposed additional configuration for:

*   fieldsToNull (Insert Null Values)
*   File upload maximum size and row limits
*   Record batch sizes
*   Caching and compression
*   Proxy Settings
*   Alphabetizing of field names
*   Auto-jump for Query and Search results
*   Invalidate Salesforce session on logout

*   Expanded GZIP compression to include outbound compression with the option to disable completely
*   Added support for connection via proxy servers
*   Enhanced base client to support nested sObjects for Smart Lookup functionality and added fieldsToNull to sObject class definition with optional supporting logic
*   Added support for manual queryMore() retrieve when displaying query results in browser for increased performance and avoid browser timeouts for large queries
*   Corrected bugs inserting values with non-English latin characters
*   Added Requested Time performance clock to footer
*   Added support for field names to be alphabetized
*   Renamed Export to Query

**1.3.12** 2008/04/29

*   Corrected bug to allow for PHP Magic Quotes to be enabled and dynamically detected to strip slashes from queries

**1.1.12** 2008/04/19

*   Added auto-update reminder function if cURL is enabled
*   Corrected EMEA login URL bug
*   Corrected Export Query Builder bug that did not properly unquote null values
*   Started to move some constants to central shared.php for version control and auto-update reminders
*   Migrated SVN, downloads, and issue tracking to [Google Code](http://code.google.com/p/forceworkbench/)
*   Moved Help documentation to [developer.force.com](http://wiki.apexdevnet.com/index.php/Workbench)

**1.0.12** 2008/03/22

*   Updated documentation for general availability
*   First public release on Sourceforge

**0.6.12** 2008/03/18

*   Added an elapsed time clock to Query Results. Note, this is the time for the query() and queryMore() functions took to complete their requests with the Force.com API, not including the time it takes for PHP to process, transmit, and display the results to the end user.
*   Added line numbers to the Query Results to match the Excel row numbers.

**0.5.12** 2008/02/25

*   Upgraded WSDL to Force.com API Partner 12.0
*   Upgraded base client to PHP Toolkit 11.1
*   Added support for login with URL arguments for single sign on inside a Salesforce Web Tab. [Instructions](http://wiki.developerforce.com/index.php/Workbench#Login)
*   Simplified standard and advanced logins at code level
*   Added support for endpoint changes when logging in with username and password under Advanced login option with auto-enabling fields
*   Improved user interface on Export with more accessible and intuitive layout, auto-enabling field choices, and an additional filter selection
*   Support for count() keyword in SOQL queries displaying results in browser
*   Query result anchor jumping so the user does not have to scroll after running a query
*   Enhanced Select page with auto-enabling field choices for more intuitive workflow
*   Collapsible tree view for Describe function
*   Increased maximum record size for Insert, Update, and Upsert to 2000 records, and Delete, Undelete, and Purge to 5000 records
*   Added tooltip hovers to menubar
*   Fixed minor bugs:

*   Corrected PHP warnings for non-existent foreach() variables before field selections are made
*   Corrected wording of Purge info and error messages
*   Allowed API calls that do not depend on an object secion to not require selection of an object

**0.4.11** 2007/11/18

*   Upgraded WSDL to Force.com API Partner 11.0
*   Added Purge functionality to remove items from the Recycle Bin by ID
*   Expanded deleteUndelete function to allow for any simple API call that takes only IDs
*   Updated Salesforce.com branding from Apex to Force.com

**0.3.10** 2007/09/12

*   Changed branding from Apex DataLoader.PHP to Workbench
*   Enhanced and streamlined the SOQL builder JavaScript function to automatically update the entire SOQL query when the user makes any changes to the criteria in the form. Also added onKeyUp event handling for realtime updating while the user types criteria in text fields.
*   Added ORDER BY sorting to SOQL builder
*   Added error handling if no object is selected when using SOQL builder
*   Streamlined login and action-picking process with Jump To menu on login screen to avoid extra step with Select page

**0.2.10** 2007/09/04

*   Partially abstracted PHP code and shared common functions for more efficient code re-use
*   Added support for Query All method to query recycled and archived records
*   Re-coded the Export functions to support queries of more than 2000 records by passing the Query Locater to a looping Query More calls to the Apex API
*   Re-coded all basic put functions to support more than 200 records by looping multiple calls to the Apex API
*   Added JavaScript functions for persistent login and SOQL builder
*   Upgraded WSDL to Apex API 10.0

**0.1.9** 2007/08/23

*   Completed development of all basic functions without JavaScript flourishes
*   Upgraded WSDL to Apex API 9.0

**0.0.8** 2007/08/05

*   Blank screen
*   Stripped down Apex API 8.0 Partner WSDL-based PHP Toolkit
