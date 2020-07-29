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
        $isGetCache = false; // 是否获取到缓存
        $isSetCache = false; // 是否设置缓存
        $address    = $clientInfo['address']; // 设置发送地址
        $port       = $clientInfo['port']; // 设置端口

        // 解析出请求包体
        $request = Request::decode($data);

        // 获取缓存实例
        $cache = $this->config->getDnsCache();

        // 获取DNS的Queries
        $queries = current($request->getBody()->getQueries());

        // 判断是否是DNS请求
        if ($request->getHeader()->isQuery()) {

            // 查询该域名缓存
            $cacheData = $cache->get($queries->getName());
            
            // 判断是否是个Queries
            if ($queries->isQueries()) {

                // 判断缓存不存在
                if (!$cacheData) {

                    // 不存在缓存数据则设置上游DNS服务地址和端口进行查询
                    $address    = current($this->config->getUpDNS());
                    $port       = 53;
                    $isSetCache = true; // 设置缓存数据

                } else {
                    // 判断缓存过期
                    if (time() > $cacheData['ttl']) {

                        // 刷新对应缓存
                        $cache->del($queries->getName());

                        // 缓存过期则设置上游DNS服务地址和端口进行查询
                        $address    = current($this->config->getUpDNS());
                        $port       = 53;
                        $isSetCache = true; // 设置缓存数据

                    } else {

                        $isGetCache = true; // 设置获取缓存

                        // 存在缓存则设置发送至客户端
                        $address = $clientInfo['address'];
                        $port    = $clientInfo['port'];

                        // 解析缓存信息
                        $requestCache = Request::decode($cacheData['data']);

                        // 修改缓存的DNS报文事务ID本次请求的ID
                        $requestCache->getHeader()->setId($request->getHeader()->getId());

                        // 生成新的报文
                        $info = DnsEncode::start($requestCache);
                    }
                }
            }

            $this->client = $clientInfo; // 保存请求响应的客户端信息

        } else {
            // 如果是响应类型的DNS请求则将查询到的信息发送至客户端
            $address = $this->client['address'];
            $port    = $this->client['port'];
        }
        // 判断没有获取到缓存
        if (!$isGetCache) {
            // 生成响应报文协议
            $info = DnsEncode::start($request);
        }

        // 判断需要设置缓存
        if (!$isSetCache) {
            // 设置DNS缓存
            $cache->set($queries->getName(), [
                'data'       => $info,
                'ttl'        => (time() + $this->config->getCacheTtl()),
                'createTime' => time()
            ]);
        }

        // 发送至客户端
        $udpServer->sendto($address, $port, $info);
    }


}