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
            'title' => '默认页面',
            'key'   => 'Admin 恭喜你， 模块创建成功',
        ]);
        $this->view->setView('test');
    }
}
