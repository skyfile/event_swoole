# event_swoole
这是从swool_framework中单独拆出来的事务功能, 个人项目需要, 改进中......

### 帮助
>  sudo php src/bin/event_workers.php -h

### 启动
>  sudo php src/bin/event_workers.php start
#### 参数
>     -w|-worder  设置Worker进程的数量（建议 50）
>
>     -d|-daemon  启用守护进程模式

### 停止
>  sudo php src/bin/event_workers.php stop

### 重启
>  sudo php src/bin/event_workers.php reload

### 动态添加进程
>  sudo php src/bin/event_workers.php add
#### 参数
>     -w|-worder  设置Worker进程的数量

### 动态减少进程
>  sudo php src/bin/event_workers.php del
#### 参数
>     -w|-worder  设置Worker进程的数量
