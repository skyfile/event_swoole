<?php
namespace Sys\Log;

class EchoLog extends Log
{
    protected $display = true;

    public function __construct($config)
    {
        if (isset($config['display']) && $config['display'] == false) {
            $this->display = false;
        }
        parent::__construct($config);
    }

    public function put($msg, $level = self::INFO)
    {
        if ($this->display) {
            $log = $this->format($msg, $level);
            if ($log) {
                echo $log;
            }

        }
    }
}
