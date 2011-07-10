<!DOCTYPE unspecified PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    <head>
        <style type="text/css">
            * {
                font-family: sans-serif;
            }
            
            p {
                font-size: smaller;
            }
            
            label {
                font-weight: bold;
            }
            
            input[type="text"] {
                width: 500px;
            }

            .error {
                color: red;
            }

            .output * {
               	white-space: pre-wrap;
                font-family: courier, monotype;
                font-size: small;
            }
        </style>
    </head>
    <body>
        <h2>PHP Bulk API Client Samples</h2>

        <p>Provide the details below to run a sample script:</p> 
        
        <form method="GET" action="">
            <p><label>Session Id:<br/><input name="sessionId"
                                             type="text"
                                             value="<?php print isset($_REQUEST['sessionId']) ? htmlspecialchars($_REQUEST['sessionId']) : ""; ?>"/></label></p>
            <p><label>Partner API Endpoint:<br/><input name="partnerApiEndpoint"
                                                       type="text"
                                                       value="<?php print isset($_REQUEST['partnerApiEndpoint']) ?
                                                               htmlspecialchars($_REQUEST['partnerApiEndpoint']) : ""; ?>"/></label></p>
            <p>
                <label>Sample Script File:<br/> 
                    <select name="sampleFile">
                        <?php
                            $thisFile  = basename($_SERVER["PHP_SELF"]); 
                            $thisDir = dirname($_SERVER["SCRIPT_FILENAME"]);
                            foreach (scandir($thisDir) as $file) {
                                if (stristr($file, ".php") && $file != $thisFile) {
                                    print "<option value=\"" . htmlspecialchars($file) . "\"" .
                                          (($_REQUEST['sampleFile'] == $file) ? "selected='selected'" : "") .
                                          ">" . htmlspecialchars($file) . "</option>";
                                }
                            }
                        ?>
                    </select>
                </label>
            </p>
            <p><input name="submit" type="submit" value="Submit"></p>
        </form>


        <div class="output">
            <?php
            if (!isset($_REQUEST["submit"])) {
                exit;
            }

            try {
                require_once($_REQUEST["sampleFile"]);
            } catch (Exception $e) {
                print "<span class='error'><label>Error: </label>" . htmlspecialchars($e->getMessage()) . "</span>";
            }
            ?>
        </div>

    </body>
</html>