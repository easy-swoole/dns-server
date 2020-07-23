<?php


namespace EasySwoole\DNSServer;

use EasySwoole\DNSServer\Exception\DNSException;
use EasySwoole\DNSServer\Protocol\Request;
use Swoole\Server as SwooleServer;

class Server
{
    public $socket;
    public $request;

    public $setting    = null;
    public $cache      = null;
    public $nameServer = null;
    public $client     = null;

    /**
     * @var Config
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function onMessage(SwooleServer $udpServer, $data, $clientInfo)
    {
        //解析出请求包体
        $request = Request::decode($data);

    }

}