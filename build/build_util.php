<?php

    function matchOne($pattern, $subject, $group) {
        preg_match($pattern, $subject, $matches);
        return $matches[$group];
    }

?>
