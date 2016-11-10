<?php
namespace Sys;

/**
 * 日志类
 */
class Log
{
    static $obj;
    public $log;

    private function __construct($config)
    {
        if (!isset($config['type'])) {
            $config['type'] = 'FileLog';
        }
        $class     = '\\Sys\\Log\\' . ucfirst($config['type']);
        $this->log = new $class($config);
    }

    public static function getInstance($key = 'master')
    {
        if (!self::$obj) {
            $config = \Sys::$obj->config['log'];
            if (!isset($config[$key]) || empty($config[$key])) {
                trigger_error("log->{$key} config is not fund.");
            }
            self::$obj = new self($config[$key]);
        }
        return self::$obj;
    }

    public function __call($func, $params)
    {
        return call_user_func_array([$this->log, $func], $params);
    }
}
