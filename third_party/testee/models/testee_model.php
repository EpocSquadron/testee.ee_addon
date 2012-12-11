<?php if ( ! defined('BASEPATH')) exit('Invalid file request');

/**
 * Test-driven add-on development module model.
 *
 * @author      Stephen Lewis (http://github.com/experience/)
 * @copyright   Experience Internet
 * @package     Testee
 */

require_once dirname(__FILE__) .'/../config.php';
require_once dirname(__FILE__) .'/../classes/testee_addon.php';
require_once dirname(__FILE__) .'/../classes/testee_unit_test_case.php';

class Testee_model extends CI_Model
{
	private $EE;
	private $_package_name;
	private $_package_version;

	private $prefs_table_name = 'testee_preferences';

	// --------------------------------------------------------------------
	//	PUBLIC METHODS
	// --------------------------------------------------------------------

	// --------------------------------------------------------------------

	/**
	 * Constructor.
	 *
	 * @access  public
	 * @param   string    $package_name     Mock package name.
	 * @param   string    $package_version  Mock package version.
	 * @return  void
	 */

	public function __construct($package_name = '', $package_version = '')
	{
		parent::__construct();

		$this->EE =& get_instance();

		/**
		 * Constants defined in the NSM Add-on Updater config.php file, so we don't
		 * have the package name and version defined in multiple locations.
		 */

		$this->_package_name = $package_name
			? $package_name
			: TESTEE_NAME;

		$this->_package_version = $package_version
			? $package_version
			: TESTEE_VERSION;
	}
	//END __construct


	// --------------------------------------------------------------------

	/**
	 * Returns the child sub-directories of the specified directory.
	 *
	 * @access  public
	 * @param   string      $dir_path     The directory to examine.
	 * @return  array
	 */

	public function get_directory_names($dir_path = '')
	{
		return $this->_get_directory_contents($dir_path, 'DIRECTORY');
	}
	//END get_directory_names


	// --------------------------------------------------------------------

	/**
	 * Returns the child files of the specified directory.
	 *
	 * @access  public
	 * @param   string      $dir_path     The directory to examine.
	 * @return  array
	 */

	public function get_file_names($dir_path = '')
	{
		return $this->_get_directory_contents($dir_path, 'FILE');
	}
	//END get_file_names


	// --------------------------------------------------------------------

	/**
	 * Returns the package name.
	 *
	 * @access  public
	 * @return  string
	 */

	public function get_package_name()
	{
		return $this->_package_name;
	}
	//END get_package_name


	// --------------------------------------------------------------------

	/**
	 * Returns the package version.
	 *
	 * @access  public
	 * @return  string
	 */

	public function get_package_version()
	{
		return $this->_package_version;
	}
	//END get_package_version


	// --------------------------------------------------------------------

	/**
	 * Returns an array of all the available tests. Testee assumes that
	 * each add-on will define its own tests, in a /third_party/add_on/tests/
	 * directory.
	 *
	 * @access  public
	 * @param	string	[varname] [description]
	 * @return  array
	 */

	public function get_tests($type = 'php')
	{
		$tests  = array();

		// Retrieve the contents of the third-party add-ons directory.
		if ( ! $all_addons = $this->get_directory_names(PATH_THIRD))
		{
			return $tests;
		}

		$prefs = $this->get_prefs();

		foreach ($all_addons AS $addon)
		{
			$test_dir_path = PATH_THIRD .$addon .DIRECTORY_SEPARATOR .'tests';

			if (isset($prefs['test_location_' . $addon]))
			{
				$test_dir_path = $prefs['test_location_' . $addon];
			}

			if ( ! $all_tests = $this->get_file_names($test_dir_path))
			{
				continue;
			}

			if ($type == 'all')
			{
				$test_pattern = '/^test[_|\.]([^\.]*)\.(?:php|js)$/i';
			}
			else if ($type == 'js')
			{
				$test_pattern = '/^test[_|\.]([^\.]*)\.js$/i';
			}
			else
			{
				$test_pattern = '/^test[_|\.]([^\.]*)\.php$/i';
			}

			$addon_tests  = array();

			foreach ($all_tests AS $test)
			{
				if ( ! preg_match($test_pattern, $test))
				{
					continue;
				}

				$addon_tests[] = new Testee_test(array(
					'file_name' => $test,
					'file_path' => $test_dir_path .DIRECTORY_SEPARATOR .$test
				));
			}

			if ($addon_tests)
			{
				$tests[] = new Testee_addon(array(
					'name'  => $addon,
					'tests' => $addon_tests
				));
			}
		}

		return $tests;
	}
	//END get_tests


