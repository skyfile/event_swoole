<?php
namespace Sys;

/**
 * 数据库
 */
class Db
{
    const TYPE_MYSQL   = 1;
    const TYPE_MYSQLI  = 2;
    const TYPE_PDO     = 3;
    const TYPE_CLMYSQL = 4;

    const DB_MASTER = 1;
    const DB_SLAVE  = 2;

    const CACHE_PREFIX   = 'sys_selectdb_';
    const CACHE_LIFETIME = 300;

    public $debug       = false;
    public $read_times  = 0;
    public $write_times = 0;

    static $obj;
    public $db          = null;
    public $db_apt      = null;
    public $forceMaster = true; //强制发送主库
    public $call_by     = 'func';

    protected $config;
    protected $masterDB; //主库
    protected $slaveDB;  //从库
    protected $table;    //数据表

    protected $enableCache;
    protected $cacheOptions = [];

    private function __construct($db_config)
    {
        $this->config = $db_config;
        //判定是否读写分离
        if (isset($config['user_proxy']) && $config['user_proxy']) {
            $this->forceMaster = false;
        } else {
            $this->config['use_proxy'] = false;
            $this->config['slaves']    = [];
        }
    }

    public static function getInstance($key)
    {
        if (!self::$obj) {
            $config = \Sys::$obj->config['db'];
            if (!isset($config[$key]) || empty($config[$key])) {
                throw new \Exception("db->{$key} config is not fund.");
            }
            self::$obj = new self($config[$key]);
        }
        return self::$obj;
    }

    /**
     * SQL拼接方法
     * @return [type] [description]
     */
    public function selectDB()
    {
        $this->db_apt = new SelectDB();
        //设置表名
        $this->db_apt->from($this->getTable());

        if ($this->debug) {
            $this->db_apt->debug = true;
        }
    }

    /**
     * 获取数据库实例
     * @param  [type] $type [description]
     * @return [type]       [description]
     */
    public function getDB($type = self::DB_MASTER)
    {
        if ($this->forceMaster || $type == self::DB_MASTER) {
            return $this->_getMasterDB();
        }
        if (!$this->config['use_proxy'] || empty($this->config['slaves'])) {
            return $this->_getMasterDB();
        }
        return $this->_getSlaveDB();
    }

    /**
     * 获取主库
     * @return [type] [description]
     */
    protected function _getMasterDB()
    {
        if (empty($this->masterDB)) {
            //连接到主库
            $config = $this->config;
            if (isset($config['use_proxy'])) {
                unset($config['use_proxy']);
            }
            if (isset($config['slaves'])) {
                unset($config['slaves']);
            }
            $this->masterDB = $this->_DBInit($config);
        }
        return $this->masterDB;
    }

    /**
     * 获取从库
     * @return [type] [description]
     */
    protected function _getSlaveDB()
    {
        if (empty($this->slaveDB)) {
            //连接到从库
            $config = $this->config;
            //从从库中随机选取一个
            $server = \Sys\Tool::getServer($config['slaves']);
            unset($config['slaves'], $config['use_proxy']);
            $config['host'] = $server['host'];
            $config['port'] = $server['port'];
            $this->slaveDB  = $this->_DBInit($config);
        }
        return $this->slaveDB;
    }

    /**
     * 初始化数据库
     * @param  [type] $db_config [description]
     * @return [type]            [description]
     */
    protected function _DBInit($db_config)
    {
        switch ($db_config['type']) {
            case self::TYPE_MYSQL:
                $db = new Db\MySQL($db_config);
                break;
            case self::TYPE_MYSQLI:
                $db = new Db\MySQLi($db_config);
                break;
            case self::TYPE_CLMysql:
                $db = new Db\CLMySQL($db_config);
                break;
            default:
                $db = new Db\PdoDB($db_config);
                break;
        }
        $db->connect();
        return $db;
    }

