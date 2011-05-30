<?php
require_once 'session.php';
setcookie("sid", WorkbenchContext::get()->getSessionId());
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <script type="text/javascript" src="cometd/dojo/dojo.js.uncompressed.js"></script>
    <script type="text/javascript" src="cometd/bayeux.js"></script>

    <script type="text/javascript">
        var config = {
            contextPath: "<?php echo dirname(parse_url($_SERVER["PHP_SELF"], PHP_URL_PATH)); ?>"
        };
    </script>
</head>
<body>

    <div id="body"></div>

</body>
</html>
