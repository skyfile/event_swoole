<?php
namespace Sys;

/**
 * 配置文件加载类
 */
class Config implements \ArrayAccess
{
    public $config;
    public $config_path;

    public function setPath($dir)
    {
        $this->config_path[] = $dir;
    }

    public function load($index)
    {
        foreach ($this->config_path as $path) {
            $filename = $path . '/' . $index . '.php';
            if (is_file($filename)) {
                $data = include $filename;
                if (empty($data)) {
                    trigger_error(__CLASS__ . ": $filename no return data");
                } else {
                    $this->config[$index] = $data;
                }
            }
        }
    }

    public function offsetGet($index)
    {
        if (!isset($this->config[$index])) {
            $this->load($index);
        }
        return isset($this->config[$index]) ? $this->config[$index] : false;
    }

    public function offsetSet($index, $newval)
    {
        $this->config[$index] = $newval;
    }

    public function offsetUnset($index)
    {
        unset($this->config[$index]);
    }

    public function offsetExists($index)
    {
        return isset($this->config[$index]);
    }
}
