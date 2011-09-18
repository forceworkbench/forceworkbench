<?php

abstract class CacheableValueProvider {

    private $cacheKey;
    private $serializableCache;

    public function __construct($cacheKey) {
        $this->cacheKey = $cacheKey;
    }

    public function &get($args) {
        if ($this->isCachingEnabled()) {
            if ($this->isCached($args)) {
                $cachedValue =& $this->fetch($args);
                return $cachedValue;
            } else {
                $loadedValue = $this->load($args);
                $this->store($loadedValue, $args);
                return $this->fetch($args);
            }
        } else {
            $loadedValue =& $this->load($args);
            return $loadedValue;
        }
    }

    final protected function getCacheKey() {
        return $this->cacheKey;
    }

    protected function isCachingEnabled() {
        return true;
    }

    protected function isCached($args) {
        $cacheLocation = $this->getCacheLocation();
        return (isset($cacheLocation));
    }

    protected function store($value, $args) {
        $cacheLocation =& $this->getCacheLocation();
        $cacheLocation = $value;
    }

    protected abstract function load($args);

    protected function &fetch($args) {
        return $this->getCacheLocation();
    }

    protected function &getCacheLocation() {
        return $this->serializableCache;
    }

    public function clear() {
        unset($this->serializableCache);
    }
}

?>
