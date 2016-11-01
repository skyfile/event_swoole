<?php
namespace Sys;

/**
* 工具类
*/
class Tool
{
    const WEEK_TWO = '周';
    const WEEK_THREE = '星期';

    static public $url_key_join = '=';
    static public $url_param_join = '&';
    static public $url_prefix = '';
    static public $url_add_end = '';
    static public $number = array('〇','一','二','三','四','五','六','七','八','九');

    /**
     * 数字转星期
     * @param  [type]  $num [description]
     * @param  boolean $two [description]
     * @return [type]       [description]
     */
    static public function num2week($num, $two = true)
    {
        return ($two ? self::WEEK_TWO : self::WEEK_THREE) . ($num == '6' ? '日' : self::num2han($num + 1));
    }

    /**
     * 数字转为汉字
     * @param $num_str
     * @return mixed
     */
    static public function num2han($num_str)
    {
        return str_replace(range(0,9),self::$number,$num_str);
    }

    /**
     * 列出目录中的文件和目录(去除.和..)
     * @param  [type] $dir [description]
     * @return [type]      [description]
     */
    static public function scandir($dir)
    {
        if (function_exists('scandir')) {
            $files = scandir($dir);
            foreach ($files as $k => $v) {
                if ($v == '.' || $v == '..') {
                    unset($files[$k]);
                }
            }
            return array_values($files);
        } else {
            $dh = opendir($dir);
            while (false !== ($filename = readdir($dh))) {
                if ($filename == '.' || $filename == '..') {
                    continue;
                }
                $files[] = $filename;
            }
            sort($files);
            return $files;
        }
    }

    /**
     * 加锁读取文件
     * @param  [type]  $file      [description]
     * @param  boolean $exclusive [description]
     * @return [type]             [description]
     */
    static public function readFile($file, $exclusive = false)
    {
        $fp = fopen($file, 'r');
        if (!$fp) return false;
        $lockType = $exclusive ? LOCK_EX : LOCK_SH;
        if (flock($fp, $lockType) === false) {
            fclose($fp);
        }
        $content = '';
        while (!feof($fp)) {
            $content .= fread($fd, 8192);
        }
        flock($fd, LOCK_UN);
        return $content;
    }

    /**
     * 获取字符串最后一位
     * @param $string
     * @return mixed
     */
    static public function endchar($string)
    {
        return $string[strlen($string) - 1];
    }

    /**
     * 解析URI
     * @param string $url
     * @return array $return
     */
    static public function uri($url)
    {
        $res = parse_url($url);
        $return['protocol'] = $res['scheme'];
        $return['host'] = $res['host'];
        $return['port'] = $res['port'];
        $return['user'] = $res['user'];
        $return['pass'] = $res['pass'];
        $return['path'] = $res['path'];
        $return['id'] = $res['fragment'];
        parse_str($res['query'], $return['params']);
        return $return;
    }

    /**
     * 多久之前
     * @param $datetime
     * @return unknown_type
     */
    static public function howLongAgo($datetime)
    {
        $timestamp = strtotime($datetime);
        $seconds = time();

        $time = date('Y', $seconds) - date('Y', $timestamp);
        if ($time > 0) {
            return $time == 1 ? '去年' : $time . '年前';
        }

        $time = date('m', $seconds) - date('m', $timestamp);
        if ($time > 0) {
            return $time == 1 ? '上月' : $time . '个月前';
        }

        $time = date('d', $seconds) - date('d', $timestamp);
        if ($time > 0) {
            if ($time == 1) {
                return '昨天';
            } elseif ($time == 2) {
                return '前天';
            } else {
                return $time . '天前';
            }
        }

        $time = date('H', $seconds) - date('H', $timestamp);
        if ($time >= 1) {
            return $time . '小时前';
        }

        $time = date('i', $seconds) - date('i', $timestamp);
        if ($time >= 1) {
            return $time . '分钟前';
        }

        $time = date('s', $seconds) - date('s', $timestamp);
        return $time . '秒前';
    }

    /**
     * 合并URL字串，parse_query的反向函数
     * @param $urls
     * @return string
     */
    static public function combineQuery($urls)
    {
        $url = [];
        foreach ($urls as $k => $v) {
            if (!empty($k)) {
                $url[] = $k . self::$url_key_join . urlencode($v);
            }
        }
        return implode(self::$url_param_join, $url);
    }

