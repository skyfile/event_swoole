<?php

$config = \Sys::$obj->config['event'];
if (empty($config[\Sys::$obj->factory_key])) {
	throw new \Exception("event->{".\Sys::$obj->factory_key."} config is not fund.");
}

return new Sys\libs\Event($config[\Sys::$obj->factory_key]);
