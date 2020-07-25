<?php


namespace EasySwoole\DNSServer\Utility;


use EasySwoole\DNSServer\AbstractInterface\DnsCacheInterface;
use Swoole\Table;

class DnsCache implements DnsCacheInterface
{
    /** @var Table */
    protected $table;

    function __construct($size = 1024*1024)
    {
        //这边创建一个Table用于存储域名信息
    }

}