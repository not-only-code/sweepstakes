// Site Promos plugin 0.1 - Carlos Sanz Garcia <carlos.sanz@gmail.com> - http://github.com/not-only-code/site-promos

jQuery(document).ready(function($) {
	
	// Debug function
	// _______________________________________________________
	
	function _debug(msg) {
		if(window.console) {
			console.debug(msg);
		}
	}

	// Code: selector
	// _______________________________________________________
	$('#promo-activate-code').bind('change', function(_event) {
		var display = ($(this).is(':checked')) ? 'block' : 'none';
		$('#promo-code-table').css('display', display);
	});
	
});