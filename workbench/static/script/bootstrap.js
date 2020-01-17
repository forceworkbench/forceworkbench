$(function() {

  // Boostrap Selectpicker
  $('select').each(function(i, element) {
    var select = $(element),
      options = {};
    if ( 5 < select.find('option').length ) {
      options['liveSearch'] = true;
    }
    if ( select.prop('multiple') ) {
      options['actionsBox'] = true;
    }
    select.selectpicker(options);

  }).on('change', function() {
    $('select').selectpicker('refresh');
  });

  $('input[type="submit"]').on('click', function(e) {
    $('select').selectpicker('refresh');
  })

});
