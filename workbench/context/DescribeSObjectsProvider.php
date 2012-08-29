<?php
require_once 'CacheableValueProvider.php';

class DescribeSObjectsProvider extends CacheableValueProvider {

    public $cache;

    public function __construct($cacheKey) {
        parent::__construct($cacheKey);
        $this->cache =& $this->getCacheLocation();
        $this->cache = array();
    }

    public function &get($args) {
        $types = is_array($args) ? $args : array($args);
        $gottenTypes = parent::get($types);
        $requestedTypes = is_array($args) ? $gottenTypes : $gottenTypes[$args];
        return $requestedTypes;
    }

    function isCachingEnabled() {
        return WorkbenchConfig::get()->value('cacheDescribeSObject');
    }

    protected function isCached($types) {
        $describeCache =& $this->getCacheLocation();

        foreach ($types as $type) {
            if (!array_key_exists($type, $describeCache)) {
                return false;
            }
        }
        return true;
    }

    protected function &fetch($types) {
        $describeCache =& $this->getCacheLocation();
        $requestedTypes =& $loadedTypes;
        foreach ($types as $type) {
            if (!array_key_exists($type, $describeCache)) {
                throw new Exception("Expected $type to be in cache, but was not");
            }

            $requestedTypes[$type] = $describeCache[$type];
        }

        return $requestedTypes;
    }

    protected function store($value, $types) {
        $describeCache =& $this->getCacheLocation();
        $describeCache = array_merge($describeCache, $value);
    }

    function load($types) {
        $describeCache =& $this->getCacheLocation();
        $typesToLoad = array();

        foreach ($types as $type) {
            if (!array_key_exists($type, $describeCache)) {
                $typesToLoad[] = $type;
            }
        }

        if (count($typesToLoad) > 100) {
            throw new Exception("Too many object types to load: " . count($typesToLoad));
        }

        $rawLoadedTypes = WorkbenchContext::get()->getPartnerConnection()->describeSObjects($typesToLoad);

        $loadedTypes = array();
        if ($rawLoadedTypes instanceof stdClass) {
            $loadedTypes = array($rawLoadedTypes->name => $rawLoadedTypes);
        } else if (is_array($rawLoadedTypes)) {
            foreach ($rawLoadedTypes as $rawLoadedType) {
                $loadedTypes[$rawLoadedType->name] = $rawLoadedType;
            }
        } else {
            throw new Exception("Unknown Describe SObject results");
        }


        foreach ($loadedTypes as $name => $result) {
            if (!is_array($result->fields)) {
                $loadedTypes[$name]->fields = array($result->fields);
            }

            if (WorkbenchConfig::get()->value("abcOrder")) {
                $loadedTypes[$name] = $this->alphaOrderFields($result);
            }
        }

        $requestedTypes =& $loadedTypes;
        foreach ($types as $type) {
            if (array_key_exists($type, $describeCache)) {
                $requestedTypes[$type] = $describeCache[$type];
            }
        }

        return $requestedTypes;
    }

    function alphaOrderFields($describeSObjectResult) {
        //move field name out to key name and then ksort based on key for field abc order
        if (isset($describeSObjectResult->fields) && is_array($describeSObjectResult->fields)) {
            if(!is_array($describeSObjectResult->fields)) $describeSObjectResult->fields = array($describeSObjectResult->fields);
            foreach ($describeSObjectResult->fields as $field) {
                $fieldNames[] = $field->name;
            }

            $describeSObjectResult->fields = array_combine($fieldNames, $describeSObjectResult->fields);
            $describeSObjectResult->fields = natcaseksort($describeSObjectResult->fields);
        }
        return $describeSObjectResult;
    }
}

?>
