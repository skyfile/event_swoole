<?php
namespace Sys\libs;
/**
* redis
*/
class Redis
{
	public $_redis;
    public $config;

    public static $prefix = "autoinc_key:";

	function __construct($config)
	{
		$this->config = $config;
        $this->connect();
	}

	public function connect()
	{
		try {
			if ($this->_redis) {
				unset($this->_redis);
			}
			$this->_redis = new \Redis();
			if ($this->config['pconnect']) {
				$this->_redis->pconnect($this->config['host'], $this->config['port'], $this->config['timeout']);
			} else {
				$this->_redis->connect($this->config['host'], $this->config['port'], $this->config['timeout']);
			}

			if (!empty($this->config['password'])) {
				$this->_redis->auth($this->config['password']);
			}

			if (!empty($this->config['database'])) {
				$this->_redis->select($this->config['database']);
			}

		} catch (\RedisException $e) {
			\Sys::$obj->log->error(__CLASS__ . " Swoole Redis Exception" . var_export($e, 1));
            return false;
		}
	}

	/**
     * 获取自增ID
     * @param $appKey
     * @param int $init_id
     * @return bool|int
     */
    static function getIncreaseId($appKey, $init_id = 1)
    {
        if (empty($appKey))
        {
            return false;
        }
        $main_key = self::$prefix . $appKey;
        //已存在 就加1
        if (\Sys::$obj->redis->exists($main_key)) {
            $inc = \Sys::$obj->redis->incr($main_key);
            if (empty($inc)) {
                \Sys::$obj->log->put("redis::incr() failed. Error: ".\Sys::$obj->redis->getLastError());
                return false;
            }
            return $inc;
        }
        //上面的if条件返回false,可能是有错误，或者key不存在，这里要判断下
        else if(\Sys::$obj->redis->getLastError()) {
            return false;
        }
        //这才是说明key不存在，需要初始化
        else {
            $init = \Sys::$obj->redis->set($main_key, $init_id);
            if ($init == false) {
                \Sys::$obj->log->put("redis::set() failed. Error: ".\Sys::$obj->redis->getLastError());
                return false;
            } else {
                return $init_id;
            }
        }
    }

    function __call($method, $args = [])
    {
        $reConnect = false;
        while (1)
        {
            try
            {
                $result = call_user_func_array(array($this->_redis, $method), $args);
            }
            catch (\RedisException $e)
            {
                //已重连过，仍然报错
                if ($reConnect)
                {
                    throw $e;
                }

                \Sys::$obj->log->error(__CLASS__ . " [" . posix_getpid() . "] Swoole Redis[{$this->config['host']}:{$this->config['port']}]
                 Exception(Msg=" . $e->getMessage() . ", Code=" . $e->getCode() . "), Redis->{$method}, Params=" . var_export($args, 1));
                if ($this->_redis->isConnected())
                {
                    $this->_redis->close();
                }
                $this->connect();
                $reConnect = true;
                continue;
            }
            return $result;
        }
    }

}
