<?php
namespace Sys\Cache;

/**
 * redis缓存类
 */
class RedisCache
{
    public $redis;
    public function __construct($ckey = 'master')
    {
        $this->redis = \Sys::$obj->Redis($ckey);
    }

    /**
     * 设置缓存
     * @param $key
     * @param $value
     * @param $expire
     * @return bool
     */
    public function set($key, $value, $expire = 0)
    {
        if ($expire <= 0) {
            $expire = 0x7fffffff;
        }
        return $this->redis->setex($key, $expire, serialize($value));
    }

    /**
     * 获取缓存值
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return unserialize($this->redis->get($key));
    }

    /**
     * 删除缓存值
     * @param $key
     * @return bool
     */
    public function delete($key)
    {
        return $this->redis->del($key);
    }
}
