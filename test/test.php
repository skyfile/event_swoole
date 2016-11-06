<?php
require realpath(__DIR__) . '/../src/Boot/boot.php';

$model = new \Model\MailAppid();

var_dump($model);

echo 'ok';
