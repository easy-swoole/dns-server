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
        var_dump($request->getBody()->getQueries());
        $cache = $this->config->getDnsCache();
        //判断本地是否存在该域名的Cache记录，如果存在则响应，否则就发送给指定的上级DNS服务器查询

        $upDns = $this->config->getUpDNS();

    }

}