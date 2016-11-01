<?php
namespace Sys\libs;
/**
* 事件服务
*/
class Event
{
	protected $_queue;
	protected $_events = [];

	protected $_atomic;
    protected $_workers = [];

    protected $config;
    protected $async = false;
    protected $pidfile;
    public static $event_path;

	function __construct($config)
	{
		$this->config = $config;

        if (defined('EVENT_PATH')) {
            self::$event_path = EVENT_PATH;
        } elseif (defined('WEB_PATH')) {
            self::$event_path = WEB_PATH. '/events/';
            define('EVENT_PATH', self::$event_path);
        } else {
            trigger_error("Can't define EVENT_PATH", E_USER_WARNING);
        }

		if (isset($this->config['async']) && $this->config['async']) {
			$this->_queue = \Sys::$obj->queue;
			$this->async = true;
		}
	}

	/**
	 * 处理服务
	 * @param  [string] $type [处理类型]
	 * @param  [string] $data [处理数据]
	 * @return [boole]
	 */
	protected function _execute($type, $data)
	{
		if (!isset($this->_events[$type])) {
			$events = [];
            $class = "\\Event\\".$type."Event";
            if (class_exists($class)) {
                $events[] = new $class;
            }else {
                \Sys::$obj->log->error("$class is not exists");
            }
            $this->_events[$type] = $events;
		}

		foreach ($this->_events[$type] as $event) {
			$event->trigger($type, $data);
		}

		return true;
	}

	/**
	 * 触发服务
	 * @param  [string] $type [服务类型]
	 * @param  [string] $data [服务数据]
	 * @return [boole]
	 */
	public function trigger($type, $data)
	{
		//异步，压入队列
		if ($this->async) {
			return $this->_queue->push([ 'type' => $type, 'data' => $data ]);
		//同步，立即执行
		} else {
			return $this->_execute($type, $data);
		}
	}

	/**
	 * 轮循
	 * @return [type] [description]
	 */
	public function _worker()
	{
		while ($this->_atomic->get() == 1) {
			$event = $this->_queue->pop();
			if ($event) {
				$this->_execute($event['type'], $event['data']);
			} else {
				usleep(100000);
			}
		}
	}

	/**
     * 增加event进程
     * @param integer $num [description]
     */
    protected function _addWorkerNum($num = 1) {
        \Sys::setProcessName('event_swoole_worker');
        $num = abs($num);
        for ($i=0; $i < $num; $i++) {
            $process = new \swoole\process(array($this, '_worker'), false, false);
            $process->start();
            $this->_workers[] = $process;
            // \Sys::$obj->log->info(var_export($process, true));
        }
    }

    /**
     * 减少event进程
     * @param  integer $num [description]
     * @return [type]       [description]
     */
    protected function _delWorkerNum() {
        if(count($this->_workers) > 0) {
            $worker = array_pop($this->_workers);
            if (\swoole_process::kill($worker->pid, 0)){
                $res = \swoole_process::kill($worker->pid);
                \Sys::$obj->log->info("$res\n");
            }
        }
    }

	/**
	 * 运行服务
	 * @param  integer $worker_num [进程数量]
	 * @param  boolean $daemon     [是否后台运行]
	 * @param  string  $pid_name   [Pid文件名]
	 * @return mixed
	 */
	public function runWorker($worker_num = 1, $daemon = false, $pid_name = 'event_swoole_worker')
	{

		$this->pidfile = "/var/run/$pid_name.pid";
		if ($worker_num > 1 || $daemon) {
			//检查是否安装了swoole扩展
			if (!class_exists('\swoole\process')) {
				throw new \Exception("require swoole extension");
			}
			//最高启动200个进程
			if ($worker_num < 0 || $worker_num > 200) {
				$worker_num = 200;
			}
		} else {
			$this->_atomic = new \swoole_atomic(1);
            $this->_worker();
            return;
		}

		//后台运行
		if ($daemon) {
			\swoole_process::daemon();
		}

		$this->_atomic = new \swoole_atomic(1);
		$mastPid = posix_getpid();
		file_put_contents($this->pidfile, $mastPid);

		$this->_addWorkerNum($worker_num);
        \Sys::setProcessName('event_swoole_master');

        //监听子进程崩溃
        \swoole_process::signal(SIGCHLD, function() {
            while(true) {
                $exitProcess = \swoole_process::wait(false);
                if ($exitProcess)
                {
                    foreach ($this->_workers as $k => $p)
                    {
                        if ($p->pid == $exitProcess['pid'])
                        {
                            if ($this->_atomic->get() == 1)
                            {
                                $p->start();
                            }
                            else
                            {
                                unset($this->_workers[$k]);
                                if (count($this->_workers) == 0)
                                {
                                    swoole_event_exit();
                                }
                            }
                        }
                    }
                }
                else
                {
                    break;
                }
            }
        });

        //监听进程终止
        \swoole_process::signal(SIGTERM, function() {
            //停止运行
            $this->_atomic->set(0);
            unlink($this->pidfile);
        });

        //自定义监听:  动态添加新的work进程
        \swoole_process::signal(SIGUSR1, function() {
            $this->_addWorkerNum();
        });

        //自定义监听: 动态减少work进程
        \swoole_process::signal(SIGUSR2, function() {
            $this->_delWorkerNum();
        });
	}

}
