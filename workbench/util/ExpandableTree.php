<?php
 
class ExpandableTree {

    private $name;
    private $nodes;
    private $forceCollapse = false;
    private $additionalMenus = null;
    private $containsIds = false;
    private $containsDates = false;

    function __construct($name, $nodes) {
        $this->name = $name;
        $this->nodes = $nodes;
    }

    public function setForceCollapse($forceCollapse) {
        $this->forceCollapse = $forceCollapse;
    }

    public function setAdditionalMenus($additionalMenus) {
        $this->additionalMenus = $additionalMenus;
    }

    public function setContainsIds($containsIds) {
        $this->containsIds = $containsIds;
    }

    public function setContainsDates($containsDates) {
        $this->containsDates = $containsDates;
    }

    public function printTree() {
        print "<a class=\"pseudoLink\" onclick=\"javascript:ddtreemenu.flatten('$this->name', 'expand'); return false;\">Expand All</a> | " .
              "<a class=\"pseudoLink\" onclick=\"javascript:ddtreemenu.flatten('$this->name', 'collapse'); return false;\">Collapse All</a>\n";

        if (isset($this->additionalMenus)) {
            print $this->additionalMenus;
        }

        print "<ul id='$this->name' class='treeview'>";

        $this->printNode($this->nodes);

        print "</ul>\n";

        addFooterScript("<script type='text/javascript' src='" . getStaticResourcesPath() . "/script/simpletreemenu.js'></script>");

        addFooterScript("<script type='text/javascript'>" .
                           "ddtreemenu.createTree('$this->name', true);" .
                            ($this->forceCollapse ? "ddtreemenu.flatten('$this->name', 'collapse');" : "") .
                        "</script>");
    }

    private function printNode($node) {
        foreach ($node as $nodeKey => $nodeValue) {
            if (is_array($nodeValue) || is_object($nodeValue)) {
                print "<li>$nodeKey<ul style='display:none;'>\n";
                $this->printNode($nodeValue);
                print "</ul></li>\n";
            } else {
                $nodeKey = is_numeric($nodeKey) ? "" : $nodeKey . ": ";

                if (is_bool($nodeValue)) {
                    $nodeValue = $nodeValue == 1 ? "<span class='trueColor'>true</span>" : "<span class='falseColor'>false</span>";
                } else {
                    $nodeValue = $this->containsDates ? localizeDateTimes($nodeValue) : $nodeValue;
                    $nodeValue = $this->containsIds ? addLinksToUiForIds($nodeValue) : $nodeValue;
                }

                print "<li>$nodeKey<span style='font-weight:bold;'>$nodeValue</span></li>\n";
            }
        }
    }

}

?>
