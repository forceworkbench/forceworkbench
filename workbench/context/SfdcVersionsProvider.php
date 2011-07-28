<?php
require_once 'context/CacheableValueProvider.php';

class CurrentAppVersionProvider extends CacheableValueProvider {

    function isCachingEnabled() {
        return true;
    }

    function load($args) {
        $versionsResponse = WorkbenchContext::get()->getRestDataConnection()->send("GET", "/services/data", null, null, false);
        return json_decode($versionsResponse->body);
    }
}

?>