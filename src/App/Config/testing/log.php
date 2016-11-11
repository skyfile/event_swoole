<?php
$log['master'] = [
    'type'     => 'FileLog',
    // 'file' => WEBPATH . '/logs/app.log',
    'dir'      => WEB_PATH . '/Data/Logs/',
    'date'     => true,
    'cut_file' => true,
];

return $log;
