<?php

$config = \Sys::$obj->config['redis'];
if (empty($config[\Sys::$obj->factory_key])) {
	throw new \Exception("event->{".\Sys::$obj->factory_key."} config is not fund.");
}
$config = $config[\Sys::$obj->factory_key];

if (empty($config['port'])) {
	$config['port'] = 6379;
}

if (empty($config["pconnect"])) {
    $config["pconnect"] = false;
}

if (empty($config['timeout'])) {
    $config['timeout'] = 0.5;
}

return new \Sys\libs\Redis($config);
