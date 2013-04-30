var WorkbenchLogin = new function() {

    var form = document.getElementById('login_form');

    this.initializeForm = function(loginType) {
        var loginTypeElem = form['loginType_' + loginType];
        if (loginTypeElem === undefined) {
            alert("Unknown login type '" + loginType + "'. Check configuration!");
            return;
        }
        switchLoginTypeTo(loginTypeElem);

        if (wbLoginConfig.customServerUrl === "") {
            buildServerUrl();
        } else {
            form.serverUrl.value = wbLoginConfig.customServerUrl;
        }

        // add listeners to the loginType radio buttons
        var loginTypes = document.getElementsByName("loginType");
        for (var i = 0; i < loginTypes.length; i++) {
            bindEvent(loginTypes[i], "click", setFormForLoginType);
        }

        bindEvent(form.un, "keyup", toggleUsernamePasswordSessionDisabled);
        bindEvent(form.pw, "keyup", toggleUsernamePasswordSessionDisabled);
        bindEvent(form.sid, "keyup", toggleUsernamePasswordSessionDisabled);
        bindEvent(form.sid, "keyup", fuzzyServerUrlSelect);
        
        bindEvent(form.un, "change", toggleUsernamePasswordSessionDisabled);
        bindEvent(form.pw, "change", toggleUsernamePasswordSessionDisabled);
        bindEvent(form.sid, "change", toggleUsernamePasswordSessionDisabled);
        bindEvent(form.sid, "change", fuzzyServerUrlSelect);
                
        bindEvent(form.pw, "keypress", checkCaps); // must be keypress, not keyup

        bindEvent(form.inst, "keyup", buildServerUrl);
        bindEvent(form.api, "keyup", buildServerUrl);

        bindEvent(form.inst, "change", buildServerUrl);
        bindEvent(form.api, "change", buildServerUrl);
    };

    function setFormForLoginType() {
        var loginTypes = document.getElementsByName("loginType");
        for (var i = 0; i < loginTypes.length; i++) {
            if (loginTypes[i].checked) {
                switchLoginTypeTo(loginTypes[i]);
                break;
            }
        }
    }

    function switchLoginTypeTo(typeElem) {
        typeElem.checked = true;

        var divs = document.getElementsByTagName("div");
        for (var i = 0; i < divs.length; i++) {
            if (divs[i].className.indexOf("loginType") >= 0) {
                if (divs[i].className.indexOf(typeElem.id) >= 0) {
                    divs[i].style.display = "block";
                } else {
                    divs[i].style.display = "none";
                }
            }
        }

        switch (typeElem.id) {
            case "loginType_oauth":
                form.loginBtn.value = "Login with Salesforce";
                break;
            default:
                form.loginBtn.value = "Login";
                setFocus();
                break;
        }
    }

    function setFocus() {
        try {
            if (form.un.value == null || form.un.value == "") {
                form.un.focus();
            } else {
                form.pw.focus();
            }
        } catch (e) {
            // you suck IE
        }
    }

    function checkCaps(event) {
        var key = 0;
        var shifted = false;

        // IE
        if ( document.all ) {
            key = event.keyCode;
        // Everything else
        } else {
            key = event.which;
        }

        shifted = event.shiftKey;

        var pwcaps = document.getElementById('pwcaps');

        var upper = (key >= 65 && key <= 90);
        var lower = (key >= 97 && key <= 122);

        if ( (upper && !shifted) || (lower && shifted) ) {
            pwcaps.style.visibility='visible';
        } else if ( (lower && !shifted) || (upper && shifted) ) {
            pwcaps.style.visibility='hidden';
        }
    }

    function toggleUsernamePasswordSessionDisabled() {
        if (form.sid.value) {
            form.un.disabled = true;
            form.pw.disabled = true;
        } else {
            form.un.disabled = false;
            form.pw.disabled = false;
        }

        if (form.un.value || form.pw.value) {
            form.sid.disabled = true;
        } else {
            form.sid.disabled = false;
        }
    }

    function fuzzyServerUrlSelect() {
        var sid = form.sid.value
        var sidIndex = sid.indexOf('00D');

        if (sidIndex > -1) {
            var serverId = sid.substring(sidIndex + 3, sidIndex + 5);
            var inst = wbLoginConfig.serverIdMap[serverId];
            if (inst != null) {
                form.inst.value = inst;
                buildServerUrl();
            }
        }
    }


    function buildServerUrl() {
        form.serverUrl.value = 'http' +
                                (wbLoginConfig.useHTTPS && (form.inst.value.search(/localhost/i) == -1) ? 's' : '') +
                                '://' +
                                form.inst.value +
                                '.salesforce.com/services/Soap/u/' +
                                form.api.value;
    }

    function bindEvent(el, eventName, eventHandler) {
        if (el.addEventListener){
            el.addEventListener(eventName, eventHandler, false);
        } else if (el.attachEvent){
            el.attachEvent("on"+eventName, eventHandler);
        }
    }
};
