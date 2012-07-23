// Sweepstakes 0.1 - Carlos Sanz Garcia <carlos.sanz@gmail.com> - http://github.com/not-only-code/sweepstakes

jQuery(document).ready(function($) {
	
	// Utils
	// _______________________________________________________
	
	function _debug(msg) {
		if(window.console) {
			console.debug(msg);
		}
	}
	
	var promo_form = $('#promo-form, #promo-form-login'),
		promo_form_selector = $('#promo-form-selector'),
		promo_submit = $('#promo-form-submit'),
		promo_terms = $('.promo-form-terms'),
		send_promo_form = function(_event) {
			_event.preventDefault();
			promo_form.submit();
		},
		activate_promo_form = function(activate, force) {
			
			var time = (force) ? 0 : 'fast';
			var alpha = (activate) ? 1 : .5;
			var disabled = (activate) ? false : true;
		
			promo_submit.fadeTo(time, alpha);
			//promo_submit.prop('disabled', disabled);
			
			if (activate) {
				promo_submit.bind('click', send_promo_form);
			} else {
				promo_submit.unbind('click', send_promo_form);
			}
		},
		switch_form = function(_event) {
			_event.preventDefault();
			
			promo_form.hide();
			promo_form_selector.find('> a').removeClass('active');
			
			switch(_event.target.id) {
				case 'promo-form-selector-register':
					$('#promo-form').show();
				break;
				case 'promo-form-selector-login':
					$('#promo-form-login').show();
				break;
			}
			
			$(this).addClass('active');
		},
		hash_swith = function(hash) {
			if (!hash) return;
			
			promo_form.hide();
			
			switch(hash) {
				case '#register':
					$('#promo-form-selector-register').addClass('active');
					$('#promo-form').show();
					break;
				case '#login':
					$('#promo-form-selector-login').addClass('active');
					$('#promo-form-login').show();
					break;
			}
		}

	// Form: terms
	// _______________________________________________________
	/*
	if (promo_terms.length) {
		
		promo_submit.bind('click', function(_e){_e.preventDefault()});
		activate_promo_form(false, true);
	
		promo_terms.bind('change', function(_e) {
			activate_promo_form($(this).is(':checked'));
		});
		
	} else {
		
		activate_promo_form(true, true);
	}
	*/
	
	// Form: switcher
	// _______________________________________________________
	
	promo_form_selector.find(' > a').bind('click', switch_form);
	hash_swith(window.location.hash);
	
});