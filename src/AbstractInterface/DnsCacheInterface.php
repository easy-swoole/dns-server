<?php


namespace EasySwoole\DNSServer\AbstractInterface;


interface DnsCacheInterface
{
    public function isCache(string $key) : bool;

    public function get(string $key, string $field = null);

    public function set(string $key, array $value) : bool;

    public function del(string $key): bool;

    public function refresh();
}