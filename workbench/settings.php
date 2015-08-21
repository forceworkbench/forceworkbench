<?php
require_once 'session.php';
require_once 'shared.php';

$errors = null;

if (isset($_POST['submitConfigSetter'])) {
    //find errors
    foreach (WorkbenchConfig::get()->entries() as $configKey => $configValue) {
        if (!isset($configValue['isHeader']) && isset($_POST[$configKey])) {
            if (isset($configValue['maxValue']) && $configValue['maxValue'] < $_POST[$configKey]) {
                $errors[] = $configValue['label'] . " must not be greater than " . $configValue['maxValue'];
            } else if (isset($configValue['minValue']) && $configValue['minValue'] > $_POST[$configKey]) {
                $errors[] = $configValue['label'] . " must not be less than " . $configValue['minValue'];
            }
        }
    }

    if (isset($_POST['assignmentRuleHeader_useDefaultRule']) && isset($_POST['assignmentRuleHeader_assignmentRuleId'])
    && ($_POST['assignmentRuleHeader_useDefaultRule'] != FALSE) && ($_POST['assignmentRuleHeader_assignmentRuleId'] != "")) {
        $errors[] = "Can not set both 'Use Default Assignment Rule' and 'Assignment Rule Id'";
    }
}

if (!isset($errors) && isset($_POST['submitConfigSetter']) || isset($_POST['restoreDefaults'])) {
    foreach (WorkbenchConfig::get()->entries() as $configKey => $configValue) {
        // ignore headers
        if (isset($configValue['isHeader'])) {
            continue;
        }

        // don't even try to deal with complex types
        if (isset($configValue['dataType']) && $configValue['dataType'] == "complex") {
            continue;
        }

        //clear config cookies if restoreDefaults selected or the config is not overrideable
        if (isset($_POST['restoreDefaults']) || !isset($configValue['overrideable']) || !$configValue['overrideable']) {
             // ...and is actually in the user's cookies
             if (isset($_COOKIE[$configKey])){
                setcookie($configKey,NULL,time()-3600);
             }
            continue;
        }

        // should only get down here if we're actually setting configs

        //special case for default clientId so that it doesnt persist after upgrading if not customized
        if ($configKey == 'callOptions_client' && $_POST[$configKey] == getWorkbenchUserAgent()) {
            setcookie($configKey,NULL,time()-3600);
            continue;
        }
       
        if (($configValue['dataType'] == "boolean") && 
            !(($configValue['default'] == true  && isset($_POST[$configKey]) || 
               $configValue['default'] == false && !isset($_POST[$configKey])))) {
            
            //for overriden booleans
            setcookie($configKey,(isset($_POST[$configKey]) ? 1 : 0),time()+60*60*24*365*10);
        } else if (isset($_POST[$configKey]) && $configValue['default'] != $_POST[$configKey]) {
            //for non-null strings and numbers
            setcookie($configKey,$_POST[$configKey],time()+60*60*24*365*10);
        } else {
            //for null or non-overriding strings and numbers (remove cookie)
            setcookie($configKey,NULL,time()-3600);
        }
    }

    if (WorkbenchContext::isEstablished()) {
        WorkbenchContext::get()->clearCache();
    }
    
    header("Location: " . htmlspecialchars($_SERVER['PHP_SELF']) . "?saved=" . (isset($_POST['restoreDefaults']) ? "D" : "S"));
}


require_once 'header.php';

if (isset($errors)) {
    displayError($errors);
} else if (isset($_GET['saved'])) {
    displayInfo(($_GET['saved'] == "D" ? "Defaults restored" : "Settings saved") . " successfully.");
}

if (isLoggedIn()) {
    $unsupportedConfigs = array();
    foreach (WorkbenchConfig::get()->entries() as $configKey => $configValue) {
         if (isset($configValue['minApiVersion']) && !WorkbenchContext::get()->isApiVersionAtLeast($configValue['minApiVersion'])) {
             $unsupportedConfigs[] = $configValue['label'] . sprintf(" (Requires %01.1f)", $configValue['minApiVersion']);
         }
    }
    
    if (count($unsupportedConfigs) > 0) {
        print "<p/>";
        displayWarning(array_merge(array(
                       "The following settings will be ignored for your current API version " . WorkbenchContext::get()->getApiVersion() . ":"),
                       $unsupportedConfigs));
                       
        print "<p/><em style='color: orange;'>Quick Fix: <a style='color: orange;' href='sessionInfo.php' target='_blank'>Change API Version</a></em>";
    }
}

