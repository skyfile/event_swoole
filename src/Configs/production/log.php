<?php
$log['master'] = [
    'type'     => 'FileLog',
    // 'file' => WEBPATH . '/logs/app.log',
    'dir'      => WEB_PATH . '/logs/',
    'date'     => true,
    'cut_file' => true,
];

return $log;
