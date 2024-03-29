<?php

namespace App\Model;

class SiteStatus
{
    private $curlInfo;

    public function __construct($url)
    {
        $this->curlInfo = new Curl($url);
    }

    public function getStatus()
    {
        return $this->curlInfo->getStatus();
    }

    public function getLatency()
    {
        return $this->curlInfo->getLatency();
    }

    public function getContent()
    {
        return $this->curlInfo->getContent();
    }

    public function getError()
    {
        return $this->curlInfo->getError();
    }

}
