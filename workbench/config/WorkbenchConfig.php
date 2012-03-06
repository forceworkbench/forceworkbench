<?php

class WorkbenchConfig {

    const INSTANCE = "WORKBENCH_CONFIG";

    private $config;

    /**
     * @static
     * @return WorkbenchConfig
     */
    static function get() {
        if (!isset($GLOBALS[self::INSTANCE])) {
            $GLOBALS[self::INSTANCE] = new WorkbenchConfig();
        }

        return $GLOBALS[self::INSTANCE];
    }

    function __construct() {
        //load default config values
        require 'defaults.php';

        // load file-based config overrides
        if(is_file('config/overrides.php')) require 'overrides.php';

        // TODO: remove this temp hackiness
        $this->config = $GLOBALS["WORKBENCH_CONFIG_TEMP"];
        unset($GLOBALS["WORKBENCH_CONFIG_TEMP"]);
        unset($GLOBALS["config"]);

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
                    $point = &$this->config;
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
        
        foreach ($this->config as $configKey => $configValue) {
            // skip headers
            if (isset($configValue['isHeader'])) {
                continue;
            }
        
            // does the user have an override?
            else if (isset($_COOKIE[$configKey])) {
                // override the session value with that of the cookie
                if ($configValue['overrideable']) {
                    $this->config[$configKey]['value'] = $_COOKIE[$configKey];
                }
                // remove the override if not actually overridable and set to default
                else {
                    setcookie($configKey,NULL,time()-3600);
                    $this->config[$configKey]['value'] = $configValue['default'];
                }
            }
            // otherwise, just use the default
            else {
                $this->config[$configKey]['value'] = $configValue['default'];
            }
        }

        if ($this->config['callOptions_client']['default'] == 'WORKBENCH_DEFAULT' && !isset($_COOKIE['callOptions_client'])) {
            $this->config['callOptions_client']['value'] = getWorkbenchUserAgent();
        }
    }


    // TODO: what to do about min api version?
    public function value($configKey) {
        if (!isset($this->config[$configKey]["value"])) {
            if ($this->config[$configKey]["dataType"] == "boolean") {
                return false;
            } else {
                return null;
            }
        }

        return $this->config[$configKey]["value"];
    }

    public function overrideable($configKey) {
        if (isset($this->config[$configKey]["overrideable"])) {
            return false;
        }

        return $this->config[$configKey]["overrideable"];
    }

    public function valuesToLabels($configKey) {
        if (!isset($this->config[$configKey]["valuesToLabels"])) {
            return array();
        }

        return $this->config[$configKey]["valuesToLabels"];
    }

    public function minApiVersion($configKey) {
        if (isset($this->config[$configKey]["minApiVersion"])) {
            return false;
        }

        return $this->config[$configKey]["minApiVersion"];
    }
}
