<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('CURR_MODULE', 'App'); //定义模块名称(必须首字母大写)

require realpath(__DIR__) . '/../Boot/boot.php';

// $res = \Sys::$obj->Restfull->run();
$res = \Sys::$obj->app->run();
if ($res === false) {
    exit(\Sys::$obj->Restfull->error);
}
