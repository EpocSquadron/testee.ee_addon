<?php

/**
 * Tests for the Testee_model class.
 *
 * @author			Stephen Lewis (http://github.com/experience/)
 * @copyright		Experience Internet
 * @package			Testee
 */

require_once realpath(dirname(__FILE__) . '/../models/testee_model.php');

class Test_testee_model extends Testee_unit_test_case
{

	private $_package_name;
	private $_package_version;
	private $_subject;

	/* --------------------------------------------------------------
	 * PUBLIC METHODS
	 * ------------------------------------------------------------ */

	/**
	 * Runs before each test.
	 *
	 * @access	public
	 * @return	void
	 */
	public function setUp()
	{
		parent::setUp();

		$this->_package_name    = 'example_package';
		$this->_package_version = '1.2.3';

		$this->_subject = new Testee_model($this->_package_name,
			$this->_package_version);
	}



	/* --------------------------------------------------------------
	 * TEST METHODS
	 * ------------------------------------------------------------ */

	public function test__get_package_name__returns_correct_package_name()
	{
		$this->assertIdentical($this->_package_name,
			$this->_subject->get_package_name());
	}


	public function tests__get_package_version__retrieves_valid_version_number()
	{
		$this->assertIdentical($this->_package_name,
			$this->_subject->get_package_name());
	}


	public function test__get_theme_url__works_with_trailing_slash()
	{
		//EE 2.5+ supports moveable third party themes
		//and it already adds the trailing slash for you
		if (defined('URL_THIRD_THEMES'))
		{
			$full_url = URL_THIRD_THEMES;
		}
		else
		{
			$themes_url	= 'http://example.com/themes/';
			$full_url	= $themes_url .'third_party/';

			$this->EE->config->expectOnce('item', array('theme_folder_url'));
			$this->EE->config->setReturnValue('item', $themes_url);
		}

		$full_url .= $this->_package_name .'/';

		$this->assertIdentical($full_url, $this->_subject->get_theme_url());
	}


	public function test__get_theme_url__works_without_trailing_slash()
	{

		//EE 2.5+ supports moveable third party themes
		//and it already adds the trailing slash for you
		if (defined('URL_THIRD_THEMES'))
		{
			$full_url = URL_THIRD_THEMES;
		}
		else
		{
			$themes_url	= 'http://example.com/themes/';
			$full_url	= $themes_url .'third_party/';

			$this->EE->config->expectOnce('item', array('theme_folder_url'));
			$this->EE->config->setReturnValue('item', $themes_url);
		}

		$full_url .= $this->_package_name .'/';

		$this->assertIdentical($full_url, $this->_subject->get_theme_url());
	}


	public function test__install_module__adds_module_to_db_and_returns_true()
	{
		$module_data = array(
			'has_cp_backend'		  => 'y',
			'has_publish_fields'	=> 'n',
			'module_name'			    => $this->_package_name,
			'module_version'		  => $this->_package_version
		);

		$action_data = array(
			'class'  => $this->_package_name,
			'method' => 'run_tests'
		);

		$this->EE->db->expectCallCount('insert', 2);
		$this->EE->db->expectAt(0, 'insert', array('modules', $module_data));
		$this->EE->db->expectAt(1, 'insert', array('actions', $action_data));

		// -------------------------------------
		//	installs prefs table?
		// -------------------------------------

		$this->EE->db->expectOnce('table_exists', array('testee_preferences'));
		$this->EE->db->returnsAt(0, 'table_exists', FALSE);

		$this->EE->dbforge->expectOnce('create_table', array('testee_preferences', TRUE));

		$this->assertIdentical(TRUE, $this->_subject->install_module());
	}


