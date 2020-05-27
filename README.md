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

//监听数据接收事件
$serv->on('Packet', function ($serv, $data, $clientInfo) {
    $pkt = $data;
    $pkt = unpack("C*", substr($pkt,13, strlen($pkt)-18));
    $o = '';

    // Now lets loop through the pkt and translate the response to human readable ASCII
    foreach($pkt as $s){
        // If it's less than 32, it's assumed to be a period
        if($s < 32)
            $o .= ".";
        // If its more than 32 less than 127, it's seen as a regular character
        elseif($s > 32 && $s < 127)
            $o .= chr($s);
        else
            continue;
    }
    var_dump($o);
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