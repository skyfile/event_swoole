<?php
namespace Sys\libs;

/**
 * 查询数据库的封装类，基于底层数据库封装类，实现SQL生成器
 * 注：仅支持MySQL，不兼容其他数据库的SQL语法
 * @author Tianfeng.Han
 * @package SwooleSystem
 * @subpackage Database
 */
class SelectDB
{
    static $error_call = '';
    static $allow_regx = '#^([a-z0-9\(\)\._=\-\+\*\`\s\'\",]+)$#i';

    public $table = '';
    public $primary = 'id';
    public $select = '*';
    public $sql = '';
    public $limit = '';
    public $where = '';
    public $order = '';
    public $group = '';
    public $use_index = '';
    public $having = '';
    public $join = '';
    public $union = '';
    public $for_update = '';

    public $debug = false;

    /**
     * @var \Swoole\RecordSet
     */
    public $result;

    //Union联合查询
    private $if_union = false;
    private $union_select = '';

    //Join连接表
    private $if_join = false;
    private $if_add_tablename = false;

    protected $extraParmas = [];

    /**
     * 缓存选项
     * @var array
     */
    protected $cacheOptions = [];

    //Count计算
    private $count_fields = '*';

    public $page_size = 10;
    public $num = 0;
    public $pages = 0;
    public $page = 0;
    public $pager = null;

    public $auto_cache = false;

    public $RecordSet;

    public $is_execute = 0;

    public $result_filter = [];

    /**
     * @var \Swoole\Database
     */
    public $db;

    function __construct()
    {

    }

    /**
     * 初始化，select的值，参数$where可以指定初始化哪一项
     * @param $what
     */
    function init($what = '')
    {
        if($what == '') {
            $this->table='';
            $this->primary='id';
            $this->select='*';
            $this->sql='';
            $this->limit='';
            $this->where='';
            $this->order='';
            $this->group='';
            $this->use_index='';
            $this->join='';
            $this->union='';
        } else {
            $this->$what = '';
        }
    }

    /**
     * 字段等于某个值，支持子查询，$_where可以是对象
     * @param $field
     * @param $_where
     */
    function equal($field, $_where)
    {
        if ($_where instanceof SelectDB) {
            $where = $field. '=(' . $_where->getsql() . ')';
        } else {
            $where = "`$field`='$_where'";
        }
        $this->where($where);
    }

    /**
     * 指定表名，可以使用table1,table2
     * @param $table
     */
    function from($table)
    {
        if (strpos($table,"`") === false) {
            $this->table= "`".$table."`";
        } else {
            $this->table=$table;
        }
    }

    /**
     * 指定查询的字段，select * from table
     * 可多次使用，连接多个字段
     * @param $select
     * @param $force
     * @return null
     */
    function select($select, $force = false)
    {
        if (is_array($select)) {
            $select = implode(',', $select);
        }
        if ($this->select == "*" || $force) {
            $this->select = $select;
        } else {
            $this->select = $this->select . ',' . $select;
        }
    }

    /**
     * where参数，查询的条件
     * @param $where
     * @return null
     */
    function where($where)
    {
        if ($this->where == "") {
            $this->where = "where " . $where;
        } else {
            $this->where = $this->where . " and " . $where;
        }
    }

    /**
     * 指定查询所使用的索引字段
     * @param $field
     * @return null
     */
    function useIndex($field)
    {
        self::sql_safe($field);
        $this->use_index = "use index($field)";
    }

    /**
     * 相似查询like
     * @param $field
     * @param $like
     * @return null
     */
    function like($field,$like)
    {
        self::sql_safe($field);
        $this->where("`{$field}` like '{$like}'");
    }

    /**
     * 使用or连接的条件
     * @param $where
     * @return null
     */
    function orwhere($where)
    {
        if ($this->where == "") {
            $this->where = "where " . $where;
        } else {
            $this->where = $this->where . " or " . $where;
        }
    }

    /**
     * 查询的条数
     * @param $limit
     * @return null
     */
    function limit($limit)
    {
        if (!empty($limit)) {
            $_limit = explode(',', $limit, 2);
            if (count($_limit) == 2) {
                $this->limit = 'limit ' . (int)$_limit[0] . ',' . (int)$_limit[1];
            } else {
                $this->limit = "limit " . (int)$limit;
            }
        } else {
            $this->limit = '';
        }
    }

