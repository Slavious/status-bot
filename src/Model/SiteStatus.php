<?php

namespace App\Model;

class SiteStatus
{
    public function getStatus($url)
    {
        $curl = new Curl($url);
        return $curl->getStatus();
    }
}