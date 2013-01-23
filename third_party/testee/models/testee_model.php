<?php if ( ! defined('BASEPATH')) exit('Invalid file request');

/**
 * Test-driven add-on development module model.
 *
 * @author      Stephen Lewis (http://github.com/experience/)
 * @copyright   Experience Internet
 * @package     Testee
 */

require_once realpath(dirname(__FILE__) . '/../config.php');
require_once realpath(dirname(__FILE__) . '/../classes/simpletest/testee_addon.php');
require_once realpath(dirname(__FILE__) . '/../classes/simpletest/testee_unit_test_case.php');
require_once realpath(dirname(__FILE__) . '/../classes/phpunit/testee_phpunit_test_case.php');
require_once realpath(dirname(__FILE__) . '/../vendor/autoload.php');

class Testee_model extends CI_Model
{
	private $EE;
	private $_package_name;
	private $_package_version;

	private $prefs_table_name = 'testee_preferences';

	protected $test_type = 'simpletest';

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
	 * @param	string	$type	specifies the type of test to run
	 * @return  array
	 */

	public function get_tests($type = 'php', $suite = 'simpletest')
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
				if ( ! preg_match($test_pattern, $test, $matches))
				{
					continue;
				}

				$file_path = $test_dir_path . '/' . $test;

				$has_tests = FALSE;

				//JS gets a pass here
				if ($type == 'js')
				{
					$addon_tests[] = new Testee_test(array(
						'file_name' => $test,
						'file_path' => $file_path
					));

					continue;
				}

				// -------------------------------------
				//	test type?
				// -------------------------------------

				if ($suite == 'simpletest')
				{
					$loader = new SimpleFileLoader();
					$result = $loader->load($file_path);
					$has_tests = ! ( $result instanceof BadTestSuite);
				}
				else
				{
					$loader = new PHPUnit_Framework_TestSuite();
					$loader->addTestFile($file_path);
					$has_tests = ($loader->count() > 0);
				}

				if ($has_tests)
				{
					$addon_tests[] = new Testee_test(array(
						'file_name' => $test,
						'file_path' => $file_path
					));
				}
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