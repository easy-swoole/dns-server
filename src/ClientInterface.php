<?php
/**
 * Created by PhpStorm.
 * User: EZ
 * Date: 2020/6/22
 * Time: 02:13
 */

namespace EasySwoole\DNSServer;

interface ClientInterface
{
    /**
     * 增加客户端信息
     *
     * @param int   $key
     * @param array $client
     *
     * @return ClientInterface
     */
    public function addClientList(int $key, array $client) : ClientInterface;

    /**
     * 获取客户端信息
     *
     * @param string $key
     * @param string $field
     */
    public function getClient(string $key, string $field);

}