	public function test__uninstall_module__removes_module_from_db_and_returns_true()
	{
		$module_id	= '123';
		$db_result	= $this->_get_mock('db_query');
		$db_row		= (object) array('module_id' => $module_id);

		// Retrieve the module ID.
		$this->EE->db->expectOnce('select', array('module_id'));

		$this->EE->db->expectOnce('get_where', array('modules',
			array('module_name' => $this->_package_name)));

		$this->EE->db->setReturnReference('get_where', $db_result);

		$db_result->expectOnce('num_rows');
		$db_result->setReturnValue('num_rows', 1);

		$db_result->expectOnce('row');
		$db_result->setReturnReference('row', $db_row);

		// Delete the module.
		$this->EE->db->expectCallCount('delete', 3);

		$this->EE->db->expectAt(0, 'delete', array('module_member_groups',
			array('module_id' => $module_id)));

		$this->EE->db->expectAt(1, 'delete', array('modules',
			array('module_name' => $this->_package_name)));

		$this->EE->db->expectAt(2, 'delete', array('actions',
			array('class' => $this->_package_name)));

		// -------------------------------------
		//	drops prefs table?
		// -------------------------------------

		$this->EE->db->expectOnce('table_exists', array('testee_preferences'));
		$this->EE->db->returnsAt(0, 'table_exists', TRUE);

		$this->EE->dbforge->expectOnce('drop_table', array('testee_preferences'));

		$this->assertIdentical(TRUE, $this->_subject->uninstall_module());
	}


	public function test__uninstall_module__returns_false_if_module_not_found()
	{
		$db_result = $this->_get_mock('db_query');

		// Retrieve the module ID.
		$this->EE->db->expectOnce('select', array('module_id'));

		$this->EE->db->expectOnce('get_where', array('modules',
			array('module_name' => $this->_package_name)));

		$this->EE->db->setReturnReference('get_where', $db_result);

		$db_result->expectOnce('num_rows');
		$db_result->setReturnValue('num_rows', 0);
		$db_result->expectNever('row');

		// Should never get this far.
		$this->EE->db->expectNever('delete');

		$this->assertIdentical(FALSE, $this->_subject->uninstall_module());
	}


	public function test__update_module__returns_false_if_no_update_required()
	{
		$subject = $this->_subject;

		$this->assertIdentical(FALSE, $subject->update_module('1.0.0', '0.9.0'));
		$this->assertIdentical(FALSE, $subject->update_module('1.0b2', '1.0b1'));
		$this->assertIdentical(FALSE, $subject->update_module('1.0.0', ''));
	}


	public function test__update_module__returns_true_if_update_required()
	{
		$subject = $this->_subject;

		$this->assertIdentical(TRUE, $subject->update_module('10.0.0', '10.0.1'));
		$this->assertIdentical(TRUE, $subject->update_module('10.0b2', '10.0b3'));
		$this->assertIdentical(TRUE, $subject->update_module('', '0.1.0'));
	}


	public function test__update_module__registers_the_run_tests_action_if_upgrading_to_version_220b1()
	{
		$this->EE->db->expectOnce('insert', array('actions',
			array('class' => $this->_package_name, 'method' => 'run_tests')));

		$this->_subject->update_module('2.1.0', '2.2.0b1');
	}


	public function test__update_module__installs_the_table_if_upgrading_to_version_224()
	{
		$this->EE->db->expectOnce('table_exists', array('testee_preferences'));
		$this->EE->db->returnsAt(0, 'table_exists', FALSE);

		$this->EE->dbforge->expectOnce('create_table', array('testee_preferences', TRUE));

		$this->_subject->update_module('2.2.3', '2.2.4');
	}

	public function test__get_prefs__returns_empty_array_on_no_results()
	{
		//this cannot be mocked effectively, so
		//running this before any tests require it
		if ( ! function_exists('json_decode'))
		{
			require_once APPPATH . 'libraries/services_json.php';
		}

		$db_results = $this->_get_mock('db_query');

		$this->EE->db->expectOnce('select', array('name, value'));
		$this->EE->config->returnsAt(0, 'item', '1');
		$this->EE->config->expectOnce('item', array('site_id'));
		$this->EE->db->expectOnce('where', array('site_id', '1'));
		$this->EE->db->expectOnce('get', array('testee_preferences'));

		$db_results->expectOnce('num_rows', array());
		$db_results->returnsAt(0, 'num_rows', 0);

		$this->EE->db->returnsByReferenceAt(0, 'get', $db_results);

		$this->assertIdentical(array(), $this->_subject->get_prefs());
	}

