<?php
$log['master'] = array(
    'type' => 'FileLog',
    // 'file' => WEBPATH . '/logs/app.log',
    'dir'  => BASE_PATH . '/Logs/',
    'date' => true,
    'cut_file' => true,
);

return $log;