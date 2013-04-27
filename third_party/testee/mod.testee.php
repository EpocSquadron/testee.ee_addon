<?php if ( ! defined('BASEPATH')) exit('Invalid file request');

/**
 * Test-driven add-on development module.
 *
 * @author      Stephen Lewis
 * @copyright   Experience Internet
 * @package     Testee
 */

require_once dirname(__FILE__) .'/classes/simpletest/testee_json_reporter.php';

class Testee {

	private $EE;


	/* --------------------------------------------------------------
	 * PUBLIC METHODS
	 * ------------------------------------------------------------ */

	/**
	 * Constructor.
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->model('testee_model');
		$this->EE->lang->loadfile('testee');
	}


	/**
	 * Handles the 'run_tests' ACTion. Runs all of the available tests for the
	 * specified add-on, and outputs the results in JSON format.
	 *
	 * @access  public
	 * @return  void
	 */
	public function run_tests()
	{
		$req_tests = $this->EE->input->get_post('addon');

		if ( $req_tests === FALSE AND REQ == 'PAGE' AND isset($this->EE->TMPL))
		{
			$req_tests = $this->EE->TMPL->fetch_param('addon');
		}

		// Determine the tests to run.
		$input_tests = array_filter(
			explode('|', $req_tests)
		);

		if ( ! $input_tests)
		{
			// HTTP status code 412: Precondition Failed.
			$json = array('code' => 412,
				'message' => $this->EE->lang->line('json_error__412'));

			$this->_output_json(json_encode($json), 412);
			return;
		}

		// Guard against nonsense.
		$input_tests = array_unique($input_tests);

		// Retrieve all of the available tests, organised by add-on.
		$all_tests = $this->EE->testee_model->get_tests();
		$bad_tests = array();
		$run_tests = array();


		if (in_array('all', $input_tests))
		{
			/**
			 * The special 'all' keyword tells us to run all the available tests. If
			 * it is present in the list of requested tests, we ignore anything else
			 * (including unknown add-ons), and just run everything.
			 */

			foreach ($all_tests AS $addon)
			{
				foreach ($addon->tests AS $addon_test)
				{
					$run_tests[] = $addon_test->file_path;
				}
			}
		}
		else
		{
			// Only interested in specific tests.
			foreach ($input_tests AS $input_test)
			{
				foreach ($all_tests AS $addon)
				{
					if ($addon->name != $input_test)
					{
						continue;
					}

					// The add-on exists. Grab all the associated tests.
					foreach ($addon->tests AS $addon_test)
					{
						$run_tests[] = $addon_test->file_path;
					}

					continue 2;   // Move to the next input add-on.
				}

				// Unknown add-on.
				$bad_tests[] = $input_test;
			}
		}

		// Were there any unknown tests?
		if ($bad_tests)
		{
			// HTTP status code 404: Unknown Test(s).
			$json = array(
				'code'    => 404,
				'message' => $this->EE->lang->line('json_error__404_details')
					.implode('; ', $bad_tests)
			);

			$this->_output_json(json_encode($json), 404);
		}

		/**
		 * It's possible, although highly unlikely, that we could have found an
		 * add-on's test suite, but no associated tests.
		 */

		if ( ! $run_tests)
		{
			// HTTP status code 404: (Tests) Not Found.
			$json = array('code' => 404,
				'message' => $this->EE->lang->line('json_error__404_general'));

			$this->_output_json(json_encode($json), 404);
			return;
		}

		// Finally, we can run the tests.
		try
		{
			$json = $this->EE->testee_model->run_tests($run_tests,
				new Testee_json_reporter());
		}
		catch (Exception $e)
		{
			// HTTP status code 500: Internal Server Error.
			$json = array('code' => 500,
				'message' => $this->EE->lang->line('json_error__500'));

			$this->_output_json(json_encode($json), 500);
			return;
		}

		$this->_output_json($json);
	}


	/**
	 * Handles the 'run_phpunit_tests' ACTion. Runs all of the available tests for the
	 * specified add-on, and outputs the results in JSON format.
	 *
	 * @access  public
	 * @return  void
	 */
	public function run_phpunit_tests()
	{
		$req_tests		= $this->EE->input->get_post('addon');
		$show_errors	= $this->EE->input->get_post('show_errors');

		if ( $req_tests === FALSE AND REQ == 'PAGE' AND isset($this->EE->TMPL))
		{
			$req_tests		= $this->EE->TMPL->fetch_param('addon', $req_tests);
			$show_errors	= $this->EE->TMPL->fetch_param('show_errors', $show_errors);
		}

		$show_errors = $show_errors !== 'no';

		$all = (trim($req_tests) == 'all');

		// Determine the tests to run.
		$input_tests = array_filter(
			explode('|', $req_tests)
		);

		if (empty($input_tests))
		{
			// HTTP status code 412: Precondition Failed.
			$json = array(
				'code' => 412,
				'message' => $this->EE->lang->line('json_error__412')
			);

			$this->_output_json(json_encode($json), 412);
			return;
		}

		// Guard against nonsense.
		$input_tests = array_unique($input_tests);

		// Retrieve the contents of the third-party add-ons directory.
		$all_addons = $this->EE->testee_model->get_directory_names(PATH_THIRD);

		$prefs = $this->EE->testee_model->get_prefs();

		$run_tests = array();

		foreach ($all_addons AS $addon)
		{
			if ( ! $all AND ! in_array($addon, $input_tests))
			{
				continue;
			}

			$test_dir_path = PATH_THIRD . $addon . '/tests';

			if (isset($prefs['test_location_' . $addon]))
			{
				$test_dir_path = $prefs['test_location_' . $addon];
			}

			if ( ! $all_tests = $this->EE->testee_model->get_file_names($test_dir_path))
			{
				continue;
			}

			foreach ($all_tests AS $test)
			{
				if (preg_match('/^test[_|\.]([^\.]*)\.php$/i', $test, $matches))
				{
					$run_tests[] = $test_dir_path . '/' . $test;
				}
			}
		}

		// Finally, we can run the tests.
		try
		{
			$this->EE->load->library('TesteeSuiteRunner');

			$this->EE->testeesuiterunner->setTestType('phpunit');

			if ( ! $show_errors)
			{
				require_once PATH_THIRD . '/testee/classes/phpunit/testee_phpunit_short_reporter.php';
				$this->EE->testeesuiterunner->reporter = new Testee_phpunit_short_reporter();
			}

			$result = $this->EE->testeesuiterunner->runTests($run_tests);
		}
		catch (Exception $e)
		{
			// HTTP status code 500: Internal Server Error.
			$json = array('code' => 500,
				'message' => $this->EE->lang->line('json_error__500'));

			$this->_output_json(json_encode($json), 500);
			return;
		}

		exit($result);
	}



	/* --------------------------------------------------------------
	 * PROTECTED METHODS
	 * ------------------------------------------------------------ */

	/**
	 * Outputs the supplied JSON.
	 *
	 * @access  protected
	 * @param   string    $json    The JSON content.
	 * @param   int    $code    The HTTP response code.
	 * @return  void
	 */
	protected function _output_json($json, $code = 200)
	{
		set_status_header($code);
		@header('Content-type: application/json');
		exit($json);
	}


}


/* End of file		: mod.testee.php */
/* File location	: third_party/testee/mod.testee.php */
