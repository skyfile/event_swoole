<?php
namespace Sys;

/**
* 队列
*/
class Queue
{
    static $obj;
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

    public function getInstance($key = 'master')
    {
        if (!self::$obj) {
            $config = \Sys::$obj->config['queue'];
            if (!isset($config[$key]) || empty($config[$key])) {
                $config[$key] = [];
            }
            self::$obj = new self($config[$key]);
        }
        return self::$obj;
    }

    /**
     * 检查服务
     * @return [type] [description]
     */
    public function ping()
    {
        if (\Sys::$obj->redis($this->_factory_key)->ping() == '+PONG') {
            return true;
        }
        return false;
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
