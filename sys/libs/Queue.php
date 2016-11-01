<?php
namespace Sys\libs;

/**
* 队列
*/
class Queue
{
	protected $_factory_key;
    protected $key = 'event:queue';

	function __construct($config)
	{
		if (empty($config['id'])) {
			$config['id'] = 'master';
		}
		$this->_factory_key = $config['id'];
		if (!empty($config['key'])) {
			$this->$key = $config['key'];
		}
	}

	/**
     * 出队
     * @return bool|mixed
     */
    function pop()
    {
        $ret = \Sys::$obj->redis($this->_factory_key)->lPop($this->key);
        if ($ret) {
            return unserialize($ret);
        } else {
            return false;
        }
    }

    /**
     * 入队
     * @param $data
     * @return int
     */
    function push($data)
    {
        return \Sys::$obj->redis($this->_factory_key)->lPush($this->key, serialize($data));
    }


}