<?php

/**
 * Records the results of a Testee test suite.
 *
 * @author    Stephen Lewis
 * @copyright Experience Internet
 * @package   Testee
 */

require_once rtrim(realpath(dirname(__FILE__) . '/../../'), '/') . '/vendor/autoload.php';

class Testee_phpunit_reporter extends PHPUnit_TextUI_ResultPrinter
{
	protected function printHeader()
	{
		echo "<pre>";
		parent::printHeader();
	}

	/**
	 * Paints the report footer.
	 *
	 * @access  public
	 * @param string    $test_name    The test name.
	 * @return  void
	 */
	public function printFooter(PHPUnit_Framework_TestResult $result)
	{
		parent::printFooter($result);
		echo '</pre>';
	}
}
/* End of file    : testee_phpunit_reporter.php */
/* File location  : third_party/testee/classes/phpunit/testee_phpunit_reporter.php */