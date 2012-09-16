<?php
require_once "futures.php";
require_once "controllers/RestExplorerController.php";

class RestExplorerFutureTask extends FutureTask {

    private $c;

    /**
     * @param $c RestExplorerController
     */
    function __construct($c) {
        parent::__construct();
        $this->c = $c;
    }

    function perform() {
        $this->c->execute();
        return $this->result();
    }

    private function result() {
        ob_start();
        if ($this->c->errors != null) {
            displayError($this->c->errors);
            ?><p/><?php
        }
        ?>
        <div style="float: left;">
            <?php if (trim($this->c->instResponse) != "") { ?>
            <a href="javascript:ddtreemenu.flatten('responseList', 'expand')">Expand All</a> |
            <a href="javascript:ddtreemenu.flatten('responseList', 'contact')">Collapse All</a> |
            <a id="codeViewPortToggler" href="javascript:toggleCodeViewPort();">Show Raw Response</a>

            <div id="responseListContainer" class="results"></div>

            <script type='text/javascript' class='evalable'>convert(<?php echo $this->c->instResponse ?>);</script>
            <?php } ?>
        </div>

        <?php if (isset($this->c->rawResponse)) { ?>
        <div id="codeViewPortContainer" style="display: <?php echo trim($this->c->instResponse) != "" ? "none; right:10px;" : "block"  ?>;">
            <strong>Raw Response</strong>
            <p id="codeViewPort"><?php echo htmlspecialchars($this->c->rawResponse->header); ?><br /><?php echo htmlspecialchars($this->c->rawResponse->body); ?></p>
        </div>
        <?php
        }
        $result = ob_get_contents();
        ob_end_clean();
        return $result;
    }
    
}

?>