	// --------------------------------------------------------------------

	/**
	 * Run the tests.
	 *
	 * @author  Stephen Lewis
	 * @author  Jamie Rumbelow
	 * @author  Bjørn Børresen
	 * @param   array             $test_path  The tests to run.
	 * @param   Testee_reporter   $reporter   The custom reporter used for output.
	 * @return  string
	 */

	public function run_tests(Array $test_path = array(),
		Testee_reporter $reporter
	)
	{
		// Can't do anything without tests to run.
		if ( ! $test_path)
		{
			throw new Exception('Missing test path(s).');
		}

		// Get rid of E_DEPRECATION errors for anybody using PHP5.3.
		if (phpversion() >= 5.3)
		{
			error_reporting(error_reporting() & ~E_DEPRECATED);
		}

		// Create the Test Suite.
		$test_suite = new TestSuite('Testee Test Suite');

		// Add the test files.
		foreach ($test_path AS $path)
		{
			if (stristr(PATH_THIRD, $path))
			{
				/**
				 * Handle Windows paths correctly.
				 *
				 * @author  Bjørn Børresen (http://twitter.com/bjornbjorn)
				 * @since   0.9.0
				 */

				$package_path = explode(DIRECTORY_SEPARATOR,
					str_replace(PATH_THIRD, '', $path));

				if (count($package_path) == 3
					&& $package_path[1] == 'tests'
					&& file_exists($path)
				)
				{
					$test_suite->addFile($path);
				}
			}
			else if (file_exists($path))
			{
				$test_suite->addFile($path);
			}
		}

		// Make a note of the real EE objects. These are replaced by
		// mock objects during testing.

		$real_config		= $this->EE->config;
		$real_db			= $this->EE->db;
		$real_extensions	= $this->EE->extensions;
		$real_functions		= $this->EE->functions;
		$real_input			= $this->EE->input;
		$real_lang			= $this->EE->lang;
		$real_loader		= $this->EE->load;
		$real_output		= $this->EE->output;
		$real_session		= $this->EE->session;
		$real_uri			= $this->EE->uri;

		// These don't always exist.
		$real_cp		= (isset($this->EE->cp)) ? $this->EE->cp : FALSE;
		$real_dbforge	= (isset($this->EE->dbforge)) ? $this->EE->dbforge : FALSE;
		$real_email		= (isset($this->EE->email)) ? $this->EE->email : FALSE;
		$real_layout	= (isset($this->EE->layout)) ? $this->EE->layout : FALSE;
		$real_table		= (isset($this->EE->table)) ? $this->EE->table : FALSE;
		$real_template	= (isset($this->EE->template)) ? $this->EE->template : FALSE;
		$real_tmpl		= (isset($this->EE->TMPL)) ? $this->EE->TMPL : FALSE;

		$real_javascript = (isset($this->EE->javascript))
			? $this->EE->javascript : FALSE;

		$real_typography = (isset($this->EE->typography))
			? $this->EE->typography : FALSE;


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


		// TRICKY:
		// Ideally, we'd just like to run our tests, and return the result to the
		// caller to do with as they please. This would let the reporter return
		// whatever is most appropriate (raw HTML, structured data, etc).
		//
		// Unfortunately, that's not how SimpleTest works. The run() method returns
		// a boolean value indicating whether the test suite ran, and the reporter
		// is expected to echo out its results to the buffer.
		//
		// We capture said buffer to prevent it from being echoed directly to the
		// screen, and return it to the caller.

		ob_start();
		$test_suite->run($reporter);
		$test_results = ob_get_clean();

		// Reinstate the real EE objects.
		$this->EE->config		= $real_config;
		$this->EE->cp			= $real_cp;
		$this->EE->db			= $real_db;
		$this->EE->extensions	= $real_extensions;
		$this->EE->functions	= $real_functions;
		$this->EE->input		= $real_input;
		$this->EE->lang			= $real_lang;
		$this->EE->load			= $real_loader;
		$this->EE->output		= $real_output;
		$this->EE->session		= $real_session;
		$this->EE->uri			= $real_uri;

		// The optional extras.
		if ($real_cp)			$this->EE->cp			= $real_cp;
		if ($real_dbforge)		$this->EE->dbforge		= $real_dbforge;
		if ($real_email)		$this->EE->email		= $real_email;
		if ($real_javascript)	$this->EE->javascript	= $real_javascript;
		if ($real_layout)		$this->EE->layout		= $real_layout;
		if ($real_table)		$this->EE->table		= $real_table;
		if ($real_template)		$this->EE->template		= $real_template;
		if ($real_tmpl)			$this->EE->TMPL			= $real_tmpl;
		if ($real_typography)	$this->EE->typography	= $real_typography;

		return $test_results;
	}
	//END run_tests


