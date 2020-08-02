# register_shutdown_function与__destruct顺序问题

## 问题描述
最近开发中遇到一个问题，发现有些情况下程序运行记录的Log一条也没有记录下来，php-error也没有报错，只有nginx的access_log有请求记录，通过var_dump()判断的确运行了这段代码..很奇怪，于是就写了一段程序模拟了这个过程

## 代码示例
### 简写的Log类
```php
class ALog
{
    /**
     * @var array 缓存Log
     */
    protected static $logs = [];
 
    public function __destruct()
    {
        echo __METHOD__ . PHP_EOL;
        $this->flush();
    }
 
    /**
     * 刷新Log缓冲区到文件
     */
    protected function flush()
    {
        var_dump(self::$logs);
        // 写入文件
        // file_put_contents('ddd/log', implode(PHP_EOL, self::$logs), FILE_APPEND);
        self::$logs = [];
    }
 
    protected function isOOM()
    {
        return true;
    }
 
    /**
     * @param string $msg 记录Log
     */
    public function debug($msg)
    {
        if ($this->isOOM()) {
            $this->flush();
        }
        self::$logs[__FUNCTION__][] = $msg;
    }
}
```

运行流程图
![aYoWMq.png](https://s1.ax1x.com/2020/08/02/aYoWMq.png)

### 简写的记录排班过程的Log类
```php
class ScheduleLog
{
    /**
     * @var array 保存运行记录
     */
    protected static $infos = [];
    /**
     * @var bool 是否注册过
     */
    protected static $registered = false;
 
    /**
     * @param string $msg 记录
     */
    public static function record($msg)
    {
        if (self::$registered === false) {
            register_shutdown_function(ScheduleLog::class . '::commit');
            self::$registered = true;
        }
        self::$infos[] = $msg;
    }
 
    /**
     * 最后提交 一次性批量保存到数据库
     */
    public static function commit()
    {
        echo __METHOD__ . PHP_EOL;
        // throw new Exception('ss');
        var_dump(self::$infos);
    }
}
```

### 业务逻辑
```php
$log = new ALog();
 
//业务逻辑1....
$log->debug('业务逻辑1....');
//业务逻辑2....
$log->debug('业务逻辑2....');
 
// 巴拉巴拉1...
ScheduleLog::record('巴拉巴拉1...');
// 巴拉巴拉2...
ScheduleLog::record('巴拉巴拉2...');
// 巴拉巴拉3...
ScheduleLog::record('巴拉巴拉3...');
 
//业务逻辑3....
$log->debug('业务逻辑3....');
```
### 结果分析
经过var_dump 运行排查，将问题代码锁定在了ScheduleLog::commit() 这个方法运行出错导致Log没有写入
正常情况下运行结果
![aYTCJH.png](https://s1.ax1x.com/2020/08/02/aYTCJH.png)
commit异常时
![aYTAyt.png](https://s1.ax1x.com/2020/08/02/aYTAyt.png)

当register_shutdown_function和__destruct同时存在时，会先运行register_shutdown_function 在运行__destruct。这就导致当register_shutdown_function运行出错时就停止运行了，导致__destruct没有运行。
在php源码中也找到的对应的代码：
![aYTJmV.png](https://s1.ax1x.com/2020/08/02/aYTJmV.png)

所以为了避免在使用register_shutdown_function时异常情况下__destruct没有运行，应该在register_shutdown_function中捕获所有能使脚本停止的异常。
```php
try {

} catch (\Throwable $t) {
}
```
### 为什么php-error 也没有错误
为什么php-error没有register_shutdown_function产生的错误呢？其实还是顺序问题。
```php
register_shutdown_function(function () {
    echo 'register_shutdown_function_1' . PHP_EOL;
});
 
echo 'Do something' . PHP_EOL;
 
register_shutdown_function(function () {
    echo 'register_shutdown_function_2' . PHP_EOL;
});
 
echo 'OVER' . PHP_EOL;
```
运行结果：
![aYTD61.png](https://s1.ax1x.com/2020/08/02/aYTD61.png)

可以看出当有多个register_shutdown_function时，脚本结束时会按照注册时间依次执行。



在Bootstrap.php文件里我注册了php错误信息加上log_id后写入php-error 里面也是注册了一个
![aYTWfH.png](https://s1.ax1x.com/2020/08/02/aYTWfH.png)
![aYTTnP.png](https://s1.ax1x.com/2020/08/02/aYTTnP.png)

因为Bootstrap会先执行，所以最后结束时这个register_shutdown_function的写php-error回调会优先ScheduleLog::commit()执行，这时没有php错误，所以不会有php-error产生