<?php


namespace EasySwoole\DNSServer\Protocol;


class Request
{
    protected $body;
    protected $header;


    public static function decode(string $raw):?Request
    {
        $request = new Request();
        return  $request;
    }

    function __construct()
    {
        if(!$this->body){
            $this->body = new Body();
        }
        if(!$this->header){
            $this->header = new Header();
        }
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param mixed $body
     */
    public function setBody($body): void
    {
        $this->body = $body;
    }

    /**
     * @return mixed
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param mixed $header
     */
    public function setHeader($header): void
    {
        $this->header = $header;
    }
}