function doesSearchHaveName() {
    var saveSr = document.getElementById('saveSr');
    if (saveSr.value == null || saveSr.value.length == 0) {
        alert('Search must have a name to save.');
        return false;
    }

    return true;
}

function toggleFieldDisabled() {

    if (document.getElementById('SB_searchString').value) {
        document.getElementById('SB_limit').disabled = false;
        document.getElementById('SB_fieldTypeSelect').disabled = false;
        document.getElementById('SB_objSelect_0').disabled = false;
        if (document.getElementById('SB_objSelect_0').value) {
            document.getElementById('SB_objDetail_0').disabled = false;
        } else {
            document.getElementById('SB_objDetail_0').disabled = true;
        }
    } else {
        document.getElementById('SB_limit').disabled = true;
        document.getElementById('SB_fieldTypeSelect').disabled = true;
        document.getElementById('SB_objSelect_0').disabled = true;
        document.getElementById('SB_objDetail_0').disabled = true;
    }

    var allPreviousRowsUsed = true;
    for (var ro = 1; ro < document.getElementById('numReturningObjects').value; ro++) {
        var this_SB_objSelect = document.getElementById('SB_objSelect_' + ro);
        var this_SB_objDetail = document.getElementById('SB_objDetail_' + ro);

        var last_SB_objSelect = document.getElementById('SB_objSelect_' + (ro - 1));
        var last_SB_objDetail = document.getElementById('SB_objDetail_' + (ro - 1));

        if (allPreviousRowsUsed && last_SB_objSelect.value && last_SB_objDetail.value) {
            this_SB_objSelect.disabled = false;
            this_SB_objDetail.disabled = false;
            if (this_SB_objSelect.value) {
                this_SB_objDetail.disabled = false;
            } else {
                this_SB_objDetail.disabled = true;
            }
        } else {
            this_SB_objSelect.disabled = true;
            this_SB_objDetail.disabled = true;
            allPreviousRowsUsed = false;
        }
    }
}

function buildSearch() {
    toggleFieldDisabled();

    var searchString = 'FIND {' + document.getElementById('SB_searchString').value + '}';

    var fieldTypeSelect = '';
    if (document.getElementById('SB_fieldTypeSelect').value && !document.getElementById('SB_fieldTypeSelect').disabled) {
        fieldTypeSelect = ' IN ' + document.getElementById('SB_fieldTypeSelect').value;
    }

    var roString = '';
    for (var ro = 0; ro < document.getElementById('numReturningObjects').value; ro++) {
        var SB_objSelect = document.getElementById('SB_objSelect_' + ro);
        var SB_objDetail = document.getElementById('SB_objDetail_' + ro);

        if (SB_objSelect.value && !SB_objSelect.disabled) {
            roString += ro == 0 ? ' RETURNING ' : ', ';

            roString += SB_objSelect.value;

            if (SB_objDetail.value && !SB_objDetail.disabled) {
                roString += '(' + SB_objDetail.value + ')';
            }
        }
    }

    var limit = '';
    if (document.getElementById('SB_limit').value && !document.getElementById('SB_limit').disabled) {
        limit = ' LIMIT ' + document.getElementById('SB_limit').value;
    }


    if (searchString) {
        document.getElementById('sosl_search_textarea').value = searchString + fieldTypeSelect + roString + limit;
    }
}

function addReturningObjectRow(rowNum, defaultObject, defaultFields) {
    //build the row inner html
    var row = "";

    row +=     "<select id='SB_objSelect_" + rowNum + "' name='SB_objSelect_" + rowNum + "' style='width: 20em;' onChange='buildSearch();' onkeyup='buildSearch();'>" +
        "<option value=''></option>";

    for (var obj in searchable_objects) {
        row += "<option value='" + obj + "'";
        if (defaultObject == obj) row += " selected='selected' ";
        row += "'>" + obj + "</option>";
    }

    defaultFields = defaultFields != null ? defaultFields : "";
    row +=  "</select>&nbsp;" +
        "<input type='text' id='SB_objDetail_" + rowNum + "' size='51' name='SB_objDetail_" + rowNum + "' value='" + defaultFields + "' onkeyup='buildSearch();' />";


    //add to the DOM
    var leadingTxtCell = document.createElement('td');
    leadingTxtCell.setAttribute('nowrap','true');
    leadingTxtCell.innerHTML = rowNum == 0 ? "returning object:" : "and object:" ;

    var bodyCell = document.createElement('td');
    bodyCell.setAttribute('nowrap','true');
    bodyCell.innerHTML = row;

    var newPlusCell = document.createElement('td');
    newPlusCell.setAttribute('id','add_row_plus_cell_' + rowNum);
    newPlusCell.setAttribute('vAlign','bottom');
    newPlusCell.innerHTML = "<img id='row_plus_button' src='" + getPathToStaticResource('/images/plus_icon.jpg') + "' onclick='addReturningObjectRow(document.getElementById(\"numReturningObjects\").value++);toggleFieldDisabled();' onmouseover='this.style.cursor=\"pointer\";'  style='padding-top: 4px;'/>";

    var newRow = document.createElement('tr');
    newRow.setAttribute('id','returning_objects_row_' + rowNum);
    newRow.appendChild(leadingTxtCell);
    newRow.appendChild(bodyCell);
    newRow.appendChild(newPlusCell);

    var lastRow = document.getElementById('sosl_search_textarea_row');
    lastRow.parentNode.insertBefore(newRow, lastRow);

    if (rowNum > 0) {
        var row_plus_button = document.getElementById('row_plus_button');
        row_plus_button.parentNode.removeChild(row_plus_button);
    }
}