	public function test__get_prefs__returns_row_array()
	{
		//this cannot be mocked effectively, so
		//running this before any tests require it
		if ( ! function_exists('json_decode'))
		{
			require_once APPPATH . 'libraries/services_json.php';
		}

		$return_array = array(
			array(
				'name'	=> 'pref_1',
				'value'	=> 'value_1'
			),
			array(
				'name'	=> 'pref_2',
				'value'	=> 'value_2'
			),
			array(
				'name'	=> 'pref_3',
				'value'	=> 'value_3'
			),
			array(
				'name'	=> 'pref_4',
				'value'	=> 'value_4'
			),
		);

		$expected_results = array(
			'pref_1' => 'value_1',
			'pref_2' => 'value_2',
			'pref_3' => 'value_3',
			'pref_4' => 'value_4'
		);

		$db_results = $this->_get_mock('db_query');

		$this->EE->db->expectOnce('select', array('name, value'));
		$this->EE->config->returnsAt(0, 'item', '1');
		$this->EE->config->expectOnce('item', array('site_id'));
		$this->EE->db->expectOnce('where', array('site_id', '1'));
		$this->EE->db->expectOnce('get', array('testee_preferences'));

		$db_results->expectOnce('num_rows', array());
		$db_results->returnsAt(0, 'num_rows', 4);

		$db_results->expectOnce('result_array', array());
		$db_results->returnsAt(0, 'result_array', $return_array);

		$this->EE->db->returnsByReferenceAt(0, 'get', $db_results);

		$this->assertIdentical($expected_results, $this->_subject->get_prefs());
	}


	public function test__get_prefs__parses_json_prefs_accurately()
	{
		//this cannot be mocked effectively, so
		//running this before any tests require it
		if ( ! function_exists('json_decode'))
		{
			require_once APPPATH . 'libraries/services_json.php';
		}

		$return_array = array(
			array(
				'name'	=> 'pref_1',
				'value'	=> '[test]'
			),
			array(
				'name'	=> 'pref_2',
				'value'	=> '{"test":"test1"}'
			),
			array(
				'name'	=> 'pref_3',
				'value'	=> '["test","test2"]'
			),
			array(
				'name'	=> 'pref_4',
				'value'	=> 'value_4'
			),
		);

		$expected_results = array(
			'pref_1' => '[test]',
			'pref_2' => array("test" => "test1"),
			'pref_3' => array("test","test2"),
			'pref_4' => 'value_4'
		);

		$db_results = $this->_get_mock('db_query');

		$this->EE->db->expectOnce('select', array('name, value'));
		$this->EE->config->returnsAt(0, 'item', '1');
		$this->EE->config->expectOnce('item', array('site_id'));
		$this->EE->db->expectOnce('where', array('site_id', '1'));
		$this->EE->db->expectOnce('get', array('testee_preferences'));

		$db_results->expectOnce('num_rows', array());
		$db_results->returnsAt(0, 'num_rows', 4);

		$db_results->expectOnce('result_array', array());
		$db_results->returnsAt(0, 'result_array', $return_array);

		$this->EE->db->returnsByReferenceAt(0, 'get', $db_results);

		$this->assertIdentical($expected_results, $this->_subject->get_prefs());
	}

	public function test__set_prefs__key_value_input_gets_saved_to_name_value()
	{
		$input = array(
			'my_key'		=> 'my_value',
			'my_json_key'	=> array('json' => 'value'),
			'null_ignored'	=> NULL
		);

		$save = array(
			array(
				'name'		=> 'my_key',
				'value'		=> 'my_value'
			),
			array(
				'name'		=> 'my_json_key',
				'value'		=> '{"json":"value"}'
			)
		);

		$this->EE->db->expectOnce('truncate', array('testee_preferences'));

		//our save array should be transformed here, and this is the real test
		$this->EE->db->expectOnce('insert_batch', array('testee_preferences', $save));

		$this->_subject->set_prefs($input);
	}

	public function test__set_prefs__empty_save_should_not_insert()
	{
		$input = array(
			'null_ignored'	=> NULL,
			'bool_ignored'	=> TRUE
		);

		$this->EE->db->expectOnce('truncate', array('testee_preferences'));

		//our save array should be transformed here, and this is the real test
		$this->EE->db->expectNever('insert_batch');

		$this->_subject->set_prefs($input);
	}
}

/* End of file		: test_testee_model.php */
/* File location	: third_party/testee/tests/test.testee_model.php */
