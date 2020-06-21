<?php
/**
 * Created by PhpStorm.
 * User: EZ
 * Date: 2020/6/5
 * Time: 15:20
 */

namespace EasySwoole\DNSServer;


use EasySwoole\DNSServer\Exception\DNSException;

class DnsSetting
{

    private $aList = [];


    public function exist(int $type, string $name)
    {

        if (!method_exists(self::class, RdataDecode::$names[$type]) || !array_key_exists($type, RdataDecode::$names)) {
            return null;
        }


        return call_user_func([$this, 'get'.RdataDecode::$names[$type]], $name);
    }

    /**
     * 修改解析记录的返回值
     *
     * @param int   $type
     * @param array $data
     *
     * @return $this
     * @throws DNSException
     */
    public function revise(int $type, array $data)
    {
        if (!array_key_exists($type, RdataDecode::$names)) {
            throw new DNSException('Record type is not a supported type3.');
        }

        if (!method_exists(self::class, RdataDecode::$names[$type])) {
            throw new DNSException('Record type is not a supported type4');
        }


        call_user_func([$this, RdataDecode::$names[$type]], $data);

        return $this;
    }

    public function a(array $data)
    {
        foreach ($data as $value) {
            if (!is_array($value) || !isset($value['name']) || !isset($value['ip'])) {
                throw new DNSException('Invalid data type a');
            }
            $this->aList[$value['name'].'.'][] = $value['ip'];
        }
    }

    public function aaaa(array $data)
    {
        $this->a($data);
    }

    public function getA(string $name):?array
    {
        if (isset($this->aList[$name])) {
            return $this->aList[$name];
        }
        return null;
    }

}