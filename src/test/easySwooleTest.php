<?php
/**
 * Created by PhpStorm.
 * User: EZ
 * Date: 2020/5/28
 * Time: 22:37
 */

require "../../vendor/autoload.php";


try {
    $set = new \EasySwoole\DNSServer\DnsSetting();

    $data = [
        [
            'name' => 'www.a.shifen.com',
            'ip'   => '192.168.3.1'
        ]
    ];

    $set->revise(1, $data);

    $dnsServer = new \EasySwoole\DNSServer\Server(null, null, $set);
    $dnsServer->start();

} catch (Exception $exception) {
    print_r($exception->getMessage());
}
