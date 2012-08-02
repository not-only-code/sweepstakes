// Sweepstakes 0.1 - Carlos Sanz Garcia <carlos.sanz@gmail.com> - http://github.com/not-only-code/sweepstakes

jQuery(document).ready(function($) {
	
	// Utils
	// _______________________________________________________
	
	function _debug(msg) {
		if(window.console) {
			console.debug(msg);
		}
	}
	
	var keys = keys || function (o) { var a = []; for (var k in o) a.push(k); return a; };
	
	var slug = function (string) {
	//  var accents = "àáäâèéëêìíïîòóöôùúüûñç";
	  var accents = "\u00e0\u00e1\u00e4\u00e2\u00e8"
	    + "\u00e9\u00eb\u00ea\u00ec\u00ed\u00ef"
	    + "\u00ee\u00f2\u00f3\u00f6\u00f4\u00f9"
	    + "\u00fa\u00fc\u00fb\u00f1\u00e7";
 
	  var without = "aaaaeeeeiiiioooouuuunc";
 
	  var map = {'@': ' at ', '\u20ac': ' euro ', 
	    '$': ' dollar ', '\u00a5': ' yen ',
	    '\u0026': ' and ', '\u00e6': 'ae', '\u0153': 'oe'};
 
	  return string
	    // Handle uppercase characters
	    .toLowerCase()
 
	    // Handle accentuated characters
	    .replace(
	      new RegExp('[' + accents + ']', 'g'),
	      function (c) { return without.charAt(accents.indexOf(c)); })
 
	    // Handle special characters
	    .replace(
	      new RegExp('[' + keys(map).join('') + ']', 'g'),
	      function (c) { return map[c]; })
 
	    // Dash special characters
	    .replace(/[^a-z0-9]/g, '-')
 
	    // Compress multiple dash
	    .replace(/-+/g, '-')
 
	    // Trim dashes
	    .replace(/^-|-$/g, '');
	};

	// Code: selector
	// _______________________________________________________
	$('#promo-code-enabled').bind('change', function(_event) {
		var $promo_input = $('#promo-form-list').find('input[value="Promo Code"]');
		if ($(this).is(':checked')) {
			$('#promo-code-table').show();
			if ($promo_input.length == 0)
				$('#promo-form-list').append($("<li style='cursor: move'>&nbsp;&nbsp;&nbsp;Promo Code<input type='hidden' name='promo-form-list[promo_code]' value='Promo Code' /></li>"));
		} else {
			$('#promo-code-table').hide();
			if ($promo_input.length > 0)
				$promo_input.parent().remove();
		}
	});
	
	// Form: sortables
	// _______________________________________________________
	
	// add list item
	$('#promo-form-add-field').bind('click', {}, function(_event){
		var _selectable = $('#promo-form-fields');
		var _container = $('#promo-form-list');
		if (_selectable.val() != 0) {
			var $curr_option = _selectable.find('option:selected');
			if (!$curr_option.prop('disabled')) {
				var $item = "<li style='cursor: move'><span><a href='#' class='ntdelbutton sw-remove-item' style='top:-3px;'>X</a></span>&nbsp;&nbsp;"+$curr_option.text()+"<input type='hidden' name='promo-form-list["+_selectable.val()+"]' value='"+$curr_option.text()+"' /></li>";
				_container.append($($item));
				$curr_option.prop('disabled', true);
			}
		}
		_event.preventDefault();
		return false;
	});
	// add list item
	$('#promo-form-create-field').bind('click', {}, function(_event){
		var _input = $('#promo-form-new-field');
		var _container = $('#promo-form-list');
		if ( _input.val() != '' && _input.val() != -1 && _input.val().length > 2 && _container.find('input[value="'+_input.val()+'"]').length == 0 ) {
			var $item = "<li style='cursor: move'><span><a href='#' class='ntdelbutton sw-remove-item' style='top:-3px;'>X</a></span>&nbsp;&nbsp;"+_input.val()+"<input type='hidden' name='promo-form-list["+slug(_input.val())+"]' value='"+_input.val()+"' /></li>";
			_container.append($($item));
		}
		_event.preventDefault();
		return false;
	});
	
	$('.sw-remove-item').live('click', {}, function(_event){
		var _parent = $(this).parent().parent();
		var _val = _parent.find('input').val();
		//---
		$('#promo-form-fields').find("option:contains('"+_val+"')").prop('disabled', false);
		//---
		_parent.remove();
		//---
		_event.preventDefault();
		return false;
	});
	
	// Participants: winner
	// _______________________________________________________
	
	var select_winner = $('#promo-select-winner'),
		delete_winner = $('#promo-delete-winners'),
		user_list = $('#promo-user-list'),
		winner_num = $('#promo-winner-num').val(),
		active_buttons = function(active) {
			var disabled = (active) ? false : true;
			var a = (active) ? 1 : 0.5 ;
			select_winner.prop('disabled', disabled).fadeTo(0, a);
			delete_winner.prop('disabled', disabled).fadeTo(0, a);
		},
		promo_winner_response = function(response) {
			if (response.status) $('#promo-winner-result').html(response.content);
			if (response.status) $('#promo-winner-result').find(' > *').hide().fadeIn('slow');
			active_buttons(true);
		},
		promo_process_winner = function(_event) {
			_event.preventDefault();
			
			if ($(this).prop('disabled')) {
				_debug('is disabled');
				return;
			}
			
			var _action = false;
			
			switch (_event.target.id) {
				case 'promo-select-winner':
				_action = 'set_promo_winner';
					break;
				case 'promo-delete-winners':
				_action = 'del_promo_winner';
					break;
			}
			if (!_action) return;
			
			_debug(_action);
			
			var package = {
					'action' : _action,
					'nonce' : $('#promo-winner-nonce').val(),
					'post_id' : $('#post_ID').val()
				};
			$.post(ajaxurl, package, promo_winner_response);
			
			active_buttons();
		};

	select_winner.bind('click', promo_process_winner);
	delete_winner.bind('click', promo_process_winner);
	$('.form-list-container').sortable({ placeholder: 'ui-state-highlight'});
});