    /**
     * 调用$driver的自带方法
     * @param  [type] $method [description]
     * @param  array  $args   [description]
     * @return [type]         [description]
     */
    public function __call($method, $args = [])
    {
        $res = call_user_func_array([$this->db_apt, $method], $args);
        return $res ? $res : $this;
    }

    /**
     * 检查连接状态，如果连接断开，则重新连接
     */
    public function checkStatus()
    {
        if (!$this->getDB()->ping()) {
            $this->getDB()->close();
            $this->getDB()->connect();
        }
    }

    /**
     * 获取表名
     * @return [type] [description]
     */
    public function getTable()
    {
        if (!$this->table) {
            trigger_error('SelectDB param error, Table Name is empty!!');
            return false;
        }
        return $this->table;
    }

    /**
     * 设置表名
     * @param [type] $table [description]
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * 执行
     * @param  [type] $sql [description]
     * @return [type]      [description]
     */
    public function query($sql)
    {
        if ($this->debug) {
            echo $sql . NL;
        }
        if (strcasecmp($sql, 'select') === 0) {
            return $this->getDB(self::DB_SLAVE)->query($sql);
        }
        return $this->getDB()->query($sql);
    }

    /**
     * SQL转义
     * @param  [type] $str [description]
     * @return [type]      [description]
     */
    public function quote($str)
    {
        return $this->getDB()->quote($str);
    }

    /**
     * 执行生成的SQL语句
     * @param $sql
     * @return null
     */
    protected function exeucte($sql = '')
    {
        if ($sql == '') {
            $this->db_apt->getsql(false);
        } else {
            $this->db_apt->sql = $sql;
        }
        $this->db_apt->result = $this->query($this->db_apt->sql);
        $this->db_apt->is_execute++;
        $this->db_apt->init();
    }

    /**
     * 多条记录
     * @return [type] [description]
     */
    protected function _execute()
    {
        if ($this->db_apt->is_execute == 0) {
            $this->exeucte();
        }
        if ($this->db_apt->result) {
            return $this->db_apt->result->fetchall();
        } else {
            return false;
        }
    }

    /**
     * 获取记录
     * @param $field
     * @return array
     */
    public function getone($field = '')
    {
        $this->limit('1');
        if ($this->db_apt->auto_cache || !empty($cache_id)) {
            $cache_key = empty($cache_id) ? self::CACHE_PREFIX . '_one_' . md5($this->sql) : self::CACHE_PREFIX . '_all_' . $cache_id;
            $record    = \Sys::$obj->redis->get($cache_key);
            if (empty($record)) {
                if ($this->db_apt->is_execute == 0) {
                    $this->exeucte();
                }
                $record = $this->db_apt->result->fetch();
                \Sys::$obj->redis->set($cache_key, $record, $this->cache_life);
            }
        } else {
            if ($this->db_apt->is_execute == 0) {
                $this->exeucte();
            }
            $record = $this->db_apt->result->fetch();
        }

        if ($field === '') {
            return $record ?: [];
        }
        return isset($record[$field]) ? $record[$field] : '';
    }

    /**
     * 获取所有记录
     * @return array | bool
     */
    public function getall()
    {
        //启用了Cache
        if ($this->enableCache) {
            $this->getsql(false);
            //指定Cache的Key
            if (empty($this->cacheOptions['key'])) {
                $cache_key = self::CACHE_PREFIX . '_all_' . md5($this->sql);
            } else {
                $cache_key = $this->cacheOptions['key'];
            }
            //指定使用哪个Cache实例
            if (empty($this->cacheOptions['object_id'])) {
                $cacheObject = \Sys::$obj->redis;
            } else {
                $cacheObject = \Sys::$obj->redis($this->cacheOptions['object_id']);
            }
            //指定缓存的生命周期
            if (empty($this->cacheOptions['lifetime'])) {
                $cacheLifeTime = self::CACHE_LIFETIME;
            } else {
                $cacheLifeTime = intval($this->cacheOptions['lifetime']);
            }

            $data = $cacheObject->get($cache_key);
            //Cache数据为空，从DB中拉取
            if (empty($data)) {
                $data = $this->_execute();
                $cacheObject->set($cache_key, $data, $cacheLifeTime);
            }

            return $data;
        } else {
            return $this->_execute();
        }
    }

