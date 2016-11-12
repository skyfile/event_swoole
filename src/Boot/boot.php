<?php
require realpath(__DIR__) . '/define.php';

if (!defined('CURR_MODULE') || CURR_MODULE == '') {
    die('Please define CURR_MODULE in index.php!');
} else {
    $first = substr(CURR_MODULE, 0, 1);
    if (strtoupper($first) != $first) {
        die('The first letter of the module name must be capital!');
    }
    unset($first);
}

defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/' . CURR_MODULE . '/'); //模块路径
defined('CONF_PATH') || define('CONF_PATH', APP_PATH . 'Config/');

require SYS_PATH . 'Sys.php';
\Sys::addNameSpace('Sys', BASE_PATH . '/Sys/');

//自动加载
$nameSpace = ['Event', 'Model'];
foreach ($nameSpace as $name) {
    \Sys::addNameSpace($name, APP_PATH . "$name/");
}

spl_autoload_register('\\Sys::autoload');

//composer
require_once VENDOR_PATH . 'autoload.php';

\Sys::getInstance();
