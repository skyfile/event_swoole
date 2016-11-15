<?php
require realpath(__DIR__) . '/cli.php';
require realpath(__DIR__) . '/../Boot/define.php';
Cli::index();

//composer
require_once VENDOR_PATH . 'autoload.php';

// \Sys::getInstance();
$start = new createModule();
$start->run();

/**
 * 创建模型
 */
class createModule
{
    public $argv;
    public $appPath;
    public $over = true;

    //模块目录结构
    public $dirStructure = [
        'Api'        => [
            'isDir' => true,
            'mode'  => 0775,
            'list'  => [
                'Base' => ['isDir' => false, 'suffix' => 'php'],
            ],
        ],
        'Config'     => [
            'isDir' => true,
            'mode'  => 0775,
            'list'  => [
                'development' => [
                    'isDir' => true,
                    'mode'  => 0775,
                    'list'  => [
                        'db'    => ['isDir' => false, 'suffix' => 'php'],
                        'event' => ['isDir' => false, 'suffix' => 'php'],
                        'log'   => ['isDir' => false, 'suffix' => 'php'],
                        'redis' => ['isDir' => false, 'suffix' => 'php'],
                    ],
                ],
                'production'  => [
                    'isDir' => true,
                    'mode'  => 0775,
                    'list'  => [
                        'db'    => ['isDir' => false, 'suffix' => 'php'],
                        'event' => ['isDir' => false, 'suffix' => 'php'],
                        'log'   => ['isDir' => false, 'suffix' => 'php'],
                        'redis' => ['isDir' => false, 'suffix' => 'php'],
                    ],
                ],
                'test'        => [
                    'isDir' => true,
                    'mode'  => 0775,
                    'list'  => [
                        'db'    => ['isDir' => false, 'suffix' => 'php'],
                        'event' => ['isDir' => false, 'suffix' => 'php'],
                        'log'   => ['isDir' => false, 'suffix' => 'php'],
                        'redis' => ['isDir' => false, 'suffix' => 'php'],
                    ],
                ],
            ],
        ],
        'Controller' => [
            'isDir' => true,
            'mode'  => 0775,
            'list'  => [
                'Base'  => ['isDir' => false, 'suffix' => 'php'],
                'Index' => ['isDir' => false, 'suffix' => 'php'],
            ],
        ],
        'Data'       => [
            'isDir' => true,
            'mode'  => 0777,
            'list'  => [
                'Logs' => ['isDir' => true, 'mode' => 0775, 'list' => []],
                'View' => ['isDir' => true, 'mode' => 0775, 'list' => []],
            ],
        ],
        'Event'      => [
            'isDir' => true,
            'mode'  => 0775,
            'list'  => [],
        ],
        'Model'      => [
            'isDir' => true,
            'mode'  => 0775,
            'list'  => [
                'Base' => ['isDir' => false, 'suffix' => 'php'],
            ],
        ],
        'View'       => [
            'isDir' => true,
            'mode'  => 0775,
            'list'  => [
                'Index' => [
                    'isDir' => true,
                    'mode'  => 0775,
                    'list'  => [
                        'Index' => ['isDir' => false, 'suffix' => 'html'],
                    ],
                ],
            ],
        ],
    ];

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
            'n|name?' => '模块名称',
            'a|add'   => '创建新模块',
            'l|list'  => '显示当前模块列表',
            'd|del'   => '删除指定模块',
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
     * 新增模块
     */
    public function add()
    {
        while (true) {
            $this->appPath = BASE_PATH . '/' . Cli::$module . '/';
            if (is_dir($this->appPath)) {
                Cli::warning_cli('该模块已经存在，请输入其它模块名称');
                Cli::$module = '';
                Cli::index();
                continue;
            }
            if (!mkdir($this->appPath)) {
                Cli::warning_cli('创建模块根目录失败，请检查用户与目录的权限，或使用root权限操作');
                break;
            }
            //循环创建结构
            foreach ($this->dirStructure as $dir => $list) {
                $this->create($dir);
            }
            if ($this->over) {
                Cli::echo_cli(Cli::$module . '模块创建完成！！');
            } else {
                Cli::delDirAndFile($this->appPath);
            }
            break;
        }
    }

    /**
     * 执行方法
     * @return [type] [description]
     */
    public function run()
    {
        if (empty($this->argv[1]) || isset($this->opt['help'])) {
            return $this->help();
        }

        //模块列表
        if (isset($this->opt['add'])) {
            return $this->add();
        }
    }