	// --------------------------------------------------------------------

	/**
	 * Returns the `theme` folder URL.
	 *
	 * @access  public
	 * @return  string
	 */

	public function get_theme_url()
	{
		if (defined('URL_THIRD_THEMES'))
		{
			$url = URL_THIRD_THEMES;
		}
		else
		{
			$url = (
				rtrim($this->EE->config->item('theme_folder_url'), '/') .
				'/third_party/'
			);
		}

		$url .= strtolower($this->get_package_name()) .'/';

		return $url;
	}
	//END get_theme_url


	// --------------------------------------------------------------------

	/**
	 * Get preferences from DB table
	 *
	 * @access	public
	 * @return	array	prefs or empty array
	 */

	public function get_prefs()
	{
		$prefs	= $this->EE->db
					->select('name, value')
					->where('site_id', $this->EE->config->item('site_id'))
					->get($this->prefs_table_name);

		if ($prefs->num_rows() > 0)
		{
			$results = $prefs->result_array();

			$out = array();

			//any JSON results?
			foreach ($results as $key => $row)
			{
				if (preg_match('/^(\[|\{)/', $row['value']))
				{
					$usi = $this->json_decode($row['value'], TRUE);

					if (is_array($usi))
					{
						$out[$row['name']] = $usi;
					}
				}

				if ( ! isset($out[$row['name']]))
				{
					$out[$row['name']] = $row['value'];
				}
			}

			return $out;
		}
		else
		{
			return array();
		}
	}
	//END get_prefs


	// --------------------------------------------------------------------

	/**
	 * Set Preferences to DB
	 *
	 * @access	public
	 * @param	array	$prefs	key/value array of prefs to be saved
	 */

	public function set_prefs($prefs)
	{
		$save = array();

		foreach ($prefs as $key => $value)
		{
			if (is_array($value) OR is_object($value))
			{
				$value = $this->json_encode($value);
			}
			else if ( ! is_string($value))
			{
				continue;
			}

			$save[] = array('name' => $key, 'value' => $value);
		}

		$this->EE->db->truncate($this->prefs_table_name);

		if ( ! empty($save))
		{
			$this->EE->db->insert_batch($this->prefs_table_name, $save);
		}
	}
	//END set_prefs


	// --------------------------------------------------------------------

	/**
	 * JSON Decode with fallback for PHP 5.1.x
	 *
	 * @access	public
	 * @param	string	$value	values to convert to real variables
	 * @param	boolean $assoc	make objects into associative arrays?
	 * @return	mixed			parsed JSON output
	 */

	public function json_decode($value, $assoc = FALSE)
	{
		if ( ! function_exists('json_decode'))
		{
			$this->EE->load->library('Services_json');
		}

		return json_decode($value, $assoc);
	}
	//END json_decode


	// --------------------------------------------------------------------

	/**
	 * JSON Encode with fallback for PHP 5.1.x
	 *
	 * @access	public
	 * @param	mixed	$value	values to convert to JSON string
	 * @return	string			JSON encoded values
	 */

	public function json_encode($value)
	{
		if (function_exists('json_encode'))
		{
			return json_encode($value);
		}
		//EE is too cool to use Services_json encode
		//so they removed it completely, and ask you
		//to rely on CI's generate_json, which assumes
		//any object you sent to it MUST be a DB
		//result array and nothing else.
		//This is very lame and I wont even unit test it.
		else
		{
			$this->EE->load->library('javascript');
			return $this->EE->javascript->generate_json($value);
		}
	}
	//END json_encode


	// --------------------------------------------------------------------
	//	EE Install/Update functions
	// --------------------------------------------------------------------

	// --------------------------------------------------------------------

	/**
	 * install prefs table
	 *
	 * @access	public
	 * @return	void
	 */

