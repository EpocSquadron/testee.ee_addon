<?php

/**
 * Base Testee unit test case.
 *
 * @author        Stephen Lewis (http://github.com/experience/)
 * @copyright     Experience Internet
 * @package       Testee
 */

// Classes extended by, but not 'required' by the 'mock' classes, below.
require_once BASEPATH .'database/DB_driver.php';
require_once BASEPATH .'libraries/Email.php';
require_once BASEPATH .'libraries/Javascript.php';
require_once BASEPATH .'libraries/Table.php';
require_once BASEPATH .'libraries/Typography.php';

// Classes mocked by Testee.
require_once BASEPATH .'database/DB_active_rec.php';
require_once BASEPATH .'database/DB_forge.php';
require_once BASEPATH .'database/DB_result.php';
require_once BASEPATH .'database/DB_utility.php';
//needed for mysql_driver
//in theory this would never be hit, but you never know.
if ( ! class_exists('CI_DB'))
{
	eval('class CI_DB extends CI_DB_active_record { }');
}

require_once BASEPATH .'database/drivers/mysql/mysql_driver.php';

require_once APPPATH .'core/EE_Config.php';
require_once APPPATH .'core/EE_Input.php';
require_once APPPATH .'core/EE_Lang.php';
require_once APPPATH .'core/EE_Loader.php';
require_once APPPATH .'core/EE_Output.php';
require_once APPPATH .'core/EE_URI.php';

require_once APPPATH .'libraries/Cp.php';
require_once APPPATH .'libraries/EE_Email.php';
require_once APPPATH .'libraries/EE_Javascript.php';
require_once APPPATH .'libraries/EE_Table.php';
require_once APPPATH .'libraries/EE_Typography.php';
require_once APPPATH .'libraries/Extensions.php';
require_once APPPATH .'libraries/Functions.php';
require_once APPPATH .'libraries/Layout.php';
require_once APPPATH .'libraries/Session.php';
require_once APPPATH .'libraries/Template.php';

require_once 'testee_equal_without_whitespace_expectation.php';
require_once PATH_THIRD . '/testee/vendor/simpletest/unit_tester.php';
require_once PATH_THIRD . '/testee/vendor/simpletest/mock_objects.php';

class Testee_unit_test_case extends UnitTestCase
{

	protected $EE;

	// @see _initialize_active_record_methods
	protected $_active_record_methods = array(
		'distinct',
		'from',
		'group_by',
		'having',
		'join',
		'like',
		'limit',
		'not_like',
		'or_having',
		'or_like',
		'or_not_like',
		'or_where',
		'or_where_in',
		'or_where_not_in',
		'order_by',
		'select',
		'select_avg',
		'select_max',
		'select_min',
		'select_sum',
		'set',
		'where',
		'where_in',
		'where_not_in'
	);

	// @see setUp
	protected $_mysql_methods = array(
		'db_connect',
		'db_pconnect',
		'reconnect',
		'db_select',
		'trans_begin',
		'trans_commit',
		'trans_rollback',
		'escape_str',
		'affected_rows',
		'insert_id',
		'count_all',
		'escapes',
		'implicitly',
		'maps'
	);


	// --------------------------------------------------------------------
	//	PUBLIC METHODS
	// --------------------------------------------------------------------

	/**
	 * Constructor.
	 *
	 * @access  public
	 * @return  void
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	//END __construct


	// --------------------------------------------------------------------

	/**
	 * Get things ready for the test.
	 *
	 * @access  public
	 * @return  void
	 */

