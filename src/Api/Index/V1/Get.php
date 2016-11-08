<?php
namespace Api\Index\V1;

/**
 * 取出资源
 */
class Get extends \Api\Base
{
    public function appid($name = '')
    {
        $name = ($name == '') ? (isset($_GET['name']) ? $_GET['name'] : '') : $name;
        if ($name == '') {
            return $this->error('参数错误');
        }
        $model = \Sys::$obj->getModel('MailAppid');
        $res   = $model->select(['name', 'appkey'])->where("name = '$name'")->getOne();
        if (!$res) {
            return $this->error('该应用不存在');
        }
        return $this->success($res);
    }
}
