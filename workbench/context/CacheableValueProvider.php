<?php
 
interface CacheableValueProvider {

    function isSerializable();

    function isCacheable();

    function load($args);

}

?>
