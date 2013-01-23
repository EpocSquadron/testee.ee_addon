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

//require_once dirname(__FILE__) .'/testee_equal_without_whitespace_expectation.php';
require_once realpath(dirname(__FILE__) . '/../../vendor/autoload.php');

class Testee_phpunit_test_case extends PHPUnit_Framework_TestCase
{
	/**
	 * $EE instance object
	 *
	 * @var object
	 * @see	__construct
	 */
	protected $EE;


	/**
	 * Active Record methods that should return
	 * the AR object for chaining support
	 *
	 * @var array
	 * @see initializeActiveRecordMethods
	 */
	protected $activeRecordMethods = array(
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


	/**
	 * MySQL driver methods
	 *
	 * @var	array
	 * @see	__construct
	 * @see	setUp
	 */
	protected $mysqlMethods = array(
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


	/**
	 * Mock list for retrieveale by new class name
	 * @var	array
	 * @see buildMock
	 */
	protected $mockList = array();

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

		//these will get all of the correct public methods
		$this->mysqlMethods = get_class_methods('CI_DB_mysql_driver');
	}
	//END __construct


	// --------------------------------------------------------------------

	/**
	 * Get things ready for the test.
	 *
	 * @access  public
	 * @return  void
	 */

	public function setUp( $use_mockery = FALSE)
	{
		// -------------------------------------
		// Create the mock objects. A class prefix
		// is used to avoid 'redeclared class'
		// errors when generating mock object classes.
		// -------------------------------------

		$prefix = get_class($this);

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
		// redefine the $mysqlMethods property.
		// -------------------------------------

		$this->buildMock('CI_DB_active_record', $prefix .'_mock_db',
			array_unique(array_merge(
				get_class_methods('CI_DB_active_record'),
				$this->mysqlMethods
			))
		);

		// Everything else is much more straightforward.
		$this->buildMock('CI_DB_result',	$prefix .'_mock_db_query');
		$this->buildMock('CI_DB_forge',		$prefix .'_mock_dbforge');
		$this->buildMock('CI_DB_utility',	$prefix .'_mock_dbutil');
		$this->buildMock('Cp',				$prefix .'_mock_cp');
		$this->buildMock('EE_Config',		$prefix .'_mock_config');
		$this->buildMock('EE_Email',		$prefix .'_mock_email');
		$this->buildMock('EE_Extensions',	$prefix .'_mock_extensions');
		$this->buildMock('EE_Functions',	$prefix .'_mock_functions');
		$this->buildMock('EE_Input',		$prefix .'_mock_input');
		$this->buildMock('EE_Javascript',	$prefix .'_mock_javascript');
		$this->buildMock('EE_Lang',			$prefix .'_mock_lang');
		$this->buildMock('EE_Loader',		$prefix .'_mock_loader');
		$this->buildMock('EE_Output',		$prefix .'_mock_output');
		$this->buildMock('EE_Session',		$prefix .'_mock_session');
		$this->buildMock('EE_Table',		$prefix .'_mock_table');
		$this->buildMock('EE_Template',		$prefix .'_mock_template');
		$this->buildMock('EE_Typography',	$prefix .'_mock_typography');
		$this->buildMock('EE_URI',			$prefix .'_mock_uri');
		$this->buildMock('Layout',			$prefix .'_mock_layout');

		// Assign the mock objects to the EE superglobal.
		$this->EE->config		= $this->getMockByName('config');
		$this->EE->cp			= $this->getMockByName('cp');
		$this->EE->db			= $this->getMockByName('db');
		$this->EE->dbforge		= $this->getMockByName('dbforge');
		$this->EE->email		= $this->getMockByName('email');
		$this->EE->extensions	= $this->getMockByName('extensions');
		$this->EE->functions	= $this->getMockByName('functions');
		$this->EE->input		= $this->getMockByName('input');
		$this->EE->javascript	= $this->getMockByName('javascript');
		$this->EE->lang			= $this->getMockByName('lang');
		$this->EE->layout		= $this->getMockByName('layout');
		$this->EE->load			= $this->getMockByName('loader');
		$this->EE->output		= $this->getMockByName('output');
		$this->EE->session		= $this->getMockByName('session');
		$this->EE->TMPL			= $this->getMockByName('template');
		$this->EE->template		= $this->getMockByName('template');
		$this->EE->typography	= $this->getMockByName('typography');
		$this->EE->uri			= $this->getMockByName('uri');

		// EE compatibility layer
		$this->initializeActiveRecordMethods();
	}
	//END setUp


	// --------------------------------------------------------------------
	//	PRIVATE & PROTECTED METHODS
	// --------------------------------------------------------------------

	// --------------------------------------------------------------------

	/**
	 * Build Mock Object with options
	 *
	 * @access	protected
	 * @param	string	$class		class to be mocked
	 * @param	string	$clone_name	name of new class built as clone
	 * @param	array	$methods	methods to add to mock
	 * @return	object				instance of getMock
	 */

	protected function buildMock($class, $clone_name = '', $methods = array())
	{
		if ( ! class_exists($class))
		{
			throw new Exception('Class does not exist: ' . $class);
			return;
		}

		$mock = $this->getMockBuilder($class);
		$mock->disableOriginalConstructor();

		$list_key = $class;

		if ((string) $clone_name != '')
		{
			$list_key = (string) $clone_name;
			$mock->setMockClassName((string) $clone_name);
		}

		if ( ! empty($methods))
		{
			$mock->setMethods($methods);
		}
		else
		{
			$mock->setMethods(get_class_methods($class));
		}

		$obj = $mock->getMock();

		//store ref at key
		$this->mockList[$list_key] =& $obj;

		return $obj;
	}
	//END buildMock


	// --------------------------------------------------------------------

	/**
	 * Returns a mock object of the specified type.
	 *
	 * @access  protected
	 * @param   string      $class    The class of mock object to return
	 *                                e.g. 'db', or 'query'.
	 * @return  bool|object
	 */
	protected function getMockByName($class = '')
	{
		$class_name = get_class($this) .'_mock_' .$class;

		if (isset($this->mockList[$class_name]))
		{
			return $this->mockList[$class_name];
		}

		return FALSE;
	}
	//END getMockByName


	// --------------------------------------------------------------------

	/**
	 * Ensures that the 'chainable' Active Record mock methods still return a
	 * reference to the mock DB class.
	 *
	 * @author Jamie Rumbelow
	 * @author Stephen Lewis
	 * @return void
	 */
	protected function initializeActiveRecordMethods()
	{
		foreach ($this->activeRecordMethods AS $method)
		{
			$this->EE->db
					->expects($this->any())
					->method($method)
					->will($this->returnSelf());
		}
	}
	//END initializeActiveRecordMethods


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