#! /bin/bash

find . -type f -name \*.js | xargs perl -pi -e 's/show_describeSObject_form/displayDescribeSObjectForm/g'

find . -type f -name \*.js | xargs perl -pi -e 's/show_describeSObject_result/displayDescribeSObjectResults/g'

find . -type f -name \*.js | xargs perl -pi -e 's/display_login/displayLogin/g'

find . -type f -name \*.js | xargs perl -pi -e 's/form_become_adv/toggleLoginFormToAdv/g'

find . -type f -name \*.js | xargs perl -pi -e 's/form_become_std/toggleLoginFormToStd/g'

find . -type f -name \*.js | xargs perl -pi -e 's/build_location/buildLocation/g'

find . -type f -name \*.js | xargs perl -pi -e 's/process_Login/processLogin/g'

find . -type f -name \*.js | xargs perl -pi -e 's/form_upload_objectSelect_show/displayUploadFileWithObjectSelectionForm/g'

find . -type f -name \*.js | xargs perl -pi -e 's/csv_upload_valid_check/validateCsvUpload/g'

find . -type f -name \*.js | xargs perl -pi -e 's/csv_file_to_array/convertCsvFileToArray/g'

find . -type f -name \*.js | xargs perl -pi -e 's/csv_array_show/displayCsvArray/g'

find . -type f -name \*.js | xargs perl -pi -e 's/field_mapping_set/setFieldMappings/g'

find . -type f -name \*.js | xargs perl -pi -e 's/field_map_to_array/convertFieldMapToArray/g'

find . -type f -name \*.js | xargs perl -pi -e 's/field_mapping_confirm/confirmFieldMappings/g'

find . -type f -name \*.js | xargs perl -pi -e 's/field_mapping_show/displayFieldMappings/g'

find . -type f -name \*.js | xargs perl -pi -e 's/show_putAndId_results/displayIdOnlyPutResults/g'

find . -type f -name \*.js | xargs perl -pi -e 's/show_query_form/displayQueryForm/g'

find . -type f -name \*.js | xargs perl -pi -e 's/build_query/buildQuery/g'

find . -type f -name \*.js | xargs perl -pi -e 's/show_query_result/displayQueryResults/g'

find . -type f -name \*.js | xargs perl -pi -e 's/export_query_csv/exportQueryAsCsv/g'

find . -type f -name \*.js | xargs perl -pi -e 's/show_search_form/displaySearchForm/g'

find . -type f -name \*.js | xargs perl -pi -e 's/build_search/buildSearch/g'

find . -type f -name \*.js | xargs perl -pi -e 's/show_search_result/displatSearchResult/g'

find . -type f -name \*.js | xargs perl -pi -e 's/show_select_form/displaySelechForm/g'

find . -type f -name \*.js | xargs perl -pi -e 's/show_error/displayError/g'

find . -type f -name \*.js | xargs perl -pi -e 's/show_warnings/displayWarning/g'

find . -type f -name \*.js | xargs perl -pi -e 's/show_info/displayInfo/g'

find . -type f -name \*.js | xargs perl -pi -e 's/arr_to_csv_line/convertArrayToCsvLine/g'

find . -type f -name \*.js | xargs perl -pi -e 's/arr_to_csv/convertArrayToCsv/g'

find . -type f -name \*.js | xargs perl -pi -e 's/xml_pretty_printer/prettyPrintXml/g'

