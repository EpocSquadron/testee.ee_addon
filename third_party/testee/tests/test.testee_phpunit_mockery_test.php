<?php

class Testee_phpunit_mockery_test extends Testee_phpunit_test_case
{
	public function setUp()
	{
		//use mockery
		parent::setUp(true);
	}

	public function test__db__mock_methods_return_self()
	{
		$this->assertEquals(
			get_class($this->EE->db),
			get_class($this->EE->db->select()),
			'Mocked DB functions should return themselves.'
		);
	}

	public function test__db__getting_fresh_mocks_work()
	{
		$tester = $this->getMockByName('db', true);

		$result = get_class($tester->select());
		$this->assertTrue(
			!!stristr($result, 'mockery'),
			'New mocks should be of class Mockery. Recieved: ' . $result
		);
	}
}