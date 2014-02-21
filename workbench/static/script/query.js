function parentChildRelationshipQueryBlocker() {
    var soql = document.getElementById('soql_query_textarea').value.toUpperCase();

    if (soql.indexOf('(SELECT') != -1 && soql.indexOf('IN (SELECT') == -1 && document.getElementById('export_action_csv').checked) {
        return confirm ("Export of parent-to-child relationship queries to CSV are not yet supported by Workbench and may give unexpected results. Are you sure you wish to continue?");
    }

}

function doesQueryHaveName() {
    var saveQr = document.getElementById('saveQr');
    if (saveQr.value == null || saveQr.value.length == 0) {
        alert('Query must have a name to save.');
        return false;
    }

    return true;
}


function toggleFieldDisabled() {
    var QB_field_sel = document.getElementById('QB_field_sel');

    if (document.getElementById('QB_object_sel').value) {
        QB_field_sel.disabled = false;
    } else {
        QB_field_sel.disabled = true;
    }


    var isFieldSelected = false;
    for (var i = 0; i < QB_field_sel.options.length; i++)
        if (QB_field_sel.options[i].selected)
            isFieldSelected = true;

    if (isFieldSelected || (document.getElementById('matrix_rows').value != '' && document.getElementById('matrix_cols').value != '')) {
        document.getElementById('QB_orderby_field').disabled = false;
        document.getElementById('QB_orderby_sort').disabled = false;
        document.getElementById('QB_nulls').disabled = false;
        document.getElementById('QB_limit_txt').disabled = false;

        document.getElementById('QB_filter_field_0').disabled = false;
        if (document.getElementById('QB_filter_field_0').value) {
            document.getElementById('QB_filter_value_0').disabled = false;
            document.getElementById('QB_filter_compOper_0').disabled = false;
        } else {
            document.getElementById('QB_filter_value_0').disabled = true;
            document.getElementById('QB_filter_compOper_0').disabled = true;
        }
    } else {
        document.getElementById('QB_filter_field_0').disabled = true;
        document.getElementById('QB_filter_compOper_0').disabled = true;
        document.getElementById('QB_filter_value_0').disabled = true;
        document.getElementById('QB_orderby_field').disabled = true;
        document.getElementById('QB_orderby_sort').disabled = true;
        document.getElementById('QB_nulls').disabled = true;
        document.getElementById('QB_limit_txt').disabled = true;
    }

    var allPreviousRowsUsed = true;
    for (var r = 1; r < document.getElementById('numFilters').value; r++) {
        var lastRow = r-1;
        var thisRow = r;

        if (isFieldSelected && allPreviousRowsUsed && document.getElementById('QB_filter_field_' + lastRow).value && document.getElementById('QB_filter_compOper_' + lastRow).value && document.getElementById('QB_filter_value_' + lastRow).value) {
            document.getElementById('QB_filter_field_' + thisRow).disabled = false;
            if (document.getElementById('QB_filter_field_' + thisRow).value) {
                document.getElementById('QB_filter_value_' + thisRow).disabled = false;
                document.getElementById('QB_filter_compOper_' + thisRow).disabled = false;
            } else {
                document.getElementById('QB_filter_value_' + thisRow).disabled = true;
                document.getElementById('QB_filter_compOper_' + thisRow).disabled = true;
            }
        } else {
            allPreviousRowsUsed = false;
            document.getElementById('QB_filter_field_' + thisRow).disabled = true;
            document.getElementById('QB_filter_compOper_' + thisRow).disabled = true;
            document.getElementById('QB_filter_value_' + thisRow).disabled = true;
        }
    }
}

function updateObject() {
    document.query_form.justUpdate.value = 1;
    document.query_form.submit();
}

function exportActionIs(type) {
    var exportActions = document.getElementById('query_form')['export_action'];
    for (var i = 0; i < exportActions.length; i++) {
        if (exportActions[i].checked && exportActions[i].value == type) {
            return true;
        }
    }
    return false;
}

