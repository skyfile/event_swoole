<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require realpath(__DIR__) . '/../Boot/boot.php';

$res = \Sys::$obj->Restfull->run();
if ($res === false) {
	$res = \Sys::$obj->Restfull->error;
}

// var_dump($res);