print "<p/><form id='settings_form' method='post' action=''>\n";
print getCsrfFormTag();

print "<table border='0' cellspacing='5' style='border-width-top: 1'>\n";

print "<tr> <td colspan='3' align='left'><input type='submit' name='submitConfigSetter' value='Apply Settings'/>&nbsp;<input type='submit' name='restoreDefaults' value='Restore Defaults'/>&nbsp;<input type='reset' value='Cancel'/></td> </tr>";

foreach (WorkbenchConfig::get()->entries() as $configKey => $configValue) {
    // don't even try to deal with complex types
    if (isset($configValue['dataType']) && $configValue['dataType'] == "complex") {
        continue;
    }

    if (isset($configValue['isHeader']) && $configValue['display']) {
        print "\t<tr><th align='left' colspan='3'><br/>" . htmlspecialchars($configValue['label'],ENT_QUOTES) . "</th></tr>\n";
    } else if (isset($configValue['overrideable']) && $configValue['overrideable']==true) {
        $tip = htmlspecialchars(addslashes($configValue['description']),ENT_NOQUOTES);
        $tip .= isset($configValue['minApiVersion']) ? "<br/><br/>Minimum API Version: " . sprintf("%01.1f", $configValue['minApiVersion']) : "";
        print "\t<tr onmouseover=\"Tip('$tip')\">\n";
        print "\t\t<td align='right'><label for='$configKey'" . 
              (isLoggedIn() && isset($configValue['minApiVersion']) && !WorkbenchContext::get()->isApiVersionAtLeast($configValue['minApiVersion']) ? " style='color:orange;'" : "") .
              ">" . htmlspecialchars($configValue['label'],ENT_QUOTES) . "</label></td><td>&nbsp;&nbsp;</td>\n";
        print "\t\t<td align='left'>";
        if ($configValue['dataType'] == "boolean") {
            print "<input name='$configKey' id='$configKey' type='checkbox' ";
            if($configValue['value']) print " checked='true'";
            print "/></td>\n";
        } else if ($configValue['dataType'] == "string" || $configValue['dataType'] == "int") {
            print "<input name='$configKey' id='$configKey' type='text' value='" . (isset($configValue['value']) ? $configValue['value'] : "") . "' size='30'/></td>\n";
        } else if ($configValue['dataType'] == "password") {
            print "<input name='$configKey' id='$configKey' type='password' value='". (isset($configValue['value']) ? $configValue['value'] : "")  . "' size='30'/></td>\n";
        } else if ($configValue['dataType'] == "picklist") {
            print "<select name='$configKey' id='$configKey'>";
            foreach ($configValue['valuesToLabels'] as $value => $label) {
                if (isset($configValue['labelKey'])) {
                    $label = $label[$configValue['labelKey']]; //if the label is an array, this will pull the nested label out
                }
                print "<option value=\"" . $value . "\"";
                if (isset($configValue['value']) && $configValue['value'] == $value) {
                    print " selected=\"selected\"";
                }
                print ">" . $label . "</option>";
            }
            print "</select>";
        } else {
            print "</td>\n";
        }
        print "\t</tr>\n";
    }
}

print "<tr> <td></td> <td></td> <td></td> </tr>\n";

print "<tr> <td colspan='3' align='left'>" .
          "<input type='submit' name='submitConfigSetter' value='Apply Settings'/>&nbsp;" . 
          "<input type='submit' name='restoreDefaults' value='Restore Defaults'/>&nbsp;" . 
          "<input type='reset' value='Cancel'/>" . 
          "</td> </tr>\n";

print "</table>\n";

print "</form>\n";

require_once 'footer.php';

?>
<script>
var isDirty = false;
window.onbeforeunload = function() {
  if (isDirty) {
    return 'You have unsaved changes. Click \'Apply Settings\' before navigating away from this page.';
  }
}

var editor_form = document.getElementById("settings_form");
for (var i = 0; i < editor_form.length; i++) {
    if (editor_form[i].type == 'submit') {
        editor_form[i].onclick = function() {
            isDirty = false;
        }        
    } else {
        editor_form[i].onchange = function() {
            isDirty = true;
        }
    }
}
</script>
