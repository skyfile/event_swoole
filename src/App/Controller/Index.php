<?php
namespace Controller;

/**
 * 默认
 */
class Index extends Base
{
    public function index($value = '')
    {
        $this->view->setVar(['name' => 'is ok']);
        var_dump('this is working');
    }
}
