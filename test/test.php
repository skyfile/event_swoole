<?php
require realpath(__DIR__) . '/../src/boot.php';

$model = new \Model\MailAppid();

var_dump($model);

echo 'ok';
