<?php


namespace EasySwoole\DNSServer\Utility;


use EasySwoole\DNSServer\AbstractInterface\DnsCacheInterface;
use Swoole\Table;

class DnsCache implements DnsCacheInterface
{

    /** @var Table */
    protected $table;
    private   $keyCacheList = []; // 缓存key列表

    public function __construct(int $size = 1024 * 1024)
    {
        $table = new Table($size);
        $table->column('data', Table::TYPE_STRING, 900);
        $table->column('ttl', Table::TYPE_STRING, 11);
        $table->column('createTime', Table::TYPE_STRING, 11);
        $table->create();
        $this->table = $table;
    }

    /**
     * 检测缓存是否存在
     *
     * @param string $key
     *
     * @return bool
     */
    public function isCache(string $key) : bool
    {
        return $this->table->exist($key);
    }

    /**
     * 获取缓存数据
     *
     * @param string      $key
     * @param string|null $field
     *
     * @return array|bool
     */
    public function get(string $key, string $field = null)
    {
        return $this->table->get($key, $field);
    }

    /**
     * 设置缓存数据
     *
     * @param string $key
     * @param array  $value
     *
     * @return bool
     */
    public function set(string $key, array $value) : bool
    {
        $this->keyCacheList[] = $key;

        return $this->table->set($key, $value);
    }

    /**
     * 删除缓存数据
     *
     * @param string $key
     *
     * @return bool
     */
    public function del(string $key) : bool
    {
        unset($this->keyCacheList[$key]);

        return $this->table->del($key);
    }

    /**
     * 刷新缓存
     */
    public function refresh()
    {
        foreach ($this->keyCacheList as $v) {
            $this->del($v);
        }
    }

}