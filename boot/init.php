<?php
if (version_compare(PHP_VERSION, '5.5.0', '<')) {
    exit('程序最低支持 PHP v5.5.0, 您当前版本为：'. PHP_VERSION);
}

if (!extension_loaded('swoole')) {
	exit('缺少 Swoole 扩展， 请先安装！！');
}

if (version_compare(swoole_version(), '1.7.8', '<')) {
	exit('Swoole 扩展最低支持1.7.8， 您当前版本为：'.swoole_version());
}

if (!extension_loaded('redis')) {
	exit('缺少 php-redis 扩展， 请先安装！！');
}

require WEB_PATH . '/boot/defined.php';

require SYS_PATH . 'Sys.php';

//自动加载
Sys::addNameSpace('Sys', SYS_PATH);
Sys::addNameSpace('Event', EVENT_PATH);
Sys::addNameSpace('Model', MODEL_PATH);

spl_autoload_register('\\Sys::autoload');

//composer
require_once VENDOR_PATH. 'autoload.php';

Sys::getInstance();
