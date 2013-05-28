<?php if ( ! defined('BASEPATH')) exit('Invalid file request');

/**
 * Testee module control panel.
 *
 * @author      Stephen Lewis
 * @copyright   Experience Internet
 * @package     Testee
 */

require_once 'config.php';

class Testee_mcp
{
	protected $_base_qs;
	protected $_base_url;
	protected $_model;
	protected $EE;

	// --------------------------------------------------------------------
	//	PUBLIC METHODS
	// --------------------------------------------------------------------

	/**
	 * Constructor.
	 *
	 * @access  public
	 * @param   bool    $switch   My whole life I don't know what this song means.
	 * @return  void
	 */
	public function __construct($switch = TRUE)
	{
		$this->EE =& get_instance();
		$this->EE->load->model('testee_model');
		$this->EE->load->helper('form');

		$this->_model =& $this->EE->testee_model;

		$this->_base_qs = 'C=addons_modules' .AMP .'M=show_module_cp'
			.AMP .'module=testee';

		$this->_base_url = BASE .AMP .$this->_base_qs;

		$this->EE->cp->set_breadcrumb($this->_base_url,
			$this->EE->lang->line('testee_module_name'));

		// Retrieve the theme folder URL.
		$theme_url = $this->_model->get_theme_url();

		// Include the custom CSS and JS on all pages.
		$this->EE->cp->add_to_head('<link rel="stylesheet" href="'
			.$theme_url .'css/cp.css?' . TESTEE_VERSION . '" />');

		$this->EE->cp->add_to_foot('<script src="'
			.$theme_url .'js/cp.js?' . TESTEE_VERSION . '"></script>');

		$this->EE->javascript->compile();

		$this->docs_url = $this->EE->cp->masked_url(
			'https://github.com/experience/testee.ee_addon/wiki'
		);

		$this->module_menu = array(
			'index' => array(
				'name'			=> 'index',
				'link'			=> $this->_base_url,
				'title'			=> lang('simpletest_php_tests_title')
			),
			'phpunit_tests' => array(
				'name'			=> 'phpunit_tests',
				'link'			=> $this->_base_url . AMP . 'method=phpunit_tests',
				'title'			=> lang('phpunit_php_tests_title')
			),
			'js_tests' => array(
				'name'			=> 'js_tests',
				'link'			=> $this->_base_url . AMP . 'method=js_tests',
				'title'			=> lang('js_tests_title')
			),
			'prefs' => array(
				'name'			=> 'prefs',
				'link'			=> $this->_base_url . AMP . 'method=prefs',
				'title'			=> lang('preferences')
			),
			'docs' => array(
				'name'			=> 'docs',
				'link'			=> $this->docs_url,
				'title'			=> lang('documentation'),
				'new_window'	=> TRUE
			)
		);

		//phpunit min version
		if (version_compare(PHP_VERSION, '5.3.2', '<'))
		{
			unset($this->module_menu['phpunit_tests']);
		}
	}
	//END __construct


	protected function view($function, $view, $view_vars)
	{
		$vars = array(
			'module_menu'			=> $this->module_menu,
			'module_menu_highlight' => $function
		);

		if ( ! isset($view_vars['cp_page_title']))
		{
			$view_vars['cp_page_title']	= lang('testee_module_name') . ': ' .
									$this->module_menu[$function]['title'];
		}
		else if (substr($view_vars['cp_page_title'], 0, strlen(lang('testee_module_name')) + 1) != lang('testee_module_name') . ':')
		{
			$view_vars['cp_page_title']	= lang('testee_module_name') . ': ' .
											$view_vars['cp_page_title'];
		}

		$header	= $this->EE->load->view('header.html', $vars, TRUE);
		$page	= $this->EE->load->view($view, $view_vars, TRUE);

		return $header . $page;
	}


	// --------------------------------------------------------------------

	/**
	 * Displays the default module control panel page.
	 *
	 * @access  public
	 * @return  string
	 */

