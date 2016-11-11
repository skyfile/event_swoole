<?php
if (version_compare(PHP_VERSION, '5.5.0', '<')) {
    exit('程序最低支持 PHP v5.5.0, 您当前版本为：' . PHP_VERSION);
}

if (!extension_loaded('swoole')) {
    exit('缺少 Swoole 扩展， 请先安装！！');
}

if (version_compare(swoole_version(), '1.7.8', '<')) {
    exit('Swoole 扩展最低支持1.7.8， 您当前版本为：' . swoole_version());
}

if (!extension_loaded('redis')) {
    exit('缺少 php-redis 扩展， 请先安装！！');
}

// 生产环境
defined('PRODUCTION') || define('PRODUCTION', is_file('/etc/php.env.production'));
// 预发环境
defined('STAGING') || define('STAGING', is_file('/etc/php.env.staging'));
// 测试环境
defined('TESTING') || define('TESTING', is_file('/etc/php.env.testing'));
// 开发环境
defined('DEVELOPMENT') || define('DEVELOPMENT', !(PRODUCTION || STAGING || TESTING));
//当前环境
defined('CURRENV') || define('CURRENV', (PRODUCTION ? 'production' : (TESTING ? 'testing' : 'development')));

//换行符
define('NL', PHP_OS == 'WINNT' ? "\r\n" : "\n");
define('BL', '<br />' . NL);

//是否开启debug
defined('DEBUG') || define('DEBUG', PRODUCTION ? 'off' : 'on');

//运行环境时区
date_default_timezone_set('PRC');

//项目根目录
define('BASE_PATH', realpath(__DIR__ . '/../'));
define('SYS_PATH', BASE_PATH . '/Sys/');
define('VENDOR_PATH', BASE_PATH . '/../vendor/');

if (!defined('CURR_MODULE') || CURR_MODULE == '') {
    die('Please define CURR_MODULE in index.php!');
} else {
    $first = substr(CURR_MODULE, 0, 1);
    if (strtoupper($first) != $first) {
        die('The first letter of the module name must be capital!');
    }
    unset($first);
}

define('APP_PATH', BASE_PATH . '/' . CURR_MODULE . '/'); //模块路径
define('CONF_PATH', APP_PATH . 'Config/');

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
