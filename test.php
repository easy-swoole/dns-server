<?php
/**
 * Created by PhpStorm.
 * User: EZ
 * Date: 2020/7/23
 * Time: 22:13
 */

require  'vendor/autoload.php';

$serv = new Swoole\Server("0.0.0.0", 53, SWOOLE_PROCESS, SWOOLE_SOCK_UDP);

$dnsConfig = new \EasySwoole\DNSServer\Config();

$dns = new EasySwoole\DNSServer\Server($dnsConfig);

//监听数据接收事件
$serv->on('Packet', function ($serv, $data, $clientInfo) use($dns){
    $dns->onMessage($serv, $data,$clientInfo);
});

//启动服务器
$serv->start();