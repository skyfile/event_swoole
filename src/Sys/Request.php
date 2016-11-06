<?php
namespace Sys;

/**
* 路由类
*/
class Request
{
    static $obj;

    public $host;

    public $scheme;

    public $urlFull;

    private $pathArr = [];

    private $paramArr = [];

    private function __construct()
    {
        $this->init();
    }

    static public function getInstance($key = 'master')
    {
        if (!self::$obj) {
            self::$obj = new self();
        }
        return self::$obj;
    }

    public function init()
    {
        $parse = parse_url($this->getUrlFull());
        $this->scheme = $parse['scheme'];
        $this->host = $parse['host'];
        $this->parsePath($parse['path']);
        if (isset($parse['query'])) {
            $this->parseQuery($parse['query']);
        }
    }

    /**
     * 获取完整URL
     * @return [type] [description]
     */
    public function getUrlFull()
    {
        if (!$this->urlFull) {
            $this->urlFull = $_SERVER['REQUEST_SCHEME'] ."://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        }
        return $this->urlFull;
    }

    /**
     * 解析路径
     * @param  [type] $path [description]
     * @return [type]       [description]
     */
    public function parsePath($path)
    {
        if ($path != '/') {
            $this->pathArr = array_map('trim', explode('/', substr($path, 1)));
        }
    }

    /**
     * 解析参数
     * @param  [type] $query [description]
     * @return [type]        [description]
     */
    public function parseQuery($query)
    {
        $arr = array_map('trim', explode('&', $query));
        foreach ($arr as $v) {
            if ($v != '') {
                $v = array_map('trim', explode('=', $v));
                $this->paramArr[$v[0]] = isset($v[1]) ? $v[1] : '';
            }
        }
    }

    /**
     * 获取参数
     * @param  string $key [description]
     * @return [type]      [description]
     */
    public function getParam($key = '')
    {
        if ($key == '') {
            return $this->paramArr;
        }
        return isset($this->paramArr[$key]) ? $this->paramArr[$key] : '';
    }

    /**
     * 获取路径数组
     * @return [type] [description]
     */
    public function getPath()
    {
        return $this->pathArr;
    }

}