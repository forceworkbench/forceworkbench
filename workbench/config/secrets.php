<?php
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// THIS FILE IS INTENDED FOR ADVANCED ADMINS ONLY TO SET SECRETS THAT OVERRIDE VALUES IN default.php
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// OAUTH 2.0 SETTINGS
// Uncomment and populate with keys and secrets to enable OAuth.
// Note, Production and Sandbox can have the same key and secret, but it is not required
// If connecting to other Salesforce environments, add a new entry to the array:
//
//$config["oauthConfigs"]["default"] = array(
//                                            "login.salesforce.com" => array(
//                                                "label" => "Production",
//                                                "key" => "",
//                                                "secret" => ""
//                                            ),
//                                            "test.salesforce.com" => array(
//                                                "label" => "Sandbox",
//                                                "key" => "",
//                                                "secret" => ""
//                                            )
//                                        );


// CSRF SECURITY SETTINGS
// Uncomment and change the value below to a random, secret value:
//
// $config["csrfSecret"]["default"] = "CHANGE_ME";

/* SODIUM ENCRYPTION KEY AND NONCE SETTINGS
It is recommended that you change the values below to custom strings.
    -The sodiumKey must be 64 characters long
    -The nonce must be 24 characters long
 */
$config["sodiumKey"]["default"] = "T8TAoGtlCWOwWrFFRjTThFDn9+iGsGGjhvALbWkSONN4KyDNbI2VNZmm+sCiM5X7";
$config["nonce"]["default"] = "aojzmL4AKy1s5T5JnQ1yn+2U";

?>
