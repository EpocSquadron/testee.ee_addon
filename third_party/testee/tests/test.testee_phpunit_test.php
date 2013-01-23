<?php

class Testee_phpunit_test extends Testee_phpunit_test_case
{
	public function testMessage()
	{
		$this->assertTrue(FALSE, 'This is failing on purpose.');
		$this->assertEquals(get_class($this->EE->db->select()), 'Mod_freeform_phpunit_mock_db', 'should be equal');
	}
}