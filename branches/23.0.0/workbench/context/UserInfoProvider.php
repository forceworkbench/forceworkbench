<?php
require_once 'context/CacheableValueProvider.php';

class UserInfoProvider extends CacheableValueProvider {

    function isCachingEnabled() {
        return getConfig('cacheGetUserInfo');
    }

    function load($args) {
        return WorkbenchContext::get()->getPartnerConnection()->getUserInfo();
    }
}

?>
