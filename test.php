<?php
/**
 * Created by PhpStorm.
 * User: EZ
 * Date: 2020/7/23
 * Time: 22:13
 */

$str = '123456789abcdefghijklmn';

$strs = substr($str,12);

var_dump($strs[0]);
var_dump($str[12]);




require  'vendor/autoload.php';

$serv = new Swoole\Server("0.0.0.0", 53, SWOOLE_PROCESS, SWOOLE_SOCK_UDP);

$dns = new EasySwoole\DNSServer\Server(new \EasySwoole\DNSServer\Config());

//监听数据接收事件
$serv->on('Packet', function ($serv, $data, $clientInfo) use($dns){
    $dns->onMessage($serv, $data,$clientInfo);
});

//启动服务器
$serv->start();