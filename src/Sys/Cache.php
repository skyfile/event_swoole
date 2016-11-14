<?php
namespace Sys;

/**
 * 缓存类
 */
class Cache
{
    static $obj;

    private function __construct($type) {}

    /**
     * 获取实例
     * @param  [type] $type      [description]
     * @param  string $configKey [description]
     * @return [type]            [description]
     */
    public static function getInstance($type, $cKey = 'master')
    {
        $index = $type . '_' . $cKey;
        if (!isset(self::$obj[$index])) {
            $config = \Sys::$obj->config[$type];
            if (!isset($config[$cKey]) || !$config[$cKey]) {
                throw new \Exception("Config not found Cache By $type");
            }
            $class             = '\\Sys\\Cache\\' . ucfirst(strtolower($type)) . 'Cache';
            self::$obj[$index] = new $class($cKey);
        }
        return self::$obj[$index];
    }
}
