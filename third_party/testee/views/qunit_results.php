<!--[if LTE IE 9]>
	<script type='text/javascript'>
		var extraHeight = 20;
	</script>
<![endif]-->
<!--[if (gt IE 9)|!(IE)]><!-->
	<script type='text/javascript'>
		var extraHeight = 0;
	</script>
<!--<![endif]-->
	<script type='text/javascript'>
		// -------------------------------------
		//	This seems archaic but the problem
		//	here is stupid ie8. It doesn't fire
		//	load events for iframes unless
		//	its on the element itself.
		//	The second problem is that its
		//	scrollheight is not counted in its
		//	overallheight for whatever reason
		//	so you get the madness below. But it
		//	works.
		// -------------------------------------

		function frameLoad (that)
		{
			var counter = 0;
			var timer;
			var thing = that;

			timer = setInterval(function(){
				//lets let this run 10 times
				//once each second. After that,
				//if the iframe body is clicked
				//it will run the doHeight function
				if (counter++ >= 10)
				{
					clearInterval(timer);
				}
				doHeight(thing);
			}, 1000);
		}

		function doHeight(thing)
		{
			var $that		= $(typeof thing === 'string' ? '#' + thing : thing);
			var contents	= $that.contents();
			var htmlHeight	= contents.find('html').outerHeight();
			var body		= contents.find('body');

			if (body.length == 0)
			{
				setTimeout(function(){
					doHeight(thing);
				},0);
				return;
			}

			//add id to body for listener
			body.attr('id', $that.attr('id'));

			var bodyHeight	= body.outerHeight();
			var bodyScrollh	= body.get(0).scrollHeight;
			//get tallest
			var height		= (htmlHeight > bodyHeight ? htmlHeight : bodyHeight);
			//is scrollheight taller still?
			var height		= (height > bodyScrollh ? height : bodyScrollh);
			$that.height(htmlHeight + extraHeight);
		}

		jQuery(function($){
			$('#retest_submit').click(function(e){
				e.preventDefault();

				$('iframe').each(function(){
					//reload hack because ie sucks
					$(this).attr('src',function(i, val){return val;});
				});

				return false;
			}).css('cursor', 'pointer');
		});
	</script>
<!-- Test results -->
<div id="qunit_tests">
<?php
	$count = 0;
	foreach ($tests as $addon_name => $test_array):?>
	<h2 class="qunit_addon_header"><?=ucfirst(str_replace('_', ' ', $addon_name))?></h2>
	<?php foreach ($test_array as $test_location):?>
	<iframe
		id="<?=$addon_name?>_<?=($count++)?>"
		style="border:none;width:100%;height:100px;"
		src="<?=$qunit_results_url?>&amp;addon=<?=$addon_name?>&amp;file=<?=urlencode($test_location)?>"
		onload="frameLoad(this);"
		></iframe>
	<?php endforeach;?>
<?php endforeach;?>
</div>

<div class="submit_wrapper">
	<button class="submit" id="retest_submit"><?=lang('retest')?></button>
	&nbsp;<?=lang('or')?>&nbsp;
	<a href="<?=$tests_index_url?>" title="<?=lang('start_over')?>"><?=lang('start_over')?></a>
</div>

