<?php
namespace Sys;

/**
 * 控制器基础类
 */
class Controller
{
    static $obj;

    public $view; //视图

    public function __construct()
    {
        $this->view = \Sys::$obj->View;
        $arr        = explode('\\', get_class($this));
        $this->view->setView(end($arr));
    }

    public static function getInstance($key = '')
    {
        if (!self::$obj) {
            self::$obj = new self();
        }
        return self::$obj;
    }
}
