<?php

namespace App\Model;

class Curl
{
    private $status;

    private $result;

    private $info;

    private $url;

    public function __construct($url)
    {
        $this->url = $url;
        $this->init();
    }

    public function init()
    {
        $curl = curl_init($this->url);
        curl_setopt_array($curl, self::getOptions());
        $this->result = curl_exec($curl);
        $this->info = curl_getinfo($curl);
        curl_close($curl);

        return $this->result;
    }

    public function getStatus()
    {
        return $this->info['http_code'];
    }

    public function getLatency()
    {
        return $this->info['total_time'];
    }

    /**
     * @return array
     */
    private static function getOptions(): array
    {
        return [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_ENCODING => "",
            CURLOPT_AUTOREFERER => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => "status-bot.awag-it.de - Test request",
        ];
    }

}