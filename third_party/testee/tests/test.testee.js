module('testee');

Testee.loadFiles([
		Testee.addonPkgJS + 'testee_test_import.js',
		Testee.addonPkgCSS + 'testee_test_import.css',
		'jquery'
	],
	function()
	{
		QUnit.stop();
		test('EE pkg JS load test', function(){
			ok(typeof testEEImportTest === 'function' && testEEImportTest() === true, 'testEEImportTest() should exist and return true');
		});
		test('EE pkg CSS load test', function(){
			$('#qunit-fixture').append('<div id="testee_testing"></div>');
			var $testing = $('#testee_testing');
			ok($testing.length && $testing.css('width') == '123px', 'Test div\'s width should be \'123px\'. Returned: ' + $testing.css('width') + '.');
		});
		QUnit.start();
	}
);

// -------------------------------------
//	This is packaged separately from
//	the other files incase the load itself fails
// -------------------------------------

Testee.loadFiles([Testee.eeJSUrl + 'underscore.js'], function(){
	QUnit.stop();
	test('EE themes Underscore load and _.extend({}) test', function(){
		ok(typeof _.extend({}) === 'object', 'Underscore should load and _.extend({}) should return an object');
	});
	QUnit.start();
});