    /**
     * URL添加参数
     * @param  [type] $url   [description]
     * @param  [type] $array [description]
     * @return [type]        [description]
     */
    static public function urlAppend($url, $array)
    {
        if (!is_array($array)) return false;
        $tip = (strpos($url, '?') === false) ? '?' : '&';
        $query = [];
        foreach ($array as $k => $v) {
            $query[] = $k . self::$url_key_join . urlencode($v);
        }
        return $url . $tip . implode(self::$url_param_join, $query);
    }

    /**
     * URL合并
     * @param $key
     * @param $value
     * @param $ignore
     * @return string
     */
    static function urlMerge($key, $value, $ignore = null, $urls = null)
    {
        $url = [];
        if ($urls === null) $urls = $_GET;

        $urls = array_merge($urls, array_combine(explode(',', $key), explode(',', $value)));
        if ($ignore !== null) {
            $ignores = explode(',', $ignore);
            foreach ($ignores as $ig) {
                unset($urls[$ig]);
            }
        }

        if (self::$url_prefix == '') {
            $qm = strpos($_SERVER['REQUEST_URI'], '?');
            if ($qm !== false) {
                $prefix = substr($_SERVER['REQUEST_URI'], 0, $qm + 1);
            } else {
                $prefix = $_SERVER['REQUEST_URI'] . '?';
            }
        } else {
            $prefix = self::$url_prefix;
        }
        return $prefix . http_build_query($urls) . self::$url_add_end;
    }