function arrayContains(haystack, needle) {
    for (i in haystack) {
        if (haystack[i] == needle) {
            return true;
        }
    }

    return false;
}

function buildQuery() {
    toggleFieldDisabled();
    var QB_object_sel = document.getElementById('QB_object_sel').value;
    var QB_field_sel = document.getElementById('QB_field_sel');
    QB_fields_selected = new Array();
    for (var i = 0; i < QB_field_sel.options.length; i++) {
        if (QB_field_sel.options[i].selected) {
            QB_fields_selected.push(QB_field_sel.options[i].value);
        }
    }

    if (exportActionIs('matrix')) {
        var matrix_cols = document.getElementById('matrix_cols');
        var matrix_rows = document.getElementById('matrix_rows');

        if (matrix_cols.value != '' && matrix_rows.value != '') {
            if (!arrayContains(QB_fields_selected, matrix_cols.value)) QB_fields_selected.push(matrix_cols.value);
            if (!arrayContains(QB_fields_selected, matrix_rows.value)) QB_fields_selected.push(matrix_rows.value);
        }
    }

    var soql_select = '';
    if (QB_fields_selected.toString().indexOf('count()') != -1 && QB_fields_selected.length > 1) {
        alert('Warning: Choosing count() with other fields will result in a malformed query. Unselect either count() or the other fields to continue.');
    } else    if (QB_fields_selected.length > 0) {
        var soql_select = 'SELECT ' + QB_fields_selected + ' FROM ' + QB_object_sel;
    }

    soql_where = '';
    for (var f = 0; f < document.getElementById('numFilters').value; f++) {

        var QB_filter_field = document.getElementById('QB_filter_field_' + f).value;
        var QB_filter_compOper = document.getElementById('QB_filter_compOper_' + f).value;
        var QB_filter_value = document.getElementById('QB_filter_value_' + f).value;

        var soql_where_logicOper = '';
        if (f > 0) {
            soql_where_logicOper = ' AND ';
        }

        if (QB_filter_field && QB_filter_compOper && QB_filter_value) {
            if (QB_filter_compOper == 'starts') {
                QB_filter_compOper = 'LIKE'
                QB_filter_value = QB_filter_value + '%';
            } else if (QB_filter_compOper == 'ends') {
                QB_filter_compOper = 'LIKE'
                QB_filter_value = '%' + QB_filter_value;
            } else if (QB_filter_compOper == 'contains') {
                QB_filter_compOper = 'LIKE'
                QB_filter_value = '%' + QB_filter_value + '%';
            }


            if (QB_filter_compOper == 'IN' ||
                QB_filter_compOper == 'NOT IN' ||
                QB_filter_compOper == 'INCLUDES' ||
                QB_filter_compOper == 'EXCLUDES') {
                QB_filter_value_q = '(' + QB_filter_value + ')';
            } else if ((QB_filter_value == 'null') ||
                (field_type_array[QB_filter_field] == "datetime") ||
                (field_type_array[QB_filter_field] == "date") ||
                (field_type_array[QB_filter_field] == "currency") ||
                (field_type_array[QB_filter_field] == "percent") ||
                (field_type_array[QB_filter_field] == "double") ||
                (field_type_array[QB_filter_field] == "int") ||
                (field_type_array[QB_filter_field] == "boolean")) {
                QB_filter_value_q = QB_filter_value;
            } else {
                QB_filter_value_q = '\'' + QB_filter_value + '\'';
            }

            soql_where += soql_where_logicOper + QB_filter_field + ' ' + QB_filter_compOper + ' ' + QB_filter_value_q;
        } else {
            break;
        }
    }
    soql_where = soql_where != '' ? ' WHERE ' + soql_where : '';

    var QB_orderby_field = document.getElementById('QB_orderby_field').value;
    var QB_orderby_sort = document.getElementById('QB_orderby_sort').value;
    var QB_nulls = document.getElementById('QB_nulls').value;
    if (QB_orderby_field) {
        var soql_orderby = ' ORDER BY ' + QB_orderby_field + ' ' + QB_orderby_sort;
        if (QB_nulls)
            soql_orderby = soql_orderby + ' NULLS ' + QB_nulls;
    } else
        var soql_orderby = '';


    var QB_limit_txt = document.getElementById('QB_limit_txt').value;
    if (QB_limit_txt)
        var soql_limit = ' LIMIT ' + QB_limit_txt;
    else
        var soql_limit = '';

    if (soql_select)
        document.getElementById('soql_query_textarea').value = soql_select + soql_where + soql_orderby + soql_limit ;

}


