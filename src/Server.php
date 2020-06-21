<?php


namespace EasySwoole\DNSServer;

use EasySwoole\DNSServer\Exception\DNSException;
use EasySwoole\DNSServer\Protocol\Request;
use EasySwoole\EasySwoole\ServerManager;

class Server
{
    public $socket;
    public $request;

    public $setting    = null;
    public $cache      = null;
    public $nameServer = null;
    public $client     = null;

    private $path = '/etc/resolv.conf';
//    public $encode = null;
//    public $decode = null;


    public function __construct(\Swoole\Server $socket = null, DnsClient $client=null,  DnsSetting $setting = null, DnsCache $cache = null)
    {
        $this->socket  = $socket;
        $this->request = new Request();
        $this->setting = $setting;
        $this->cache   = $cache;
        $this->client  = $client;
    }

    public function onMessage(\Swoole\Server $socket, $data, $clientInfo)
    {

        try {
            // 解析接收到的信息
            DnsDecode::start($data, $this->request);

            // 用于查询  header的Flags标志的QR为0
            if ($this->request->getHeader()->isQuery()) {

                // 根据DNS的报文ID记录请求用户
                if ($this->request->getHeader()->getId()) {
                    $this->client->addClientList($this->request->getHeader()->getId(), $clientInfo);
                }

                $socket->sendto($this->nameServer, 53, $data);
            }

            // 用于响应  header的Flags标志的QR为1
            if ($this->request->getHeader()->isResponse()) {

                $answerKey = 0;
                // 遍历响应的回答区域
                foreach ($this->request->getBody()->getAnswers() as $key => $answer) {
                    // 获取自定义的相关信息（暂时只支持A和AAAA记录）
                    $data = $this->setting->exist($answer->getType(), $answer->getName());
                    if ($data && isset($data[$answerKey])) {
                        // 改变回答信息
                        $answer->setData($data[$answerKey]);
                        ++$answerKey;
                    }
                }

                // 生成响应报文协议
                $info = DnsEncode::start($this->request);
                // 获取报文ID
                $dnsID = $this->request->getHeader()->getId();

                if ($dnsID) {
                    // 获取报文ID记录的对应客户端IP
                    $client = $this->client->getClient($dnsID);
                    if (!$client) {
                        throw new DNSException(" DNS ID {$dnsID} corresponding IP does not exist ");
                    }
                    // 发送至客户端
                    $socket->sendto($client['address'], $client['port'], $info);

                } else {
                    throw new DNSException(" DNS ID {$dnsID} corresponding IP does not exist ");
                }

            }

        } catch (\Exception $exception) {
            print_r($exception->getMessage());
        }
    }

    /**
     * 启动前准备-本地DNS地址及相关个人自定义处理
     * @throws DNSException
     */
    private function prepare()
    {
        // 配置DNS响应
        if (!is_object($this->setting)) {
            $this->setting = new DnsSetting();
        }

        // 配置缓存
        if (!is_object($this->cache)) {
            $this->cache = new DnsCache();
        }

        // 配置客户端管理
        if (!is_object($this->client)) {
            $this->client = new DnsClient();
        }

        // 配置DNS地址
        if (!$this->nameServer) {
            $content = @file_get_contents($this->path);

            if (!$content) {
                throw new DNSException(('DNS address not set'));
            }
            // 匹配DNS地址
            $preg = preg_match('/nameserver\s([0-9.]{7,})/', $content, $address);

            if (!$preg) {
                throw new DNSException(('DNS address not set'));
            }

            $this->nameServer = $address[1];
        }

        // 配置UDP
        if (!$this->socket) {
            $server = ServerManager::getInstance()->getSwooleServer();
            $this->socket = $server->addListener('0.0.0.0', '53', SWOOLE_UDP);
        }
    }

    /**
     * 刷新本地DNS
     */
    private function rePrepare()
    {
        try {
            $this->prepare();
        } catch (DNSException $exception) {
            print $exception->getMessage();
        }
    }

    /**
     * 启动DNS服务
     */
    public function start()
    {
        try {
            $this->prepare();
            $this->socket->on('Packet', [$this, 'onMessage']);

            if ($this->socket instanceof \Swoole\Server) {
                $this->socket->start();
            }
        } catch (DNSException $exception) {
            print $exception->getMessage();
        }

    }
}