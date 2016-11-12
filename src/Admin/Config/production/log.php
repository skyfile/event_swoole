<?php

$log['master'] = [
    'type'     => 'FileLog',
    'dir'      => APP_PATH . '/Data/Logs/',
    'date'     => true,
    'cut_file' => true,
];

return $log;