    /**
     * 数组编码转换
     * @param $in_charset
     * @param $out_charset
     * @param $data
     * @return $data
     */
    static public function arrayIconv($in_charset, $out_charset, $data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $value = self::array_iconv($in_charset, $out_charset, $value);
                } else {
                    $value = iconv($in_charset, $out_charset, $value);
                }
                $data[$key] = $value;
            }
        }
        return $data;
    }

    /**
     * 数组饱满度
     * @param $array
     * @return unknown_type
     */
    static public function arrayFullness($array)
    {
        $nulls = 0;
        foreach ($array as $v) {
            if (empty($v) or intval($v) < 0) {
                $nulls++;
            }
        }
        return 100 - intval($nulls / count($array) * 100);
    }

    /**
     * 根据生日中的月份和日期来计算所属星座*
     * @param int $birth_month
     * @param int $birth_date
     * @return string
     */
    static public function getConstellation($birth_month, $birth_date)
    {
        //判断的时候，为避免出现1和true的疑惑，或是判断语句始终为真的问题，这里统一处理成字符串形式
        $birth_month = strval($birth_month);
        $constellation_name = ['水瓶座', '双鱼座', '白羊座', '金牛座', '双子座', '巨蟹座', '狮子座', '处女座', '天秤座', '天蝎座', '射手座', '摩羯座'];
        if ($birth_date <= 22) {
            if ('1' !== $birth_month) {
                $constellation = $constellation_name[$birth_month - 2];
            } else {
                $constellation = $constellation_name[11];
            }
        } else {
            $constellation = $constellation_name[$birth_month - 1];
        }
        return $constellation;
    }

    /**
     * 根据生日中的年份来计算所属生肖
     * @param int $birth_year
     * @return string
     */
    static function getAnimal($birth_year, $format = '1')
    {
        //1900年是子鼠年
        if ($format == '2') {
            $animal = ['子鼠', '丑牛', '寅虎', '卯兔', '辰龙', '巳蛇', '午马', '未羊', '申猴', '酉鸡', '戌狗', '亥猪'];
        } elseif ($format == '1') {
            $animal = ['鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪'];
        }
        $my_animal = ($birth_year - 1900) % 12;
        return $animal[$my_animal];
    }

    /**
     * 根据生日来计算年龄
     *
     * 用Unix时间戳计算是最准确的，但不太好处理1970年之前出生的情况
     * 而且还要考虑闰年的问题，所以就暂时放弃这种方式的开发，保留思想
     *
     * @param int $birth_year
     * @param int $birth_month
     * @param int $birth_date
     * @return int
     */
    static public function getAge($birth_year, $birth_month, $birth_date)
    {
        $full_age = 0; //周岁，该变量放着，根据具体情况可以随时修改
        $now_year = date('Y', time());
        $now_date_num = date('z', time()); //该年份中的第几天
        $birth_date_num = date('z', mktime(0, 0, 0, $birth_month, $birth_date, $birth_year));

        if ($now_date_num - $birth_date_num > 0) {
            $full_age = $now_year - $birth_year;
        } else {
            $full_age = $now_year - $birth_year - 1;
        }

        return $full_age + 1;;
    }

    /**
     * 发送一个UDP包
     * @return unknown_type
     */
    static public function sendUDP($server_ip, $server_port, $data, $timeout = 30)
    {
        $client = stream_socket_client("udp://$server_ip:$server_port", $errno, $errstr, $timeout);
        if (!$client) {
            echo "ERROR: $errno - $errstr<br />\n";
        } else {
            fwrite($client, $data);
            fclose($client);
        }
    }

    /**
     * 复制目录
     * @param $fdir源目录名(不带/)
     * @param $tdir目标目录名(不带/)
     * @return
     */
    static public function dirCopy($fdir, $tdir)
    {
        if (is_dir($fdir)) {
            if (!is_dir($tdir)) {
                mkdir($tdir);
            }
            $handle = opendir($fdir);
            while (false !== ($filename = readdir($handle))) {
                if ($filename != "." && $filename != "..") {
                    self::dir_copy($fdir . "/" . $filename, $tdir . "/" . $filename);
                }
            }
            closedir($handle);
        } else {
            copy($fdir, $tdir);
        }
        return true;
    }

    /**
     * 在文件末尾处添加内容
     * @param  [type] $log  [description]
     * @param  string $file [description]
     * @return [type]       [description]
     */
    static public function fileAppend($log, $file = '')
    {
        if (empty($file)) return false;
        if (!is_string($log)) {
            $log = var_export($log, true);
        }
        if (self::endchar($log) !== "\n") {
            $log .= "\n";
        }
        file_put_contents($file, $log, FILE_APPEND);
    }

    /**
     * 字节转换为 KB MB GB
     * @param  [type]  $n     [description]
     * @param  integer $round [description]
     * @return [type]         [description]
     */
    static public function getHumanSize($n, $round = 3)
    {
        if ($n > 1024 * 1024 * 1024) {
            return round($n / (1024 * 1024 * 1024), $round) . "G";
        } elseif ($n > 1024 * 1024) {
            return round($n / (1024 * 1024), $round) . "M";
        } elseif ($n > 1024) {
            return round($n / (1024), $round) . "K";
        } else {
            return $n;
        }
    }

    /**
     * 显示程序消耗
     * @param  [type] $func [description]
     * @return [type]       [description]
     */
    static public function showCost($func)
    {
        $_t = microtime(true);
        $_m = memory_get_usage(true);
        call_user_func($func);
        $t = round((microtime(true) - $_t) * 1000, 3);
        $m = memory_get_usage(true) - $_m;
        echo "cost Time: {$t}ms, Memory=".self::getHumanSize($m)."\n";
    }

    /**
     * 驼峰转下划线 格式
     * @param  string $value [description]
     * @return [type]        [description]
     */
    static public function toUnderScore($str)
    {
        $arr = [];
        $len = strlen($str);
        for ($i=0; $i < $len; $i++) {
            $lower = strtolower($str[$i]);
            if ($str[$i] != $lower && $i > 0) {
                $arr[] = '_';
            }
            $arr[] = $lower;
        }
        return implode('', $arr);
    }

    /**
     * 下划线转驼峰 格式
     * @param  [type] $str [description]
     * @return [type]      [description]
     */
    static public function toCamelCase($str)
    {
      return implode('', array_map('ucfirst', explode('_', $str)));
    }

    /**
     * 根据配置获取随机从库配置信息
     * @param  array  $servers [description]
     * @return array          [description]
     */
    static function getServer(array $servers)
    {
        $weight = 0;
        //移除不在线的节点
        foreach ($servers as $k => $svr) {
            //节点已掉线
            if (!empty($svr['status']) and $svr['status'] == 'offline') {
                unset($servers[$k]);
            }
            $weight += $svr['weight'];
        }

        //计算权重并随机选择一台机器
        $use = rand(0, $weight - 1);
        $weight = 0;
        foreach ($servers as $k => $svr) {
            //默认100权重
            if (empty($svr['weight'])) {
                $svr['weight'] = 100;
            }
            $weight += $svr['weight'];
            //在权重范围内
            if ($use < $weight) {
                return $svr;
            }
        }
        //绝不会到这里
        return $servers[0];
    }

}