	public function index( $test_type = 'simpletest', $highlight = __FUNCTION__)
	{
		$this->EE->load->library('table');

		$action_url = $this->EE->functions->fetch_site_index()
			.'?ACT=' .$this->EE->cp->fetch_action_id(
					$this->_model->get_package_name(), 'run_tests')
			.'&addon={addon_name}';

		$vars = array(
			'action_url'	=> $action_url,
			'form_action'	=> $this->_base_qs . AMP .'method=run_test' .
												AMP . 'test_type=' . $test_type,
			'docs_url'		=> $this->docs_url,
			'tests'			=> $this->_model->get_tests('php', $test_type)
		);

		return $this->view($highlight, 'tests_index', $vars);
	}
	//END index


	// --------------------------------------------------------------------

	/**
	 * Displays the default module control panel page.
	 *
	 * @access  public
	 * @return  string
	 */

	public function phpunit_tests()
	{
		return $this->index('phpunit', 'phpunit_tests');
	}
	//END phpunit_tests


	// --------------------------------------------------------------------

	/**
	 * Displays the default module control panel page.
	 *
	 * @access  public
	 * @return  string
	 */

	public function js_tests()
	{
		$this->EE->load->library('table');

		$vars = array(
			'form_action'	=> $this->_base_qs . AMP .'method=run_qunit_test',
			'docs_url'		=> $this->docs_url,
			'tests'			=> $this->_model->get_tests('js')
		);

		return $this->view(__FUNCTION__, 'js_tests_index', $vars);
	}
	//END index


	// --------------------------------------------------------------------

	/**
	 * Displays the module prefs page.
	 *
	 * @access  public
	 * @return  string
	 */

	public function prefs()
	{
		$prefs = $this->_model->get_prefs();

		// -------------------------------------
		//	build addon list
		// -------------------------------------

		$all_addons = $this->_model->get_directory_names(PATH_THIRD);
		$all_addons = array_combine($all_addons, $all_addons);

		// Not all addons will be installed
		// so not going to trust the EE addon model and will
		// just make names based on folders
		foreach ($all_addons as $key => $value)
		{
			$all_addons[$key] = ucwords(str_replace('_', ' ', $value));
		}

		$vars = array(
			'all_addons'	=> $all_addons,
			'form_action'	=> $this->_base_qs .AMP .'method=save_prefs',
			'prefs'			=> $prefs
		);

		return $this->view(__FUNCTION__, 'prefs.html', $vars);
	}
	//END prefs


	// --------------------------------------------------------------------

	/**
	 * save prefs to table
	 *
	 * @access	public
	 * @return	void
	 */

	public function save_prefs()
	{
		$all_addons = $this->_model->get_directory_names(PATH_THIRD);

		$prefs = array();

		//no funny business
		if (empty($_POST))
		{
			return $this->EE->functions->redirect($this->module_menu['prefs']['link']);
		}

		foreach ($_POST as $key => $value)
		{
			if (preg_match('/^test_location_addon_([0-9]+)/i', $key, $matches))
			{
				$item_num = $matches[1];
				$loc = 'test_location_location_' . $item_num;

				if (
					in_array($value, $all_addons) AND
					! empty($_POST[$loc])
				)
				{
					$prefs['test_location_' . $value] = $_POST[$loc];
				}
			}
		}

		// -------------------------------------
		//	do something here if there are ever
		//	any more prefs
		// -------------------------------------

		$this->_model->set_prefs($prefs);

		//go back to prefs
		$this->EE->functions->redirect($this->module_menu['prefs']['link']);
	}
	//END save_prefs


	// --------------------------------------------------------------------

	/**
	 * Handles a 'run_test' request.
	 *
	 * @access  public
	 * @return  void
	 */

