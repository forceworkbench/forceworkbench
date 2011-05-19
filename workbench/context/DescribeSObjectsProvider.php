<?php
require_once 'CacheableValueProvider.php';

class DescribeSObjectsProvider extends CacheableValueProvider {

    function isCachingEnabled() {
        return getConfig('cacheDescribeSObject');
    }

    protected function isCached($args) {
        if (is_array($args)) {
            throw new Exception("Bulk SObject describes not yet implemented in Workbench Context"); //TODO!!!
        }

        $describeCache =& $this->getCacheLocation();
        return isset($describeCache[$args]);
    }

    protected function &fetch($args) {
        if (is_array($args)) {
            throw new Exception("Bulk SObject describes not yet implemented in Workbench Context"); //TODO!!!
        }

        $describeCache =& $this->getCacheLocation();
        return $describeCache[$args];
    }

    protected function store($value, $args) {
        if (is_array($args)) {
            throw new Exception("Bulk SObject describes not yet implemented in Workbench Context"); //TODO!!!
        }

        $describeCache =& $this->getCacheLocation();
        $describeCache[$args] = $value;
    }

    function load($args) {
        if (is_array($args)) {
            throw new Exception("Bulk SObject describes not yet implemented in Workbench Context"); //TODO!!!
        }

        return WorkbenchContext::get()->getPartnerConnection()->describeSObject($args);
    }

}

?>