    /**
     * 创建配置文件目录
     * @return [type] [description]
     */
    protected function create($keyName, $basePath = '', $list = '')
    {
        if ($basePath == '') {
            $structure = $this->dirStructure[$keyName];
            $basePath  = $this->appPath . "$keyName/";
            if (!mkdir($basePath, $structure['mode'])) {
                Cli::warning_cli("创建 $keyName 目录出错");
                $this->over = false;
                return false;
            }
            $list = $structure['list'];
        }
        foreach ($list as $k => $v) {
            //创建目录
            if ($v['isDir']) {
                $fileName = $basePath . $k . '/';
                if (!mkdir($fileName, $v['mode'])) {
                    Cli::warning_cli("创建 $fileName 目录出错");
                    $this->over = false;
                    return false;
                }
                $this->create($keyName, $fileName, $v['list']);
            } else {
                //创建文件
                $fileName = $basePath . $k . '.' . $v['suffix'];
                $content  = call_user_func_array([$this, 'get' . ucfirst($keyName) . ucfirst($k) . 'File'], []);
                if (!file_put_contents($fileName, $content)) {
                    Cli::warning_cli("创建文件：$fileName 出错！！");
                    $this->over = false;
                    return false;
                }
            }
        }
    }

    protected function getApiBaseFile()
    {
        $content = <<<EOF
<?php
namespace Api;

/**
* Api方法基础类
*/
class Base extends \Sys\Api
{
}

EOF;
        return $content;
    }

    /**
     * Config db文件
     * @return [type] [description]
     */
    protected function getConfigDbFile()
    {
        $content = <<<EOF
<?php
\$db['master'] = [
    'type'       => \Sys\Db::TYPE_MYSQLI,
    'host'       => '127.0.0.1',
    'port'       => 3306,
    'dbms'       => 'mysql',
    'user'       => 'root',
    'passwd'     => 'root',
    'name'       => 'test',
    'charset'    => 'utf8',
    'setname'    => true,
    'persistent' => true,  //MySQL长连接
    'use_proxy'  => false, //启动读写分离Proxy
    'slaves'     => [
        ['host' => '127.0.0.1', 'port' => '3307', 'weight' => 100],
        ['host' => '127.0.0.1', 'port' => '3308', 'weight' => 99],
        ['host' => '127.0.0.1', 'port' => '3309', 'weight' => 98],
    ],
];

return \$db;
EOF;
        return $content;
    }

    /**
     * Config event文件
     * @return [type] [description]
     */
    protected function getConfigEventFile()
    {
        $module  = Cli::$module;
        $content = <<<EOF
<?php

return [
    'master' => [
        'async'    => true,
        'key'      => '$module:queue', //队列存储key
        'pid_path' => '/var/run/',
    ],
];
EOF;
        return $content;
    }

    /**
     * Config Log文件
     * @return [type] [description]
     */
    protected function getConfigLogFile()
    {
        $content = <<<EOF
<?php

\$log['master'] = [
    'type'     => 'FileLog',
    'dir'      => APP_PATH . '/Data/Logs/',
    'date'     => true,
    'cut_file' => true,
];

return \$log;

EOF;
        return $content;
    }

    /**
     * Config Redis 文件
     * @return [type] [description]
     */
    protected function getConfigRedisFile()
    {
        $content = <<<EOF
<?php

\$redis['master'] = [
    'host'     => '127.0.0.1',
    'port'     => 6379,
    'password' => '',
    'timeout'  => 0.25,
    'pconnect' => false,
    'database' => 1,
];
return \$redis;
EOF;
        return $content;
    }

    /**
     * Controller Base 文件
     * @return [type] [description]
     */
    protected function getControllerBaseFile()
    {
        $content = <<<EOF
<?php
namespace Controller;

/**
 * 基本类
 */
class Base extends \Sys\Controller
{
}

EOF;
        return $content;
    }

    /**
     * Controller Index 文件
     * @return [type] [description]
     */
    protected function getControllerIndexFile()
    {
        $module  = Cli::$module;
        $content = <<<EOF
<?php
namespace Controller;

/**
 * 默认
 */
class Index extends Base
{
    public function index(\$value = '')
    {
        \$this->view->setVar([
            'title' => '默认页面',
            'key'   => '$module 恭喜你， 模块创建成功',
        ]);
        \$this->view->setView('test');
    }
}

EOF;
        return $content;
    }

    /**
     * Model Base 文件
     * @return [type] [description]
     */
    protected function getModelBaseFile()
    {
        $content = <<<EOF
<?php
namespace Model;

/**
 * appid模型
 */
class Base extends \Sys\Model
{
}

EOF;
        return $content;
    }

    /**
     * View Index文件
     * @return [type] [description]
     */
    public function getViewIndexFile()
    {
        $content = <<<EOF
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>{{ title }}</title>
</head>
<body>
	{{ name }}
</body>
</html>
EOF;
        return $content;
    }
}
