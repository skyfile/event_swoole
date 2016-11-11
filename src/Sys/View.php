<?php
namespace Sys;

/**
 * 视图类
 */
class View
{
    static $obj;
    public $twig;

    public function __construct($config)
    {

        $loader     = new \Twig_Loader_Filesystem(APP_PATH . 'View');
        $this->twig = new \Twig_Environment($loader, [
            'cache' => APP_PATH . '/Data/View',
        ]);
    }

    public static function getInstance($key = 'master')
    {
        if (!self::$obj) {
            $config = \Sys::$obj->config['view'];
            if (!$config) {
                throw new \Exception('view config is not fund.');
            }
            self::$obj = new self($config);
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

        if (!empty($this->setVar)) {
            $this->setVar = array_merge($this->setVar, $value);
        } else {
            $this->setVar = $value;
        }

    }

    public function __call($func, $params)
    {
        return call_user_func_array([$this->twig, $func], $params);
    }
}
