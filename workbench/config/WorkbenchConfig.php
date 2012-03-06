<?php

class WorkbenchConfig {

    const INSTANCE = "WORKBENCH_CONFIG";

    private $config;

    static function init() {
        $GLOBALS[self::INSTANCE] = new WorkbenchConfig();
        return $GLOBALS[self::INSTANCE];
    }

    function __construct() {
        //load default config values
        require_once 'config/defaults.php';

        // load file-based config overrides
        if(is_file('config/overrides.php')) require_once 'config/overrides.php';

        $config = $GLOBALS["WORKBENCH_CONFIG_TEMP"];

        // load environment variable based overrides
        $configNamespace = "forceworkbench";
        $configDelim = "__";
        foreach ($_ENV as $envKey => $envValue) {
            if (strpos($envKey, $configNamespace) !== 0) {
                continue;
            }
        
            $envKey = str_replace("___DOT___", ".", $envKey);
        
            $envKeyParts = explode($configDelim, $envKey);
        
            foreach ($envKeyParts as $keyPart) {
                if ($keyPart === $configNamespace) {
                    $point = &$config;
                    continue;
                }
        
                if (!isset($point[$keyPart])) {
                    $point[$keyPart] = "";
                }
        
                $point = &$point[$keyPart];
            }
        
            if (!isset($point) || is_array($point)) {
                workbenchLog(LOG_ERR, "Invalid location for $envKey");
                continue;
            }
        
            $point = ($envValue === "false") ? false : $envValue;
        }
        
        foreach ($config as $configKey => $configValue) {
            // skip headers
            if (isset($configValue['isHeader'])) {
                continue;
            }
        
            // does the user have an override?
            else if (isset($_COOKIE[$configKey])) {
                // override the session value with that of the cookie
                if ($configValue['overrideable']) {
                    $this->config[$configKey] = $_COOKIE[$configKey];
                }
                // remove the override if not actually overridable and set to default
                else {
                    setcookie($configKey,NULL,time()-3600);
                    $this->config[$configKey] = $configValue['default'];
                }
            }
            // otherwise, just use the default
            else {
                $this->config[$configKey] = $configValue['default'];
            }
        }
        
        unset($GLOBALS["WORKBENCH_CONFIG_TEMP"]);
    }
    
    public function get($configKey) {
        if (!isset($this->config[$configKey]) || 
            (isset($GLOBALS["config"][$configKey]["minApiVersion"])) &&
             !WorkbenchContext::get()->isApiVersionAtLeast($GLOBALS["config"][$configKey]["minApiVersion"])) {
            
            if ($GLOBALS["config"][$configKey]["dataType"] == "boolean") {
                return false;
            } else {
                return null;
            }
        }
        
        return $this->config[$configKey];
    }
}
