<?php

class Testee_mockery_contaier extends \Mockery\Container
{
	//for one function? Yes. The version of CI
	//that EE uses still uses old PHP 4 required
	//constructors that are the same as the
	//classname and not __construct *SIGH*
	protected function _getInstance($mockName)
	{
		if (!method_exists($mockName, '__construct') AND
			//added
			!method_exists($mockName, $mockName))
		{
			$return = new $mockName;
			return $return;
		}
		$return = unserialize(sprintf('O:%d:"%s":0:{}', strlen($mockName), $mockName));
		return $return;
	}
}