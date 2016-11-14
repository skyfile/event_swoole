<?php
namespace Controller;

/**
 * 默认
 */
class Index extends Base
{
    public function index($value = '')
    {
        // $this->view->setVar([
        //     'title' => '测试标题',
        //     'key'   => '测试成功222',
        // ]);
        // $this->view->setView('test');

        // $cache = \Sys::$obj->cache('db');
        // echo '<pre>';
        // var_dump($cache);
        $model = \Sys::$obj->getModel('test');
        echo '<pre>';
        var_dump($model);
    }
}
