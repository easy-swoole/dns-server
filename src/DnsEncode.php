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

    public static function start(Request $request)
    {
        $header        = self::header($request->getHeader());
        $question      = self::splicing($request->getBody()->getQueries(), true);
        $answer        = self::splicing($request->getBody()->getAnswers());
        $authoritative = self::splicing($request->getBody()->getAuthoritative());
        $additional    = self::splicing($request->getBody()->getAdditional());

        return $header . $question . $answer . $authoritative . $additional;
    }

    private static function header(Header $header)
    {

        $data = pack('nnnnnn', $header->getId(), self::flags($header), $header->getQuestionCount(), $header->getAnswerCount(), $header->getNameServerCount(), $header->getAdditionalRecordsCount());

//        $data = pack('nnnnnn', $header->getId(), self::flags($header), 1, 0, 0, 0);

        return $data;
    }

    private static function splicing($resources, $isQueries = false)
    {
        if ($isQueries) {
            $records = array_map('self::splicingQueries', $resources);
        } else {
            $records = array_map('self::splicingResources', $resources);
        }

        return implode('', $records);
    }

    private static function splicingQueries(Resources $resources)
    {
        $info = self::name($resources->getName());

        return $info . pack('nn', $resources->getType(), $resources->getClass());
    }

    private static function splicingResources(Resources $resources)
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


    private static function flags(Header $header)
    {

        $ss = 0 | ($header->isResponse() & 0) << 15
            | ($header->getOpcode() & 1111) << 11
            | ($header->isAuthoritative() & 1) << 10
            | ($header->isTruncated() & 1) << 9
            | ($header->isRecursionDesired() & 1) << 8
            | ($header->isRecursionAvailable() & 1) << 7
            | ($header->getZ() & 111) << 4
            | ($header->getRcode() & 1111);

        return $ss;
    }

}