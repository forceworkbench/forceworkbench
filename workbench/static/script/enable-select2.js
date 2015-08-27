console.log('Enable select2 loaded')
jQuery(document).ready(function( $ ) {
	$("select:not(.select2-hidden-accessible)").select2();
});