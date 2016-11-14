<?php
namespace Sys;

/**
 * 视图类
 */
class View
{
    //模板后缀名
    const SUFFIX = '.html';

    static $obj;
    public $twig;
    protected $var      = [];
    protected $template = '';
    protected $rootPath;
    protected $cachePath;

    public function __construct($config = [])
    {

        $this->rootPath  = APP_PATH . 'View/';
        $this->cachePath = APP_PATH . '/Data/View/';
    }

    public static function getInstance($key = 'master')
    {
        if (!self::$obj) {
            // $config = \Sys::$obj->config['view'];
            // if (!$config) {
            //     throw new \Exception('view config is not fund.');
            // }
            self::$obj = new self();
        }
        return self::$obj;
    }

    /**
     * 设置参数
     * @param string $value [description]
     */
    public function setVar(array $value = [])
    {
        if (!is_array($value)) {
            die('must be array');
        }
        if (!empty($this->var)) {
            $this->var = array_merge($this->var, $value);
        } else {
            $this->var = $value;
        }
        return true;
    }

    /**
     * 获取参数
     * @return [type] [description]
     */
    public function getVar()
    {
        return $this->var;
    }

    /**
     * 设置模板文件
     * @param string $template [description]
     */
    public function setView($template = '')
    {
        $this->template = $template;
    }

    /**
     * 获取模板文件
     * @return [type] [description]
     */
    public function getView()
    {
        return $this->template;
    }

    /**
     * 渲染模板
     * @param  string $template [description]
     * @return [type]           [description]
     */
    public function render($dir = '', $file = '')
    {
        if (!$dir) {
            if (strpos($this->template, '/') !== false) {
                $arr  = array_map('\\Sys\\Tool::toCamelCase', explode('/', $this->template));
                $dir  = $arr[0];
                $file = $file ? $file : $arr[1];
            } else {
                $dir  = 'Index';
                $file = $file ? $file : \Sys\Tool::toCamelCase($this->template);
            }
        }
        if (!$this->twig) {
            $this->twig = new \Twig_Environment(
                new \Twig_Loader_Filesystem([$dir], $this->rootPath),
                [
                    'cache' => $this->cachePath,
                    'debug' => DEBUG ? false : true,
                ]);
        }
        echo $this->twig->render($file . self::SUFFIX, $this->var);
        return;
    }
}
