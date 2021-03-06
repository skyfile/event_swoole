<?php
/**
 * 基础类
 */
class Sys
{
    public static $obj;
    protected static $namespaces;
    public static $models;

    public $config;
    public $factorys;
    public $factory_key;

    private function __construct()
    {
        $this->config = new Sys\Config;
        $this->config->setPath(CONF_PATH . CURRENV);
    }

    /**
     * 初始化
     * @return Swoole
     */
    public static function getInstance()
    {
        if (!self::$obj) {
            self::$obj = new self;
        }
        return self::$obj;
    }

    /**
     * 获取模型
     * @param  [type] $modelName [description]
     * @return [type]            [description]
     */
    public function getModel($modelName, $ckey = 'master')
    {
        $modelName = \Sys\Tool::toCamelCase($modelName);
        if (!isset(self::$models[$modelName])) {
            $model = '\\Model\\' . $modelName;
            if (class_exists($model)) {
                $class = new $model('', $ckey);
            } else {
                $class = new \Sys\Model($modelName, $ckey);
            }
            self::$models[$modelName] = $class;
        }
        return self::$models[$modelName];
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
            throw new \Exception('module name cannot be null.');
        }
        return $this->loadModule($func, $param[0]);
    }

    /**
     * 属性重载
     */
    public function __get($lib_name)
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
        $func  = \Sys\Tool::toCamelCase($func);
        $index = $func . '_' . $key;
        if (empty($this->factorys[$index])) {
            $this->factory_key = $key;
            //优先加载自定义方法
            $class = '\\Class\\' . $func;
            if (!class_exists($class)) {
                //默认方法
                $class = '\\Sys\\' . $func;
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
    public static function autoload($class)
    {
        $root = explode('\\', trim($class, '\\'), 2);
        if (count($root) > 1 && isset(self::$namespaces[$root[0]])) {
            $fileName = self::$namespaces[$root[0]] . str_replace('\\', '/', $root[1]) . '.php';
            if (is_file($fileName)) {
                include $fileName;
            }
        }
    }

    /**
     * 设置根命名空间
     * @param $root
     * @param $path
     */
    public static function addNameSpace($root, $path)
    {
        self::$namespaces[$root] = $path;
    }

    /**
     * 设置进程的名称
     * @param $name
     */
    public static function setProcessName($name)
    {
        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($name);
        } elseif (function_exists('swoole_set_process_name')) {
            swoole_set_process_name($name);
        } else {
            trigger_error(__METHOD__ . ' failed. require cli_set_process_title or swoole_set_process_name.');
        }
    }
}