function addFilterRow(filterRowNum, defaultField, defaultCompOper, defaultValue) {
    //build the row inner html
    var row = filterRowNum == 0 ? "<br/>Filter results by:<br/>" : "" ;
    row +=     "<select id='QB_filter_field_" + filterRowNum + "' name='QB_filter_field_" + filterRowNum + "' style='width: 16em;' onChange='buildQuery();' onkeyup='buildQuery();'>" +
        "<option value=''></option>";

    for (var field in field_type_array) {
        row += "<option value='" + field + "'";
        if (defaultField == field) row += " selected='selected' ";
        row += "'>" + field + "</option>";
    }

    row += "</select>&nbsp;" +
        "" +
        "<select id='QB_filter_compOper_" + filterRowNum + "' name='QB_filter_compOper_" + filterRowNum + "' style='width: 6em;' onChange='buildQuery();' onkeyup='buildQuery();'>";

    for (var opKey in compOper_array) {
        row += "<option value='" + opKey + "'";
        if (defaultCompOper == opKey) row += " selected='selected' ";
        row += ">" + compOper_array[opKey] + "</option>";
    }

    defaultValue = defaultValue != null ? defaultValue : "";
    row +=  "</select>&nbsp;" +
        "<input type='text' id='QB_filter_value_" + filterRowNum + "' size='31' name='QB_filter_value_" + filterRowNum + "' value='" + defaultValue + "' onkeyup='buildQuery();' />";


    //add to the DOM
    var newFilterCell = document.createElement('td');
    newFilterCell.setAttribute('colSpan','4');
    newFilterCell.setAttribute('vAlign','top');
    newFilterCell.setAttribute('nowrap','true');
    newFilterCell.innerHTML = row;

    var newPlusCell = document.createElement('td');
    newPlusCell.setAttribute('id','filter_plus_cell_' + filterRowNum);
    newPlusCell.setAttribute('vAlign','bottom');
    newPlusCell.innerHTML = "<img id='filter_plus_button' src='" + getPathToStaticResource('/images/plus_icon.jpg') + "' onclick='addFilterRow(document.getElementById(\"numFilters\").value++);toggleFieldDisabled();' onmouseover='this.style.cursor=\"pointer\";'  style='padding-top: 4px;'/>";

    var newFilterRow = document.createElement('tr');
    newFilterRow.setAttribute('id','filter_row_' + filterRowNum);
    newFilterRow.appendChild(newFilterCell);
    newFilterRow.appendChild(newPlusCell);

    document.getElementById('QB_right_sub_table').getElementsByTagName("TBODY").item(0).appendChild(newFilterRow);

    if (filterRowNum > 0) {
        var filter_plus_button = document.getElementById('filter_plus_button');
        filter_plus_button.parentNode.removeChild(filter_plus_button);
    }

    //expand the field list so it looks right
    document.getElementById('QB_field_sel').size += 2;
}

function toggleMatrixSortSelectors(hasChanged) {
    if (exportActionIs('matrix')) {
        document.getElementById('matrix_selection_headers').style.display = '';
        document.getElementById('matrix_selection_row').style.display = '';
        document.getElementById('QB_field_sel').size += 4;

        if(hasChanged) buildQuery();
    } else if (document.getElementById('matrix_selection_headers').style.display == '') {
        document.getElementById('matrix_selection_headers').style.display = 'none';
        document.getElementById('matrix_selection_row').style.display = 'none';
        document.getElementById('QB_field_sel').size -= 4;

        if(hasChanged) buildQuery();
    }

    //don't do anything if moving from screen to csv
}