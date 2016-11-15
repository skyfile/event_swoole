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

        $cache = \Sys::$obj->cache('file');
        $res   = $cache->set('test', 'jfdkdslfadsfasdf');
        echo '<pre>';
        var_dump($res);
        // $model = \Sys::$obj->getModel('test');
        // echo '<pre>';
        // var_dump($model);

    }
}
