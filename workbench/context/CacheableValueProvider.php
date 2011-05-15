<?php
 
interface CacheableValueProvider {

    function isSerializable();

    function load($args);

}

?>
