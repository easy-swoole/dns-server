<?php
/**
 * Created by PhpStorm.
 * User: EZ
 * Date: 2020/6/1
 * Time: 12:13
 */

namespace EasySwoole\DNSServer;


use EasySwoole\DNSServer\Exception\DNSException;
use EasySwoole\DNSServer\Exception\Exception;

class RdataEncode
{
    private static $names = [
        self::TYPE_A     => 'A',
        self::TYPE_NS    => 'NS',
        self::TYPE_CNAME => 'CNAME',
        self::TYPE_SOA   => 'SOA',
        self::TYPE_MX    => 'MX',
        self::TYPE_TXT   => 'TXT',
        self::TYPE_AAAA  => 'AAAA',
        self::TYPE_SRV   => 'SRV',
        self::TYPE_OPT   => 'OPT',
        self::TYPE_CAA   => 'CAA',
    ];

    public const TYPE_A     = 1;
    public const TYPE_NS    = 2;
    public const TYPE_CNAME = 5;
    public const TYPE_SOA   = 6;
    public const TYPE_MX    = 15;
    public const TYPE_TXT   = 16;
    public const TYPE_AAAA  = 28;
    public const TYPE_SRV   = 33;
    public const TYPE_OPT   = 41;
    public const TYPE_CAA   = 257;

    /**
     * @param $info
     * @param $type
     *
     * @return mixed
     * @throws DNSException
     */
    public static function encode($info, $type)
    {
        if (!array_key_exists($type, self::$names) || !method_exists(self::class, self::$names[$type])) {
            throw new DNSException('Record type is not a supported type');
        }

        return call_user_func(['self', self::$names[$type]], $info);
    }


    /**
     * A或AAAA的TYPE
     *
     * @param $rdata
     *
     * @return string
     * @throws DNSException
     */
    public static function a($rdata) : string
    {

        if (!$rdata) {
            throw new DNSException('IP address error');
        }

        return inet_pton($rdata);
    }

    /**
     * CNAME或DNAME或NS或PTR的TYPE
     *
     * @param string $rdata
     *
     * @return string
     */
    public static function cname(string $rdata) : string
    {
        return RdataEncode::name($rdata);
    }

    public static function dname(string $rdata) : string
    {
        return self::cname($rdata);
    }

    public static function ns(string $rdata) : string
    {
        return self::cname($rdata);
    }

    public static function ptr(string $rdata) : string
    {
        return self::cname($rdata);
    }

    /**
     * SOA的TYPE(有问题待解决)
     *
     * @param array $rdata
     *
     * @return string
     */
    public static function soa(array $rdata) : string
    {
    }

    /**
     * MX的TYPE
     *
     * @param array $rdata
     *
     * @return string
     */
    public static function mx(array $rdata) : string
    {
        return pack('n', (int) $rdata['preference']) . RdataEncode::name($rdata['exchange']);
    }

    /**
     * TXT的TYPE
     *
     * @param string $rdata
     *
     * @return string
     */
    public static function txt(string $rdata) : string
    {
        $rdata = substr($rdata, 0, 255);

        return chr(strlen($rdata)) . $rdata;
    }

    /**
     * SRV的TYPE
     *
     * @param array $rdata
     *
     * @return string
     */
    public static function srv(array $rdata) : string
    {
        return pack('nnn', (int) $rdata['priority'], (int) $rdata['weight'], (int) $rdata['port']) . RdataEncode::name($rdata['target']);
    }

    /**
     * OPT的TYPE(可能有问题，测试不全)
     *
     * @param string $rdata
     *
     * @return array
     */
    public static function opt(array $rdata) : string
    {
        return $rdata['code'] . $rdata['length'] . $rdata['data'];
    }

    /**
     * CAA的TYPE(有问题未解决)
     *
     * @param array $rdata
     *
     * @return string
     */
    public static function caa(array $rdata) : string
    {

    }

    public static function name($name)
    {
        // 获取域名长度
        if ('.' === $name) {
            return chr(0);
        }

        $name = rtrim($name, '.') . '.';

        $res = '';

        foreach (explode('.', $name) as $label) {
            $res .= chr(strlen($label)) . $label;
        }

        return $res;
    }

}