<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>QUnit: <?=$addon_name?> - <?=$test_name?></title>
		<link rel="stylesheet" href="<?=$testee_themes_url?>css/qunit.css">
		<!-- script src="<?=$testee_themes_url?>js/require.js"></script -->
		<script src="<?=$testee_themes_url?>js/curl.js"></script>
		<script type="text/javascript">
			(function(window){
				window.Testee = window.Testee || {};

				//just in case you want to make
				//absolutely sure you are here ;)
				Testee.QUnit			= true;
				Testee.QUnitAutostart	= true;

				//disable AMD autoloading unless user enables
				Testee.amdLoad			= false;

				//add a datetime string to file urls?
				Testee.cacheBust		= true;

				//urls
				Testee.eeThemesUrl		= '<?=$themes_url?>';
				Testee.eeThirdThemesUrl	= '<?=$third_themes_url?>';
				Testee.addonThemeUrl	= '<?=$third_themes_url?>/<?=$addon_short_name?>/';
				Testee.addonPkgJS		= '<?=$ee_pkg_js_base?>';
				Testee.addonPkgCSS		= '<?=$ee_pkg_css_base?>';
				Testee.eeJSUrl			= '<?=$ee_js_url?>';
				Testee.eejQueryUrl		= Testee.eeJSUrl + 'jquery/jquery.js';

				//quotes out regex strings like
				//PHP's preg_quote
				function regQuote (str, delimiter)
				{
					return (str + '').replace(
						new RegExp(
							'[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\' +
								(delimiter || '') +
								'-]',
							'g'
						), '\\$&'
					);
				}

				Testee.loadFiles = function(files, callback)
				{
					//array? empty?
					if (Object.prototype.toString.call( files ) !== '[object Array]' ||
						files.length == 0)
					{
						return;
					}

					//callback? (kind of need this, but what the hell ;D)
					callback = (typeof callback == 'function') ? callback : function(){};

					var loads = [];

					for (var i = 0, l = files.length; i < l; i++)
					{
						var end = '';

						//cachebust?
						if (Testee.cacheBust)
						{
							var qs = (files[i].indexOf('?') > -1) ? '&' : '?';
							end = qs + '=' + (new Date()).getTime();
						}

						//load EE's built in jQuery
						if (files[i] == 'jquery')
						{
							loads.push('js!' + Testee.eejQueryUrl + end + '!order');
						}
						//remove trailing css from addon package JS loading
						//because EE cannot see fit to do that itself >:|
						else if ((new RegExp("^" + regQuote(Testee.addonPkgCSS), 'i')).exec(files[i]))
						{
							loads.push('css!' + files[i].replace(/\.css$/, '') + end);
						}
						//are they notating that its css the curl way?
						else if (/^css!/i.exec(files[i]))
						{
							loads.push(files[i] + end);
						}
						//css file?
						else if (/\.css$/i.exec(files[i]))
						{
							loads.push('css!' + files[i] + end);
						}
						//remove trailing JS from addon package JS loading
						//because EE cannot see fit to do that itself >:|
						else if ((new RegExp("^" + regQuote(Testee.addonPkgJS), 'i')).exec(files[i]))
						{
							loads.push('js!' + files[i].replace(/\.js$/, '') + end + '!order');
						}
						//just a JS file?
						else
						{
							loads.push('js!' + files[i].replace(/^js!/, '') + end + '!order');
						}
					}

					//disable AMD loading?
					if ( ! Testee.amdLoad)
					{
						var oldDefine = define;
						define = false;
					}

					//have to do this funky due to ability to disable AMD
					curl(loads, function(){
						//get args as real array
						var args = Array.prototype.slice.call(arguments);

						if ( ! Testee.amdLoad)
						{
							define = oldDefine;
						}

						//apply callback function with passed args.
						callback.apply(this, args);
					});
				};
				//END Testee.loadFiles
			}(window));
		</script>
	</head>
	<body>
		<div id="qunit"></div>
		<div id="qunit-fixture"></div>
		<script src="<?=$testee_themes_url?>js/qunit.js"></script>
		<script type="text/javascript">
			//we have to do this because in the URL
			//we have a query string module='' and
			//qunit looks for that and assigns it
			QUnit.config.module = '<?=$addon_short_name?>';

			//the following must be run after qunit load
			//and body start

			//this sets a click listener on itself for
			//all clicks and reports
			//back to the parent
			(function(document){
				function addListener(element, eventName, handler)
				{
					if (element.addEventListener)
					{
						element.addEventListener(eventName, handler, false);
					}
					else if (element.attachEvent)
					{
						element.attachEvent('on' + eventName, handler);
					}
					else
					{
						element['on' + eventName] = handler;
					}
				}

				var body	= document.getElementsByTagName('body')[0];
				var bodyId	= body.getAttribute('id');

				addListener(body, 'click', function(){
					setTimeout(function(){
						window.parent.doHeight(bodyId);
					},100);
				});

				QUnit.done = function(failures, total)
				{
					window.parent.doHeight(bodyId);
				};
			}(document));
		</script>
		<script type='text/javascript'>
			<?=$test_js?>
		</script>
	</body>
</html>