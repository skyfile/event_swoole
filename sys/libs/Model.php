<?php
namespace Sys\libs;

/**
* 模型方法
*/
class Model
{

    private $table = '';
    public $select = '*';

    function __construct($config_key = 'master')
    {
        $this->db = \Sys::$obj->db($config_key);
        $this->db->setTable( $this->getTableName() );
        $this->db->selectDB();
    }

    /**
     * 获取表名
     * @return [type] [description]
     */
    public function getTableName()
    {
        if(empty($this->table)) {
            $this->table = \Sys\Tool::toUnderScore( end( explode( '\\', get_class($this) ) ) );
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

    public function debug()
    {
        $this->db->debug = true;
    }

    public function __call($func, $params)
    {
       return call_user_func_array([$this->db, $func], $params);
    }

}
