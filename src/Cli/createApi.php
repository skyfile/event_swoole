<?php
require realpath(__DIR__) . '/cli.php';
Cli::index(true);
require realpath(__DIR__ . '/../') . '/Boot/boot.php';

$start = new CreateApi();
$start->run();

/**
 * 创建API
 */
class CreateApi
{
    public $name;
    public $argv;
    public $init         = false;
    public $version      = 0;
    public $createFailed = false; //是否创建失败
    public $files        = [];
    public $floor        = 1;
    public $delAll       = false; //是否删除全部模块

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
            'd|del'      => '删除指定API模块',
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
        Cli::echo_cli('帮助信息:');
        $this->optionKit->specs->printOptions();
        Cli::echo_cli();
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
            $dirName = APP_PATH . '/Api' . ($this->name ? '/' . $this->name : '');
        }
        if (!is_dir($dirName)) {
            return Cli::warning_cli('没有此目录, 可创建');
        }
        if ($handle = opendir($dirName)) {
            $prefix   = '|' . str_repeat('_', ($this->floor - 1) * 2);
            $showName = explode('/', $dirName);
            $showName = $prefix . end($showName);
            Cli::echo_cli($showName, 'green');
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if (is_dir("$dirName/$item")) {
                        $this->floor += 1;
                        $this->getlist("$dirName/$item");
                        $this->floor -= 1;
                    } else {
                        Cli::echo_cli($prefix . '__' . $item . '*');
                    }
                }
            }
            closedir($handle);
        }
    }

    /**
     * 删除指定模块
     * @param  [type] $dirName [description]
     * @return [type]          [description]
     */
    public function del()
    {
        while (true) {
            if (isset($this->opt['name']) && $this->opt['name']->value) {
                $this->name = \Sys\Tool::toCamelCase($this->opt['name']->value);
            } elseif (strpos($this->argv[1], '-') !== 0) {
                $this->name = \Sys\Tool::toCamelCase($this->argv[1]);
            } else {
                if (!$this->name) {
                    $this->name = Cli::ask_cli('输入指定模块名称: ');
                    if ($this->name == '') {
                        continue;
                    }
                }
            }

            //判定模块是否存在
            $modelDir = APP_PATH . '/Api/' . \Sys\Tool::toCamelCase($this->name);
            if (!is_dir($modelDir)) {
                Cli::warning_cli("{$this->name} 模块不存在");
                $this->name = '';
                continue;
            }

            //询问是否完整删除或指定版本
            if (isset($this->opt['version']) && $this->opt['version']->value) {
                $this->version = (int) $this->opt['version']->value;
            } else {
                if ($this->delAll === false) {
                    $this->delAll = Cli::ask_cli('是否删除整个模块(y/n): ');
                    if ($this->delAll == 'y') {
                        $this->version = 0;
                    } else {
                        $this->version = $this->version ? $this->version : 0;
                        $this->delAll  = 'n';
                    }
                }
                if ($this->delAll == 'n' && !$this->version) {
                    $this->version = (int) Cli::ask_cli('输入指定版本号：v');
                    if ($this->version == 0) {
                        continue;
                    }
                }
            }

            $dir = $modelDir . ($this->version != 0 ? '/V' . $this->version : '');
            if ($this->delAll == 'n' && !is_dir($dir)) {
                Cli::warning_cli("{$this->name} 模块下 v{$this->version} 版本不存在");
                $this->version = 0;
                continue;
            }

            if ('y' == Cli::ask_cli('确定删除?(y/n):')) {
                Cli::echo_cli($dir);
                Cli::delDirAndFile($dir);
            }
            break;
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

        //模块列表
        if (isset($this->opt['list'])) {
            return $this->getlist();
        }

        //删除模块
        if (isset($this->opt['del'])) {
            return $this->del();
        }

        //是否强制
        $this->init = isset($this->opt['init']) ? true : $this->init;

        //API名称
        if (isset($this->opt['name']) && $this->opt['name']->value) {
            $this->name = \Sys\Tool::toCamelCase($this->opt['name']->value);
        } elseif (strpos($this->argv[1], '-') !== 0) {
            $this->name = \Sys\Tool::toCamelCase($this->argv[1]);
        } else {
            Cli::warning_cli('请输入有效参数');
            return $this->help();
        }

        //API版本
        if (isset($this->opt['version']) && $this->opt['version']->value) {
            $this->version = (int) $this->opt['version']->value;
        } else {
            $this->version = 1;
        }

        $dir = APP_PATH . '/Api/' . $this->name . '/V' . $this->version;
        if (is_dir($dir) && !$this->init) {
            $r = Cli::ask_cli('该Api模块此版本已经存在， 是否重置该模块(y/n):');
            if ($r == 'y') {
                $this->init = true;
                return $this->run();
            } else {
                return;
            }
        }

        // Cli::echo_cli($this->name);
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
            $dir = APP_PATH . '/' . str_replace('\\', '/', $namespace);
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
                    Cli::warning_cli('文件: ' . $fileName . ' 创建失败!');
                    return;
                }
                Cli::echo_cli('文件: ' . $fileName . ' 创建成功!');
            }
        }
        if (!$this->createFailed) {
            Cli::echo_cli('创建完成!!!');
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
            Cli::echo_cli('目录: ' . $dir . ' 创建成功.');
            return true;
        } else {
            Cli::warning_cli('目录: ' . $dir, ' 创建失败, 可以尝试root权限执行');
            return false;
        }
    }

    public function __destruct()
    {
        if ($this->createFailed) {
            Cli::delDirAndFile(APP_PATH . '/Api/' . $this->name . ($this->version ? '/V' . $this->version : ''));
        }
    }
}
