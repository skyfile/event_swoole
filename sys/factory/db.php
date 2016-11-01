<?php

$configs = \Sys::$obj->config['db'];
if (empty($configs[\Sys::$obj->factory_key]))
{
    throw new \Exception("db->". \Sys::$obj->factory_key ." is not found.");
}
$config = $configs[\Sys::$obj->factory_key];
$db = new \Sys\libs\DB($config);
// $db->connect();
return $db;
