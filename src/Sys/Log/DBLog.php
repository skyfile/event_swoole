<?php
namespace Sys\Log;

/**
 * 数据库日志记录类
 * @author Tianfeng.Han
 */
class DBLog extends Log
{
    /**
     * @var \Swoole\Database;
     */
    protected $db;
    protected $table;

    public function __construct($config)
    {
        if (empty($config['table'])) {
            throw new \Exception(__CLASS__ . ": require \$config['table']");
        }
        $this->table = $config['table'];
        if (isset($config['db'])) {
            $this->db = \Sys::$obj->db($config['db']);
        } else {
            $this->db = \Sys::$obj->db('master');
        }
        parent::__construct($config);
    }

    public function put($msg, $level = self::INFO)
    {
        $put['logtype'] = self::convert($level);
        $msg            = $this->format($msg, $level);
        if ($msg) {
            $put['msg'] = $msg;
            \Sys::$obj->db->insert($put, $this->table);
        }
    }

    public function create()
    {
        return $this->db->query("CREATE TABLE `{$this->table}` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `addtime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
            `logtype` TINYINT NOT NULL ,
            `msg` VARCHAR(255) NOT NULL
            )");
    }
}
