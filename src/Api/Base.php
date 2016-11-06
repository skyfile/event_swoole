<?php
namespace Api;

/**
* 方法基础类
*/
class Base
{
	const GET_OK = 200;
	const GET_CREATED = 201;
	const GET_ACCEPTED = 202;
	const GET_NO_CENTENT = 204;

	public function error($message = '', $code = 400)
	{

	}
}