<?php
namespace Sys\Cache;

/**
 * 文件缓存类
 */
class FileCache
{
    protected $config;
    protected $shard_id = 0;
    public function __construct($ckey)
    {
        $config = \Sys::$obj->config['file'][$ckey];
        if (!isset($config['cache_dir'])) {
            throw new \Exception(__CLASS__ . ': require cache_dir');
        }
        if (!is_dir($config['cache_dir'])) {
            if (@!mkdir($config['cache_dir'], 0755, true)) {
                throw new \Exception(__CLASS__ . ': can not create cache_dir [' . $config['cache_dir'] . ']');
            }
        }
        $this->config = $config;
    }

    public function shard($id = 0)
    {
        $this->shard_id = $id;
    }

    /**
     * 获取文件名称
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    protected function getFileName($key)
    {
        $file     = $this->config['cache_dir'] . $this->shard_id . '/' . trim(str_replace('_', '/', $key), '/');
        echo $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $file;
    }

    /**
     * 设置
     * @param [type]  $key     [description]
     * @param [type]  $value   [description]
     * @param integer $timeout [description]
     */
    public function set($key, $value, $timeout = 0)
    {
        $file            = $this->getFileName($key);
        $data['value']   = $value;
        $data['timeout'] = $timeout;
        $data['mktime']  = time();
        return file_put_contents($file, serialize($data));
    }

    /**
     * 获取
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    public function get($key)
    {
        $file = $this->getFileName($key);
        if (!is_file($file)) {
            return false;
        }

        $data = unserialize(file_get_contents($file));
        if (empty($data) || !isset($data['timeout']) || !isset($data['value'])) {
            return false;
        }
        //已过期
        if ($data['timeout'] != 0 && ($data['mktime'] + $data['timeout']) < time()) {
            $this->delete($key);
            return false;
        }
        return $data['value'];
    }

    /**
     * 删除
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    public function delete($key)
    {
        $file = $this->getFileName($key);
        return @unlink($file);
    }
}
