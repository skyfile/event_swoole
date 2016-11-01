<?php
$config = \Sys::$obj->config['queue'] ? \Sys::$obj->config['queue'] : [];
return new Sys\libs\Queue($config);