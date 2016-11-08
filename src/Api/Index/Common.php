<?php
namespace Api\Index;

/**
 * 模块公共类
 */
class Common extends \Api\Base
{
    /**
     * 创建appKey
     * @param  string $name [description]
     * @return [type]       [description]
     */
    public function createAppKey($name = '')
    {
        return md5($name . time());
    }
}
