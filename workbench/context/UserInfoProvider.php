<?php
require_once 'context/CacheableValueProvider.php';

class UserInfoProvider implements CacheableValueProvider {

    function isSerializable() {
        return true;
    }

    function isCacheable() {
        return getConfig('cacheGetUserInfo');
    }

    function load($args) {
        return WorkbenchContext::get()->getPartnerConnection()->getUserInfo();
    }

}

?>
