<?php

return [
    'master' => [
        'async'    => true,
        'key'      => 'mail:queue', //队列存储key
        'pid_path' => '/var/run/',
    ],
];
