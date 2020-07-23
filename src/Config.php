<?php


namespace EasySwoole\DNSServer;


class Config
{
    //默认的上游DNS
    protected $upDNS = ['223.5.5.5','223.6.6.6'];

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