<?php

define('WEB_PATH', realpath(__DIR__));
require WEB_PATH . '/boot/init.php';

$model = new \Model\MailAppid();
$model->debug();

// $res = $model->insert([
//         'name'  => 'test2',
//         'appkey'=> md5('sky'),
//         'uptime'=> time()
//     ]);
// $res = $model->where(['id' => 3])->getOne();\
$res = $model->getAll();
var_dump($res);