    /**
     * 获取当前条件下的记录数
     * @return int
     */
    public function count()
    {
        $sql = "select count({$this->count_fields}) as c from {$this->table} {$this->join} {$this->where} {$this->union} {$this->group}";

        if ($this->db_apt->if_union) {
            $sql = str_replace('{#union_select#}', "count({$this->count_fields}) as c", $sql);
            $c   = $this->query($sql)->fetchall();
            $cc  = 0;
            foreach ($c as $_c) {
                $cc += $_c['c'];
            }
            $count = intval($cc);
        } else {
            $_c = $this->query($sql);
            if ($_c === false) {
                return false;
            } else {
                $c = $_c->fetch();
            }
            $count = intval($c['c']);
        }
        return $count;
    }

    /**
     * 查询条件
     * @param  [type] $where [description]
     * @return [type]        [description]
     */
    public function where($where)
    {
        if (is_array($where)) {
            $arr = [];
            foreach ($where as $k => $v) {
                $value = $this->quote($v);
                if ($value != '' && $value{0} == '`') {
                    $arr[] = "`$k`=$value";
                } else {
                    $arr[] = "`$k`='$value'";
                }
            }
            $where = implode(' AND ', $arr);
        }
        $this->db_apt->where($where);
        return $this;
    }

    /**
     * 查询条件
     * @param  [type] $where [description]
     * @return [type]        [description]
     */
    public function orwhere($where)
    {
        if (is_array($where)) {
            $arr = '';
            foreach ($where as $k => $v) {
                $value = $this->quote($v);
                if ($value != '' && $value{0} == '`') {
                    $arr[] = "`$k`=$value";
                } else {
                    $arr[] = "`$k`='$value'";
                }
            }
            $where = implode(' AND ', $arr);
        }
        $this->db_apt->orwhere($where);
        return $this;
    }

    /**
     * 执行插入操作
     * @param $data
     * @return bool
     */
    public function insert($data)
    {
        $field  = '';
        $values = '';

        foreach ($data as $key => $value) {
            $value  = $this->quote($value);
            $field  = $field . "`$key`,";
            $values = $values . "'$value',";
        }

        $field  = substr($field, 0, -1);
        $values = substr($values, 0, -1);
        return $this->query("insert into {$this->table} ($field) values($values)");
    }

    /**
     * 对符合当前条件的记录执行update
     * @param $data
     * @return bool
     */
    public function update($data)
    {
        $update = '';
        foreach ($data as $key => $value) {
            $value = $this->quote($value);
            if ($value != '' && $value{0} == '`') {
                $update = $update . "`$key`=$value,";
            } else {
                $update = $update . "`$key`='$value',";
            }
        }
        $update = substr($update, 0, -1);
        return $this->query("update {$this->db_apt->table} set $update {$this->db_apt->where} {$this->db_apt->limit}");
    }

    /**
     * 删除当前条件下的记录
     * @return bool
     */
    public function delete()
    {
        return $this->query("delete from {$this->table} {$this->where} {$this->limit}");
    }

    /**
     * 获取受影响的行数
     * @return int
     */
    public function rowCount()
    {
        return $this->getDB()->getAffectedRows();
    }

    /**
     * 启动事务处理
     * @return bool
     */
    public function start()
    {
        return $this->query('START TRANSACTION');
    }

    /**
     * 提交事务处理
     * @return bool
     */
    public function commit()
    {
        return $this->query('COMMIT');
    }

    /**
     * 事务回滚
     * @return bool
     */
    public function rollback()
    {
        $this->query('ROLLBACK');
    }
}
