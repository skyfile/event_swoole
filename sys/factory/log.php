<?php
$config = \Sys::$obj->config['log'];
if (empty($config[\Sys::$obj->factory_key])) {
    throw new \Exception("log->".\Sys::$obj->factory_key." is not found.");
}
$conf = $config[\Sys::$obj->factory_key];
if (empty($conf['type'])) {
    $conf['type'] = 'EchoLog';
}
$class = 'Sys\\libs\\log\\' . $conf['type'];
$log = new $class($conf);
if (!empty($conf['level'])) {
    $log->setLevel($conf['level']);
}
return $log;