	public function setUp()
	{
		// -------------------------------------
		// Create the mock objects. A class prefix
		// is used to avoid 'redeclared class'
		// errors when generating mock object classes.
		// -------------------------------------


		$class_prefix = get_class($this);


		// -------------------------------------
		// TRICKY:
		// EE's support for multiple DB drivers
		// makes life difficult. The 'master'
		// driver class defines a bunch of methods,
		// but also delegates driver-specific calls
		// to the relevant DB driver.
		//
		// The solution is to manually add the
		// MySQL-specific methods to the DB
		// mock. If you're not using MySQL,
		// you can always sub-class this, and
		// redefine the $_mysql_methods property.
		// -------------------------------------

		Mock::generate('CI_DB_active_record', $class_prefix .'_mock_db',
			$this->_mysql_methods);

		// Everything else is much more straightforward.
		Mock::generate('CI_DB_result',	$class_prefix .'_mock_db_query');
		Mock::generate('CI_DB_forge',	$class_prefix .'_mock_dbforge');
		Mock::generate('CI_DB_utility',	$class_prefix .'_mock_dbutil');
		Mock::generate('Cp',			$class_prefix .'_mock_cp');
		Mock::generate('EE_Config',		$class_prefix .'_mock_config');
		Mock::generate('EE_Email',		$class_prefix .'_mock_email');
		Mock::generate('EE_Extensions',	$class_prefix .'_mock_extensions');
		Mock::generate('EE_Functions',	$class_prefix .'_mock_functions');
		Mock::generate('EE_Input',		$class_prefix .'_mock_input');
		Mock::generate('EE_Javascript',	$class_prefix .'_mock_javascript');
		Mock::generate('EE_Lang',		$class_prefix .'_mock_lang');
		Mock::generate('EE_Loader',		$class_prefix .'_mock_loader');
		Mock::generate('EE_Output',		$class_prefix .'_mock_output');
		Mock::generate('EE_Session',	$class_prefix .'_mock_session');
		Mock::generate('EE_Table',		$class_prefix .'_mock_table');
		Mock::generate('EE_Template',	$class_prefix .'_mock_template');
		Mock::generate('EE_Typography',	$class_prefix .'_mock_typography');
		Mock::generate('EE_URI',		$class_prefix .'_mock_uri');
		Mock::generate('Layout',		$class_prefix .'_mock_layout');

		// Assign the mock objects to the EE superglobal.
		$this->EE->config		= $this->_get_mock('config');
		$this->EE->cp			= $this->_get_mock('cp');
		$this->EE->db			= $this->_get_mock('db');
		$this->EE->dbforge		= $this->_get_mock('dbforge');
		$this->EE->email		= $this->_get_mock('email');
		$this->EE->extensions	= $this->_get_mock('extensions');
		$this->EE->functions	= $this->_get_mock('functions');
		$this->EE->input		= $this->_get_mock('input');
		$this->EE->javascript	= $this->_get_mock('javascript');
		$this->EE->lang			= $this->_get_mock('lang');
		$this->EE->layout		= $this->_get_mock('layout');
		$this->EE->load			= $this->_get_mock('loader');
		$this->EE->output		= $this->_get_mock('output');
		$this->EE->session		= $this->_get_mock('session');
		$this->EE->TMPL			= $this->_get_mock('template');
		$this->EE->template		= $this->_get_mock('template');
		$this->EE->typography	= $this->_get_mock('typography');
		$this->EE->uri			= $this->_get_mock('uri');

		// EE compatibility layer
		$this->_initialize_active_record_methods();
	}
	//END setUp


	// --------------------------------------------------------------------
	//	PRIVATE & PROTECTED METHODS
	// --------------------------------------------------------------------

	// --------------------------------------------------------------------

	/**
	 * Returns a mock object of the specified type.
	 *
	 * @access  protected
	 * @param   string      $class    The class of mock object to return
	 *                                e.g. 'db', or 'query'.
	 * @return  bool|object
	 */
	protected function _get_mock($class = '')
	{
		$class_name = get_class($this) .'_mock_' .$class;

		if (class_exists($class_name))
		{
			return new $class_name();
		}

		return FALSE;
	}
	//END _get_mock


	// --------------------------------------------------------------------

	/**
	 * Ensures that the 'chainable' Active Record mock methods still return a
	 * reference to the mock DB class.
	 *
	 * @author Jamie Rumbelow
	 * @author Stephen Lewis
	 * @return void
	 */
	protected function _initialize_active_record_methods()
	{
		foreach ($this->_active_record_methods AS $method)
		{
			$this->EE->db->setReturnReference($method, $this->EE->db);
		}
	}
	//END _initialize_active_record_methods


	// --------------------------------------------------------------------

	/**
	 * exportVar makes a var export string of an included variable
	 * for use in failure messeges for the user.
	 *
	 * @access	protected
	 * @static
	 * @param	mixed $var	variable to export
	 * @return	string		exported variable
	 */
	protected function exportVar($var)
	{
		ob_start();

		var_export($var);

		$result = ob_get_contents();

		ob_end_clean();

		return $result;
	}
	//END exportVar


	// --------------------------------------------------------------------

	/**
	 * callMethod: Function that allows access to protected functions
	 * for testing in PHP 5.3+. Uses call_user_func_array as a backup
	 * in case PHP < 5.3.
	 *
	 * @access	public
	 * @static
	 * @param	object	$obj	class intance to invoke the method from
	 * @param	string	$name	string name of method
	 * @param	array	$args	arguments to send to function
	 * @return	mixed			result of invoked method
	 */

	public static function callMethod($obj, $name, array $args)
	{
		if (class_exists('ReflectionClass'))
		{
			$class = new \ReflectionClass($obj);
			$method = $class->getMethod($name);
			$method->setAccessible(true);
			return $method->invokeArgs($obj, $args);
		}
		else
		{
			return call_user_func_array(array($obj, $name), $args);
		}
	}
	//END callMethod
}
/* End of file    : testee_unit_test_case.php */
/* File location  : third_party/testee/classes/testee_unit_test_case.php */