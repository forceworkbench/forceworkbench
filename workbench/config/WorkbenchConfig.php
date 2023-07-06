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

    static function set($config) {
        $GLOBALS[self::INSTANCE] = $config;
    }

    static function destroy() {
        unset($GLOBALS[self::INSTANCE]);
    }

    function __construct() {
        // initialize in case load issues
        $config = array();

        //load default config values
        require 'defaults.php';

        // load file-based config overrides
        if (is_file('config/overrides.php')) {
            /** @noinspection PhpIncludeInspection */
            require 'config/overrides.php';
        }

        // load legecy file-based config-overrides
        if (is_file('configOverrides.php')) {
            /** @noinspection PhpIncludeInspection */
            require 'configOverrides.php';
        }

        // unset from global namespace
        $this->config = $config;
        unset($config);

        // load environment variable based overrides
        $configNamespace = "forceworkbench";
        $configDelim = "__";
        foreach ($_ENV as $envKey => $envValue) {
            if (strpos($envKey, $configNamespace) !== 0) {
                continue;
            }
        
            $envKey = str_replace("___DOT___", ".", $envKey);
        
            $envKeyParts = explode($configDelim, $envKey);

            $lastKey = end($envKeyParts);
            reset($envKeyParts);
            foreach ($envKeyParts as $keyPart) {
                if ($keyPart === $configNamespace) {
                    $point = &$this->config;
                    continue;
                }
        
                if (!isset($point[$keyPart])) {
                    if ($keyPart === $lastKey) {
                      $point[$keyPart] = "";
                      } else {
                        $point[$keyPart] = [];
                      }
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

    public function entries() {
        return $this->config;
    }

    public function isConfigured($configKey) {
        return isset($this->config[$configKey]);
    }

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

    /**
     * Returns the configured value for a key; otherwise, returns specified default
     *
     * @param $configKey
     * @param $otherwise
     * @return mixed
     */
    public function valueOrElse($configKey, $otherwise) {
        if (isset($this->config[$configKey]["value"])) {
            return $this->config[$configKey]["value"];
        } else {
            return $otherwise;
        }
    }

    public function overrideable($configKey) {
        return isset($this->config[$configKey]["overrideable"]) && $this->config[$configKey]["overrideable"];
    }

    public function overridden($configKey) {
        return $this->config[$configKey]["default"] != $this->config[$configKey]["value"];
    }

    public function label($configKey) {
        return $this->config[$configKey]["label"];
    }

    public function valuesToLabels($configKey) {
        if (!isset($this->config[$configKey]["valuesToLabels"])) {
            return array();
        }

        return $this->config[$configKey]["valuesToLabels"];
    }
}
