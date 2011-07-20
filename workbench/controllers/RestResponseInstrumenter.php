<?php
class RestResponseInstrumenter {
    
    private $insturmentations = array();
    
    public function __construct($baseUrl) {        
        $restBasePattern = '/services/data/v\d+\.\d';

        $this->insturmentations[] = new XssProtectionInsturmentation();

        // add link url to id services
        $this->insturmentations[] = new RegexInsturmentation("https?://.*(/id/00D.*/005.*)",
                                                 		     "<a class=\'RestLinkable\' href=" . $baseUrl . "?url=$1&autoExec=1>$1</a>");

        // add link url to any rest url
        $this->insturmentations[] = new RegexInsturmentation("(" . $restBasePattern . ".*)",
                                                 		     "<a class=\'RestLinkable\' href=" . $baseUrl . "?url=$1&autoExec=0>$1</a>");

        // add autoExec to everything but query and search
        $this->insturmentations[] = new RegexInsturmentation('(url=' . $restBasePattern . '(?!/query|/search|.*/.*\{ID\}).*&)autoExec=0',
                                                             '$1autoExec=1');

        // query more
        $this->insturmentations[] = new RegexInsturmentation('(url=' . $restBasePattern . '/query/01g\w{15}-\d+&)autoExec=0',
                                                             '$1autoExec=1');

        // sample query url
        $this->insturmentations[] = new RegexInsturmentation('(' . $restBasePattern . '/query)<',
                                                             '$1</a>&nbsp;<a class=\'miniLink RestLinkable\' href=' . $baseUrl . '?url=$1?q=SELECT%2Bid,name,profile.name%2BFROM%2Buser%2BWHERE%2Busername=\'' . WorkbenchContext::get()->getUserInfo()->userName . '\'&autoExec=1>[SAMPLE]<');

        // sample search url
        $this->insturmentations[] = new RegexInsturmentation('(' . $restBasePattern . '/search)<',
                                                             '$1</a>&nbsp;<a class=\'miniLink RestLinkable\' href=' . $baseUrl . '?url=$1?q=FIND%2B%7B' . WorkbenchContext::get()->getUserInfo()->userName . '%7D%2BIN%2BALL%2BFIELDS&autoExec=1>[SAMPLE]<');

    }

    public function instrumentNode($s) {
        foreach ($this->insturmentations as $i) {
            $s = $i->instrument($s);
        }
        return $s;
    }

    public function instrumentJson($json) {
        return json_encode($this->instrumentJsonRecursively(json_decode($json)));
    }

    private function instrumentJsonRecursively($rawNodes) {
        $escapedNodes = array();

        if (is_object($rawNodes) || is_array($rawNodes)) {
            foreach ($rawNodes as $rawNodeKey => $rawNodeValue) {
                $escapedNodes[$rawNodeKey] = $this->instrumentJsonRecursively($rawNodeValue);
            }
        } else if (is_bool($rawNodes) || $rawNodes == null) {
            $escapedNodes = $rawNodes;
        } else {
            $escapedNodes = $this->instrumentNode($rawNodes);
        }

        return $escapedNodes;
    }
}

interface Insturmentation {
    /**
     * @abstract
     * @param String $s
     * @return String
     */
    function instrument($s);
}

class XssProtectionInsturmentation implements Insturmentation {
    function instrument($s) {
        return htmlspecialchars($s);
    }
}

class RegexInsturmentation implements Insturmentation {
    public $pattern;
    public $replacement; 
    
    function __construct($pattern, $replacement) {
        $this->pattern = $pattern;
        $this->replacement = $replacement;
    }

    function instrument($s) {
        return preg_replace('@' . $this->pattern . '@', $this->replacement, $s);
    }
}
?>