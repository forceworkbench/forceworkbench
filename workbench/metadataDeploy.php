<?php
require_once 'soapclient/SforceMetadataClient.php';
require_once 'session.php';
require_once 'shared.php';
define("RUN_TESTS_DEPLOY_OPTION", "runTests");
define("TEST_LEVEL_DEPLOY_OPTION", "testLevel");
define("RUN_SPECIFIED_TESTS_TEST_LEVEL", "RunSpecifiedTests");

if (!WorkbenchContext::get()->isApiVersionAtLeast(10.0)) {
    displayError("Metadata API not supported prior to version 10.0", true, true);
    exit;
}

if (isset($_POST['deploymentConfirmed']) && isset($_POST["deployFileTmpName"])) {
    $deployFileTmpName = htmlspecialchars($_POST["deployFileTmpName"]);

    if (!isset($_SESSION[$deployFileTmpName])) {
        displayError("No zip file currently staged for deployment. To re-deploy, create a new deploy request.", true, true);
        exit;
    }

    if (!isset($_SESSION[$deployFileTmpName . "_OPTIONS"])) {
        displayError("Error loading deploy options. To re-deploy, create a new deploy request.", true, true);
        exit;
    }

    try {
        $deployAsyncResults = WorkbenchContext::get()->getMetadataConnection()->deploy($_SESSION[$deployFileTmpName], $_SESSION[$deployFileTmpName . "_OPTIONS"]);
        unset($_SESSION[$deployFileTmpName]);
        unset($_SESSION[$deployFileTmpName . "_OPTIONS"]);

        if (!isset($deployAsyncResults->id)) {
            displayError("Unknown deployment error.\n" . isset($deployAsyncResults->message) ? $deployAsyncResults->message : "", true, true);
            exit;
        }

        header("Location: metadataStatus.php?asyncProcessId=" . $deployAsyncResults->id . "&op=D");
    } catch (Exception $e) {
        displayError($e->getMessage(), true, true);
        exit;
    }
}

else if (isset($_POST['stageForDeployment'])) {
    $validationErrors = validateZipFile($_FILES["deployFile"]);
    if ($validationErrors) {
        displayError($validationErrors, true, true);
        exit;
    }

    $deployFileTmpName = $_FILES["deployFile"]["tmp_name"];
    $deployFileContents = file_get_contents($deployFileTmpName);
    if (!isset($deployFileContents) || !$deployFileContents) {
        displayError("Unknown error reading file contents.", true, true);
        exit;
    }
    $_SESSION[$deployFileTmpName] = $deployFileContents;
    $_SESSION[$deployFileTmpName . "_OPTIONS"] = deserializeDeployOptions($_POST);

    require_once 'header.php';
    print "<p/>";
    displayInfo("Successfully staged " . ceil(($_FILES["deployFile"]["size"] / 1024)) . " KB zip file " . $_FILES["deployFile"]["name"] . " for deployment.", true, false);

    ?>
<p class='instructions'>Confirm the following deployment options:</p>
<form id='deployForm' name='deployForm' method='POST'
    action=''>
    <?php print getCsrfFormTag(); ?>
    <input type='hidden' name='deployFileTmpName' value='<?php print $deployFileTmpName; ?>' />
<p />
    <?php
        $tree = new ExpandableTree("deployOptionsTree", $_SESSION[$deployFileTmpName . "_OPTIONS"]);
        $tree->setForceCollapse(true);
        $tree->printTree();
    ?>
<p />
    <?php
    if (!isset($_POST['checkOnly'])) {
        displayWarning("Warning, this deployment will make permanent changes to this organization's metadata and cannot be rolled back. " .
                          "Use the 'Check Only' option to validate this deployment without making changes.");
        print "<p/>";
    }
    ?> <input type='submit' name='deploymentConfirmed' value='Deploy' />
</form>
    <?php
}

