// Sweepstakes 0.1 - Carlos Sanz Garcia <carlos.sanz@gmail.com> - http://github.com/not-only-code/sweepstakes

jQuery(document).ready(function($) {
	
	// Utils
	// _______________________________________________________
	
	function _debug(msg) {
		if(window.console) {
			console.debug(msg);
		}
	}

	// Form: terms
	// _______________________________________________________
	
	/*
	$('#promo-code-enabled').bind('change', function(_event) {
		var $promo_input = $('#promo-form-list').find('input[value="Promo Code"]');
		_debug($promo_input);
		if ($(this).is(':checked')) {
			$('#promo-code-table').show();
			if ($promo_input.length == 0)
				$('#promo-form-list').append($("<li style='cursor: move'>&nbsp;&nbsp;&nbsp;Promo Code<input type='hidden' name='promo-form-list[promo_code]' value='Promo Code' /></li>"));
		} else {
			$('#promo-code-table').hide();
			if ($promo_input.length > 0)
				$promo_input.parent().remove();
		}
	});*/
	
});