<?php
/**
 * cli基础类
 */
class Cli
{
    static $module;

    public static function index($is_exists = false)
    {
        while (empty(self::$module)) {
            self::$module = self::ask_cli('请输入模块名称:');
            if (empty(self::$module)) {
                continue;
            }
            self::$module = ucfirst(strtolower(self::$module));
            if ($is_exists) {
                if (!is_dir(realpath(__DIR__ . '/../' . self::$module))) {
                    self::warning_cli('该模块不存在!!!');
                    self::$module = '';
                    continue;
                }
                !defined('CURR_MODULE') && define('CURR_MODULE', self::$module);
            }
        }
    }

    /**
     * 询问参数
     * @param  [string] $quest [提示语]
     * @return [string] [用户输入的信息]
     */
    public static function ask_cli($quest = '')
    {
        fwrite(STDOUT, $quest);
        return trim(fgets(STDIN));
    }

    /**
     * 输出警告信息
     * @param  [type] $str [description]
     * @return [type]      [description]
     */
    public static function warning_cli($str = '')
    {
        fwrite(STDERR, sprintf("\033[0;31m%s\033[0m", $str . "\n"));
    }

    /**
     * 输出
     * @param  string $str 内容
     * @param  string $color 颜色
     * @return [type]      [description]
     */
    public static function echo_cli($str = '', $col = 'blue')
    {
        $colors = [
            'red'    => "\033[0;31m%s\033[0m",
            'green'  => "\033[0;32m%s\033[0m",
            'blue'   => "\033[0;34m%s\033[0m",
            'cyan'   => "\033[0;36m%s\033[0m",
            'purple' => "\033[0;35m%s\033[0m",
            'brown'  => "\033[0;33m%s\033[0m",
            'yellow' => "\033[1;33m%s\033[0m",
        ];
        $c = isset($colors[$col]) ? $colors[$col] : $colors['blue'];
        fwrite(STDOUT, sprintf($c, $str . "\n"));
    }

    /**
     * 循环删除目录和其中的文件
     * @param  [type] $dirName [description]
     * @return [type]          [description]
     */
    public static function delDirAndFile($dirName)
    {
        if ($handle = opendir("$dirName")) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if (is_dir("$dirName/$item")) {
                        self::delDirAndFile("$dirName/$item");
                    } else {
                        if (unlink("$dirName/$item")) {
                            self::echo_cli("成功删除文件： $dirName/$item");
                        }
                    }
                }
            }
            closedir($handle);
            if (rmdir($dirName)) {
                self::echo_cli("成功删除目录： $dirName");
            }

        }
    }
}
