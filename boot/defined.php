<?php
/**
 * 定义全局变量
 */

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

//是否开启debug
defined('DEBUG') || define('DEBUG', PRODUCTION ? 'off' : 'on');

//换行符
define("NL", PHP_OS == 'WINNT' ? "\r\n" : "\n");
define("BL", "<br />" . NL);

//运行环境时区
date_default_timezone_set('PRC');

//当前时间戳
// define("CURR_TIMESTAMP", time());

define("BOOT_PATH", WEB_PATH . '/boot/');
define("CONF_PATH", WEB_PATH . '/configs/');
define("EVENT_PATH", WEB_PATH . '/events/');
define("MODEL_PATH", WEB_PATH . '/models/');
define("SYS_PATH", WEB_PATH . '/sys/');
define("VENDOR_PATH", WEB_PATH . '/vendor/');
