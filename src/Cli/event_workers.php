<?php
require realpath(__DIR__) . '/cli.php';
Cli::index(true);
require realpath(__DIR__ . '/../') . '/Boot/boot.php';

$start = new Start();
$start->run();

/**
 * 启动服务
 */
class Start
{
    public $pidFile    = '/var/run/event_swoole_worker.pid';
    public $server_pid = 0;
    public $workerNums = 0;
    public $daemon     = false;
    public $argv       = [];
    public $opt        = [];
    public $optionKit  = null;

    public function __construct()
    {
        if (is_file($this->pidFile)) {
            $this->server_pid = file_get_contents($this->pidFile);
        }
        global $argv;
        $this->argv = $argv;
        $this->opt();
        $this->workerNums = isset($this->opt['worker']) ? (int) $this->opt['worker']->value : $this->workerNums;
        $this->daemon     = isset($this->opt['daemon']) ? true : $this->daemon;

    }

    public function opt()
    {
        $this->optionKit = new \GetOptionKit\GetOptionKit;
        $defaultOptions  = [
            'h|help'    => '显示帮助界面',
            'd|daemon'  => '启用守护进程模式(默认为守护进程)',
            'w|worker?' => '设置Worker进程的数量',
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
        $arr = [
            'start'  => '[启动]',
            'stop'   => '[停止]',
            'reload' => '[重启]',
            'add'    => '[动态新增进程]',
            'del'    => '[动态减少进程]',
        ];
        $tip = [];
        foreach ($arr as $key => $value) {
            $tip[] = sprintf("\033[0;34m%s\033[0m", $key) . ' ' . $value;
        }

        Cli::echo_cli(str_repeat('=', 90) . NL . implode(' | ', $tip) . NL . str_repeat('=', 90));
        $this->optionKit->specs->printOptions();
        Cli::echo_cli();
    }

    /**
     * 运行
     * @return [type] [description]
     */
    public function run()
    {
        if (empty($this->argv[1]) || isset($this->opt['help'])) {
            $this->help();
        } elseif (method_exists($this, $this->argv[1])) {
            $action = $this->argv[1];
            $this->$action();
        } else {
            $this->help();
        }
        return;
    }

    /**
     * 启动
     * @return [type] [description]
     */
    public function start()
    {
        if ($this->isRuning()) {
            Cli::warning_cli('Event_server is already running.');
            return false;
        }

        \Sys::setProcessName('event_swoole_worker');
        if ($this->workerNums) {
            Cli::echo_cli('Event_server is start running.');
            \Sys::$obj->event->runWorker($this->workerNums ? $this->workerNums : 3, $this->daemon);
        } else {
            $workerNums = (int) Cli::ask_cli('请设置进程数量 -w:');
            if ($workerNums > 0) {
                $this->workerNums = $workerNums;
            } else {
                Cli::warning_cli('您设置进程数量无效, 请重新输入');
            }
            $this->start();
        }
        return true;
    }

    /**
     * 终止
     * @return [type] [description]
     */
    public function stop()
    {
        if (!$this->isRuning()) {
            Cli::warning_cli('Event_server is not working.');
            return false;
        }

        $res = strtolower(Cli::ask_cli('确定?(y/n):'));
        if ($res == 'y') {
            if (\Sys::$obj->Platform->kill($this->server_pid, SIGTERM)) {
                Cli::echo_cli('Event_server is stop.');
                return true;
            } else {
                Cli::warning_cli("Event_server can't stop!!! You can to kill master process!!!");
                return false;
            }
        } elseif ($res == 'n') {
            Cli::echo_cli('放弃操作!');
        } else {
            $this->stop();
        }
    }

    /**
     * 动态新增进程
     * @return [type] [description]
     */
    public function add()
    {
        if (!$this->isRuning()) {
            Cli::warning_cli('Event_server is not working.');
            return false;
        }

        if ($this->workerNums) {
            for ($i = 0; $i < $this->workerNums; $i++) {
                \Sys::setProcessName('event_swoole_worker');
                if (\Sys::$obj->Platform->kill($this->server_pid, SIGUSR1)) {
                    echo ($i + 1) . ' | ';
                }
                usleep(10000);
            }
            echo "\n";
        } else {
            $workerNums = Cli::ask_cli('请设置新增进程数量 -w:');
            if ($workerNums > 0) {
                $this->workerNums = $workerNums;
            } else {
                Cli::warning_cli('您设置进程数量无效, 请重新输入');
            }
            $this->add();
        }

        return;
    }

    /**
     * 动态减少服务
     * @return [type] [description]
     */
    public function del()
    {
        if (!$this->isRuning()) {
            Cli::warning_cli('Event_server is not working.');
            return false;
        }

        if ($this->workerNums) {
            for ($i = 0; $i < $this->workerNums; $i++) {
                if (\Sys::$obj->Platform->kill((int) $this->server_pid, SIGUSR2)) {
                    echo ($i + 1) . ' | ';
                }
                usleep(10000);
            }
            echo "\n";
        } else {
            $workerNums = Cli::ask_cli('请设置减少进程数量 -w:');
            if ($workerNums > 0) {
                $this->workerNums = $workerNums;
            } else {
                Cli::warning_cli('您设置进程数量无效, 请重新输入');
            }
            $this->del();
        }

        return;
    }

    /**
     * 重启
     * @return [type] [description]
     */
    public function reload()
    {
        exec('ps aux | grep event_swoole_worker', $output);
        $this->workerNums = abs(count($output) - 2);
        if ($this->stop()) {
            Cli::echo_cli('请稍后...');
            while (\Sys::$obj->Platform->kill($this->server_pid, 0)) {
                echo '>';
                usleep(10000);
            }
            echo "\n";
            $this->start();
        }
    }

    /**
     * 判定当前是否运行
     */
    public function isRuning()
    {
        if ($this->server_pid && \Sys::$obj->Platform->kill($this->server_pid, 0)) {
            return true;
        } else {
            return false;
        }
    }
}