	public function install_prefs_table()
	{
		if ($this->EE->db->table_exists($this->prefs_table_name))
		{
			return FALSE;
		}

		$this->EE->load->dbforge();

		// Create the settings table
		$this->EE->dbforge->add_field('`id` int(10) unsigned NOT NULL AUTO_INCREMENT');
		$this->EE->dbforge->add_field('`site_id` int(10) NOT NULL DEFAULT 1');
		$this->EE->dbforge->add_field('`name` varchar(100) NOT NULL DEFAULT \'\'');
		$this->EE->dbforge->add_field('`value` text');
		$this->EE->dbforge->add_key('id', TRUE);
		$this->EE->dbforge->create_table($this->prefs_table_name, TRUE);

		return TRUE;
	}
	//END install_prefs_table


	// --------------------------------------------------------------------

	/**
	 * Installs the module.
	 *
	 * @access  public
	 * @return  bool
	 */
	public function install_module()
	{
		// Register the module.
		$this->EE->db->insert('modules', array(
			'has_cp_backend'		=> 'y',
			'has_publish_fields'	=> 'n',
			'module_name'			=> $this->get_package_name(),
			'module_version'		=> $this->get_package_version()
		));

		// Register the module actions.
		$this->EE->db->insert('actions',array(
			'class' => $this->get_package_name(),
			'method' => 'run_tests'
		));

		$this->install_prefs_table();

		return TRUE;
	}
	//END install_module


	// --------------------------------------------------------------------

	/**
	 * Uninstalls the module.
	 *
	 * @access  public
	 * @return  bool
	 */
	public function uninstall_module()
	{
		$db_module = $this->EE->db
			->select('module_id')
			->get_where('modules', array('module_name' => $this->get_package_name()));

		if ($db_module->num_rows() !== 1)
		{
			return FALSE;
		}

		$this->EE->db->delete('module_member_groups',
			array('module_id' => $db_module->row()->module_id));

		$this->EE->db->delete('modules',
			array('module_name' => $this->get_package_name()));

		$this->EE->db->delete('actions',
			array('class' => $this->get_package_name()));

		if ($this->EE->db->table_exists('testee_preferences'))
		{
			$this->EE->load->dbforge();
			$this->EE->dbforge->drop_table('testee_preferences');
		}

		return TRUE;
	}
	//END uninstall_module


	// --------------------------------------------------------------------

	/**
	 * Updates the module.
	 *
	 * @access  public
	 * @param   string    $installed_version    The installed version.
	 * @param   string    $package_version      The package version.
	 * @return  bool
	 */

	public function update_module($installed_version = '', $package_version = '')
	{
		if (version_compare($installed_version, $package_version, '>='))
		{
			return FALSE;
		}

		if (version_compare($installed_version, '2.2.0b1', '<'))
		{
			// Register the action.
			$this->EE->db->insert('actions', array(
				'class' => $this->get_package_name(),
				'method' => 'run_tests'
			));
		}

		// prefs table
		$this->install_prefs_table();

		return TRUE;
	}
	//END update_module


	// --------------------------------------------------------------------
	//	PRIVATE METHODS
	// --------------------------------------------------------------------

	// --------------------------------------------------------------------

	/**
	 * Returns the contents of a directory.
	 *
	 * @access  private
	 * @param   string    $dir_path     The directory to examine.
	 * @param   string    $item_type    'DIRECTORY' or 'FILE'.
	 * @return  void
	 */
	private function _get_directory_contents($dir_path = '',
		$item_type = 'DIRECTORY'
	)
	{
		$return     = array();
		$item_type  = strtoupper($item_type);

		if ($dir_handle = @opendir($dir_path))
		{
			$dir_path = rtrim(realpath($dir_path), DIRECTORY_SEPARATOR)
				.DIRECTORY_SEPARATOR;

			while (($dir_item = readdir($dir_handle)) !== FALSE)
			{
				// Ignore any hidden files or directories.
				if (substr($dir_item, 0, 1) == '.')
				{
					continue;
				}

				switch ($item_type)
				{
					case 'DIRECTORY':
						if (is_dir($dir_path .$dir_item))
						{
							$return[] = $dir_item;
						}
						break;

					case 'FILE':
						if (is_file($dir_path .$dir_item))
						{
							$return[] = $dir_item;
						}
						break;

					default:
						continue;
						break;
				}
			}
		}

		return $return;
	}
	//END _get_directory_contents
}
/* End of file      : testee_model.php */
/* File location    : third_party/testee/models/testee_model.php */