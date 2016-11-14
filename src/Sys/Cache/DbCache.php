<?php
namespace Sys\Cache;

/**
 * mysql缓存类
 */
class DbCache
{
    public $db;

    public function __construct($ckey)
    {
        $this->db = \Sys::$obj->db($ckey);
        $this->db->setTable('cache');
        $this->db->selectDB();
    }
}
