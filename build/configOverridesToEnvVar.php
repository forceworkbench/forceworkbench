<?php
$configOverridesFile = '../workbench/configOverrides.php';

if (!is_file($configOverridesFile)) {
    echo "No config overrides file found at $configOverridesFile";
}

require_once $configOverridesFile;
$exportCmds=array();
$herokuCmds=array();

foreach ($config as $configKey => $configValue) {
    processCmds($configKey, $configValue);
}

echo "EXPORT COMMANDS\n";
print implode("\n", $exportCmds);

echo "\n\nHEROKU COMMANDS\n";
print implode("\n", $herokuCmds);

function processCmds($key, $value) {
    $configNamespace = "forceworkbench";
    $configDelim = "__";
    global $exportCmds;
    global $herokuCmds;

    if (is_array($value)) {
        foreach ($value as $subKey => $subValue) {
            processCmds($key . $configDelim . $subKey, $subValue);
        }
    } else {
        $key = str_replace(".", "___DOT___", $key);

        $value = (!$value ? "false" : $value);

        $keyValue = "\"" . $configNamespace . $configDelim . $key . "=$value\"";
        $exportCmds[] = "export " . $keyValue;
        $herokuCmds[] = "heroku config:add " . $keyValue;
    }
}