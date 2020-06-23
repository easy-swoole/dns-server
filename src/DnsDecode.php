<?php
/**
 * Created by PhpStorm.
 * User: EZ
 * Date: 2020/6/5
 * Time: 15:27
 */

namespace EasySwoole\DNSServer;


use EasySwoole\DNSServer\Exception\DNSException;
use EasySwoole\DNSServer\Protocol\Request;
use EasySwoole\DNSServer\Protocol\Resources;

/**
 * Class DnsDecode
 * @package EasySwoole\DNSServer
 */
class DnsDecode
{
    private static $oldMessage;
    private static $message;
    private static $offset;

    public static $header;
    public static $body;

    /**
     * 启动解析
     *
     * @param string  $msg
     * @param Request $request
     */
    public static function start(string $msg, Request $request)
    {
        self::$offset  = 0;
        self::$message = self::$oldMessage = $msg;
        self::$header  = $request->getHeader();
        self::$body    = $request->getBody();

        // 解析报文头相关信息
        self::analyseHeader();

        // 解析报文正文信息
        self::analyseBody();
    }

    /**
     * 解析报文头相关信息
     */
    private static function analyseHeader()
    {
        $data = unpack('nid/nflags/nqucount/nancount/naucount/nadcount', self::$message);

        // 解析报文头的Flags相关信息
        self::analyseFlags($data['flags']);

        self::$header->setId($data['id']);
        self::$header->setQuestionCount($data['qucount']);
        self::$header->setAnswerCount($data['ancount']);
        self::$header->setNameServerCount($data['aucount']);
        self::$header->setAdditionalRecordsCount($data['adcount']);
    }

    /**
     * 解析报文头的Flags相关信息
     *
     * @param string $flags
     */
    private static function analyseFlags(string $flags)
    {
        self::$header->setResponse(($flags >> 15 & 1));
        self::$header->setOpcode(($flags >> 11 & 1111));
        self::$header->setAuthoritative(($flags >> 10 & 1));
        self::$header->setTruncated(($flags >> 9 & 1));
        self::$header->setRecursionDesired(($flags >> 8 & 1));
        self::$header->setRecursionAvailable(($flags >> 7 & 1));
        self::$header->setZ(($flags >> 4 & 111));
        self::$header->setRcode(($flags & 1111));
    }

    /**
     * 解析正文
     */
    private static function analyseBody()
    {
        // 截取掉header内容
        self::$message = substr(self::$message, 12);

        // 解析并获正文Queries区域资源
        self::$body->setQueries(self::analyseQueries(self::$header->getQuestionCount()));
        // 解析并获取回答区域，授权区域、附加区域资源
        self::$body->setAnswers(self::analyseResources(self::$header->getAnswerCount()));
        self::$body->setAuthoritative(self::analyseResources(self::$header->getNameServerCount()));
        self::$body->setAdditional(self::analyseResources(self::$header->getAdditionalRecordsCount()));
    }

    /**
     * 解析域名
     *
     * @param string $string
     *
     * @return string
     */
    private static function analyseName(string $string) : string
    {

        $len = ord($string[self::$offset]);

        ++self::$offset;

        if (0 === $len) {
            return '.';
        }

        $domainName = '';

        while (0 !== $len) {
            // 判断接下来的内容是否需要偏移 192 对应十六进制 c0
            if ($len == 192) {

                $offset     = ord($string[self::$offset]);
                $domainName .= self::offsetAnalyseName($offset);
                ++self::$offset;
                break;
            };

            $domainName .= substr($string, self::$offset, $len) . '.';

            self::$offset += $len;

            $len = ord($string[self::$offset]);

            ++self::$offset;
        }

        return $domainName;
    }

    /**
     * 解析Queries区域资源
     *
     * @param int $count
     *
     * @return array
     */
    private static function analyseQueries($count = 0) : array
    {
        $data = [];
        if ($count <= 0) {
            return $data;
        }

        for ($i = 0; $i < $count; $i++) {
            $resources = new Resources();

            $resources->setIsQueries(true);

            $resources->setName(self::analyseName(self::$message));

            // 获取查询类型、查询类
            $values       = unpack('ntype/nclass', substr(self::$message, self::$offset, 4));
            self::$offset += 4;
            $resources->setType($values['type']);
            $resources->setClass($values['class']);

            array_push($data, $resources);
        }

        return $data;
    }

    /**
     * 解析回答区域，授权区域和附加区域资源
     *
     * @param int $count
     *
     * @return array
     */
    private static function analyseResources($count = 0) : array
    {
        $data = [];

        if ($count <= 0) {
            return $data;
        }

        for ($i = 0; $i < $count; $i++) {
            $resources = new Resources();

            $resources->setIsQueries(false);

            $resources->setName(self::analyseName(self::$message));

            // 获取查询类型、查询类、生存时间、数据长度
            $values       = unpack('ntype/nclass/Nttl/ndlength', substr(self::$message, self::$offset, 10));
            self::$offset += 10;

            $resources->setType($values['type']);
            $resources->setClass($values['class']);
            $resources->setTtl($values['ttl']);
            $resources->setLength($values['dlength']);

            try {
                // 资源数据
                $resources->setData(RdataDecode::decode(substr(self::$message, self::$offset, $values['dlength']), $values['type']));
                self::$offset += $values['dlength'];
            } catch (DNSException $exception) {
                self::$offset += $values['dlength'];

                continue;
            }

            array_push($data, $resources);
        }

        return $data;
    }

    /**
     * 偏移解析域名
     *
     * @param int $offset 偏移值
     *
     * @return string
     */
    public static function offsetAnalyseName($offset = 0) : string
    {
        // 原始报文
        $string = self::$oldMessage;

        $len = ord($string[$offset]);

        ++$offset;

        if (0 === $len) {
            return '.';
        }

        $domainName = '';

        while (0 !== $len) {
            $domainName .= substr($string, $offset, $len) . '.';
            $offset     += $len;
            $len        = ord($string[$offset]);
            // 判断接下来的内容是否需要偏移 192 对应十六进制 c0
            if ($len == 192) {
                $offset += 1;

                $offset     = ord($string[$offset]);
                $domainName .= self::offsetAnalyseName($offset);
                break;
            };

            ++$offset;
        }

        return $domainName;
    }
}