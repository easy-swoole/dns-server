<?php
/**
 * Created by PhpStorm.
 * User: EZ
 * Date: 2020/6/1
 * Time: 11:32
 */

namespace EasySwoole\DNSServer;


use EasySwoole\DNSServer\Exception\DNSException;

class RdataDecode
{
    public static $names = [
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

    public static function decode($info, $type)
    {

        if (!array_key_exists($type, self::$names)) {
            throw new DNSException('Record type is not a supported type3.');
        }

        if (!method_exists(self::class, self::$names[$type])) {
            throw new DNSException('Record type is not a supported type4');
        }

        return call_user_func(['self', self::$names[$type]], $info);
    }

    /**
     * A或AAAA的TYPE
     *
     * @param string $rdata
     *
     * @return string
     */
    public static function a(string $rdata) : string
    {
        return inet_ntop($rdata);
    }

    public static function aaaa(string $rdata) : string
    {
        return self::a($rdata);
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
        return self::name($rdata);
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
     * @param string $rdata
     *
     * @return array
     */
    public static function soa(string $rdata) : array
    {
    }

    /**
     * MX的TYPE
     *
     * @param string $rdata
     *
     * @return array
     */
    public static function mx(string $rdata) : array
    {
        return [
            'preference' => unpack('npreference', $rdata)['preference'],
            'exchange'   => self::name(substr($rdata, 2)),
        ];
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
        $len = ord($rdata[0]);
        if ((strlen($rdata) + 1) < $len) {
            return '';
        }

        return substr($rdata, 1, $len);
    }

    /**
     * SRV的TYPE
     *
     * @param string $rdata
     *
     * @return array
     */
    public static function srv(string $rdata) : array
    {
        $offset           = 6;
        $values           = unpack('npriority/nweight/nport', $rdata);
        $values['target'] = self::name($rdata, $offset);

        return $values;
    }

    /**
     * OPT的TYPE(可能有问题，测试不全)
     *
     * @param string $rdata
     *
     * @return array
     */
    public static function opt(string $rdata) : array
    {
        $value['code']   = substr($rdata, 0, 1);
        $value['length'] = substr($rdata, 1, 2);
        $value['data']   = substr($rdata, 3, 4);

        return $value;
    }

    /**
     * CAA的TYPE(有问题未解决)
     *
     * @param string $rdata
     *
     * @return array
     */
    public static function caa(string $rdata) : array
    {

    }

    /**
     * 解析出地址
     *
     * @param     $info
     * @param int $offset
     *
     * @return string
     */
    public static function name($info, $offset = 0)
    {
        // 获取域名长度
        $len = ord($info[$offset]);

        // 加1跳过表示域名长度
        ++$offset;

        if (0 === $len) {
            return '.';
        }

        // 初始化域名字段
        $name = '';

        // 根据域名长度循环截取
        while (0 !== $len) {

            $name   .= substr($info, $offset, $len) . '.';
            $offset += $len;

            $len = ord($info[$offset]);

            // 判断接下来的内容是否需要偏移 192 对应十六进制 c0
            if ($len == 192) {
                $offset += 1;

                $offset = ord($info[$offset]);
                $name   .= DnsDecode::offsetAnalyseName($offset);
                break;
            };

            ++$offset;
        }

        return $name;
    }
}