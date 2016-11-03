<?php
namespace Sys;
/**
* 平台处理
*/
class Platform
{
    static $obj;
	public $platform;

	private function __construct()
	{
		$this->platform = PHP_OS == 'WINNT' ? 'windows' : 'linux';
	}

    public function getInstance($key = '')
    {
        if (!self::$obj) {
            self::$obj = new self();
        }
        return self::$obj;
    }

	public function kill($pid, $signo)
    {
    	if ($this->platform == 'windows') return false;
        return posix_kill($pid, $signo);
    }

    public function fork()
    {
    	if ($this->platform == 'windows') return false;
        return pcntl_fork();
    }

}
