<?php
namespace Controller;

/**
 * 默认
 */
class Index extends Base
{
    public function index($value = '')
    {
        $this->view->setVar([
            'title' => '测试标题',
            'key'   => '测试成功222',
        ]);
        $this->view->setView('index');

        // $model = \Sys::$obj->getModel('test');
        // echo '<pre>';
        // var_dump($model);
        // $session           = \Sys::$obj->session;
        // $_SESSION['useid'] = 155;

    }
}
