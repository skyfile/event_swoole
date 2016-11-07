<?php
namespace Sys;

/**
* 方法接口
*/
class Api
{
	const GET_OK = 200;
	const GET_CREATED = 201;
	const GET_ACCEPTED = 202;
	const GET_NO_CENTENT = 204;

	public $restFull;

	public function __contruct()
	{
		$this->restFull = \Sys::$obj->Restfull;
	}

	public function error($message = '', $code = 400)
	{
		return [
			'code'	=> $code,
			'error' => $message
		];
	}

	public function success($data = '')
	{
		return [
			'code'	=> 200,
			'data'	=> $data
		];
	}
}