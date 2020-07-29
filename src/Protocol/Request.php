<?php


namespace EasySwoole\DNSServer\Protocol;

use EasySwoole\DNSServer\Exception\DNSException;


class Request
{
    private $body;        // 报正文对象
    private $header;      // 报文头部对象
    private $message;     // DNS报文内容
    private $offset = 12; // 解析报文字符位置,12是因为要忽略掉报文头部内容
    // 支持的解析类型
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

    private const TYPE_A     = 1;
    private const TYPE_NS    = 2;
    private const TYPE_CNAME = 5;
    private const TYPE_SOA   = 6;
    private const TYPE_MX    = 15;
    private const TYPE_TXT   = 16;
    private const TYPE_AAAA  = 28;
    private const TYPE_SRV   = 33;
    private const TYPE_OPT   = 41;
    private const TYPE_CAA   = 257;

    public static function decode(string $raw) : ?Request
    {
        $request = new Request();
        $request->analyze($raw);

        return $request;
    }

    public function __construct()
    {
        if (!$this->body) {
            $this->body = new Body();
        }
        if (!$this->header) {
            $this->header = new Header();
        }
    }

    /**
     * @return Body
     */
    public function getBody() : Body
    {
        return $this->body;
    }

    /**
     * @param mixed $body
     */
    public function setBody($body) : void
    {
        $this->body = $body;
    }

    /**
     * @return Header
     */
    public function getHeader() : Header
    {
        return $this->header;
    }

    /**
     * @param mixed $header
     */
    public function setHeader($header) : void
    {
        $this->header = $header;
    }

    /**
     * 解析
     *
     * @param string $message
     */
    private function analyze(string $message)
    {
        $this->message = $message;

        $this->analyseHeader();

        $this->analyseBody();
    }

    /**
     * 解析报文头相关信息
     *
     * @param string $message
     */
    private function analyseHeader()
    {
        $data = unpack('nid/nflags/nqucount/nancount/naucount/nadcount', $this->message);

        // 解析报文头的Flags相关信息
        $this->analyseFlags($data['flags']);

        $this->header->setId($data['id']);
        $this->header->setQuestionCount($data['qucount']);
        $this->header->setAnswerCount($data['ancount']);
        $this->header->setNameServerCount($data['aucount']);
        $this->header->setAdditionalRecordsCount($data['adcount']);
    }

    /**
     * 解析报文头的Flags相关信息
     *
     * @param string $flags
     */
    private function analyseFlags(string $flags)
    {
        $this->header->setResponse(($flags >> 15 & 1));
        $this->header->setOpcode(($flags >> 11 & 1111));
        $this->header->setAuthoritative(($flags >> 10 & 1));
        $this->header->setTruncated(($flags >> 9 & 1));
        $this->header->setRecursionDesired(($flags >> 8 & 1));
        $this->header->setRecursionAvailable(($flags >> 7 & 1));
        $this->header->setZ(($flags >> 4 & 111));
        $this->header->setRcode(($flags & 1111));
    }


    /**
     * 解析正文
     */
    private function analyseBody()
    {
        // 解析并获正文Queries区域资源
        $this->body->setQueries($this->analyseQueries($this->header->getQuestionCount()));
        // 解析并获取回答区域，授权区域、附加区域资源
        $this->body->setAnswers($this->analyseResources($this->header->getAnswerCount()));
        $this->body->setAuthoritative($this->analyseResources($this->header->getNameServerCount()));
        $this->body->setAdditional($this->analyseResources($this->header->getAdditionalRecordsCount()));
    }

    /**
     * 解析域名
     *
     * @param string $string
     *
     * @return string
     */
    private function analyseName(string $string) : string
    {

        $len = ord($string[$this->offset]);

        ++$this->offset;

        if (0 === $len) {
            return '.';
        }

        $domainName = '';

        while (0 !== $len) {
            // 判断接下来的内容是否需要偏移 192 对应十六进制 c0
            if ($len == 192) {

                $offset     = ord($string[$this->offset]);
                $domainName .= $this->offsetAnalyseName($offset);
                ++$this->offset;
                break;
            };

            $domainName .= substr($string, $this->offset, $len) . '.';

            $this->offset += $len;

            $len = ord($string[$this->offset]);

            ++$this->offset;
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
    private function analyseQueries($count = 0) : array
    {
        $data = [];
        if ($count <= 0) {
            return $data;
        }

        for ($i = 0; $i < $count; $i++) {
            $resources = new Resources();

            $resources->setIsQueries(true);

            $resources->setName($this->analyseName($this->message));

            // 获取查询类型、查询类
            $values       = unpack('ntype/nclass', substr($this->message, $this->offset, 4));
            $this->offset += 4;
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
    private function analyseResources($count = 0) : array
    {
        $data = [];

        if ($count <= 0) {
            return $data;
        }

        for ($i = 0; $i < $count; $i++) {
            $resources = new Resources();

            $resources->setIsQueries(false);

            $resources->setName($this->analyseName($this->message));

            // 获取查询类型、查询类、生存时间、数据长度
            $values       = unpack('ntype/nclass/Nttl/ndlength', substr($this->message, $this->offset, 10));
            $this->offset += 10;

            $resources->setType($values['type']);
            $resources->setClass($values['class']);
            $resources->setTtl($values['ttl']);
            $resources->setLength($values['dlength']);

            try {
                // 资源数据
                $resources->setData($this->analyzeResourcesData(substr($this->message, $this->offset, $values['dlength']), $values['type']));
                $this->offset += $values['dlength'];
            } catch (DNSException $exception) {
                $this->offset += $values['dlength'];

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
    public function offsetAnalyseName($offset = 0) : string
    {
        // 原始报文
        $string = $this->message;

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
                $domainName .= $this->offsetAnalyseName($offset);
                break;
            };

            ++$offset;
        }

        return $domainName;
    }


    /**
     * 资源数据解析
     *
     * @param $info
     * @param $type
     *
     * @return mixed
     * @throws DNSException
     */
    public function analyzeResourcesData($info, $type)
    {

        if (!array_key_exists($type, self::$names) && !method_exists(self::class, self::$names[$type])) {
            throw new DNSException('Record type is not a supported type.');
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
    public function a(string $rdata) : string
    {
        return inet_ntop($rdata);
    }

    public function aaaa(string $rdata) : string
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
    public function cname(string $rdata) : string
    {
        return self::name($rdata);
    }

    public function dname(string $rdata) : string
    {
        return self::cname($rdata);
    }

    public function ns(string $rdata) : string
    {
        return self::cname($rdata);
    }

    public function ptr(string $rdata) : string
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
    public function soa(string $rdata) : array
    {
    }

    /**
     * MX的TYPE
     *
     * @param string $rdata
     *
     * @return array
     */
    public function mx(string $rdata) : array
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
    public function txt(string $rdata) : string
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
    public function srv(string $rdata) : array
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
    public function opt(string $rdata) : array
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
    public function caa(string $rdata) : array
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
    public function name($info, $offset = 0)
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
                $name   .= $this->offsetAnalyseName($offset);
                break;
            };

            ++$offset;
        }

        return $name;
    }

    /**
     * 返回原始报文
     * @return string
     */
    public function getMessage():string
    {
        return $this->message;
    }

}