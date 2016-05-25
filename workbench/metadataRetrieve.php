<?php
require_once 'soapclient/SforceMetadataClient.php';
require_once 'session.php';
require_once 'shared.php';
if (!WorkbenchContext::get()->isApiVersionAtLeast(10.0)) {
    throw new WorkbenchHandledException("Metadata API not supported prior to version 10.0");
}

if (isset($_POST['retrievalConfirmed']) && isset($_POST["retrieveRequestId"])) {
    $retrieveRequestId = htmlspecialchars($_POST["retrieveRequestId"]);

    if (!isset($_SESSION[$retrieveRequestId])) {
        throw new Exception("No retrieve request found. To re-retrieve, create a new retrieve request.");
    }

    $retrieveAsyncResults = WorkbenchContext::get()->getMetadataConnection()->retrieve($_SESSION[$retrieveRequestId]);

    if (!isset($retrieveAsyncResults->id)) {
        throw new Exception("Unknown retrieval error.\n" . isset($retrieveAsyncResults->message) ? $retrieveAsyncResults->message : "");
    }

    unset($_SESSION[$retrieveRequestId]);
    header("Location: metadataStatus.php?asyncProcessId=" . $retrieveAsyncResults->id . "&op=R");
}

else if (isset($_POST['stageForRetrieval'])) {
    if (isset($_FILES["packageXmlFile"]["name"]) && $_FILES["packageXmlFile"]["name"] == "" && isset($_POST['packageNames']) && $_POST['packageNames'] == "") {
        throw new WorkbenchHandledException("Must specify at least an unpackaged manifest file or a package name.");
    }

    $retrieveRequest = new RetrieveRequest();
    $retrieveRequest->apiVersion = WorkbenchContext::get()->getApiVersion();
    $retrieveRequest->singlePackage = isset($_POST['singlePackage']);

    if (isset($_FILES["packageXmlFile"]["name"]) && $_FILES["packageXmlFile"]["name"] != "") {
        $validationErrors = validateUploadedFile($_FILES["packageXmlFile"]);
        if ($validationErrors) {
            throw new WorkbenchHandledException($validationErrors);
        }

        if (!endsWith($_FILES["packageXmlFile"]["name"], ".xml", true)) {
            throw new WorkbenchHandledException("The file uploaded is not a valid XML file. Please try again.");
        }

        $retrieveRequest->unpackaged = parseUnpackagedManifest($_FILES["packageXmlFile"]["tmp_name"]);
    }

    if (isset($_POST['packageNames']) && $_POST['packageNames'] != "") {
        $encodedPackageNames = array();
        foreach(explodeCommaSeparated(htmlspecialchars($_POST['packageNames'])) as $p) {
            if ($p == "unpackaged") {
                throw new WorkbenchHandledException("Cannot retrieve a package named 'unpackaged' -- to retrieve metadata not in a package, upload an unpackaged manifest file (i.e. 'package.xml').");
            }

            $encodedPackageNames[] = urlencode($p);
        }
        $retrieveRequest->packageNames = $encodedPackageNames;
    }

    $retrieveRequestId = "RR-" . time();
    $_SESSION[$retrieveRequestId] = $retrieveRequest;

    require_once 'header.php';
    displayInfo("Successfully staged retrieve request.");
    ?>
<p class='instructions'>Confirm the following retrieve request:</p>
    <?php
        $tree = new ExpandableTree("retrieveRequestTree", ExpandableTree::processResults($_SESSION[$retrieveRequestId]));
        $tree->setForceCollapse(true);
        $tree->printTree();
    ?>
<form id='retrieveForm' name='retrieveForm' method='POST'
    action=''><input type='hidden'
    name='retrieveRequestId' value='<?php print $retrieveRequestId; ?>' />
    <?php print getCsrfFormTag(); ?>
<input type='submit' name='retrievalConfirmed' value='Retrieve' /></form>
    <?php
}

else {
    require_once 'header.php';
    ?>
<p class='instructions'>Choose an unpackaged manifest file (i.e.
'package.xml'), provide a comma-separated list of package names, or both
to define a retrieve request along with any applicable options:</p>
<form id='retrieveForm' name='retrieveForm' method='POST'
    action=''
    enctype='multipart/form-data'><input type='hidden'
    name='MAX_FILE_SIZE'
    value='<?php print WorkbenchConfig::get()->value("maxFileSize"); ?>' />
    <?php print getCsrfFormTag(); ?>
<table>
    <tr>
        <td style='padding-right: 20px;'>Unpackaged Manifest:</td>
        <td><input id='packageXmlFile' type='file' name='packageXmlFile'
            size='44' /></td>
        <td><img
            onmouseover="Tip('XML file defining types (name and members) and version to be retreived. See Salesforce.com Metadata API Developers guide for an example of a package.xml file.')"
            align='absmiddle' src='<?php echo getPathToStaticResource('/images/help16.png'); ?>' /></td>
    </tr>
    <tr>
        <td>Package Names:</td>
        <td><input id='packageNames' type='text' name='packageNames'
            size='44' /></td>
        <td><img
            onmouseover="Tip('Comma separated list of package names to be retrieved.')"
            align='absmiddle' src='<?php echo getPathToStaticResource('/images/help16.png'); ?>' /></td>
    </tr>
    <tr>
        <td>Single Package:</td>
        <td><input id='singlePackage' type='checkbox'
            name='singlePackage' /></td>
        <td><img
            onmouseover="Tip('Specifies whether only a single package is being retrieved. If false, then more than one package is being retrieved.')"
            align='absmiddle' src='<?php echo getPathToStaticResource('/images/help16.png'); ?>' /></td>
    </tr>
    <tr>
        <td colspan='2'></td>
    </tr>
    <tr>
        <td></td>
        <td colspan='2'><input type='submit' name='stageForRetrieval'
            value='Next' /></td>
    </tr>
</table>
</form>
    <?php
}

include_once 'footer.php';

function parseUnpackagedManifest($xmlFile) {
    try {
        libxml_use_internal_errors(true);
        libxml_disable_entity_loader(true);
        $xmlString = file_get_contents($xmlFile);
        $packageXml = simplexml_load_string(disallowDoctype($xmlString));
        if (!isset($packageXml) || !$packageXml) {
            $xmlErrors = array();
            foreach (libxml_get_errors() as $xmlError) {
                $msg = preg_replace('!"/tmp/php.*"!', "", $xmlError->message);
                $xmlErrors[] = "$msg [Line $xmlError->line : Column: $xmlError->column]";
            }
            displayError($xmlErrors, true, true);
            libxml_clear_errors();
            exit;
        }
    } finally {
        libxml_use_internal_errors(false);
        libxml_disable_entity_loader(false);
    }

    $unpackaged = new Package();

    if (isset($packageXml->version)) {
        $unpackaged->version = (string) $packageXml->version;
    } else {
        throw new WorkbenchHandledException("'version' element is required");
    }

    if (isset($packageXml->types)) {
        $unpackaged->types = array();
        foreach ($packageXml->types as $typeXml) {
            $type = new PackageTypeMembers();
            if(isset($typeXml->name)) $type->name = (string) $typeXml->name;
            if (isset($typeXml->members)) {
                $type->members = array();
                foreach ($typeXml->members as $memberXml) {
                    $type->members[] = (string) $memberXml;
                }
                $unpackaged->types[] = $type;
            }
        }
    }

    unset($packageXml);

    return $unpackaged;
}
?>