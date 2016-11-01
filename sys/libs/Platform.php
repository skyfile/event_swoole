<?php
namespace Sys\libs;
/**
* 平台处理
*/
class Platform
{
	public $platform;
	function __construct()
	{
		$this->platform = PHP_OS == 'WINNT' ? 'windows' : 'linux';
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