    /**
     * 指定排序方式
     * @param $order
     * @return null
     */
    function order($order)
    {
        if (!empty($order)) {
            self::sql_safe($order);
            $this->order = "order by $order";
        } else {
            $this->order = '';
        }
    }

    /**
     * 组合方式
     * @param $group
     * @return null
     */
    function group($group)
    {
        if (!empty($group)) {
            self::sql_safe($group);
            $this->group = "group by $group";
        } else {
            $this->group = '';
        }
    }

    /**
     * group后条件
     * @param $having
     * @return null
     */
    function having($having)
    {
        if (!empty($having)) {
            $this->having = "HAVING $having";
        } else {
            $this->having = '';
        }
    }

    /**
     * IN条件
     * @param $field
     * @param $ins
     * @return null
     */
    function in($field, $ins)
    {
        if (is_array($ins)) {
            $ins = implode(',', $ins);
        } else {
            //去掉两边的分号
            $ins = trim($ins, ',');
        }
        $this->where("`$field` in ({$ins})");
    }

    /**
     * NOT IN条件
     * @param $field
     * @param $ins
     * @return null
     */
    function notin($field,$ins)
    {
        if (is_array($ins)) {
            $ins = implode(',', $ins);
        } else {
            //去掉两边的分号
            $ins = trim($ins, ',');
        }
        $this->where("`$field` not in ({$ins})");
    }

    /**
     * INNER连接
     * @param $table_name
     * @param $on
     * @return null
     */
    function join($table_name,$on)
    {
        $this->join.="INNER JOIN `{$table_name}` ON ({$on})";
    }

    /**
     * 左连接
     * @param $table_name
     * @param $on
     * @return null
     */
    function leftjoin($table_name,$on)
    {
        $this->join.="LEFT JOIN `{$table_name}` ON ({$on})";
    }

    /**
     * 右连接
     * @param $table_name
     * @param $on
     * @return null
     */
    function rightjoin($table_name,$on)
    {
        $this->join.="RIGHT JOIN `{$table_name}` ON ({$on})";
    }

    /**
     * 分页参数,指定每页数量
     * @param $pagesize
     * @return null
     */
    function pagesize($pagesize)
    {
        $this->page_size = (int)$pagesize;
    }

    /**
     * 分页参数,指定当前页数
     * @param $page
     * @return null
     */
    function page($page)
    {
        $this->page = (int)$page;
    }

    /**
     * 主键查询条件
     * @param $id
     * @return null
     */
    function id($id)
    {
        $this->where("`{$this->primary}` = '$id'");
    }

    /**
     * 启用缓存
     * @param $params
     */
    function cache($params = true)
    {
        if ($params === false) {
            $this->enableCache = false;
        } else {
            $this->cacheOptions = $params;
            $this->enableCache = true;
        }
    }

    /**
     * 产生分页
     * @return null
     */
    function paging()
    {
        $this->num = $this->count();
        $offset = ($this->page - 1) * $this->page_size;
        if ($offset < 0) {
            $offset = 0;
        }
        if ($this->num % $this->page_size > 0) {
            $this->pages = intval($this->num / $this->page_size) + 1;
        } else {
            $this->pages = $this->num / $this->page_size;
        }
        $this->limit($offset . ',' . $this->page_size);
        // $this->pager = new Pager(array('total'    => $this->num,
        //                                'perpage'  => $this->page_size,
        //                                'nowindex' => $this->page
        // ));
    }

    /**
     * 检查SQL参数是否安全（有特殊字符）
     * @param $sql_sub
     * @throws SQLException
     */
    static function sql_safe($sql_sub)
    {
        if (!preg_match(self::$allow_regx, $sql_sub)) {
            if (self::$error_call === '') {
                throw new SQLException("sql block '{$sql_sub}' is unsafe!");
            } else {
                call_user_func(self::$error_call);
            }
        }
    }

