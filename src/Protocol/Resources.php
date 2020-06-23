<?php
/**
 * Created by PhpStorm.
 * User: EZ
 * Date: 2020/6/19
 * Time: 00:42
 */

namespace EasySwoole\DNSServer\Protocol;

/**
 * 正文中资源内容
 * Class Resources
 * @package EasySwoole\DNSServer\Protocol
 */
class Resources
{
    private $isQueries;
    private $name;
    private $type;
    private $class;
    private $ttl;
    private $length;
    private $data;

    /**
     * 资源是否是属于Queries
     * @param bool $isQueries
     */
    public function setIsQueries(bool $isQueries)
    {
        $this->isQueries= $isQueries;
    }

    /**
     * 获取资源所属区域true=Queries区域
     * @return bool
     */
    public function isQueries() : bool
    {
        return $this->isQueries();
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getName() : ?string
    {
        return $this->name;
    }

    public function setType(int $type)
    {
        $this->type = $type;
    }

    public function getType() : ?int
    {
        return $this->type;
    }

    public function setClass(int $class)
    {
        $this->class = $class;
    }

    public function getClass() : ?int
    {
        return $this->class;
    }

    public function setTtl(int $ttl)
    {
        $this->ttl = $ttl;
    }

    public function getTtl() : ?int
    {
        return $this->ttl;
    }

    public function setLength(int $length)
    {
        $this->length = $length;
    }

    public function getLength() : ?int
    {
        return $this->length;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}