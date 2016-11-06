<?php
$db['master'] = array(
    'type'       => Sys\DB::TYPE_MYSQLi,
    'host'       => "127.0.0.1",
    'port'       => 3306,
    'dbms'       => 'mysql',
    'user'       => "root",
    'passwd'     => "a6412128",
    'name'       => "app_mail",
    'charset'    => "utf8",
    'setname'    => true,
    'persistent' => true, //MySQL长连接
    'use_proxy'  => false,  //启动读写分离Proxy
    'slaves'     => array(
        array('host' => '127.0.0.1', 'port' => '3307', 'weight' => 100,),
        array('host' => '127.0.0.1', 'port' => '3308', 'weight' => 99,),
        array('host' => '127.0.0.1', 'port' => '3309', 'weight' => 98,),
    ),
);

return $db;
