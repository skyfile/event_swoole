<?php
namespace Sys;

/**
 * Session类
 */
class Session
{
    static $obj;
    static $cache_prefix    = 'phpsess_';  //缓存前缀
    static $cookie_lifetime = 86400000;    //session最大生命周期
    static $cache_lifetime  = 0;           //缓存时间 0为永久
    static $sess_size       = 32;          //session最大容量
    static $sess_name       = 'SESSID';    //session名称
    static $cookie_key      = 'PHPSESSID'; //sessionKey
    static $sess_domain;

    public $cache;

    public $handleList = ['redis', 'mysql', 'file']; //存储方式

    public function __construct($config = [])
    {
        if (isset($config['handle']) && in_array(strtolower($config['handle']), $this->handleList)) {
            $this->handle = strtolower($config['handle']);
        } else {
            $this->handle = 'file';
        }

        $this->init();
    }

    public static function getInstance($key = 'master')
    {
        if (!self::$obj) {
            $config = \Sys::$obj->config['session'];
            if (!isset($config[$key]) || empty($config[$key])) {
                throw new \Exception("redis->{$key} config is not fund.");
            }
            self::$obj = new self($config[$key]);
        }
        return self::$obj;
    }

    public function init()
    {
        //不使用 GET/POST 变量方式
        ini_set('session.use_trans_sid', 0);
        //设置垃圾回收最大生存时间
        ini_set('session.gc_maxlifetime', self::$cache_lifetime);
        //使用 COOKIE 保存 SESSION ID 的方式
        ini_set('session.use_cookies', 1);
        ini_set('session.cookie_path', '/');
        //多主机共享保存 SESSION ID 的 COOKIE
        ini_set('session.cookie_domain', self::$sess_domain);
        //将 session.save_handler 设置为 user，而不是默认的 files
        session_module_name('user');
        //定义 SESSION 各项操作所对应的方法名
        session_set_save_handler(
            [$this, 'open'],
            [$this, 'close'],
            [$this, 'get'],
            [$this, 'set'],
            [$this, 'delete'],
            [$this, 'gc']
        );
        session_start();
        return true;
    }

    /**
     * 设置 SessionID
     * @param [type] $session_id [description]
     */
    public function setId($session_id)
    {
        return session_id($session_id);
    }

    /**
     * 获取 SessionID
     * @return string
     */
    public function getId()
    {
        return session_id();
    }

    /**
     * 保存session
     * @return [type] [description]
     */
    public function save()
    {
        return $this->set($this->sessID, serialize($_SESSION));
    }

    /**
     * 打开Session
     * @param   String  $pSavePath
     * @param   String  $pSessName
     * @return  Bool    TRUE/FALSE
     */
    public function open($save_path = '', $sess_name = '')
    {
        self::$cache_prefix = $save_path . '_' . $sess_name;
        return true;
    }
    /**
     * 关闭Session
     * @param   NULL
     * @return  Bool    TRUE/FALSE
     */
    public function close()
    {
        return true;
    }
    /**
     * 读取Session
     * @param   String  $sessId
     * @return  Bool    TRUE/FALSE
     */
    public function get($sessId)
    {
        $session = $this->cache->get(self::$cache_prefix . $sessId);
        //先读数据，如果没有，就初始化一个
        if (!empty($session)) {
            return $session;
        } else {
            return [];
        }
    }

    /**
     * 设置Session的值
     * @param   String  $wSessId
     * @param   String  $wData
     * @return  Bool    true/FALSE
     */
    public function set($sessId, $session = '')
    {
        $key = self::$cache_prefix . $sessId;
        $ret = $this->cache->set($key, $session, self::$cache_lifetime);
        return $ret;
    }

    /**
     * 销毁Session
     * @param   String  $wSessId
     * @return  Bool    true/FALSE
     */
    public function delete($sessId = '')
    {
        return $this->cache->delete(self::$cache_prefix . $sessId);
    }
    /**
     * 内存回收
     * @param   NULL
     * @return  Bool    true/FALSE
     */
    public function gc()
    {
        return true;
    }
}
