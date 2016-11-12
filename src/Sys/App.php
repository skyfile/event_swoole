<?php
namespace Sys;

/**
 * 控制器基础类
 */
class App
{
    static $obj;

    public $request;    //请求
    public $Controller; //控制器
    public $Action;     //方法
    public $params;     //参数

    public function __construct()
    {
        $this->init();
    }

    public static function getInstance($key = '')
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
                $urlPath = ['index', 'index'];
                break;
            case 1:
                $urlPath = [$urlPath[0], 'index'];
                break;
            case 2:
                $urlPath = [$urlPath[0], $urlPath[1]];
                break;
            default:
                break;
        }
        $this->params     = array_slice($urlPath, 2);
        $urlPath          = array_map('\\Sys\\Tool::toCamelCase', $urlPath);
        $this->Controller = $urlPath[0];
        $this->Action     = $urlPath[1];
    }

    public function run()
    {
        \Sys::addNameSpace('Controller', APP_PATH . 'Controller/');
        $className = '\\Controller\\' . $this->Controller;
        if (!class_exists($className)) {
            die('This Controller Is Not Exists!!');
        }
        $obj = new $className;
        if (!method_exists($obj, $this->Action)) {
            die('This Action Is Not Exists In This Controller!!');
        }
        //执行程序
        call_user_func_array([$obj, $this->Action], $this->params);

        //渲染模板
        return $obj->view->render();
    }
}