    /**
     * 获取组合成的SQL语句字符串
     * @param $ifreturn
     * @return string | null
     */
    function getsql($ifreturn = true)
    {
        $this->sql = "select {$this->select} from {$this->table}";
        $this->sql .= implode(' ',
            [   $this->join,
                $this->use_index,
                $this->where,
                $this->union,
                $this->group,
                $this->having,
                $this->order,
                $this->limit,
                $this->for_update,
            ]);

        if ($this->if_union) {
            $this->sql = str_replace('{#union_select#}', $this->union_select, $this->sql);
        }

        if ($ifreturn || $this->debug) {
            return $this->sql;
        }
    }

    function raw_put($params)
    {
        foreach ($params as $array) {
            if (isset($array[0]) and isset($array[1]) and count($array) == 2) {
                $this->_call($array[0], $array[1]);
            } else {
                $this->raw_put($array);
            }
        }
    }

    /**
     * 锁定行或表
     * @return null
     */
    function lock()
    {
        $this->for_update = 'for update';
    }

    /**
     * SQL联合
     * @param $sql
     * @return null
     */
    function union($sql)
    {
        $this->if_union = true;
        if($sql instanceof SelectDB) {
            $this->union_select = $sql->select;
            $sql->select = '{#union_select#}';
            $this->union = 'UNION ('.$sql->getsql(true).')';
        }
        else $this->union = 'UNION ('.$sql.')';
    }

    // /**
    //  * 将数组作为指令调用
    //  * @param $params
    //  * @return null
    //  */
    // function put($params)
    // {
    //     if(isset($params['put'])) {
    //         trigger_error('SelectDB Error! Params put() cannot call put()!');
    //     }
    //     //处理where条件
    //     if(isset($params['where'])) {
    //         $wheres = $params['where'];
    //         if(is_array($wheres)) {
    //             foreach($wheres as $where) {
    //                 $this->where($where);
    //             }
    //         } else {
    //             $this->where($wheres);
    //         }
    //         unset($params['where']);
    //     }
    //     //处理orwhere条件
    //     if(isset($params['orwhere'])) {
    //         $orwheres = $params['orwhere'];
    //         if(is_array($orwheres)) {
    //             foreach($orwheres as $orwhere) {
    //                 $this->orwhere($orwhere);
    //             }
    //         } else {
    //             $this->$orwheres($orwheres);
    //         }
    //         unset($params['orwhere']);
    //     }
    //     //处理walk调用
    //     if (isset($params['walk'])) {
    //         foreach($params['walk'] as $call) {
    //             list($key, $value) = each($call);
    //             if (strpos($key, '_') !== 0) {
    //                 $this->_call($key, $value);
    //             } else {
    //                 $this->extraParmas[substr($key, 1)] = $value;
    //             }
    //         }
    //         unset($params['walk']);
    //     }
    //     //处理其他参数
    //     foreach ($params as $key => $value) {
    //         if (strpos($key, '_') !== 0) {
    //             $this->_call($key, $value);
    //         } else {
    //             $this->extraParmas[substr($key, 1)] = $value;
    //         }
    //     }
    // }

    // /**
    //  * @param $method
    //  * @param $param
    //  * @return bool
    //  */
    // protected function _call($method, $param)
    // {
    //     if ($method == 'update' or $method == 'delete' or $method == 'insert') {
    //         return false;
    //     }

    //     //调用对应的方法
    //     if (method_exists($this, $method)) {
    //         if (is_array($param)) {
    //             call_user_func_array([$this, $method], $param);
    //         } else {
    //             $this->$method($param);
    //         }
    //     }
    //     //直接将Key作为条件
    //     else {
    //         $param = $this->db->quote($param);
    //         if ($this->call_by == 'func') {
    //             $this->where($method . '="' . $param . '"');
    //         } elseif ($this->call_by == 'smarty') {
    //             if (strpos($param, '$') === false) {
    //                 $this->where($method . "='" . $param . "'");
    //             } else {
    //                 $this->where($method . "='{" . $param . "}'");
    //             }
    //         } else {
    //             trigger_error('Error: SelectDB 错误的参数'. "<pre>参数$method=$param</pre>");
    //         }
    //     }
    //     return true;
    // }
}

class SQLException extends \Exception
{

}
