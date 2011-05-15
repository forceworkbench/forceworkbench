<?php
require_once 'context/ConnectionConfiguration.php';
require_once 'context/CacheableValueProvider.php';

class UserInfoProvider implements CacheableValueProvider {

    function isSerializable() {
        return true;
    }

    function load($args) {
        return WorkbenchContext::get()->getPartnerConnection()->getUserInfo();
    }

}

?>
