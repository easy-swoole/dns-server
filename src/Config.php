<?php


namespace EasySwoole\DNSServer;


use EasySwoole\DNSServer\AbstractInterface\ClientCacheInterface;
use EasySwoole\DNSServer\AbstractInterface\DnsCacheInterface;
use EasySwoole\DNSServer\Utility\ClientCache;
use EasySwoole\DNSServer\Utility\DnsCache;

class Config
{
    //默认的上游DNS
    protected $upDNS = ['223.5.5.5','223.6.6.6'];

    /** @var ClientCacheInterface */
    protected $clientCache;

    /** @var DnsCacheInterface */
    protected $dnsCache;

    /**
     * @return ClientCacheInterface
     */
    public function getClientCache(): ClientCacheInterface
    {
        if(!$this->clientCache){
            $this->clientCache = new ClientCache();
        }
        return $this->clientCache;
    }

    /**
     * @param ClientCacheInterface $clientCache
     */
    public function setClientCache(ClientCacheInterface $clientCache): void
    {
        $this->clientCache = $clientCache;
    }

    /**
     * @return DnsCacheInterface
     */
    public function getDnsCache(): DnsCacheInterface
    {
        if($this->dnsCache){
            $this->dnsCache = new DnsCache();
        }
        return $this->dnsCache;
    }

    /**
     * @param DnsCacheInterface $dnsCache
     */
    public function setDnsCache(DnsCacheInterface $dnsCache): void
    {
        $this->dnsCache = $dnsCache;
    }


    /**
     * @return string[]
     */
    public function getUpDNS(): array
    {
        return $this->upDNS;
    }

    /**
     * @param string[] $upDNS
     */
    public function setUpDNS(array $upDNS): void
    {
        $this->upDNS = $upDNS;
    }
}