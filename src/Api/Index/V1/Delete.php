<?php
namespace Api\Index\V1;

/**
 * 删除资源模块
 */
class Delete
{
    public function appid($name = '')
    {
        $name = ($name == '') ? (isset($_GET['name']) ? $_GET['name'] : '') : $name;
        if ($name == '') {
            return $this->error('参数错误');
        }
        $res = \Sys::$obj->getModel('MailAppid')->where("name = '{$name}'")->delete();
        if (!$res) {
            return $this->error('删除失败');
        }
        return $this->success('删除成功');
    }
}
