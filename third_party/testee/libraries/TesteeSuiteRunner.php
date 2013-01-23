<?php

class TesteeSuiteRunner
{
	/**
	 * Copies of the real instances of EE objects during testing
	 *
	 * @var object
	 * @see runTests
	 */
	static public $real;

	/**
	 * path to the testee addon
	 * @var string
	 */
	public $testeePath = '';

	/**
	 * Which test suite are we running?
	 *
	 * @var string
	 * @see setTestSuite
	 */
	protected $testType = 'phpunit';

	/**
	 * Test reporter
	 *
	 * @var object
	 * @see getReporter
	 */
	protected $reporter;

	/**
	 * possible classes loaded by EE
	 *
	 * @var array
	 * @see runTests
	 */
	public $possibles = array(
		'cp',
		'dbforge',
		'email',
		'javascript',
		'layout',
		'table',
		'template',
		'TMPL',
		'typography',
	);

	// --------------------------------------------------------------------

	/**
	 * constructor
	 *
	 * @access public
	 */
	public function __construct()
	{
		$this->testeePath = rtrim(realpath(dirname(__FILE__) . '/../'), '/') . '/';

		require_once $this->testeePath . 'vendor/autoload.php';
		require_once $this->testeePath . 'classes/simpletest/testee_addon.php';
		require_once $this->testeePath . 'classes/simpletest/testee_unit_test_case.php';
		require_once $this->testeePath . 'classes/phpunit/testee_phpunit_test_case.php';
		//clear
		static::$real = new stdClass();
	}
	//END __construct


	// --------------------------------------------------------------------

	/**
	 * Set test suite that we are going to use
	 *
	 * @access	public
	 * @param	string	$suite	which test suite are we running?
	 * @return	object			$this, for chaning
	 */

	public function setTestType($testType = 'phpunit')
	{
		$testType = strtolower(trim((string) $testType));

		$this->testType = ($testType === 'simpletest') ? 'simpletest' : 'phpunit';

		return $this;
	}
	//END setTestSuite


	// --------------------------------------------------------------------

	/**
	 * Get test reporter object
	 *
	 * @access	public
	 * @return	object	instance of test reporter
	 */
	public function getReporter($createNew = false)
	{
		if ( !isset($this->reporter) || $createNew)
		{
			if ($this->testType == 'phpunit')
			{
				//Create a result listener or add it
				$this->reporter = new PHPUnit_Framework_TestResult();
				//if mockery is used
				$this->reporter->addListener(new \Mockery\Adapter\Phpunit\TestListener());
			}
			else
			{
				require_once $this->testeePath . 'classes/simpletest/testee_cp_reporter.php';
				$this->reporter = new Testee_cp_reporter();
			}
		}

		return $this->reporter;
	}
	//END getReporter


	// --------------------------------------------------------------------

	/**
	 * Run the tests.
	 *
	 * @author  Stephen Lewis
	 * @author  Jamie Rumbelow
	 * @author  Bjørn Børresen
	 * @param   array             $testPath  The tests to run.
	 * @param   Testee_reporter   $reporter   The custom reporter used for output.
	 * @return  string
	 */

