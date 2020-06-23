<?php
/**
 * Created by PhpStorm.
 * User: EZ
 * Date: 2020/6/5
 * Time: 15:27
 */

namespace EasySwoole\DNSServer;


use EasySwoole\DNSServer\Exception\DNSException;
use EasySwoole\DNSServer\Protocol\Header;
use EasySwoole\DNSServer\Protocol\Request;
use EasySwoole\DNSServer\Protocol\Resources;

class DnsEncode
{
    public static $info = '';

    /**
     * 开始生成报文
     *
     * @param Request $request
     *
     * @return string
     */
    public static function start(Request $request) : string
    {
        $header        = self::header($request->getHeader());
        $question      = self::splicing($request->getBody()->getQueries(), true);
        $answer        = self::splicing($request->getBody()->getAnswers());
        $authoritative = self::splicing($request->getBody()->getAuthoritative());
        $additional    = self::splicing($request->getBody()->getAdditional());

        return $header . $question . $answer . $authoritative . $additional;
    }

    /**
     * 头部报文
     *
     * @param Header $header
     *
     * @return string
     */
    private static function header(Header $header) : string
    {

        $data = pack('nnnnnn', $header->getId(), self::flags($header), $header->getQuestionCount(), $header->getAnswerCount(), $header->getNameServerCount(), $header->getAdditionalRecordsCount());

//        $data = pack('nnnnnn', $header->getId(), self::flags($header), 1, 0, 0, 0);

        return $data;
    }

    /**
     * 拼接正文
     *
     * @param array $resources
     * @param bool  $isQueries
     *
     * @return string
     */
    private static function splicing(array $resources, $isQueries = false) : string
    {
        if ($isQueries) {
            $records = array_map('self::splicingQueries', $resources);
        } else {
            $records = array_map('self::splicingResources', $resources);
        }

        return implode('', $records);
    }

    /**
     * 问题区域拼接
     *
     * @param Resources $resources
     *
     * @return string
     */
    private static function splicingQueries(Resources $resources) : string
    {
        $info = self::name($resources->getName());

        return $info . pack('nn', $resources->getType(), $resources->getClass());
    }

    /**
     * 回答区域、授权区域、附加区域拼接
     *
     * @param Resources $resources
     *
     * @return string
     */
    private static function splicingResources(Resources $resources) : string
    {
        $info = self::name($resources->getName());

        try {
            $data = RdataEncode::encode($resources->getData(), $resources->getType());

        } catch (DNSException $exception) {
            print $exception->getMessage();
        }

        $info .= pack('nnNn', $resources->getType(), $resources->getClass(), $resources->getTtl(), strlen($data));

        return $info . $data;
    }

    /**
     * 地址拼接
     *
     * @param $name
     *
     * @return string
     */
    private static function name($name)
    {
        if ('.' === $name) {
            return chr(0);
        }
        $name = rtrim($name, '.') . '.';
        $res  = '';

        foreach (explode('.', $name) as $label) {
            $res .= chr(strlen($label)) . $label;
        }

        return $res;
    }

    /**
     * flags拼接
     *
     * @param Header $header
     *
     * @return int
     */
    private static function flags(Header $header) : int
    {

        return 0 | ($header->isResponse() & 0) << 15 | ($header->getOpcode() & 1111) << 11 | ($header->isAuthoritative() & 1) << 10 | ($header->isTruncated() & 1) << 9 | ($header->isRecursionDesired() & 1) << 8 | ($header->isRecursionAvailable() & 1) << 7 | ($header->getZ() & 111) << 4 | ($header->getRcode() & 1111);
    }

}