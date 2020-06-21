<?php
/**
 * Created by PhpStorm.
 * User: EZ
 * Date: 2020/6/21
 * Time: 22:23
 */

namespace EasySwoole\DNSServer;

use Swoole\Table;

class DnsClient implements ClientInterface
{
    private $table;

    public function __construct()
    {
        $table = new Table(1024);
        $table->column('address', Table::TYPE_STRING, 128);
        $table->column('port', Table::TYPE_STRING, 128);
        $table->create();
        $this->table = $table;
    }

    /**
     * 增加客户端信息
     *
     * @param int   $key
     * @param array $client
     *
     * @return DnsClient
     */
    public function addClientList(int $key, array $client) : ClientInterface
    {
        $this->table->set($key, ['address' => $client['address'], 'port' => $client['port']]);

        return $this;
    }

    /**
     * @param string $key
     * @param string $field
     */
    public function getClient(string $key, string $field = null)
    {
        return $this->table->get($key, $field);
    }

}