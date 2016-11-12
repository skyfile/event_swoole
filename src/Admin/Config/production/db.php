<?php
$db['master'] = [
    'type'       => \Sys\Db::TYPE_MYSQLI,
    'host'       => '127.0.0.1',
    'port'       => 3306,
    'dbms'       => 'mysql',
    'user'       => 'root',
    'passwd'     => 'root',
    'name'       => 'test',
    'charset'    => 'utf8',
    'setname'    => true,
    'persistent' => true,  //MySQL长连接
    'use_proxy'  => false, //启动读写分离Proxy
    'slaves'     => [
        ['host' => '127.0.0.1', 'port' => '3307', 'weight' => 100],
        ['host' => '127.0.0.1', 'port' => '3308', 'weight' => 99],
        ['host' => '127.0.0.1', 'port' => '3309', 'weight' => 98],
    ],
];

return $db;