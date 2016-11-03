<?php
/**
* 基础类
*/
class Sys
{

    static public $obj;
    static public $base_path;
    static protected $namespaces;

    public $config;
    public $factorys;
    public $factory_key;

    private function __construct()
    {
        if (defined('BASE_PATH')) {
            self::$base_path = BASE_PATH;
        } else {
            throw new \Exception(__CLASS__ . "WEBPATH empty.");
        }
        $this->config = new Sys\Config;
        $this->config->setPath(CONF_PATH. CURRENV);
    }

    /**
     * 初始化
     * @return Swoole
     */
    static function getInstance()
    {
        if (!self::$obj) {
            self::$obj = new self;
        }
        return self::$obj;
    }

    /**
     * 方法重载
     * @param  [type] $func  [description]
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function __call($func, $param)
    {
        if (empty($param[0]) || !is_string($param[0])) {
            throw new \Exception("module name cannot be null.");
        }
        return $this->loadModule($func, $param[0]);
    }

    /**
     * 属性重载
     */
    function __get($lib_name)
    {
        //如果不存在此对象，从工厂中创建一个
        if (empty($this->$lib_name)) {
            //载入组件
            $this->$lib_name = $this->loadModule($lib_name);
        }
        return $this->$lib_name;
    }

    /**
     * 加载模块
     * @param  [type] $func  [description]
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function loadModule($func, $key = 'master')
    {
        $func = \Sys\Tool::toCamelCase($func);
        $index = $func. '_'. $key;
        if (empty($this->factorys[$index])) {
            $this->factory_key = $key;
            //优先加载自定义方法
            $class = '\\Class\\'.$func;
            if (!class_exists($class)) {
                //默认方法
                $class = '\\Sys\\'.$func;
                if (!class_exists($class)) {
                    trigger_error("function [$func] not found.");
                }
            }
            $this->factorys[$index] = $class::getInstance($key);
        }
        return $this->factorys[$index];
    }

    /**
     * 自动载入类
     * @param $class
     */
    static function autoload($class)
    {
        $root = explode('\\', trim($class, '\\'), 2);
        if (count($root) > 1 and isset(self::$namespaces[$root[0]])) {
            include self::$namespaces[$root[0]].str_replace('\\', '/', $root[1]).'.php';
        }
    }

    /**
     * 设置根命名空间
     * @param $root
     * @param $path
     */
    static function addNameSpace($root, $path)
    {
        self::$namespaces[$root] = $path;
    }

    /**
     * 设置进程的名称
     * @param $name
     */
    static function setProcessName($name)
    {
        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($name);
        } elseif (function_exists('swoole_set_process_name')) {
            swoole_set_process_name($name);
        } else {
            trigger_error(__METHOD__ . " failed. require cli_set_process_title or swoole_set_process_name.");
        }
    }
}
