<?php
require realpath(__DIR__ . '/../') . '/Boot/boot.php';

$start = new CreateApi();
$start->run();

/**
 * 创建API
 */
class CreateApi
{
    public $name;
    public $init = false;

    public function __construct()
    {
        global $argv;
        $this->argv = $argv;
        $this->opt();
    }

    public function opt()
    {
        $this->optionKit = new \GetOptionKit\GetOptionKit;
        $defaultOptions  = [
            'h|help'  => '显示帮助界面',
            'n|name?' => 'api模块名称',
            'i|init'  => '强制创建或重置',
        ];
        foreach ($defaultOptions as $k => $v) {
            //解决Windows平台乱码问题
            if (PHP_OS == 'WINNT') {
                $v = iconv('utf-8', 'gbk', $v);
            }
            $this->optionKit->add($k, $v);
        }
        $this->opt = $this->optionKit->parse($this->argv);
    }

    /**
     * 帮助
     * @return [type] [description]
     */
    public function help()
    {
        $this->echo_cli();
        $this->optionKit->specs->printOptions();
        $this->echo_cli();
    }

    /**
     * 运行
     * @return [type] [description]
     */
    public function run()
    {
        if (empty($this->argv[1]) || isset($this->opt['help'])) {
            return $this->help();
        }

        $this->init = isset($this->opt['init']) ? true : $this->init;

        if (isset($this->opt['name']) && $this->opt['name']) {
            $this->name = \Sys\Tool::toCamelCase($this->opt['name']);
        } elseif (strpos($this->argv[1], '-') !== 0) {
            $this->name = \Sys\Tool::toCamelCase($this->argv[1]);
        } else {
            $this->warning_cli('请输入有效参数');
            return $this->help();
        }

        $dir = BASE_PATH . '/Api/' . $this->name;
        if (is_dir($dir) && !$this->init) {
            $r = $this->ask_cli('该Api模块已经存在， 是否重置该模块(y/n):');
            if ($r == 'y') {
                $this->init = true;
                return $this->run();
            } else {
                return;
            }
        }

        $this->echo_cli($this->name);

    }

    /**
     * 询问参数
     * @param  [string] $quest [提示语]
     * @return [string] [用户输入的信息]
     */
    public function ask_cli($quest = '')
    {
        fwrite(STDOUT, $quest);
        return trim(fgets(STDIN));
    }

    /**
     * 输出警告信息
     * @param  [type] $str [description]
     * @return [type]      [description]
     */
    public function warning_cli($str = '')
    {
        fwrite(STDERR, sprintf("\033[0;31m%s\033[0m", $str . "\n"));
    }

    /**
     * 输出
     * @param  string $str [description]
     * @return [type]      [description]
     */
    public function echo_cli($str = '')
    {
        fwrite(STDOUT, sprintf("\033[0;34m%s\033[0m", $str . "\n"));
    }
}
