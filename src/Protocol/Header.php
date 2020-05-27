<?php


namespace EasySwoole\DNSServer\Protocol;


class Header
{
    const OPCODE_STANDARD_QUERY = 0;
    const OPCODE_INVERSE_QUERY = 1;
    const OPCODE_STATUS_REQUEST = 2;
    const RCODE_NO_ERROR = 0;
    const RCODE_FORMAT_ERROR = 1;
    const RCODE_SERVER_FAILURE = 2;
    const RCODE_NAME_ERROR = 3;
    const RCODE_NOT_IMPLEMENTED = 4;
    const RCODE_REFUSED = 5;
    private $id;
    private $response;
    private $opcode;
    private $authoritative;
    private $truncated;
    private $recursionDesired;
    private $recursionAvailable;
    private $z = 0;
    private $rcode;

    private $questionCount;

    private $answerCount;

    private $nameServerCount;

    private $additionalRecordsCount;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    public function isQuery()
    {
        return !$this->response;
    }

    public function isResponse()
    {
        return $this->response;
    }

    public function setResponse($response)
    {
        $this->response = (bool) $response;

        return $this;
    }

    public function setQuery($query)
    {
        $this->response = !((bool) $query);

        return $this;
    }

    public function getOpcode()
    {
        return $this->opcode;
    }

    public function setOpcode($opcode)
    {
        $this->opcode = (int) $opcode;

        return $this;
    }

    public function isAuthoritative()
    {
        return $this->authoritative;
    }

    public function setAuthoritative($authoritative)
    {
        $this->authoritative = (bool) $authoritative;

        return $this;
    }

    public function isTruncated()
    {
        return $this->truncated;
    }

    public function setTruncated($truncated)
    {
        $this->truncated = (bool) $truncated;

        return $this;
    }

    public function isRecursionDesired()
    {
        return $this->recursionDesired;
    }

    public function setRecursionDesired($recursionDesired)
    {
        $this->recursionDesired = (bool) $recursionDesired;

        return $this;
    }

    public function isRecursionAvailable()
    {
        return $this->recursionAvailable;
    }

    public function setRecursionAvailable($recursionAvailable)
    {
        $this->recursionAvailable = (bool) $recursionAvailable;

        return $this;
    }

    public function getZ()
    {
        return $this->z;
    }

    public function setZ($z)
    {
        $this->z = (int) $z;

        return $this;
    }

    public function getRcode()
    {
        return $this->rcode;
    }

    public function setRcode($rcode)
    {
        $this->rcode = (int) $rcode;

        return $this;
    }

    public function getQuestionCount()
    {
        return $this->questionCount;
    }

    public function setQuestionCount($questionCount)
    {
        $this->questionCount = (int) $questionCount;

        return $this;
    }

    public function getAnswerCount()
    {
        return $this->answerCount;
    }

    public function setAnswerCount($answerCount)
    {
        $this->answerCount = (int) $answerCount;
        return $this;
    }

    public function getNameServerCount()
    {
        return $this->nameServerCount;
    }

    public function setNameServerCount($nameServerCount)
    {
        $this->nameServerCount = (int) $nameServerCount;

        return $this;
    }

    public function getAdditionalRecordsCount()
    {
        return $this->additionalRecordsCount;
    }

    public function setAdditionalRecordsCount($additionalRecordsCount)
    {
        $this->additionalRecordsCount = (int) $additionalRecordsCount;

        return $this;
    }
}