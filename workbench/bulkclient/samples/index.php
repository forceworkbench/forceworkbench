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
            
            input, select {
                position: absolute;
                left: 160px;
            }
            
            input[type="text"] {
                width: 300px;
            }
        </style>
    </head>
    <body>
        <h2>PHP Bulk API Client Samples</h2>

        <p>Provide the details below to run a sample script:</p> 
        
        <form id="sampleLauncherForm" method="GET">
            <p><label>Session Id: <input name="sessionId" type="text"/></label></p>
            <p><label>Partner API Endpoint: <input name="partnerApiEndpoint" type="text"/></label></p>
            <p>
                <label>Sample Script File: 
                    <select id="sampleFile" onchange="setFormAction(this.value);">
                        <?php
                            $thisFile  = basename($_SERVER["PHP_SELF"]); 
                            $thisDir = dirname($_SERVER["SCRIPT_FILENAME"]);
                            foreach (scandir($thisDir) as $file) {
                                if (stristr($file, ".php") && $file != $thisFile) {
                                    print "<option value=\"$file\">$file</option>";
                                }
                            }
                        ?>
                    </select>
                </label>
            </p>
            <p><input type="submit" value="Submit"></p>
        </form>
    </body>
    <script type="text/javascript">
        function setFormAction(action) {
            document.getElementById("sampleLauncherForm").action = action;
        }

        setFormAction(document.getElementById("sampleFile").value);
    </script>
</html>
