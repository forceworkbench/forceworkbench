<?php
// block direct web access
if (php_sapi_name() != 'cli') {
    http_response_code(404);
    exit(1);
}

require_once 'shared.php';
require_once 'config/constants.php';
require_once 'config/WorkbenchConfig.php';
require_once 'context/WorkbenchContext.php';

WorkbenchContext::establish(ConnectionConfiguration::fromUrl(
    getenv('SFDC_SERVER_URL') ? getenv('SFDC_SERVER_URL') : 'https://login.salesforce.com/services/Soap/u/33.0',
    null, null));

function login($un, $pw) {
    print "Logging in as " . getenv('SFDC_USERNAME') . "... ";
    WorkbenchContext::get()->agreeToTerms();
    WorkbenchContext::get()->login($un, $pw, null, null);
    print "done\n";

    $W = WorkbenchContext::get();
    $ui = $W->getUserInfo();
    print "-----> " . $ui->userFullName . " at " . $ui->organizationName . " on API " . $W->getApiVersion() . "\n";
    print "-----> " . "Use \$W to access WorkbenchContext\n";
    print "\n";
}

if (getenv('SFDC_USERNAME') && getenv('SFDC_PASSWORD')) {
    login(getenv('SFDC_USERNAME'), getenv('SFDC_PASSWORD'));
}