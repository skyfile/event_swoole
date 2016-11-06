<?php
namespace Api\Index\V1;

/**
* 取出资源
*/
class Get extends \Api\Base
{

	public function index()
	{
		$this->error();
		return 'this is working!!!';
	}
}