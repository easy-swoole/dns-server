<?php


namespace EasySwoole\DNSServer\Protocol;


class Body
{
    public $queries;
    public $answers;
    public $authoritative;
    public $additional;

    public function setQueries($data)
    {
        $this->queries = $data;
    }

    public function getQueries()
    {
        return $this->queries;
    }

    public function setAnswers($data)
    {
        $this->answers = $data;
    }

    public function getAnswers()
    {
        return $this->answers;
    }

    public function setAuthoritative($data)
    {
        $this->authoritative = $data;
    }

    public function getAuthoritative()
    {
        return $this->authoritative;
    }

    public function setAdditional($data)
    {
        $this->additional= $data;
    }

    public function getAdditional()
    {
        return $this->additional;
    }
}