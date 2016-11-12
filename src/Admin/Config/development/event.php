<?php

return [
    'master' => [
        'async'    => true,
        'key'      => 'Admin:queue', //队列存储key
        'pid_path' => '/var/run/',
    ],
];