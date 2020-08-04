# 什么是cgi,fast-cgi,php-fpm以及php-fpm进城池调优
## 什么是CGI?
CGI不是应用程序，CGI描述了服务器和请求处理程序之间传输数据的一种标准。CGI可以用任何一种语言编写，只要这种语言具有标准输入、输出和环境变量，如php,perl,python等。
如下图：
![aYqfVP.png](https://s1.ax1x.com/2020/08/02/aYqfVP.png)

## 什么是fast-CGI
FastCGI像是一个常驻(long-live)型的CGI，它可以一直执行着，只要激活后，不会每次都要花费时间去fork一次(这是CGI最为人诟病的fork-and-execute 模式)。它还支持分布式的运算, 即 FastCGI 程序可以在网站服务器以外的主机上执行并且接受来自其它网站服务器来的请求。

不足：因为是多进程，所以比CGI多线程消耗更多的服务器内存，PHP-CGI解释器每进程消耗7至25兆内存

## FastCGI工作原理
1. Web Server启动时载入FastCGI进程管理器【PHP的FastCGI进程管理器是PHP-FPM(php-FastCGI Process Manager)】（IIS ISAPI或Apache Module)
2. FastCGI进程管理器自身初始化，启动多个CGI解释器进程 (可见多个php-cgi.exe或php-cig)并等待来自Web Server的连接；
3. 当客户端请求到达Web Server时，FastCGI进程管理器选择并连接到一个CGI解释器。Web server将CGI环境变量和标准输入发送到FastCGI子进程php-cgi
4. FastCGI子进程完成处理后将标准输出和错误信息从同一连接返回Web Server。当FastCGI子进程关闭连接时，请求便告处理完成。FastCGI子进程接着等待并处理来自FastCGI进程管理器（运行在 WebServer中）的下一个连接。
5. 在正常的CGI模式中，php-cgi.exe在此便退出了。在CGI模式中，你可以想象 CGI通常有多慢。每一个Web请求PHP都必须重新解析php.ini、重新载入全部dll扩展并重初始化全部数据结构。使用FastCGI，所有这些都只在进程启动时发生一次。一个额外的好处是，持续数据库连接（Persistent database connection）可以工作。

备注：PHP的FastCGI进程管理器是PHP-FPM（PHP-FastCGI Process Manager）

## 什么是php-cgi
PHP-CGI是PHP自带的FastCGI管理器.

PHP-CGI的不足
1、php-cgi变更php.ini配置后需重启php-cgi才能让新的php-ini生效，不可以平滑重启
2、直接杀死php-cgi进程,php就不能运行了。

## php-fpm
PHP-FPM是一个PHP FastCGI进程管理器，是只用于PHP的.
## 什么是CLI？
PHP-CLI是PHP Command Line Interface的简称，就是PHP在命令行运行的接口，也就是说，PHP不单可以写前台网页，它还可以用来写后台的程序。 PHP的CLI Shell脚本适用于所有的PHP优势，使创建要么支持脚本或系统甚至与GUI应用程序的服务端，在Windows和Linux下都是支持PHP-CLI模式的。
## php中CGI的实现
PHP的CGI实现本质是是以socket编程实现一个TCP或UDP协议的服务器，当启动时，创建TCP/UDP协议的服务器的socket监听， 并接收相关请求进行处理。这只是请求的处理，在此基础上添加模块初始化，sapi初始化，模块关闭，sapi关闭等就构成了整个CGI的生命周期。

## php-fpm的进程池配置

php-fpm进程池开启进程有三种方式，其中涉及到的一些参数，分别是pm、pm.max_children、pm.start_servers、pm.min_spare_servers和pm.max_spare_servers。
pm = dynamic ：开始时开启一定数量的php-fpm进程，当请求量变大时，动态的增加php-fpm进程数到上限，当空闲时自动释放空闲的进程数到一个下限。
pm = ondemand ：在服务启动的时候根据 pm.start_servers 指令生成进程，而非动态生成。
pm = static ：子进程的数量是由 pm.max_children 指令来确定的。
pm.max_children：静态方式下开启的php-fpm进程数量，在动态方式下他限定php-fpm的最大进程数
pm.start_servers：动态方式下的起始php-fpm进程数量。
pm.min_spare_servers：动态方式空闲状态下的最小php-fpm进程数量。
pm.max_spare_servers：动态方式空闲状态下的最大php-fpm进程数量（这里要注意pm.max_spare_servers的值只能小于等于pm.max_children）。

php的配置文件里面给出了pm.start_servers的计算公式：
min_spare_servers + (max_spare_servers - min_spare_servers) / 2
如果dm设置为static，那么其实只有pm.max_children这个参数生效。系统会开启参数设置数量的php-fpm进程。

如果dm设置为dynamic，4个参数都生效。系统会在php-fpm运行开始时启动pm.start_servers个php-fpm进程，然后根据系统的需求动态在pm.min_spare_servers和pm.max_spare_servers之间调整php-fpm进程数