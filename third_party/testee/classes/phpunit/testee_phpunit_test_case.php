<?php

/**
 * Base PHPUnit Testee unit test case.
 *
 * @author		Stephen Lewis (http://github.com/experience/)
 * @author		Greg Ferrell
 * @copyright	Experience Internet
 * @package		Testee
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
	//this has to be evaled otherwise class declaration runs
	//before the if statement. Thanks, PHP :D
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
	 * EE libs that need to be mocked
	 *
	 * @var array
	 * @see setUp
	 */
	protected $eeMockLibs = array(
		'db'			=> 'CI_DB_active_record',
		'db_query'		=> 'CI_DB_result',
		'dbforge'		=> 'CI_DB_forge',
		'dbutil'		=> 'CI_DB_utility',
		'cp'			=> 'Cp',
		'config'		=> 'EE_Config',
		'email'			=> 'EE_Email',
		'extensions'	=> 'EE_Extensions',
		'functions'		=> 'EE_Functions',
		'input'			=> 'EE_Input',
		'javascript'	=> 'EE_Javascript',
		'lang'			=> 'EE_Lang',
		'loader'		=> 'EE_Loader',
		'output'		=> 'EE_Output',
		'session'		=> 'EE_Session',
		'table'			=> 'EE_Table',
		'template'		=> 'EE_Template',
		'typography'	=> 'EE_Typography',
		'uri'			=> 'EE_URI',
		'layout'		=> 'Layout',
	);


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
	 * Active record methods that need to be mocked into DB
	 *
	 * @var array
	 * @see __construct
	 */
	protected $ARMethods = array();

	/**
	 * Mock list for retrieveale by new class name
	 * @var	array
	 * @see buildMock
	 */
	protected $mockList = array();

	/**
	 * Prefix for mocks so we get no interference
	 *
	 * @var string
	 * @see __construct
	 */
	protected $prefix	= 'mock_';

	/**
	 * Are we using mockery objects for this test?
	 *
	 * @var boolean
	 * @see setUp
	 */
	protected $useMockery = FALSE;

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

		$this->prefix = get_class($this) . '_mock_';
		//these will get all of the correct public methods
		$this->mysqlMethods	= get_class_methods('CI_DB_mysql_driver');

		$this->ARMethods	= array_unique(array_merge(
			get_class_methods('CI_DB_active_record'),
			$this->mysqlMethods
		));
	}
	//END __construct


	// --------------------------------------------------------------------

	/**
	 * Get things ready for the test.
	 *
	 * @access  public
	 * @return  void
	 */

	public function setUp($useMockery = FALSE)
	{
		$this->useMockery = (bool) $useMockery;

		// -------------------------------------
		//	build mocks
		// -------------------------------------

		//mockey mock and the funky bunch
		foreach ($this->eeMockLibs as $shortName => $class)
		{
			//we already did this!
			if ($shortName == 'db') continue;

			$this->buildMock($class,	$this->prefix . $shortName);
		}

		// -------------------------------------
		//	set mocks (getMockByName returns ref)
		// -------------------------------------

		// Assign the mock objects to the EE superglobal.
		$this->EE->config		= $this->getMockByName('config');
		$this->EE->cp			= $this->getMockByName('cp');
		//need to get a fresh copy of this because of the special build
		$this->EE->db			= $this->getMockByName('db', TRUE);
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
		$this->mockList[$list_key] = & $obj;

		return $obj;
	}
	//END buildMock


	// --------------------------------------------------------------------

	/**
	 * Returns a mock object of the specified type.
	 *
	 * @access	protected
	 * @param	string		$class		The class of mock object to return
	 *									e.g. 'db', or 'query'.
	 * @param	bool		$fresh
	 * @return  bool|object
	 */

	protected function getMockByName($shortName = '', $fresh = false)
	{
		$className = $this->prefix . $shortName;

		if ($fresh)
		{
			//special snowflake
			if ($shortName == 'db')
			{
				$obj = $this->buildMock(
					$this->eeMockLibs[$shortName],
					$className,
					$this->ARMethods
				);

				$this->initializeActiveRecordMethods($obj);

				return $obj;
			}
			else if (isset($this->eeMockLibs[$shortName]))
			{
				return $this->buildMock($this->eeMockLibs[$shortName], $className);
			}
			else
			{
				return false;
			}
		}

		if (isset($this->mockList[$className]))
		{
			return $this->mockList[$className];
		}

		return false;
	}
	//END getMockByName


	// --------------------------------------------------------------------

	/**
	 * Ensures that the 'chainable' Active Record mock methods still return a
	 * reference to the mock DB class.
	 *
	 * @access	protected
	 * @author	Jamie Rumbelow
	 * @author	Stephen Lewis
	 * @author	Greg Ferrell (Hey, everyone else was doing it)
	 * @param	mixed	$obj	optionally pass an object to set AR refs on
	 * @return	object			$this, for chaining
	 */
	protected function initializeActiveRecordMethods($obj = null)
	{
		if ( ! is_object($obj))
		{
			$obj =& $this->EE->db;
		}

		foreach ($this->activeRecordMethods AS $method)
		{
			$obj->expects($this->any())
				->method($method)
				->will($this->returnSelf());
		}

		return $this;
	}
	//END initializeActiveRecordMethods


	// --------------------------------------------------------------------

	/**
	 * exportVar makes a var export string of an included variable
	 * for use in failure messeges for the user.
	 *
	 * @access	public
	 * @static
	 * @param	mixed $var	variable to export
	 * @return	string		exported variable
	 */
	public static function exportVar($var)
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