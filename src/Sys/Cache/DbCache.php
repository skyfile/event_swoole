<?php
namespace Sys\Cache;

/**
 * mysql缓存类
 */
class DbCache implements Cache
{
    public $db;
    public $shard_id = 0;

    public function __construct($ckey)
    {
        $table                   = 'app_cache';
        $this->model             = \Sys::$obj->getModel($table, $ckey);
        $this->model->create_sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `ckey` VARCHAR( 128 ) NOT NULL ,
            `cvalue` TEXT NOT NULL ,
            `sid` INT NOT NULL ,
            `expire` INT NOT NULL ,
            INDEX ( `ckey` ),
            UNIQUE KEY `ckeysid` (`sid`,`ckey`)
            ) ENGINE = INNODB DEFAULT CHARSET=utf8;";
        $this->model->createTable();
    }

    public function shard($id = 0)
    {
        $this->shard_id = $id;
    }

    /**
     * 过滤过期数据
     * @param  [type] $rs [description]
     * @return [type]     [description]
     */
    private function _filter_expire($rs)
    {
        if ($rs['expire'] != 0 && $rs['expire'] < time()) {
            $this->model->where(['id' => $rs['id']])->delete();
            return false;
        } else {
            return unserialize($rs['cvalue']);
        }
    }

    /**
     * 获取
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    public function get($key)
    {
        $select = 'id,cvalue,expire';
        $where  = [
            'sid'  => $this->shard_id,
            'ckey' => $key,
        ];

        $rs = $this->model->select($select)->where($where)->getOne();

        if (empty($rs)) {
            return false;
        }

        return $this->_filter_expire($rs);
    }

    /**
     * 设置
     * @param [type]  $key    [description]
     * @param [type]  $value  [description]
     * @param integer $expire [description]
     */
    public function set($key, $value, $expire = 0)
    {
        $data = [
            'ckey'   => $key,
            'cvalue' => serialize($value),
            'expire' => $expire == 0 ? 0 : time() + $expire,
            'sid'    => $this->shard_id,
        ];

        if (@$this->model->insert($data) !== false) {
            return true;
        } elseif ($this->model->where(['ckey' => $key, 'sid' => $this->shard_id])->update($data) !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 删除
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    public function delete($key)
    {
        return $this->model->where(['sid' => $this->shard_id, 'ckey' => $key])->delete();
    }
}