	public function runTests(Array $testPath = array(), $reporter = false)
	{
		// Can't do anything without tests to run.
		if ( ! $testPath)
		{
			throw new Exception('Missing test path(s).');
		}

		$EE =& get_instance();

		if ( ! $reporter)
		{
			$reporter = $this->getReporter();
		}

		// Get rid of E_DEPRECATION errors for anybody using PHP5.3.
		if (phpversion() >= 5.3)
		{
			error_reporting(error_reporting() & ~E_DEPRECATED);
		}

		if ($this->testType == 'simpletest')
		{
			// Create the Test Suite.
			$testSuite = new TestSuite('Testee Test Suite');
			$addFile	= 'addFile';

		}
		else
		{
			// Create the Test Suite.
			$testSuite = new PHPUnit_Framework_TestSuite('Testee Test Suite');
			$addFile	= 'addTestFile';
		}

		// Add the test files.
		foreach ($testPath AS $path)
		{
			if (stristr(PATH_THIRD, $path))
			{
				/**
				 * Handle Windows paths correctly.
				 *
				 * @author  Bjørn Børresen (http://twitter.com/bjornbjorn)
				 * @since   0.9.0
				 */

				$packagePath = explode(DIRECTORY_SEPARATOR,
					str_replace(PATH_THIRD, '', $path));

				if (count($packagePath) == 3
					&& $packagePath[1] == 'tests'
					&& file_exists($path)
				)
				{
					$testSuite->{$addFile}($path);
				}
			}
			//if tests are not located in path third
			else if (file_exists($path))
			{
				$testSuite->{$addFile}($path);
			}
		}

		// Make a note of the real EE objects. These are replaced by
		// mock objects during testing.

		static::$real->config		= $EE->config;
		static::$real->db			= $EE->db;
		static::$real->extensions	= $EE->extensions;
		static::$real->functions	= $EE->functions;
		static::$real->input		= $EE->input;
		static::$real->lang			= $EE->lang;
		static::$real->loader		= $EE->load;
		static::$real->output		= $EE->output;
		static::$real->session		= $EE->session;
		static::$real->uri			= $EE->uri;

		//possible classes to set to real
		foreach($this->possibles as $possible)
		{
			if (isset($EE->$possible))
			{
				static::$real->$possible = $EE->$possible;
			}
			else
			{
				static::$real->$possible = false;
			}
		}


		// TRICKY:
		// If the tests are being run via an ACTion, certain EE constants appear to
		// be undefined. So far, I've only run into this issue with the BASE
		// constant, but it's quite possible that there are others.
		//
		// The current solution, which works fine for now, is to define the missing
		// constants here, before the tests are run.

		if ( ! defined('BASE'))
		{
			define('BASE', 'http://testee.com/admin.php');
		}

		// Ideally, we'd just like to run our tests, and return the result to the
		// caller to do with as they please. This would let the reporter return
		// whatever is most appropriate (raw HTML, structured data, etc).
		//
		// Unfortunately, that's not how SimpleTest/PHPUnit works. The run() method returns
		// a boolean value indicating whether the test suite ran, and the reporter
		// is expected to echo out its results to the buffer.
		//
		// We capture said buffer to prevent it from being echoed directly to the
		// screen, and return it to the caller.

		if ($this->testType == 'simpletest')
		{
			ob_start();
			$testSuite->run($reporter);
			$testResults = ob_get_clean();
		}
		else
		{
			$runner = new PHPUnit_TextUI_TestRunner();
			require_once $this->testeePath . 'classes/phpunit/testee_phpunit_reporter.php';
			$runner->setPrinter(new Testee_phpunit_reporter());

			//there is no way to turn off the version string
			//except to run it manually and capture/remove
			//then the protected self::$versionStringPrinted bool
			//gets set and it wont run again
			ob_start();
			$runner->printVersionString();
			ob_end_clean();

			ob_start();
			$runner->doRun($testSuite);
			$testResults = ob_get_clean();
		}

		// Reinstate the real EE objects.
		$EE->config		= static::$real->config;
		$EE->cp			= static::$real->cp;
		$EE->db			= static::$real->db;
		$EE->extensions	= static::$real->extensions;
		$EE->functions	= static::$real->functions;
		$EE->input		= static::$real->input;
		$EE->lang		= static::$real->lang;
		$EE->load		= static::$real->loader;
		$EE->output		= static::$real->output;
		$EE->session	= static::$real->session;
		$EE->uri		= static::$real->uri;

		//possible classes
		foreach($this->possibles as $possible)
		{
			if (static::$real->$possible)
			{
				$EE->$possible = static::$real->$possible;
			}
			//remove loaded stuff that wasn't loaded before
			else if (isset($EE->$possible) && is_object($EE->$possible))
			{
				unset($EE->$possible);
			}
		}

		//clear
		static::$real = new stdClass();

		return $testResults;
	}
	//END run_tests
}
//END RunUnitTests