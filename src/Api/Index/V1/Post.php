<?php
namespace Api\Index\V1;

/**
 * 创建资源模块
 */
class Post extends Api
{
    public function appid($name = '')
    {
        $name = ($name == '') ? (isset($_GET['name']) ? $_GET['name'] : '') : $name;
        if ($name == '') {
            return $this->error('参数错误');
        }
        $model = \Sys::$obj->getModel('MailAppid');
        $res   = $model->where("name = '{$name}'")->getOne();
        if ($res) {
            return $this->error('该app已授权');
        }
        $appkey = $this->createAppKey($name);
        $res    = $model->insert([
            'name'   => $name,
            'appkey' => $appkey,
            'uptime' => time(),
        ]);
        return $this->success($res);
    }
}
