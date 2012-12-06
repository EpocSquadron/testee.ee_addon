/**
 * Test-driven add-on development module.
 *
 * @author		Stephen Lewis <stephen@experienceinternet.co.uk>
 * @copyright	Experience Internet
 * @package		Testee
 */

(function($) {

	$(document).ready(function() {

		/* --------------------------------------
		 * Tests index.
		 * ----------------------------------- */

		$('.addons_index > li').removeClass('expanded').addClass('collapsed');

		$('.addon_title a').click(function(e) {
			$(this).closest('.addon_title').next('.addon_tests').slideToggle();
			$(this).closest('li').toggleClass('collapsed expanded');

			e.preventDefault();
		});

		$('.addon_title :checkbox').click(function() {
			$(this).attr('checked')
				? $(this).closest('.addon_title').next('.addon_tests').find(':checkbox').attr('checked', 'checked')
				: $(this).closest('.addon_title').next('.addon_tests').find(':checkbox').removeAttr('checked')
		});


		/* --------------------------------------
		 * Test results.
		 * ----------------------------------- */

		$('#retest_submit').focus();
	});

	// -------------------------------------
	//	autoDupeLastInput (private)
	// -------------------------------------

	//this checks any of the inputs on keyup, and if its the last
	//available one, it auto adds a new field below it and exposes the
	//delete button for the current one
	//this is mostly used for field_settings, but better to not load it
	//many times
	function autoDupeLastInput ($parentHolder, input_class, parent_class)
	{
		var timer = 0;

		$parentHolder.find('button.remove:last').hide();

		var doOn = function()
		{
			//this keyword not avail inside functions
			var that = this;

			clearTimeout(timer);

			timer = setTimeout(function(){
				var $that	= $(that),
					$parent	= $that.closest('.' + input_class);

				//if the last item is not empty
				//and it is indeed the last item, lets dupe a new one
				if ($.trim($that.val()) !== '' &&
					$parent.is($('.' + input_class + ':last', $parentHolder)))
				{
					//clone BEEP BOOP BORP KILL ALL HUMANS
					var $newHolder = $parent.clone();

					//empties the inputs and
					//increments names like list_value_holder_input[10] to
					// list_value_holder_input[11]
					$newHolder.find('input, select').each(function(i, item){
						var $input = $(this);

						if ($input.is('[type="text"]'))
						{
							$input.val('');
						}
						else
						{
							//remove attr doesn't work for checked and selected
							//in IE
							if ($input.attr('selected'))
							{
								$input.attr('selected', false);
							}

							if ($input.attr('checked'))
							{
								$input.attr('checked', false);
							}
						}

						var match = /([a-zA-Z\_\-]+)_([0-9]+)/ig.exec(
							$(this).attr('name')
						);

						if (match)
						{
							$(this).attr('name',
								match[1] + '_' +
									(parseInt(match[2], 10) + 1)
							);
						}
					});

					//add to parent
					$parent.parent().append($newHolder);

					//show delete button for current
					$parent.find('button.remove').show();
				}
			}, 250);
			//end setTimeout
		};

		$parentHolder.delegate('.' + input_class + ' input', 'keyup', doOn);
		$parentHolder.delegate('.' + input_class + ' select', 'change', doOn);
		//end delegate
	}
	//end autoDupeLastInput

	var $locations = $('#locations');

	if ($locations.length > 0)
	{
		autoDupeLastInput($locations, 'location_preference');

		$locations.delegate('.location_preference button', 'click', function(e){
			$(this).closest('.location_preference').remove();
			e.preventDefault();
			return false;
		});
	}


})(window.jQuery);

/* End of file		: cp.js */
/* File location	: themes/third_party/testee/js/cp.js */