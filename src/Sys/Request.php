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

    public static function getInstance($key = 'master')
    {
        if (!self::$obj) {
            self::$obj = new self();
        }
        return self::$obj;
    }

    public function init()
    {
        $parse        = parse_url($this->getUrlFull());
        $this->scheme = $parse['scheme'];
        $this->host   = $parse['host'];
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
            $this->urlFull = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
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
                $v                     = array_map('trim', explode('=', $v));
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

    /**
     * 获取客户端IP
     * @return string
     */
    public function getClientIP()
    {
        if (isset($this->server['HTTP_CLIENT_IP']) && strcasecmp($this->server['HTTP_CLIENT_IP'], 'unknown')) {
            return $this->server['HTTP_CLIENT_IP'];
        }
        if (isset($this->server['HTTP_X_FORWARDED_FOR']) && strcasecmp($this->server['HTTP_X_FORWARDED_FOR'], 'unknown')) {
            return $this->server['HTTP_X_FORWARDED_FOR'];
        }
        if (isset($this->server['REMOTE_ADDR'])) {
            return $this->server['REMOTE_ADDR'];
        }
        return '';
    }

    /**
     * 获取客户端浏览器信息
     * @return string
     */
    public function getBrowser()
    {
        $sys = $_SERVER['HTTP_USER_AGENT'];
        if (stripos($sys, 'Firefox/')) {
            preg_match("/Firefox\/([^;)]+)+/i", $sys, $browser);
            $exp = ['Firefox', $browser[1]];
        } elseif (stripos($sys, 'Maxthon')) {
            preg_match("/Maxthon\/([\d\.]+)/", $sys, $browser);
            $exp = ['傲游', $browser[1]];
        } elseif (stripos($sys, 'MSIE')) {
            preg_match("/MSIE\s+([^;)]+)+/i", $sys, $browser);
            $exp = ['IE', $browser[1]];
        } elseif (stripos($sys, 'OPR')) {
            preg_match("/OPR\/([\d\.]+)/", $sys, $browser);
            $exp = ['Opera', $browser[1]];
        } elseif (stripos($sys, 'Edge')) {
            preg_match("/Edge\/([\d\.]+)/", $sys, $browser);
            $exp = ['Edge', $browser[1]];
        } elseif (stripos($sys, 'Chrome')) {
            preg_match("/Chrome\/([\d\.]+)/", $sys, $browser);
            $exp = ['Chrome', $browser[1]];
        } elseif (stripos($sys, 'rv:') && stripos($sys, 'Gecko')) {
            preg_match("/rv:([\d\.]+)/", $sys, $browser);
            $exp = ['IE', $browser[1]];
        } elseif (stripos($sys, 'Safari')) {
            preg_match("/Safari:([\d\.]+)/", $sys, $browser);
            $exp = ['Safari', $browser[1]];
        } else {
            $exp = ['Unkown', ''];
        }
        return $exp[0] . '(' . $exp[1] . ')';
    }

    /**
     * 获取客户端操作系统信息
     * @return string
     */
    public function getOS()
    {
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($agent, 'iphone') || strpos($agent, 'ipad')) {
            $os = 'ios';
        } elseif (strpos($agent, 'android')) {
            $os = 'android';
        } elseif (preg_match('/win/i', $agent) && strpos($agent, '95')) {
            $os = 'Windows 95';
        } elseif (preg_match('/win 9x/i', $agent) && strpos($agent, '4.90')) {
            $os = 'Windows ME';
        } elseif (preg_match('/win/i', $agent) && preg_match('/98/i', $agent)) {
            $os = 'Windows 98';
        } elseif (preg_match('/win/i', $agent) && preg_match('/nt 6.0/i', $agent)) {
            $os = 'Windows Vista';
        } elseif (preg_match('/win/i', $agent) && preg_match('/nt 6.1/i', $agent)) {
            $os = 'Windows 7';
        } elseif (preg_match('/win/i', $agent) && preg_match('/nt 6.2/i', $agent)) {
            $os = 'Windows 8';
        } elseif (preg_match('/win/i', $agent) && preg_match('/nt 10.0/i', $agent)) {
            $os = 'Windows 10';
        } elseif (preg_match('/win/i', $agent) && preg_match('/nt 5.1/i', $agent)) {
            $os = 'Windows XP';
        } elseif (preg_match('/win/i', $agent) && preg_match('/nt 5/i', $agent)) {
            $os = 'Windows 2000';
        } elseif (preg_match('/win/i', $agent) && preg_match('/nt/i', $agent)) {
            $os = 'Windows NT';
        } elseif (preg_match('/win/i', $agent) && preg_match('/32/i', $agent)) {
            $os = 'Windows 32';
        } elseif (preg_match('/linux/i', $agent)) {
            $os = 'Linux';
        } elseif (preg_match('/unix/i', $agent)) {
            $os = 'Unix';
        } elseif (preg_match('/sun/i', $agent) && preg_match('/os/i', $agent)) {
            $os = 'SunOS';
        } elseif (preg_match('/ibm/i', $agent) && preg_match('/os/i', $agent)) {
            $os = 'IBM OS/2';
        } elseif (preg_match('/Mac/i', $agent) && preg_match('/PC/i', $agent)) {
            $os = 'Macintosh';
        } elseif (preg_match('/PowerPC/i', $agent)) {
            $os = 'PowerPC';
        } elseif (preg_match('/AIX/i', $agent)) {
            $os = 'AIX';
        } elseif (preg_match('/HPUX/i', $agent)) {
            $os = 'HPUX';
        } elseif (preg_match('/NetBSD/i', $agent)) {
            $os = 'NetBSD';
        } elseif (preg_match('/BSD/i', $agent)) {
            $os = 'BSD';
        } elseif (preg_match('/OSF1/i', $agent)) {
            $os = 'OSF1';
        } elseif (preg_match('/IRIX/i', $agent)) {
            $os = 'IRIX';
        } elseif (preg_match('/FreeBSD/i', $agent)) {
            $os = 'FreeBSD';
        } elseif (preg_match('/teleport/i', $agent)) {
            $os = 'teleport';
        } elseif (preg_match('/flashget/i', $agent)) {
            $os = 'flashget';
        } elseif (preg_match('/webzip/i', $agent)) {
            $os = 'webzip';
        } elseif (preg_match('/offline/i', $agent)) {
            $os = 'offline';
        } else {
            $os = 'Unknown';
        }
        return $os;
    }

    /**
     * 发送下载声明
     * @return unknown_type
     */
    public function download($mime, $filename)
    {
        header("Content-type: $mime");
        header("Content-Disposition: attachment; filename=$filename");
    }
}
