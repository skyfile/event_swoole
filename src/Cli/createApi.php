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
    public $init         = false;
    public $version      = 1;
    public $createFailed = false; //是否创建失败
    public $files        = [];
    public $floor        = 1;

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
            'h|help'     => '显示帮助界面',
            'n|name?'    => 'API模块名称',
            'v|version?' => 'API版本号',
            'i|init'     => '强制创建或重置',
            'l|list'     => '显示当前API模块列表',
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
     * 显示模块列表
     * @return [type] [description]
     */
    public function getlist($dirName = '')
    {
        if ($dirName == '') {
            if (isset($this->opt['name']) && $this->opt['name']->value) {
                $this->name = \Sys\Tool::toCamelCase($this->opt['name']->value);
            } elseif (strpos($this->argv[1], '-') !== 0) {
                $this->name = \Sys\Tool::toCamelCase($this->argv[1]);
            } else {
                $this->name = '';
            }
            $dirName = BASE_PATH . '/Api' . ($this->name ? '/' . $this->name : '');
        }
        if (!is_dir($dirName)) {
            return $this->warning_cli('没有此目录, 请检查参数');
        }
        if ($handle = opendir($dirName)) {
            $prefix   = '|' . str_repeat('_', ($this->floor - 1) * 2);
            $showName = $prefix . ' ' . end(explode('/', $dirName));
            $this->echo_cli($showName);
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if (is_dir("$dirName/$item")) {
                        $this->floor += 1;
                        $this->getlist("$dirName/$item");
                        $this->floor -= 1;
                    } else {
                        $this->echo_cli($prefix . '__' . $item);
                    }
                }
            }
            closedir($handle);
        }
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

        if (isset($this->opt['list'])) {
            return $this->getlist();
        }

        //是否强制
        $this->init = isset($this->opt['init']) ? true : $this->init;

        //API名称
        if (isset($this->opt['name']) && $this->opt['name']->value) {
            $this->name = \Sys\Tool::toCamelCase($this->opt['name']->value);
        } elseif (strpos($this->argv[1], '-') !== 0) {
            $this->name = \Sys\Tool::toCamelCase($this->argv[1]);
        } else {
            $this->warning_cli('请输入有效参数');
            return $this->help();
        }

        //API版本
        if (isset($this->opt['version']) && $this->opt['version']->value) {
            $this->version = (int) $this->opt['version']->value;
        }

        $dir = BASE_PATH . '/Api/' . $this->name . '/V' . $this->version;
        if (is_dir($dir) && !$this->init) {
            $r = $this->ask_cli('该Api模块此版本已经存在， 是否重置该模块(y/n):');
            if ($r == 'y') {
                $this->init = true;
                return $this->run();
            } else {
                return;
            }
        }

        // $this->echo_cli($this->name);
        $this->create();
    }

    public function create()
    {
        $this->files = [
            '\\' . $this->name                          => [
                'file' => [
                    [
                        'name'    => 'Common',
                        'des'     => '模块公共类',
                        'extends' => '\\Api\\Base',
                        'has'     => false,
                    ],
                ],
                'has'  => false,
            ],
            '\\' . $this->name . '\\V' . $this->version => [
                'file' => [
                    ['name' => 'Api', 'des' => '版本公共类', 'extends' => '\\Api\\' . $this->name . '\\Common', 'has' => false],
                    ['name' => 'Get', 'des' => '取出资源模块', 'extends' => 'Api', 'has' => false],
                    ['name' => 'Delete', 'des' => '删除资源模块', 'extends' => 'Api', 'has' => false],
                    ['name' => 'Options', 'des' => '获取API信息模块', 'extends' => 'Api', 'has' => false],
                    ['name' => 'Post', 'des' => '创建资源模块', 'extends' => 'Api', 'has' => false],
                    ['name' => 'Put', 'des' => '更新完整资源模块', 'extends' => 'Api', 'has' => false],
                    ['name' => 'Patch', 'des' => '新部分资源模块', 'extends' => 'Api', 'has' => false],
                ],
                'has'  => false,
            ],
        ];
        $this->createFile();
    }

    public function createFile()
    {
        foreach ($this->files as $key => $config) {
            $namespace = 'Api' . $key;
            //创建目录
            $dir = BASE_PATH . '/' . str_replace('\\', '/', $namespace);
            if (!is_dir($dir)) {
                if (!$this->createDir($dir)) {
                    return $this->createFailed = true;
                }
            } else {
                //记录是否原本就存在此文件夹, 方便回退时删除判定
                $this->files[$key]['has'] = true;
            }

            //开始创建文件
            foreach ($config['file'] as $k => $fileConf) {
                $fileName = $dir . '/' . $fileConf['name'] . '.php';
                //标记原本是否存在此文件,
                if (is_file($fileName)) {
                    $this->files[$key]['file'][$k]['has'] = true;
                    continue;
                }

                $content =
                    <<<EOF
<?php
namespace {$namespace};

/**
* {$fileConf['des']}
*/
class {$fileConf['name']} extends {$fileConf['extends']}
{

}
EOF;
                if (!file_put_contents($fileName, $content)) {
                    $this->createFailed = true;
                    $this->warning_cli('创建文件: ' . $fileName . '失败!');
                    return;
                }
                $this->echo_cli('创建文件: ' . $fileName . '成功!');
            }
        }
        if (!$this->createFailed) {
            $this->echo_cli('创建完成!!!');
        }
    }

    /**
     * 创建目录
     * @param  [type] $dir [description]
     * @return [type]      [description]
     */
    public function createDir($dir)
    {
        if (mkdir($dir, 0755, true)) {
            $this->echo_cli($dir . ' 目录创建成功.');
            return true;
        } else {
            $this->warning_cli($dir, '目录创建失败, 可以尝试root权限执行');
            return false;
        }
    }

    /**
     * 循环删除目录和其中的文件
     * @param  [type] $dirName [description]
     * @return [type]          [description]
     */
    public function delDirAndFile($dirName)
    {
        if ($handle = opendir("$dirName")) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if (is_dir("$dirName/$item")) {
                        delDirAndFile("$dirName/$item");
                    } else {
                        if (unlink("$dirName/$item")) {
                            $this->echo_cli("成功删除文件： $dirName/$item");
                        }
                    }
                }
            }
            closedir($handle);
            if (rmdir($dirName)) {
                $this->echo_cli("成功删除目录： $dirName");
            }

        }
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

    public function __destruct()
    {
        if ($this->createFailed) {
            // $this->delDirAndFile(BASE_PATH.'/'.$this)
        }
    }
}
