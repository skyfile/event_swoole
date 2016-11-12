<?php
namespace Sys;

/**
 * redis
 */
class Redis
{
    static $obj;
    public $_redis;
    public $config;

    public static $prefix = 'autoinc_key:';

    private function __construct($config)
    {
        if (!isset($config['port']) || empty($config['port'])) {
            $config['port'] = 6379;
        }
        if (!isset($config['pconnect']) || empty($config['pconnect'])) {
            $config['pconnect'] = false;
        }
        if (!isset($config['timeout']) || empty($config['timeout'])) {
            $config['timeout'] = 0.5;
        }
        $this->config = $config;
        $this->connect();
    }

    public static function getInstance($key)
    {
        if (!self::$obj) {
            $config = \Sys::$obj->config['redis'];
            if (!isset($config[$key]) || empty($config[$key])) {
                throw new \Exception("redis->{$key} config is not fund.");
            }
            self::$obj = new self($config[$key]);
        }
        return self::$obj;
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

        } catch (\Exception $e) {
            \Sys::$obj->log->error(__CLASS__ . ' Swoole Redis Exception' . var_export($e->getMessage(), 1));
            return false;
        }
    }

    /**
     * 获取自增ID
     * @param $appKey
     * @param int $init_id
     * @return bool|int
     */
    public static function getIncreaseId($appKey, $init_id = 1)
    {
        if (empty($appKey)) {
            return false;
        }
        $main_key = self::$prefix . $appKey;
        //已存在 就加1
        if (\Sys::$obj->redis->exists($main_key)) {
            $inc = \Sys::$obj->redis->incr($main_key);
            if (empty($inc)) {
                \Sys::$obj->log->put('redis::incr() failed. Error: ' . \Sys::$obj->redis->getLastError());
                return false;
            }
            return $inc;
        }
        //上面的if条件返回false,可能是有错误，或者key不存在，这里要判断下
        elseif (\Sys::$obj->redis->getLastError()) {
            return false;
        }
        //这才是说明key不存在，需要初始化
        else {
            $init = \Sys::$obj->redis->set($main_key, $init_id);
            if ($init == false) {
                \Sys::$obj->log->put('redis::set() failed. Error: ' . \Sys::$obj->redis->getLastError());
                return false;
            } else {
                return $init_id;
            }
        }
    }

    public function __call($method, $args = [])
    {
        $reConnect = false;
        while (1) {
            try
            {
                $result = call_user_func_array([$this->_redis, $method], $args);
            } catch (\RedisException $e) {
                //已重连过，仍然报错
                if ($reConnect) {
                    throw $e;
                }

                \Sys::$obj->log->error(__CLASS__ . ' [' . posix_getpid() . "] Swoole Redis[{$this->config['host']}:{$this->config['port']}]
                 Exception(Msg=" . $e->getMessage() . ', Code=' . $e->getCode() . "), Redis->{$method}, Params=" . var_export($args, 1));
                if ($this->_redis->isConnected()) {
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