	public function run_test()
	{
		$test_path = $this->EE->input->post('tests') OR ! is_array($test_path);
		$test_type = $this->EE->input->get_post('test_type');

		if ($test_type != 'phpunit')
		{
			$test_type = 'simpletest';
		}

		$this->EE->load->library('TesteeSuiteRunner');
		$this->EE->testeesuiterunner->setTestType($test_type);

		if ($test_path === FALSE)
		{
			$this->EE->functions->redirect($this->_base_url);
			return;
		}

		require_once PATH_THIRD . 'testee/classes/phpunit/testee_phpunit_reporter.php';

		try
		{
			$test_results = $this->EE->testeesuiterunner->runTests(
				$test_path,
				new Testee_phpunit_reporter()
			);
		}
		catch (Exception $e)
		{
			$this->EE->functions->redirect($this->_base_url);
			return;
		}

		$vars = array(
			'form_action'		=> $this->_base_qs .AMP .'method=run_test' .
													AMP . 'test_type=' . $test_type,
			'tests_index_url'	=> $this->_base_url . ((
										$test_type == 'phpunit'
									) ? AMP . 'method=phpunit_tests' : ''),
			'cp_page_title'		=> lang('testee_php_test_results'),
			'results'			=> $test_results,
			'tests'				=> $test_path
		);

		return $this->view(
			($test_type == 'simpletest') ? 'index' : 'phpunit_tests',
			'test_results',
			$vars
		);
	}
	//END run_test

	// --------------------------------------------------------------------

	/**
	 * Run QUnit Test.
	 *
	 * @access  public
	 * @return  string	html for test results
	 */

	public function run_qunit_test()
	{
		$tests = $this->EE->input->post('tests');

		$ok_to_run = TRUE;

		if ($tests === FALSE OR ! is_array($tests))
		{
			$ok_to_run = FALSE;
		}

		if ($ok_to_run)
		{
			$all_addons = $this->_model->get_directory_names(PATH_THIRD);

			foreach ($tests as $addon_name => $test_array)
			{
				//make sure tests exist
				if ( ! in_array($addon_name, $all_addons) OR
					 empty($tests[$addon_name]))
				{
					unset($tests[$addon_name]);
				}
				else
				{
					//are all files legit?
					foreach ($test_array as $test_key => $file_location)
					{
						if ( ! file_exists($file_location))
						{
							unset($tests[$addon_name][$test_key]);
						}
					}

					//did our cleanout fail anything?
					if (empty($tests[$addon_name]))
					{
						unset($tests[$addon_name]);
					}
				}
			}

			//tests is empty after the final cleanup?
			if (empty($tests))
			{
				$ok_to_run = FALSE;
			}
		}

		if ( ! $ok_to_run)
		{
			return $this->EE->functions->redirect(
				$this->module_menu['js_tests']['link']
			);
		}

		$vars = array(
			'qunit_results_url' => $this->_base_url .AMP .'method=qunit_test_frame',
			'cp_page_title'		=> lang('testee_js_test_results'),
			'tests'				=> $tests,
			'tests_index_url'	=> $this->module_menu['js_tests']['link'],
		);

		return $this->view('js_tests', 'qunit_results', $vars);
	}
	//END run_qunit_test


	public function qunit_test_frame()
	{
		$addon				= $this->EE->input->get_post('addon');
		$test_file			= $this->EE->input->get_post('file');
		$testee_url			= $this->_model->get_theme_url();
		$themes_url			= rtrim($this->EE->config->item('theme_folder_url'), '/') . '/';
		$js_src				= $this->EE->config->item('use_compressed_js') ? 'src' : 'compressed';
		$addon_name			= ucfirst(str_replace('_', ' ', $addon));
		$addon_short_name	= $addon;

		$path = realpath(
			rtrim(dirname(__FILE__), '/') . '/tests/' . $test_file
		);

		$vars = array(
			'testee_themes_url'	=> $testee_url,
			'themes_url'		=> $themes_url,
			'third_themes_url'	=> preg_replace('/testee\/$/i', '', $testee_url),
			'test_js'			=> file_get_contents($test_file),
			'addon_name'		=> $addon_name ,
			'addon_short_name'	=> $addon_short_name,
			'test_name'			=> end(explode(DIRECTORY_SEPARATOR, $test_file)),
			'ee_js_url'			=> $themes_url . 'javascript/' . $js_src . '/',
			'ee_pkg_js_base'	=> str_replace('&amp;', '&', BASE).'&C=javascript&M=load&package='.$addon_short_name.'&file=',
			'ee_pkg_css_base'	=> str_replace('&amp;', '&', BASE).'&C=css&M=third_party&package='.$addon_short_name.'&file='
		);

		exit($this->EE->load->view('qunit_runner', $vars, TRUE));
	}
}
//END Testee_mcp

/* End of file    : mcp.testee.php */
/* File location  : third_party/testee/mcp.testee.php */