else {
    require_once 'header.php';
    ?>
<p class='instructions'>Choose a ZIP file to deploy and any applicable
options:</p>

<form id='deployForm' name='deployForm' method='POST'
    action=''
    enctype='multipart/form-data'><input type='file' name='deployFile'
    size='44' />
    <?php print getCsrfFormTag(); ?>
    <input type='hidden' name='MAX_FILE_SIZE' value='<?php print WorkbenchConfig::get()->value("maxFileSize"); ?>' />
    <img onmouseover="Tip('Choose a ZIP file containing a project manifest, a file named package.xml, and a set of directories that contain the components to deploy.  See Salesforce.com Metadata API Developers guide more information about working with ZIP files for deployment.')"
     align='absmiddle' src='<?php echo getPathToStaticResource('/images/help16.png'); ?>' />
<p />
    <?php printDeployOptions(defaultDeployOptions()); ?>
<p />
<input type='submit' name='stageForDeployment' value='Next' /></form>
    <?php
}

include_once 'footer.php';
exit;




function deserializeDeployOptions($request) {
    $deployOptions = defaultDeployOptions();

    foreach ($deployOptions as $optionName => $optionValue) {
        if (is_bool($optionValue)) {
            $deployOptions->$optionName = isset($request[$optionName]);
        } else if ($optionName == TEST_LEVEL_DEPLOY_OPTION) {
            $deployOptions->$optionName = $request[$optionName];
        } else if ($optionName == RUN_TESTS_DEPLOY_OPTION) {
            $deployOptions->$optionName = (isset($request[$optionName]) && $request[$optionName] != "") ? explodeCommaSeparated(htmlspecialchars($request[$optionName])) : null;
        }
    }

    return $deployOptions;
}

function printDeployOptions($deployOptions) {
    if (array_key_exists('testLevel', $deployOptions)) {
        $editable = false;
    } else {
        $editable = true;
    }
    print "<table>\n";
    foreach ($deployOptions as $optionName => $optionValue) {
        print "<tr><td style='text-align: right; padding-right: 2em; padding-bottom: 0.5em;'>" .
              "<label for='$optionName'>" . unCamelCase($optionName) . "</label></td><td>";
        if (is_bool($optionValue)) {
            print "<input id='$optionName' type='checkbox' name='$optionName' " . (isset($optionValue) && $optionValue ? "checked='checked'" : "") . "/>";
        } else if ($optionName == TEST_LEVEL_DEPLOY_OPTION) {
            $targetElementId = RUN_TESTS_DEPLOY_OPTION;
            $valueToLookFor = RUN_SPECIFIED_TESTS_TEST_LEVEL;
            print "<select id='$optionName' name='$optionName' onchange=\"var el = document.getElementById('$targetElementId');this.value == '$valueToLookFor' ? el.removeAttribute('disabled') : el.disabled='disabled';\">";
            $testLevelValues = array("NoTestRun", "RunLocalTests", "RunAllTestsInOrg", RUN_SPECIFIED_TESTS_TEST_LEVEL);
            foreach ($testLevelValues as $testLevelValue) {
                print "<option value='$testLevelValue'>$testLevelValue</option>";
            }
            print "</select>";
        } else if ($optionName == RUN_TESTS_DEPLOY_OPTION) {
            print "<input id='$optionName' type='text' name='$optionName' value='" . implode(",", $optionValue) . "'" . " " . ($editable ? "" : "disabled='disabled'")  . "/>";
        }
        print "</td></tr>\n";
    }
    print "</table>\n";
}

function validateZipFile($file) {
    $validationResult = validateUploadedFile($file);

    if (!isset($file["tmp_name"]) || $file["tmp_name"] == "") {
        return("No file uploaded for deployment.");
    }

    if (!endsWith($file['name'],'.zip', true)) {
        return("The file uploaded is not a valid ZIP file. Please try again.");
    }

    return $validationResult;
}

function defaultDeployOptions() {
    $deployOptions = new DeployOptions();

    $deployOptions->allowMissingFiles = false;
    $deployOptions->autoUpdatePackage = false;
    $deployOptions->checkOnly = false;
    $deployOptions->ignoreWarnings = false;
    $deployOptions->performRetrieve = false;
    if (WorkbenchContext::get()->isApiVersionAtLeast(22.0)) { $deployOptions->purgeOnDelete = false; }
    $deployOptions->rollbackOnError = false;
    $deployOptions->singlePackage = false;
    if (WorkbenchContext::get()->isApiVersionAtLeast(34.0)) {
        $deployOptions->testLevel = "NoTestRun";
    }
    else {
        $deployOptions->runAllTests = false;
    }
    $deployOptions->runTests = array();

    return $deployOptions;
}

?>
