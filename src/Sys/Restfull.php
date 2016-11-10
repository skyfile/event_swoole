<?php
namespace Sys;

/**
 * Restfull 路由类
 */
class Restfull
{
    static $obj;

    public $error;

    public $version;  //版本
    public $protocol; //执行协议
    public $module;   //模块
    public $action;   //方法
    public $params;   //其余参数
    public $allPro = ['Get', 'Post', 'Put', 'Patch', 'Delete', 'Options'];

    public function __construct()
    {
        $this->init();
    }

    public static function getInstance($key = 'master')
    {
        if (!self::$obj) {
            self::$obj = new self();
        }
        return self::$obj;
    }

    public function init()
    {
        $urlPath = \Sys::$obj->Request->getPath();
        $count   = count($urlPath);
        switch ($count) {
            case 0:
                $urlPath = ['V1', 'get', 'index', 'index'];
                break;
            case 1:
                $urlPath = ['V1', 'get', $urlPath[0], 'index'];
                break;
            case 2:
                $urlPath = ['V1', 'get', $urlPath[0], $urlPath[1]];
                break;
            case 3:
                $urlPath = [$urlPath[0], 'get', $urlPath[1], $urlPath[2]];
                break;
            default:
                break;
        }
        $this->params   = array_slice($urlPath, 4);
        $urlPath        = array_map('\\Sys\\Tool::toCamelCase', $urlPath);
        $this->version  = $urlPath[0];
        $this->protocol = $urlPath[1];
        $this->module   = $urlPath[2];
        $this->action   = $urlPath[3];

    }

    /**
     * 启动运行
     * @return [type] [description]
     */
    public function run()
    {
        //检查协议
        if (!in_array($this->protocol, $this->allPro)) {
            return $this->error("This Protocol [{$this->protocol}] Is Not Right");
        }
        //检查模块
        $module_dir = BASE_PATH . '/Api/' . $this->module;
        if (!is_dir($module_dir)) {
            return $this->error("This Module [{$this->module}] Is Not Right");
        }
        //检查版本
        $version_dir = $module_dir . '/' . $this->version;
        if (!is_dir($module_dir)) {
            return $this->error("This Version [{$this->version}] Is Not Right");
        }
        //新增顶级域名空间
        \Sys::addNameSpace('Api', BASE_PATH . '/Api/');
        $className = '\\Api\\' . $this->module . '\\' . $this->version . '\\' . $this->protocol;
        if (!class_exists($className)) {
            return $this->error("This Api Is Not Exists!! [$className]");
        }
        $obj = new $className;
        if (!method_exists($obj, $this->action)) {
            return $this->error("This Api Is Not Exists!! [$className -> $this->action]");
        }
        $res = call_user_func_array([$obj, $this->action], $this->params);
        echo json_encode($res);
    }

    /**
     * 错误方法
     * @param  string $message [description]
     * @return [type]          [description]
     */
    public function error($message = '')
    {
        $this->error = $message;
        \Sys::$obj->log->error($message);
        return false;
    }
}
