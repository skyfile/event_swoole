<?php
namespace Api\Index\V1;

/**
 * 更新部分资源模块
 */
class Patch
{
    public function appid($name = '')
    {
        $name = ($name == '') ? (isset($_GET['name']) ? $_GET['name'] : '') : $name;
        if ($name == '') {
            return $this->error('参数错误');
        }
        $model = \Sys::$obj->getModel('MailAppid');
        $exist = $model->where("name = '{$name}'")->getOne();
        if (!$exist) {
            return $this->error('该应用不存在');
        }
        $appkey = $this->createAppKey($name);
        $res    = $model->where("name = '{$name}'")->update(['appkey' => $appkey]);
        if (!$res) {
            return $this->error('更新失败');
        }
        return $this->success('更新成功');
    }
}
