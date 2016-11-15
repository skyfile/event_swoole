<?php
namespace Sys;

/**
 * 模型方法
 */
class Model
{
    public $table  = '';
    public $select = '*';
    public $create_sql;

    public function __construct($table = '', $config_key = 'master')
    {
        $this->db = \Sys::$obj->Db($config_key);
        if ($table == '') {
            $this->getTableName();
        } else {
            $this->setTableName(\Sys\Tool::toUnderScore($table));
        }
        $this->db->setTable($this->table);
        $this->db->selectDB();
    }

    /**
     * 获取表名
     * @return [type] [description]
     */
    public function getTableName()
    {
        if (!$this->table) {
            $table       = explode('\\', get_class($this));
            $this->table = \Sys\Tool::toUnderScore(end($table));
        }
        return $this->table;
    }

    /**
     * 设置表名
     * @param [type] $table [description]
     */
    public function setTableName($table)
    {
        $this->table = $table;
        $this->db->setTable($this->table);
    }

    /**
     * 设置debug状态
     * @return [type] [description]
     */
    public function debug($stat = true)
    {
        $this->db->debug = ($stat === true) ? true : false;
    }

    /**
     * 建立表，必须在Model类中，指定create_sql
     * @return bool
     */
    public function createTable()
    {
        if ($this->create_sql) {
            return $this->db->query($this->create_sql);
        } else {
            return false;
        }
    }

    public function __call($func, $params)
    {
        return call_user_func_array([$this->db, $func], $params);
    }
}
