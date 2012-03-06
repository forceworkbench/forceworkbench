#!/usr/bin/env php
<?php
/**
 * This is a script to convert file based configuration values to environment variables
 * for use locally with `export` or on Heroku with `heroku config:add`.
 *
 * Usage: ConverConfigFileToEnvVar.php [config_file]
 */
if ($argc > 1) {
    $configFile = $argv[1];
} else {
    $defaultConfigFile = "../workbench/config/overrides.php";
    $configFile = trim(readline("Provide location of config file to convert [$defaultConfigFile]:"));
    if ($configFile == "") {
        $configFile = $defaultConfigFile;
    }
}

if (!is_file($configFile)) {
    echo "No config file found at $configFile\n";
    exit(1);
}

require_once $configFile;

if (!isset($config)) {
    echo "No config values found in file $configFile\n";
    exit(1);
}

$processedConfigs = processConfigs("forceworkbench", $config);

echo "EXPORT COMMANDS\n";
printConfigs($processedConfigs, "export ");

echo "\n\nHEROKU COMMANDS\n";
printConfigs($processedConfigs, "heroku config:add ");

function processConfigs($key, $value, $processed = array()) {
    $configDelim = "__";

    if (is_array($value)) {
        foreach ($value as $subKey => $subValue) {
            $processed = processConfigs($key . $configDelim . $subKey, $subValue, $processed);
        }
    } else {
        $key = str_replace(".", "___DOT___", $key);
        $value = (!$value ? "false" : $value);
        $processed[] = "\"" . $key . "=$value\"";
    }

    return $processed;
}

function printConfigs($processedConfigs, $cmd) {
    print "Individual:\n";
    foreach ($processedConfigs as $c) {
        print "$cmd$c\n";
    }
    print "\nBatch:\n";
    print $cmd .  implode(" ", $processedConfigs);
}