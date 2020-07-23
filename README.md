# dns-server
## 相关知识
- DNS是53端口的udp协议
- https://blog.csdn.net/tianyeming/article/details/74922454
## 测试
### 服务端
```
<?php
require 'vendor/autoload.php';
$serv = new Swoole\Server("0.0.0.0", 53, SWOOLE_PROCESS, SWOOLE_SOCK_UDP);
$dns = new EasySwoole\DNSServer\Server();
//监听数据接收事件
$serv->on('Packet', function ($serv, $data, $clientInfo) use($dns){
    $dns->onMessage($dns);
});

//启动服务器
$serv->start();
```
### 客户端
```
dig @127.0.0.1 test.com A +short
111.111.111.111

dig @127.0.0.1 test.com TXT +short
"Some text."

dig @127.0.0.1 test2.com A +short
111.111.111.111
112.112.112.112
```