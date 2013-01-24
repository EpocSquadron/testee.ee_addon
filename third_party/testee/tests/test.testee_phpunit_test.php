<?php

class Testee_phpunit_test extends Testee_phpunit_test_case
{
	public function test__db__mock_methods_return_self()
	{
		$this->assertEquals(
			'Testee_phpunit_test_mock_db',
			get_class($this->EE->db->select()),
			'Mocked DB functions should return themselves.'
		);
	}

	public function test__db__getting_fresh_methods_works()
	{
		$tester = $this->getMockByName('db', true);

		$this->assertEquals(
			'Testee_phpunit_test_mock_db',
			get_class($tester->select()),
			'Mocked DB functions should return themselves.'
		);
	}

	public function test__fail__on_purpose()
	{
		$this->assertTrue(FALSE, 'This is failing on purpose.');
	}
}