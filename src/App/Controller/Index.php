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
        $this->view->setView('test');
    }
}